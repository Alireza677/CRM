<?php

namespace App\Imports;

use App\Helpers\FormOptionsHelper;
use App\Helpers\ImportNormalize;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;
use Spatie\SimpleExcel\SimpleExcelReader;

class OpportunityImportService
{
    // Persian headers to internal field keys
    protected function aliases(): array
    {
        return [
            'نام فرصت فروش'        => 'name',
            'نام سازمان'            => 'organization_name',
            'نام مخاطب'             => 'contact_name',
            'نوع'                   => 'type',
            'منبع'                  => 'source',
            'منبع سرنخ'             => 'source',
            'ارجاع به'              => 'assigned_to_ref',
            'مرحله فروش'            => 'stage',
            'کاربری'                => 'building_usage',
            'درصد پیشرفت'           => 'success_rate',
            'مقدار'                 => 'amount',
            'تاریخ پیگیری بعدی'     => 'next_follow_up',
            'زمان ایجاد'            => 'created_at',
            'توضیحات'               => 'description',
            'استان'                 => 'state',
            'شهر'                   => 'city',
        ];
    }

    public function dryRun(string $filepath, int $ownerUserId, string $matchBy): array
    {
        $reader  = SimpleExcelReader::create($filepath);
        $headers = $reader->getHeaders() ?? [];

        $aliases = $this->aliases();
        $required = ['name'];
        $missing = array_values(array_diff($required, array_map(function ($h) use ($aliases) {
            return $aliases[$h] ?? $h;
        }, $headers)));

        $errors = [];
        $samples = [];
        $rowIndex = 0;

        foreach ($reader->getRows() as $row) {
            $rowIndex++;
            if ($rowIndex > 50 && count($samples) >= 20) {
                // keep validating fully, but limit sample payloads to 20 rows
                continue;
            }

            $mapped = $this->mapAliases($row, $aliases);
            $normalized = $this->normalize($mapped);
            $preview = $this->buildPreview($normalized, $ownerUserId);

            if (!empty($preview['errors'])) {
                $errors[$rowIndex + 1] = $preview['errors']; // +1 for header
            }

            if (count($samples) < 20) {
                $samples[] = Arr::only($preview, [
                    'action','match_key','opportunity','organization','contact','assigned_to','notes','errors'
                ]);
            }
        }

        return [
            'headers' => $headers,
            'missing_required_headers' => $missing,
            'sample_rows' => $samples,
            'validation_errors' => $errors,
            'match_by' => $matchBy,
        ];
    }

    public function import(string $filepath, int $ownerUserId, string $matchBy): array
    {
        $reader = SimpleExcelReader::create($filepath);
        $aliases = $this->aliases();
        $created = 0; $updated = 0; $failed = 0;
        $failedRows = [];

        DB::beginTransaction();
        try {
            foreach ($reader->getRows() as $i => $row) {
                $rowNo = $i + 2; // considering header
                try {
                    $mapped = $this->mapAliases($row, $aliases);
                    $data = $this->normalize($mapped);
                    $preview = $this->buildPreview($data, $ownerUserId, $matchBy);

                    if (!empty($preview['errors'])) {
                        $failed++;
                        $failedRows[$rowNo] = $preview['errors'];
                        continue;
                    }

                    // Upsert Organization
                    $organization = $preview['organization']['model'] ?? null;
                    if (!$organization) {
                        $organization = Organization::firstOrCreate(
                            ['name' => $preview['organization']['name']]
                        );
                    }
                    // Update Organization state/city if provided
                    $orgUpdates = [];
                    if (!empty($preview['organization']['state'])) $orgUpdates['state'] = $preview['organization']['state'];
                    if (!empty($preview['organization']['city']))  $orgUpdates['city']  = $preview['organization']['city'];
                    if (!empty($orgUpdates)) $organization->update($orgUpdates);

                    // Upsert Contact linked to organization
                    $contact = $preview['contact']['model'] ?? null;
                    if (!$contact && !empty($preview['contact']['full_name'])) {
                        [$first, $last] = $this->splitName($preview['contact']['full_name']);
                        $contact = Contact::firstOrCreate(
                            ['first_name' => $first, 'last_name' => $last, 'organization_id' => $organization->id],
                            ['owner_user_id' => $ownerUserId]
                        );
                    }

                    // Resolve user
                    $assignedTo = $preview['assigned_to']['id'] ?? null;

                    // Find existing opportunity by match key if requested
                    $opportunity = null;
                    if ($preview['match_key']) {
                        $opportunity = $this->findByMatchKey($preview['match_key'], $organization->id);
                    }

                    $payload = $preview['opportunity'];
                    $payload['owner_user_id'] = $ownerUserId;
                    $payload['organization_id'] = $organization->id;
                    $payload['contact_id'] = $contact?->id;
                    $payload['assigned_to'] = $assignedTo;
                    $createdAtProvided = $payload['created_at'] ?? null;
                    unset($payload['created_at']);

                    if ($opportunity) {
                        $opportunity->fill($payload)->save();
                        if ($createdAtProvided) {
                            $opportunity->created_at = $createdAtProvided;
                            $opportunity->saveQuietly();
                        }
                        $updated++;
                    } else {
                        $opportunity = Opportunity::create($payload);
                        if ($createdAtProvided) {
                            $opportunity->created_at = $createdAtProvided;
                            $opportunity->saveQuietly();
                        }
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $failedRows[$rowNo] = ['exception' => $e->getMessage()];
                    Log::error('Opportunity import row failed', ['row' => $rowNo, 'error' => $e->getMessage()]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'failed_rows' => $failedRows,
        ];
    }

    protected function mapAliases(array $row, array $aliases): array
    {
        foreach ($aliases as $from => $to) {
            if (array_key_exists($from, $row)) {
                $row[$to] = $row[$from];
                unset($row[$from]);
            }
        }
        return $row;
    }

    protected function buildPreview(array $row, int $ownerUserId, string $matchBy = ''): array
    {
        $errors = [];
        $notes = [];
        $stageLabel = null;
        $sourceLabel = null;

        // Organization
        $orgName = trim((string)($row['organization_name'] ?? ''));
        $organization = null;
        if ($orgName !== '') {
            $organization = Organization::where('name', $orgName)->first();
        } else {
            $errors['organization'] = ['سازمان الزامی است.'];
        }

        // Contact (optional) – link to org
        $contactFullName = trim((string)($row['contact_name'] ?? ''));
        $contact = null;
        if ($contactFullName !== '' && $organization) {
            [$f, $l] = $this->splitName($contactFullName);
            $contact = Contact::where('first_name', $f)
                ->where('last_name', $l)
                ->where('organization_id', $organization->id)
                ->first();
        }

        // Assigned to
        $assignedRef = trim((string)($row['assigned_to_ref'] ?? ''));
        $assignedUser = null;
        if ($assignedRef !== '') {
            $assignedUser = User::where('email', $assignedRef)->first()
                ?: User::where('username', $assignedRef)->first();
            if (!$assignedUser) {
                $errors['assigned_to'] = ["کاربر یافت نشد: {$assignedRef}"];
            }
        }

        // Success rate (0-100)
        $successRate = $this->normalizePercent($row['success_rate'] ?? null);
        if ($successRate !== null && ($successRate < 0 || $successRate > 100)) {
            $errors['success_rate'] = ['درصد پیشرفت باید بین 0 تا 100 باشد.'];
        }

        // Amount
        $amount = $this->normalizeNumber($row['amount'] ?? null);

        // Dates
        $nextFollow = $this->normalizeDate($row['next_follow_up'] ?? null);
        if ($row['next_follow_up'] ?? null) {
            if ($nextFollow === null) {
                $errors['next_follow_up'] = ['تاریخ پیگیری بعدی نامعتبر است.'];
            }
        }
        $createdAt = $this->normalizeDateTime($row['created_at'] ?? null);
        if ($row['created_at'] ?? null) {
            if ($createdAt === null) {
                $errors['created_at'] = ['زمان ایجاد نامعتبر است.'];
            }
        }

        // Normalize stage and source to canonical keys and validate (fa-IR)
        $stageKey   = ImportNormalize::normalizeStage($row['stage'] ?? null);
        $sourceKey  = ImportNormalize::normalizeSource($row['source'] ?? null);

        if (($row['stage'] ?? null) !== null && (string)($row['stage']) !== '') {
            $allowedStageKeys = array_keys(ImportNormalize::stageMap());
            if ($stageKey === null || !in_array($stageKey, $allowedStageKeys, true)) {
                $allowedListFa = implode('، ', $allowedStageKeys);
                $origText = (string)($row['stage'] ?? '');
                $errors['stage'] = ["مرحله فروش نامعتبر: «{$origText}». مقادیر مجاز: {$allowedListFa}"];
            } else {
                // Persist canonical key
                $stageLabel = $stageKey;
            }
        } else {
            $stageLabel = null;
        }

        if (($row['source'] ?? null) !== null && (string)($row['source']) !== '') {
            $allowedSourceKeys = array_keys(ImportNormalize::sourceMap());
            if ($sourceKey === null || !in_array($sourceKey, $allowedSourceKeys, true)) {
                $allowedListFa = implode('، ', $allowedSourceKeys);
                $origText = (string)($row['source'] ?? '');
                $errors['source'] = ["منبع نامعتبر: «{$origText}». مقادیر مجاز: {$allowedListFa}"];
            } else {
                // Persist canonical key
                $sourceLabel = $sourceKey;
            }
        }

        // Replace any prior generic errors with Persian detailed messages
        if (!empty($errors['stage'])) {
            $orig = (string)($row['stage'] ?? '');
            $errors['stage'] = [
                "مرحله فروش نامعتبر: «{$orig}». مقادیر مجاز: " . implode('، ', array_keys(ImportNormalize::stageMap()))
            ];
        }
        if (!empty($errors['source'])) {
            $orig = (string)($row['source'] ?? '');
            $errors['source'] = [
                "منبع نامعتبر: «{$orig}». مقادیر مجاز: " . implode('، ', array_keys(ImportNormalize::sourceMap()))
            ];
        }

        // If stage resolves to won, nullify follow-up date (import-side enforcement)
        if (($stageKey ?? null) === 'won') {
            $nextFollow = null;
        }

        // Name required
        $name = trim((string)($row['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = ['نام فرصت الزامی است.'];
        }

        // Determine match key for idempotency
        $matchKey = null;
        if ($matchBy) {
            $key = Str::lower(trim($matchBy));
            if ($key === 'name' && $name !== '') {
                $matchKey = 'name:' . $name;
            } elseif (($key === 'name+organization' || $key === 'نام فرصت + سازمان') && $name !== '' && $orgName !== '') {
                $matchKey = 'name_org:' . $name . '|' . $orgName;
            }
        }

        $action = 'create';
        if ($matchKey) {
            $existing = $this->findByMatchKey($matchKey, $organization?->id);
            if ($existing) $action = 'update';
        }

        return [
            'action' => $action,
            'match_key' => $matchKey,
            'organization' => [
                'name' => $orgName,
                'model' => $organization,
                'state' => $row['state'] ?? null,
                'city'  => $row['city'] ?? null,
            ],
            'contact' => [
                'full_name' => $contactFullName,
                'model' => $contact,
            ],
            'assigned_to' => [
                'ref' => $assignedRef,
                'id' => $assignedUser?->id,
            ],
            'opportunity' => [
                'name' => $name,
                'type' => $row['type'] ?? null,
                'source' => $sourceLabel,
                'stage' => $stageLabel,
                'building_usage' => $row['building_usage'] ?? null,
                'success_rate' => $successRate,
                'amount' => $amount,
                'next_follow_up' => $nextFollow,
                'created_at' => $createdAt,
                'description' => $row['description'] ?? null,
            ],
            'notes' => $notes,
            'errors' => $errors,
        ];
    }

    protected function findByMatchKey(?string $matchKey, ?int $organizationId): ?Opportunity
    {
        if (!$matchKey) return null;
        if (Str::startsWith($matchKey, 'name:')) {
            $name = Str::after($matchKey, 'name:');
            return Opportunity::where('name', $name)->first();
        }
        if (Str::startsWith($matchKey, 'name_org:')) {
            [$name, $org] = explode('|', Str::after($matchKey, 'name_org:'), 2);
            return Opportunity::where('name', $name)
                ->where('organization_id', $organizationId)
                ->first();
        }
        return null;
    }

    protected function splitName(string $full): array
    {
        $full = trim(preg_replace('/\s+/', ' ', $full));
        if ($full === '') return ['', ''];
        $parts = explode(' ', $full, 2);
        return [trim($parts[0]), isset($parts[1]) ? trim($parts[1]) : ''];
    }

    protected function normalize(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            if (is_string($v)) $v = $this->faToEnDigits(trim($v));
            if (in_array($k, ['success_rate','amount'], true)) {
                $v = $this->normalizeNumber($v);
            }
            if (in_array($k, ['next_follow_up','created_at'], true)) {
                // keep raw; dedicated normalizers used later for precision
            }
            $out[$k] = $v;
        }
        return $out;
    }

    protected function normalizePercent($v): ?int
    {
        if ($v === '' || $v === null) return null;
        if (is_string($v)) $v = str_replace('%', '', $v);
        if (is_numeric($v)) return (int) round((float) $v);
        return null;
    }

    protected function normalizeNumber($v)
    {
        if ($v === '' || $v === null) return null;
        if (is_string($v)) {
            // حذف فاصله و ویرگول انگلیسی، تبدیل جداکننده اعشاری فارسی/عربی به نقطه
            $v = str_replace([' ', ',', '٬', '٫'], ['', '', '', '.'], $v);
        }
        return is_numeric($v) ? (int) round((float) $v) : null;
    }

    protected function faToEnDigits(string $s): string
    {
        // ارقام فارسی (۰-۹) و ارقام عربی-هندی (٠-٩) به انگلیسی
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];
        return str_replace($fa, $en, $s);
    }

    protected function normalizeDate($v): ?string
    {
        if ($v === '' || $v === null) return null;
        // Excel serial date
        if (is_numeric($v)) {
            $base = \DateTime::createFromFormat('Y-m-d', '1899-12-30');
            if ($base) {
                $base->modify('+' . ((int)$v) . ' days');
                return $base->format('Y-m-d');
            }
        }
        $val = is_string($v) ? str_replace(['.', '\\'], ['-', '/'], (string) $v) : $v;
        if (is_string($val)) {
            $val = str_replace('-', '/', $val);
            // Normalize Persian/Arabic digits to English for robust parsing
            $val = $this->faToEnDigits($val);
        }

        // Detect possible Jalali year (e.g., 13xx/14xx) and prefer Jalali parsing first
        $preferJalali = false;
        if (is_string($val)) {
            if (preg_match('/^\s*(\d{3,4})[\/]/', $val, $m)) {
                $year = (int) $m[1];
                // Heuristic: treat years < 1600 as Jalali
                if ($year > 1200 && $year < 1600) $preferJalali = true;
            }
        }

        if ($preferJalali) {
            $formats = ['Y/m/d','Y/m/d H:i','Y/m/d H:i:s'];
            foreach ($formats as $fmt) {
                try {
                    return Jalalian::fromFormat($fmt, (string)$val)->toCarbon()->format('Y-m-d');
                } catch (\Throwable $e) { /* continue */ }
            }
            // fallback to Gregorian
            try {
                $dt = new \DateTime((string)$val);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {}
        } else {
            // Try Gregorian first
            try {
                $dt = new \DateTime((string)$val);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {}
            // Then try Jalali
            $formats = ['Y/m/d','Y/m/d H:i','Y/m/d H:i:s'];
            foreach ($formats as $fmt) {
                try {
                    return Jalalian::fromFormat($fmt, (string)$val)->toCarbon()->format('Y-m-d');
                } catch (\Throwable $e) { /* continue */ }
            }
        }

        // If nothing matched, return null
        $formats = ['Y/m/d','Y/m/d H:i','Y/m/d H:i:s'];
        return null;
    }

    protected function normalizeDateTime($v): ?string
    {
        if ($v === '' || $v === null) return null;
        // Excel serial date
        if (is_numeric($v)) {
            $base = \DateTime::createFromFormat('Y-m-d', '1899-12-30');
            if ($base) {
                $base->modify('+' . ((int)$v) . ' days');
                return $base->format('Y-m-d H:i:s');
            }
        }
        $val = is_string($v) ? str_replace(['.', '\\'], ['-', '/'], (string) $v) : $v;
        if (is_string($val)) {
            $val = str_replace('-', '/', $val);
            // Normalize Persian/Arabic digits to English for robust parsing
            $val = $this->faToEnDigits($val);
        }

        // Detect possible Jalali year and prefer Jalali parsing first
        $preferJalali = false;
        if (is_string($val)) {
            if (preg_match('/^\s*(\d{3,4})[\/]/', $val, $m)) {
                $year = (int) $m[1];
                if ($year > 1200 && $year < 1600) $preferJalali = true;
            }
        }

        if ($preferJalali) {
            $formats = ['Y/m/d','Y/m/d H:i','Y/m/d H:i:s'];
            foreach ($formats as $fmt) {
                try {
                    return Jalalian::fromFormat($fmt, (string)$val)->toCarbon()->format('Y-m-d H:i:s');
                } catch (\Throwable $e) { /* continue */ }
            }
            // fallback to Gregorian
            try {
                $dt = new \DateTime((string)$val);
                return $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {}
        } else {
            // Try Gregorian first
            try {
                $dt = new \DateTime((string)$val);
                return $dt->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {}
            // Then try Jalali
            $formats = ['Y/m/d','Y/m/d H:i','Y/m/d H:i:s'];
            foreach ($formats as $fmt) {
                try {
                    return Jalalian::fromFormat($fmt, (string)$val)->toCarbon()->format('Y-m-d H:i:s');
                } catch (\Throwable $e) { /* continue */ }
            }
        }

        // If nothing matched, return null
        $formats = ['Y/m/d','Y/m/d H:i','Y/m/d H:i:s'];
        return null;
    }
}

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
use Illuminate\Support\Facades\Auth;
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
        $report = [
            'unresolved_organizations' => [],
            'unresolved_contacts' => [],
        ];

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

                    // Resolve Organization and Contact by name only (do not create)
                    $orgNameOriginal = $preview['organization']['name'] ?? null;
                    $organizationId = $this->findOrganizationIdByName($orgNameOriginal);
                    if (($orgNameOriginal !== null && trim((string)$orgNameOriginal) !== '') && !$organizationId) {
                        $report['unresolved_organizations'][] = [
                            'row' => $rowNo,
                            'name' => $orgNameOriginal,
                        ];
                    }

                    $contactFullNameOriginal = $preview['contact']['full_name'] ?? null;
                    $contactId = $this->findContactIdByFullName($contactFullNameOriginal, $organizationId);
                    if (($contactFullNameOriginal !== null && trim((string)$contactFullNameOriginal) !== '') && !$contactId) {
                        $report['unresolved_contacts'][] = [
                            'row' => $rowNo,
                            'full_name' => $contactFullNameOriginal,
                            'organization_name' => $orgNameOriginal,
                        ];
                    }

                    // Resolve user
                    $assignedTo = $preview['assigned_to']['id'] ?? null;

                    // Find existing opportunity by match key if requested
                    $opportunity = null;
                    if ($preview['match_key']) {
                        $opportunity = $this->findByMatchKey($preview['match_key'], $organizationId);
                    }

                    $payload = $preview['opportunity'];
                    $payload['owner_user_id'] = Auth::id() ?? $ownerUserId;
                    $payload['organization_id'] = $organizationId;
                    $payload['contact_id'] = $contactId;
                    $payload['assigned_to'] = $assignedTo;
                    $createdAtProvided = $payload['created_at'] ?? null;
                    $updatedAtProvided = $payload['updated_at'] ?? null;
                    unset($payload['created_at']);
                    unset($payload['updated_at']);

                    if ($opportunity) {
                        $opportunity->fill($payload)->save();
                        if ($createdAtProvided) {
                            $opportunity->created_at = $createdAtProvided;
                            $opportunity->saveQuietly();
                        }
                        if ($updatedAtProvided) {
                            $opportunity->updated_at = $updatedAtProvided;
                            $opportunity->saveQuietly();
                        }
                        $updated++;
                    } else {
                        $opportunity = Opportunity::create($payload);
                        if ($createdAtProvided) {
                            $opportunity->created_at = $createdAtProvided;
                            $opportunity->saveQuietly();
                        }
                        if ($updatedAtProvided) {
                            $opportunity->updated_at = $updatedAtProvided;
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
            'skipped' => $failed,
            'report' => $report,
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
        // Also normalize common Persian contact mobile headers
        foreach ([
            'شماره موبایل',
            'موبایل',
            'شماره همراه',
            'موبایل مخاطب',
        ] as $h) {
            if (array_key_exists($h, $row)) {
                $row['contact_mobile'] = $row[$h];
                unset($row[$h]);
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

        // Contact (optional) - link to org
        $contactFullName = trim((string)($row['contact_name'] ?? ''));
        $contactMobileRaw = trim((string)($row['contact_mobile'] ?? ''));
        $contactMobile = $contactMobileRaw !== '' ? preg_replace('/[^\d+]/u', '', $this->faToEnDigits($contactMobileRaw)) : '';
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
        // Organization is optional; do not fail preview if not provided
        unset($errors['organization']);

        return [
            'action' => $action,
            'match_key' => $matchKey,
            'organization' => [
                'name' => $orgName,
                'state' => $row['state'] ?? null,
                'city'  => $row['city'] ?? null,
            ],
            'contact' => [
                'full_name' => $contactFullName,
                'mobile' => $contactMobile ?: null,
            ],
            'assigned_to' => [
                'ref' => $assignedRef,
                'id' => $assignedUser?->id,
            ],
            // Compute updated_at here (no strict validation needed for preview)
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
                'updated_at' => $this->normalizeDateTime($row['updated_at'] ?? null),
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
            if (in_array($k, ['next_follow_up','created_at','updated_at'], true)) {
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

    // ----------------------- Lookup helpers (search-only) -----------------------
    protected function findOrganizationIdByName(?string $name): ?int
    {
        $needle = $this->normalizeComparable($name);
        if ($needle === null) return null;

        // Try exact candidates first
        $variants = array_values(array_unique(array_filter([
            $name,
            $this->swapArabicVariants($name),
        ], fn($v) => is_string($v) && trim($v) !== '')));

        if (!empty($variants)) {
            $candidates = Organization::query()
                ->whereIn('name', $variants)
                ->limit(50)
                ->get(['id','name']);
            foreach ($candidates as $org) {
                if ($this->normalizeComparable($org->name) === $needle) {
                    return (int)$org->id;
                }
            }
        }

        // Fallback: LIKE search with normalization check in PHP
        $like = '%' . str_replace(' ', '%', $name) . '%';
        $candidates = Organization::query()
            ->where('name', 'like', $like)
            ->limit(50)
            ->get(['id','name']);
        foreach ($candidates as $org) {
            if ($this->normalizeComparable($org->name) === $needle) {
                return (int)$org->id;
            }
        }
        return null;
    }

    protected function findContactIdByFullName(?string $fullName, ?int $organizationId = null): ?int
    {
        $full = $this->normalizeComparable($fullName);
        if ($full === null) return null;
        [$firstRaw, $lastRaw] = $this->splitName($fullName ?? '');
        $first = $this->normalizeComparable($firstRaw);
        $last  = $this->normalizeComparable($lastRaw);

        $firstAlts = array_values(array_unique(array_filter([
            $firstRaw,
            $this->swapArabicVariants($firstRaw),
        ], fn($v) => is_string($v) && trim($v) !== '')));
        $lastAlts = array_values(array_unique(array_filter([
            $lastRaw,
            $this->swapArabicVariants($lastRaw),
        ], fn($v) => is_string($v) && trim($v) !== '')));

        $query = Contact::query();
        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }
        if (!empty($firstAlts)) $query->whereIn('first_name', $firstAlts);
        if (!empty($lastAlts))  $query->whereIn('last_name', $lastAlts);

        $candidates = $query->limit(50)->get(['id','first_name','last_name','organization_id']);
        $matches = [];
        foreach ($candidates as $c) {
            if ($this->normalizeComparable($c->first_name) === $first && $this->normalizeComparable($c->last_name) === $last) {
                $matches[] = (int)$c->id;
            }
        }
        if (!empty($matches)) {
            // Prefer unique match; otherwise pick the first consistent match in provided org
            return $matches[0];
        }

        // Fallback across all orgs only if org scope was not provided
        if (!$organizationId) {
            $candidates = Contact::query()
                ->whereIn('first_name', $firstAlts)
                ->whereIn('last_name', $lastAlts)
                ->limit(50)
                ->get(['id','first_name','last_name','organization_id']);
            $matches = [];
            foreach ($candidates as $c) {
                if ($this->normalizeComparable($c->first_name) === $first && $this->normalizeComparable($c->last_name) === $last) {
                    $matches[] = (int)$c->id;
                }
            }
            if (count($matches) === 1) {
                return $matches[0];
            }
        }

        return null;
    }

    protected function normalizeComparable(?string $s): ?string
    {
        if ($s === null) return null;
        $s = trim($s);
        if ($s === '') return null;
        // Remove NBSP, ZWNJ, Tatweel
        $s = str_replace(["\xC2\xA0", "\u{200C}", "\u{0640}"], ' ', $s);
        // Normalize Arabic variants to Persian forms
        $s = $this->swapArabicVariants($s);
        // Collapse spaces
        $s = preg_replace('/\s+/u', ' ', $s);
        // Case-insensitive compare
        return mb_strtolower($s, 'UTF-8');
    }

    protected function swapArabicVariants(?string $s): ?string
    {
        if ($s === null) return null;
        // Arabic Yeh/Kaf to Persian Yeh/Kaf
        $search = ["\u{064A}", "\u{0643}"];
        $replace = ["\u{06CC}", "\u{06A9}"];
        return str_replace($search, $replace, $s);
    }
}

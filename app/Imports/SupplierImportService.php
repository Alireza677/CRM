<?php

namespace App\Imports;

use App\Models\Supplier;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Spatie\SimpleExcel\SimpleExcelReader;
use Morilog\Jalali\Jalalian;

class SupplierImportService
{
    public function dryRun(string $filepath, array $rules, array $aliases = []): array
    {
        $reader  = SimpleExcelReader::create($filepath);
        $headers = $reader->getHeaders() ?? [];

        // Normalize and resolve headers using aliases before checking required/extra
        $resolvedHeaders = $this->resolveHeaders($headers, $aliases);

        $requiredHeaders = $this->requiredFields($rules);
        $missing = array_values(array_diff($requiredHeaders, $resolvedHeaders));
        $extra   = array_values(array_diff($resolvedHeaders, array_keys($rules)));

        $errors  = [];
        $samples = [];

        foreach ($reader->getRows() as $i => $row) {
            if ($i >= 10) break;

            $payload = $this->mapAliases($row, $aliases);
            $payload = $this->normalize($payload);
            $payload = $this->applyLookups($payload);
            $payload = Arr::only($payload, array_keys($rules));

            $v = Validator::make($payload, $rules);
            if ($v->fails()) {
                $errors[$i + 2] = $v->errors()->toArray();
            }

            $samples[] = $payload;
        }

        return [
            'headers' => $headers,
            'missing_required_headers' => $missing,
            'extra_headers' => $extra,
            'sample_rows' => $samples,
            'validation_errors' => $errors,
        ];
    }

    public function import(string $filepath, array $rules, array $aliases = []): array
    {
        $reader = SimpleExcelReader::create($filepath);
        $rows   = $reader->getRows();

        $batch  = [];
        $failed = [];
        $ok     = 0;

        foreach ($rows as $index => $row) {
            $payload = $this->mapAliases($row, $aliases);
            $payload = $this->normalize($payload);
            $payload = $this->applyLookups($payload);
            $payload = Arr::only($payload, array_keys($rules));

            $v = Validator::make($payload, $rules);
            if ($v->fails()) {
                $failed[$index + 2] = $v->errors()->toArray();
                continue;
            }

            if (!array_key_exists('is_active', $payload) || $payload['is_active'] === null) {
                unset($payload['is_active']); // let DB default apply
            }

            // If created_at/updated_at couldn't be parsed, drop them to let DB/Model handle timestamps
            foreach (['created_at','updated_at'] as $ts) {
                if (array_key_exists($ts, $payload) && ($payload[$ts] === null || $payload[$ts] === '')) {
                    unset($payload[$ts]);
                }
            }

            $batch[] = $payload;

            if (count($batch) >= 500) {
                $ok += $this->insertChunk($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $ok += $this->insertChunk($batch);
        }

        return [
            'inserted' => $ok,
            'failed_rows' => $failed,
        ];
    }

    protected function insertChunk(array $rows): int
    {
        Supplier::insert($rows);
        return count($rows);
    }

    protected function requiredFields(array $rules): array
    {
        $required = [];
        foreach ($rules as $field => $rule) {
            $str = is_array($rule) ? implode('|', $rule) : (string)$rule;
            if (stripos($str, 'required') !== false) $required[] = $field;
        }
        return $required;
    }

    protected function mapAliases(array $row, array $aliases): array
    {
        $out = [];
        $normalizedAliases = $this->normalizedAliases($aliases);

        foreach ($row as $key => $value) {
            $nkey = $this->normalizeHeader((string)$key);
            $dst  = $normalizedAliases[$nkey] ?? $nkey;
            $out[$dst] = $value;
        }

        return $out;
    }

    protected function normalize(array $row): array
    {
        $out = [];

        foreach ($row as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
            }

            if (in_array($k, ['created_at','updated_at'], true)) {
                $v = $this->normalizeDate($v);
            }

            if (in_array($k, ['is_active'], true)) {
                $v = $this->normalizeBoolean($v);
            }

            // Force textual fields that may come as numeric from Excel
            if (in_array($k, ['phone','postal_code'], true)) {
                $v = $this->normalizeTextNumber($v);
            }

            $out[$k] = $v;
        }

        return $out;
    }

    /**
     * Resolve headers from file to canonical field names using normalization + aliases.
     * Returns a list of resolved header keys.
     */
    protected function resolveHeaders(array $headers, array $aliases): array
    {
        $resolved = [];
        $normalizedAliases = $this->normalizedAliases($aliases);
        foreach ($headers as $h) {
            $nh = $this->normalizeHeader((string)$h);
            $resolved[] = $normalizedAliases[$nh] ?? $nh;
        }
        return $resolved;
    }

    /** Build an alias map where keys are normalized header variants */
    protected function normalizedAliases(array $aliases): array
    {
        $out = [];
        foreach ($aliases as $from => $to) {
            $out[$this->normalizeHeader((string)$from)] = $to;
        }
        return $out;
    }

    /**
     * Normalize Persian/Arabic headers:
     * - Unify Arabic Yeh/Kaf to Persian Yeh/Keheh
     * - Unify Alef forms to plain Alef
     * - Remove diacritics, tatweel, zero‑width and NBSP
     * - Collapse spaces
     */
    protected function normalizeHeader(string $s): string
    {
        $s = trim($s);

        $search = [
            "\u{064A}", // Arabic Yeh
            "\u{0643}", // Arabic Kaf
            "\u{0622}", // Alef with madda
            "\u{0623}", // Alef with hamza above
            "\u{0625}", // Alef with hamza below
            "\u{0640}", // Tatweel
            "\u{200C}", // ZWNJ
            "\u{200B}", // ZWSP
            "\u{00A0}", // NBSP
            "\u{200E}", // LRM
            "\u{200F}", // RLM
        ];
        $replace = [
            "\u{06CC}", // Persian Yeh
            "\u{06A9}", // Persian Keheh
            "\u{0627}", // Alef
            "\u{0627}", // Alef
            "\u{0627}", // Alef
            '', '', '', '', '',
        ];
        $s = str_replace($search, $replace, $s);

        // Remove combining marks (harakat)
        $s = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $s);

        // Normalize multiple spaces to single space
        $s = preg_replace('/\s+/u', ' ', $s);

        return trim($s);
    }

    protected function applyLookups(array $row): array
    {
        // Map category name -> id if needed
        if (array_key_exists('category_id', $row) && $row['category_id'] !== null && $row['category_id'] !== '') {
            $val = $row['category_id'];
            if (!is_numeric($val)) {
                $cat = Category::where('name', $val)->first();
                $row['category_id'] = $cat?->id; // let validator catch null
            }
        }

        // Map assigned_to (user) name/email -> id
        if (array_key_exists('assigned_to', $row) && $row['assigned_to'] !== null && $row['assigned_to'] !== '') {
            $val = $row['assigned_to'];
            if (!is_numeric($val)) {
                $user = User::where('email', $val)->first() ?: User::where('name', $val)->first();
                $row['assigned_to'] = $user?->id; // let validator catch null
            }
        }

        return $row;
    }

    protected function normalizeDate($v)
    {
        if ($v === '' || $v === null) return null;

        // Excel serial date (days since 1899-12-30)
        if (is_numeric($v)) {
            $base = \DateTime::createFromFormat('Y-m-d', '1899-12-30');
            if ($base) {
                $base->modify('+' . ((int)$v) . ' days');
                return $base->format('Y-m-d H:i:s');
            }
        }

        // Normalize digits and separators for potential Jalali strings
        $sv = is_string($v) ? $this->toEnDigits(trim($v)) : $v;
        if (is_string($sv)) {
            // Accept both '-' and '/' in inputs
            $sv = preg_replace('/\s+/u', ' ', $sv);
            $slash = str_replace('-', '/', $sv);

            // Try common Jalali formats first (year usually 13xx/14xx)
            $jalaliFormats = [
                'Y/m/d H:i:s',
                'Y/m/d H:i',
                'H:i:s Y/m/d',
                'H:i Y/m/d',
                'Y/m/d',
            ];
            foreach ($jalaliFormats as $fmt) {
                try {
                    $c = Jalalian::fromFormat($fmt, $slash)->toCarbon();
                    return $c->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                    // try next
                }
            }

            // Try common Gregorian textual formats (d/m/Y, d-m-Y, etc.)
            $gregorianCandidates = [
                $sv,                  // original (could be "1401-07-16 09:24:14")
                $slash,               // slashed version
                preg_replace('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', '$3-$2-$1', $slash), // d/m/Y -> Y-m-d
            ];
            foreach ($gregorianCandidates as $cand) {
                try {
                    $dt = new \DateTime($cand);
                    return $dt->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                    // try next
                }
            }
        }

        // If nothing worked, drop the value to avoid DB errors (nullable field)
        return null;
    }

    /** Convert Persian/Arabic digits to English */
    protected function toEnDigits($s)
    {
        if (!is_string($s)) return $s;
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($ar, $en, str_replace($fa, $en, $s));
    }

    protected function normalizeBoolean($v)
    {
        if ($v === '' || $v === null) return null;

        $true  = ['1','true','yes','y','on','بله','فعال','روشن'];
        $false = ['0','false','no','n','off','خیر','غیرفعال','خاموش'];

        $sv = is_string($v) ? mb_strtolower(trim($v)) : $v;

        if (is_numeric($sv)) {
            return ((int)$sv) ? 1 : 0;
        }
        if (in_array($sv, $true, true))  return 1;
        if (in_array($sv, $false, true)) return 0;

        return null;
    }

    /**
     * Convert numbers coming from Excel to string without scientific notation
     * (keeps original digits length; does not try to infer country codes).
     */
    protected function normalizeTextNumber($v)
    {
        if ($v === '' || $v === null) return null;

        if (is_numeric($v)) {
            // Format as integer-like string (no separators, no decimals)
            // Using sprintf avoids scientific notation on large numbers
            return sprintf('%.0f', (float)$v);
        }

        if (is_string($v)) {
            return trim($v);
        }

        return (string)$v;
    }
}

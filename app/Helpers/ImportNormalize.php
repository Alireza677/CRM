<?php

namespace App\Helpers;

class ImportNormalize
{
    public static function clean(?string $val): ?string
    {
        if ($val === null) return null;
        // Normalize whitespace and characters
        $v = trim($val);
        if ($v === '') return null;
        // Replace Arabic variants to Persian
        $v = strtr($v, [
            "\xD9\x8A" => 'ی', // ي → ی
            "\xD9\x83" => 'ک', // ك → ک
        ]);
        // Replace ZWNJ and NBSP with space
        $v = str_replace(["\xE2\x80\x8C", "\xC2\xA0"], ' ', $v); // ZWNJ, NBSP
        // Collapse multiple spaces
        $v = preg_replace('/\s+/', ' ', $v);
        // Lowercase (multibyte)
        $v = mb_strtolower($v, 'UTF-8');
        return $v;
    }

    public static function stageMap(): array
    {
        return [
            // canonical => aliases/labels
            'new'                      => ['جدید', 'اولیه', 'followup', 'doing', 'to_contact'],
            'contacted'                => ['تماس گرفته شده', 'پیگیری', 'future', 'qualified', 'contacted', 'qualifying'],
            'converted'                => ['converted', 'converted_to_opportunity', 'تبدیل شده', 'فرصت', 'مشتری بالقوه', 'پایان موفق'],
            'discarded'                => ['junk', 'disqualified', 'رد صلاحیت', 'سرکاری', 'حذف شده', 'bad lead'],
            'proposal'                 => ['ارسال پروپوزال', 'proposal', 'quote'],
            'negotiation'              => ['مذاکره', 'negotiation'],
            'won'                      => ['برنده', 'won', 'closed-won', 'closed won'],
            'lost'                     => ['از دست رفته', 'lost', 'closed-lost', 'closed lost'],
        ];
    }

    public static function sourceMap(): array
    {
        // If helper exists, use its canonical keys; then provide aliases per key
        $keys = array_keys(FormOptionsHelper::opportunitySources());
        $map = [];
        foreach ($keys as $key) {
            switch ($key) {
                case 'website':
                    $map[$key] = ['وبسایت', 'وب‌سایت', 'سایت', 'website'];
                    break;
                case 'phone':
                    $map[$key] = ['تماس تلفنی', 'تماس', 'phone', 'call'];
                    break;
                case 'referral':
                    $map[$key] = ['معرفی', 'ارجاع', 'referral'];
                    break;
                case 'event':
                    $map[$key] = ['نمایشگاه/ رویداد', 'نمایشگاه/رویداد', 'نمایشگاه', 'رویداد', 'event', 'expo'];
                    break;
                case 'representative':
                    $map[$key] = ['نماینده', 'عامل فروش', 'agent', 'reseller'];
                    break;
                case 'old_customer':
                    $map[$key] = ['مشتری قدیمی', 'existing customer', 'repeat', 'existing'];
                    break;
                case 'tender':
                    $map[$key] = ['مناقصه', 'tender', 'bid', 'rfp'];
                    break;
                case 'in_person_marketing':
                    $map[$key] = ['بازاریابی حضوری', 'حضوری', 'بازاریابی میدانی', 'in-person', 'field marketing'];
                    break;
                default:
                    // fallback: accept the key itself
                    $map[$key] = [$key];
                    break;
            }
        }

        // Fallback if list is empty for any reason
        if (empty($map)) {
            $map = [
                'website'   => ['وبسایت', 'وب‌سایت', 'سایت', 'website'],
                'phone'     => ['تماس تلفنی', 'تماس', 'phone', 'call'],
                'referral'  => ['معرفی', 'ارجاع', 'referral'],
                'event'     => ['نمایشگاه/ رویداد', 'نمایشگاه', 'رویداد', 'event', 'expo'],
                'agent'     => ['نماینده', 'عامل فروش', 'agent', 'reseller'],
                'existing'  => ['مشتری قدیمی', 'existing customer', 'repeat', 'existing'],
                'tender'    => ['مناقصه', 'tender', 'bid', 'rfp'],
            ];
        }

        return $map;
    }

    public static function normalizeByMap($value, array $map): ?string
    {
        $norm = self::clean(is_string($value) ? $value : (is_null($value) ? null : (string)$value));
        if ($norm === null) return null;
        foreach ($map as $canonical => $aliases) {
            // Match canonical key itself
            if (self::clean($canonical) === $norm) return $canonical;
            foreach ((array)$aliases as $alias) {
                if (self::clean($alias) === $norm) {
                    return $canonical;
                }
            }
        }
        return null;
    }

    public static function normalizeStage($value): ?string
    {
        return self::normalizeByMap($value, self::stageMap());
    }

    public static function normalizeSource($value): ?string
    {
        return self::normalizeByMap($value, self::sourceMap());
    }

    /**
     * Normalize mixed Persian/Gregorian/Jalali/Excel date inputs into Carbon (Gregorian).
     * - Empty or blank => null
     * - Persian digits converted to Latin; spaces collapsed; T replaced with space
     * - Detect Jalali by Persian digits or year starting with 13xx/14xx, then try formats:
     *   Y-m-d H:i:s, Y-m-d H:i, Y/m/d H:i:s, Y/m/d H:i, Y-m-d, Y/m/d
     *   If date-only, time defaults to 00:00:00
     * - If numeric (Excel serial), convert via PhpSpreadsheet
     * - Otherwise, try Gregorian with Carbon::parse
     */
    public static function normalizeDate($v): ?\Carbon\Carbon
    {
        if ($v === null) return null;
        if ($v === '') return null;

        // Excel serial (also accept numeric strings)
        if (is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$v);
                return \Carbon\Carbon::instance($dt);
            } catch (\Throwable $e) {
                // fallthrough
            }
        }

        $orig = is_scalar($v) ? (string)$v : '';
        $s = self::digitsFaToEn(self::collapseSpaces((string)$v));
        // normalize slashes and dots
        $s = str_replace(['\\\\', '.'], ['/', '-'], $s);
        $s = str_replace('T', ' ', $s); // ISO 8601 T to space
        $s = trim($s);
        if ($s === '') return null;

        $hasPersianDigits = self::containsPersianDigits($orig);
        $year = null;
        if (preg_match('/^(\d{4})[\/-]/', $s, $m)) {
            $year = (int)$m[1];
        }
        $looksJalali = $hasPersianDigits || ($year !== null && $year >= 1300 && $year < 1500);

        if ($looksJalali) {
            $formats = [
                'Y-m-d H:i:s', 'Y-m-d H:i', 'Y/m/d H:i:s', 'Y/m/d H:i', 'Y-m-d', 'Y/m/d',
            ];
            foreach ($formats as $fmt) {
                try {
                    $c = \Morilog\Jalali\Jalalian::fromFormat($fmt, $s)->toCarbon();
                    // Ensure time exists when date-only
                    if (!preg_match('/\d{2}:\d{2}/', $s)) {
                        $c->setTime(0, 0, 0);
                    }
                    return $c;
                } catch (\Throwable $e) {
                    // try next
                }
            }
        }

        // Try Gregorian parse with explicit common formats first
        $gFormats = ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y/m/d H:i:s', 'Y/m/d H:i', 'Y-m-d', 'Y/m/d'];
        foreach ($gFormats as $gf) {
            try {
                $c = \Carbon\Carbon::createFromFormat($gf, $s);
                if (!preg_match('/\d{2}:\d{2}/', $s)) {
                    $c->setTime(0, 0, 0);
                }
                return $c;
            } catch (\Throwable $e) {
                // continue
            }
        }
        try {
            return \Carbon\Carbon::parse($s);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function digitsFaToEn(string $s): string
    {
        $search = [
            '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹', // Persian
            '٠','١','٢','٣','٤','٥','٦','٧','٨','٩', // Arabic
        ];
        $replace = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];
        return str_replace($search, $replace, $s);
    }

    protected static function containsPersianDigits(string $s): bool
    {
        return (bool) preg_match('/[\x{06F0}-\x{06F9}\x{0660}-\x{0669}]/u', $s);
    }

    protected static function collapseSpaces(string $s): string
    {
        // Replace ZWNJ and NBSP with space, then collapse
        $s = str_replace(["\xE2\x80\x8C", "\xC2\xA0"], ' ', $s);
        return preg_replace('/\s+/u', ' ', trim($s));
    }
}

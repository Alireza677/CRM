<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Spatie\SimpleExcel\SimpleExcelReader;


class ProductImportService
{
    /**
     * Dry-Run: برگرداندن گزارش هدرها، نمونه‌داده و خطاهای اعتبارسنجی
     */
    public function dryRun(string $filepath, array $rules, array $aliases = []): array
    {
        $reader  = SimpleExcelReader::create($filepath);
        $headers = $reader->getHeaders() ?? [];

        $requiredHeaders = $this->requiredFields($rules);
        $missing = array_values(array_diff($requiredHeaders, $headers));
        $extra   = array_values(array_diff($headers, array_keys($rules)));

        $errors  = [];
        $samples = [];

        foreach ($reader->getRows() as $i => $row) {
            if ($i >= 10) break;

            $payload = $this->mapAliases($row, $aliases);
            $payload = $this->normalize($payload);
            $payload = Arr::only($payload, array_keys($rules));

            $v = Validator::make($payload, $rules);
            if ($v->fails()) {
                // +2 چون شماره‌ی ردیف اکسل بعد از هدر از 2 شروع می‌شود
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

    /**
     * Import: خواندن فایل و ذخیره‌ی محصولات
     * - به‌صورت insert گروهی (بدون upsert مگر خودت اضافه کنی)
     */
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
            $payload = Arr::only($payload, array_keys($rules));

            $v = Validator::make($payload, $rules);
            if ($v->fails()) {
                $failed[$index + 2] = $v->errors()->toArray();
                continue;
            }

            // مقادیر پیش‌فرض منطقی برای بولین‌ها
            if (!array_key_exists('has_vat', $payload) || $payload['has_vat'] === null)  $payload['has_vat']  = 0;
            if (!array_key_exists('is_active', $payload) || $payload['is_active'] === null) $payload['is_active'] = 1;

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

    /** درج گروهی */
    protected function insertChunk(array $rows): int
    {
        Product::insert($rows);
        return count($rows);
    }

    /** استخراج فیلدهای required از روی قوانین */
    protected function requiredFields(array $rules): array
    {
        $required = [];
        foreach ($rules as $field => $rule) {
            $str = is_array($rule) ? implode('|', $rule) : (string)$rule;
            if (stripos($str, 'required') !== false) $required[] = $field;
        }
        return $required;
    }

    /** نگاشت هدرهای فارسی/غیراستاندارد به نام فیلدهای دیتابیس */
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

    /** نرمال‌سازی عمومی: تاریخ/بولین/اعداد/trim */
    protected function normalize(array $row): array
    {
        $out = [];

        foreach ($row as $k => $v) {
            if (is_string($v)) {
                $v = trim($v);
            }

            // تاریخ‌ها
            if (in_array($k, [
                'sales_start_date','sales_end_date',
                'support_start_date','support_end_date'
            ], true)) {
                $v = $this->normalizeDate($v);
            }

            // بولین‌ها
            if (in_array($k, ['has_vat','is_active'], true)) {
                $v = $this->normalizeBoolean($v);
            }

            // اعداد اعشاری
            if (in_array($k, ['length','unit_price','thermal_power','commission','purchase_cost'], true)) {
                $v = $this->normalizeNumber($v);
            }

            // FKها
            if (in_array($k, ['category_id','supplier_id'], true)) {
                $v = ($v === '' || $v === null) ? null : $v;
            }

            $out[$k] = $v;
        }

        return $out;
    }

    /** تبدیل عدد سریال اکسل یا متن تاریخ به Y-m-d */
    protected function normalizeDate($v)
    {
        if ($v === '' || $v === null) return null;

        if (is_numeric($v)) {
            // مبنای اکسل (OpenXML): 1899-12-30
            $base = \DateTime::createFromFormat('Y-m-d', '1899-12-30');
            if ($base) {
                $base->modify('+' . ((int)$v) . ' days');
                return $base->format('Y-m-d');
            }
        }

        try {
            $dt = new \DateTime($v);
            return $dt->format('Y-m-d');
        } catch (\Throwable $e) {
            return $v; // بگذار Validator رد کند
        }
    }

    /** نگاشت بولین‌های متنی به 0/1 */
    protected function normalizeBoolean($v)
    {
        if ($v === '' || $v === null) return null;

        $true  = ['1','true','yes','y','on','بله','هست','فعال'];
        $false = ['0','false','no','n','off','خیر','نیست','غیرفعال'];

        $sv = is_string($v) ? mb_strtolower(trim($v)) : $v;

        if (is_numeric($sv)) {
            return ((int)$sv) ? 1 : 0;
        }
        if (in_array($sv, $true, true))  return 1;
        if (in_array($sv, $false, true)) return 0;

        return null;
    }

    /** پاک‌سازی فرمت اعداد اعشاری: حذف فاصله/کاما و تبدیل به عدد */
    protected function normalizeNumber($v)
    {
        if ($v === '' || $v === null) return null;

        if (is_string($v)) {
            // "12,345.67" یا "12 345,67" → "12345.67"
            $v = str_replace([' ', '٬', ','], ['', '', '.'], $v);
        }
        return is_numeric($v) ? (float)$v : $v;
    }
}

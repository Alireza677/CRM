<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Proforma;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelReader;
use Carbon\Carbon;
use Morilog\Jalali\CalendarUtils;
use Illuminate\Support\Str;

/**
 * ایمپورت پیش‌فاکتور + تکمیل/ایجاد سازمان و مخاطب + اتصال به فرصت فروش
 */
class ProformaImportController extends Controller
{
    public function form()
    {
        Log::debug('📄 نمایش فرم ایمپورت پیش‌فاکتور');
        return view('sales.proformas.import');
    }

    public function import(Request $request)
    {
        Log::debug('✅ وارد متد import شد.');
        Log::debug('📥 داده‌های ارسال‌شده توسط فرم.');
    
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);
    
        @set_time_limit(600);
        DB::disableQueryLog();
    
        $path = $request->file('file')->store('imports', ['disk' => 'private']);
        Log::debug('📂 فایل ذخیره شد در: ' . storage_path('app/private/' . $path));
    
        $saved = 0; $rejected = 0; $rowNo = 1;
    
        // کش‌های محلی
        $orgCache     = [];
        $userCache    = [];
        $oppCache     = [];   // فقط برای «پیدا کردن»، نه ساختن
        $contactCache = [];
    
        $rows = SimpleExcelReader::create(storage_path('app/private/' . $path))->getRows();
        $loggedHeader = false;
    
        foreach ($rows as $row) {
            $rowNo++;
    
            if (!$loggedHeader) {
                Log::debug('🧾 Headers: ' . json_encode(array_keys($row), JSON_UNESCAPED_UNICODE));
                $loggedHeader = true;
            }
            Log::debug('➡️ Row: ' . json_encode($row, JSON_UNESCAPED_UNICODE));
    
            // 1) نرمال‌سازی کلیدها
            $row = $this->normalizeHeaders($row);
    
            // 2) اعتبارسنجی پایه
            $reasons = [];
            $subject = trim((string)($row['subject'] ?? ''));
            if ($subject === '') $reasons[] = 'موضوع خالی است';
    
            // 3) تبدیل تاریخ
            $proformaDateRaw = $row['proforma_date'] ?? null;
            $proformaDate    = $this->toGregorian($proformaDateRaw);
            if (!empty($proformaDateRaw) && !$proformaDate) $reasons[] = 'تاریخ نامعتبر: ' . $proformaDateRaw;
    
            if ($reasons) { $rejected++; Log::debug('❌ رد شد: ' . implode(' | ', $reasons)); continue; }
    
            // 4) سازمان: یافتن یا ایجاد
            $orgName = $this->n((string)($row['organization_name'] ?? ''));
            $organization = null;
            if ($orgName !== '') {
                $orgKey = mb_strtolower($orgName, 'UTF-8');
                if (isset($orgCache[$orgKey])) {
                    $organization = $orgCache[$orgKey];
                } else {
                    $organization = Organization::whereRaw('LOWER(name) = ?', [$orgKey])->first();
                    if (!$organization) {
                        $organization = Organization::create(['name' => $orgName]);
                        Log::debug('🏢 سازمان جدید ایجاد شد: ' . $orgName);
                    }
                    $orgCache[$orgKey] = $organization;
                }
            }
    
            // 5) مخاطب: یافتن یا ایجاد (در صورت وجود نام مخاطب)
            $contactName = $this->n((string)($row['contact_name'] ?? ''));
            $contact = null;
    
            if ($contactName !== '') {
                $contactKey = ($organization?->id ?: 'noorg') . '|' . mb_strtolower($contactName, 'UTF-8');
    
                if (isset($contactCache[$contactKey])) {
                    $contact = $contactCache[$contactKey];
                } else {
                    $q = Contact::query();
    
                    $hasName      = Schema::hasColumn('contacts', 'name');
                    $hasFullName  = Schema::hasColumn('contacts', 'full_name');
                    $hasFirstName = Schema::hasColumn('contacts', 'first_name');
                    $hasLastName  = Schema::hasColumn('contacts', 'last_name');
    
                    $needle = mb_strtolower($contactName, 'UTF-8');
    
                    $q->where(function ($qq) use ($hasName, $hasFullName, $hasFirstName, $hasLastName, $needle) {
                        if ($hasName)      $qq->orWhereRaw('LOWER(name) = ?', [$needle]);
                        if ($hasFullName)  $qq->orWhereRaw('LOWER(full_name) = ?', [$needle]);
                        if ($hasFirstName && $hasLastName) {
                            $qq->orWhereRaw('LOWER(CONCAT_WS(" ", first_name, last_name)) = ?', [$needle])
                               ->orWhereRaw('LOWER(first_name) = ?', [$needle])
                               ->orWhereRaw('LOWER(last_name) = ?', [$needle]);
                        }
                    });
    
                    if ($organization) $q->where('organization_id', $organization->id);
    
                    $contact = $q->first();
    
                    if (!$contact) {
                        $payload = ['organization_id' => $organization?->id];
    
                        if     ($hasName)     $payload['name'] = $contactName;
                        elseif ($hasFullName) $payload['full_name'] = $contactName;
                        elseif ($hasFirstName || $hasLastName) {
                            $parts = preg_split('/\s+/', $contactName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                            $payload['first_name'] = $parts[0] ?? $contactName;
                            $payload['last_name']  = $parts[1] ?? null;
                        } else {
                            $payload['display_name'] = $contactName;
                        }
    
                        $contact = Contact::create($payload);
                        Log::debug('👤 مخاطب جدید ایجاد شد: ' . $contactName);
                    }
    
                    $contactCache[$contactKey] = $contact;
                }
            }
    
            // 6) کاربر ارجاع‌گیرنده از روی ایمیل
            $assignedUserId = null;
            $assignedEmail  = $this->n((string)($row['assigned_to'] ?? ''));
            if ($assignedEmail !== '') {
                $emailKey = mb_strtolower($assignedEmail, 'UTF-8');
                if (isset($userCache[$emailKey])) {
                    $assignedUserId = $userCache[$emailKey];
                } else {
                    $user = User::whereRaw('LOWER(email) = ?', [$emailKey])->first();
                    $assignedUserId = $user?->id;
                    $userCache[$emailKey] = $assignedUserId;
                }
            }
    
            // 7) فرصت فروش: فقط «پیدا کن»، «ایجاد نکن»
            $oppTitle    = $this->n((string)($row['sales_opportunity'] ?? ''));
            $opportunity = null;
    
            if ($oppTitle !== '') {
                $oppKey = ($organization?->id ?: 'noorg') . '|' . mb_strtolower($oppTitle, 'UTF-8');
    
                if (isset($oppCache[$oppKey])) {
                    $opportunity = $oppCache[$oppKey];
                } else {
                    $norm = mb_strtolower($oppTitle, 'UTF-8');
    
                    $oq = Opportunity::query()->whereRaw('LOWER(name) = ?', [$norm]);
                    if ($organization) $oq->where('organization_id', $organization->id);
    
                    $opportunity = $oq->first();      // ← فقط جست‌وجو
                    $oppCache[$oppKey] = $opportunity; // ممکن است null باشد
                }
            }
    
            // 8) نگاشت مرحله
            $rawStage = (string)($row['proforma_stage'] ?? '');
            $stageMap = [
                'نامشخص'            => null,
                'در حال بررسی'      => 'pending',
                'ارسال شده'         => 'sent',
                'تایید شده'         => 'approved',
                'لغو شده'           => 'cancelled',
                'احتمالی'           => 'potential',
                'Accepted'          => 'approved',
                'ارسال برای تاییدیه' => 'sent',
            ];
            $proformaStage = $stageMap[$rawStage] ?? (strlen($rawStage) ? $rawStage : null);
    
            // 9) سایر فیلدها
            $totalRaw = $row['total_amount'] ?? ($row['total'] ?? null);
            $total    = $this->toDecimal($totalRaw);
    
            $city    = (string)($row['city'] ?? '');
            $state   = (string)($row['state'] ?? ($row['province'] ?? ''));
            $address = (string)($row['customer_address'] ?? ($row['address_line'] ?? ''));
    
            // 10) ذخیره پروفرما در تراکنش
            DB::beginTransaction();
            try {
                $proforma = Proforma::create([
                    'subject'            => $subject,
                    'proforma_stage'     => $proformaStage,
                    'proforma_date'      => $proformaDate,
                    'organization_id'    => $organization?->id,
                    'organization_name'  => $organization?->name,
                    'contact_id'         => $contact?->id,
                    'contact_name'       => $contact?->name,
                    'opportunity_id'     => $opportunity?->id,   // فقط اگر پیدا شد
                    'sales_opportunity'  => $oppTitle ?: null,   // متن برای لینک‌دهی بعدی
                    'assigned_to'        => $assignedUserId,
                    'city'               => $city,
                    'state'              => $state,
                    'customer_address'   => $address,
                    'total_amount'       => $total,
                ]);
    
                DB::commit();
                $saved++;
                Log::debug('✅ ذخیره شد: proforma_id=' . $proforma->id);
            } catch (\Throwable $e) {
                DB::rollBack();
                $rejected++;
                Log::debug('❌ رد شد (DB): ' . $e->getMessage());
            }
        }
    
        Log::debug("🎉 پایان ایمپورت. ذخیره‌شده‌ها: {$saved} | ردشده‌ها: {$rejected}");
        return back()->with('status', "ایمپورت انجام شد. ذخیره‌شده‌ها: {$saved} | ردشده‌ها: {$rejected}");
    }
    

    // --- Helpers ---

    /** نرمال‌سازی هدرها */
    private function normalizeHeaders(array $row): array
    {
        $map = [
            'proforma stage' => 'proforma_stage',
            'proforma date'  => 'proforma_date',
            'organization'   => 'organization_name',
            'state'          => 'state',
            'city'           => 'city',
            'total_amount'   => 'total_amount',
            'customer_address' => 'customer_address',
            // فارسی‌های رایج
            'تاریخ' => 'proforma_date',
            'مرحله' => 'proforma_stage',
            'نام سازمان' => 'organization_name',
            'نام مخاطب' => 'contact_name',
            'موضوع' => 'subject',
        ];

        $normalized = [];
        foreach ($row as $k => $v) {
            $key = $this->n((string)$k);
            $key = $map[$key] ?? $key;
            $normalized[$key] = $v;
        }
        return $normalized;
    }

    /** تبدیل جلالی/سریال اکسل/میلادی به Y-m-d میلادی */
    private function toGregorian($value): ?string
    {
        if ($value === null || $value === '') return null;
        $s = $this->n((string)$value);
        try {
            if (is_numeric($s)) {
                $carbon = Carbon::create(1899, 12, 30)->addDays((int)$s);
                return $carbon->toDateString();
            }
            if (preg_match('/^(13|14)\\d{2}[-\\/.](0[1-9]|1[0-2])[-\\/.]([0-2]\\d|3[01])$/', $s)) {
                $s = str_replace(['/', '.'], '-', $s);
                return CalendarUtils::createCarbonFromFormat('Y-m-d', $s)->toDateString();
            }
            return Carbon::parse($s)->toDateString();
        } catch (\Throwable $e) { return null; }
    }

    /** نرمال‌سازی سادهٔ رشته + تبدیل ارقام فارسی/عربی به لاتین */
    private function n(string $val): string
    {
        $val = trim($val);
        $digitsFa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','\\u0664','\\u0665','\\u0666','\\u0667','\\u0668','\\u0669'];
        $digitsLa = ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'];
        // Normalize additional Arabic-Indic digits if needed
        $val = str_replace($digitsFa, $digitsLa, $val);
        return $val;
    }

    public function store(Request $request)
{
    $request->validate([
        'file' => ['required','file','mimes:xlsx,csv,xls'],
    ]);

    $path = $request->file('file')->getRealPath();
    $reader = SimpleExcelReader::create($path);

    Log::debug('🧾 Headers', ['headers' => $reader->getHeaders()]);

    $rows = $reader->getRows();
    $chunkSize = 300;
    $ok = 0; $fail = 0; $i = 0;

    DB::transaction(function () use ($rows, $chunkSize, &$ok, &$fail, &$i) {
        $rows->chunk($chunkSize)->each(function ($chunk) use (&$ok, &$fail, &$i) {
            foreach ($chunk as $rawRow) {
                $i++;
                try {
                    $row = $this->normalizeKeys(is_array($rawRow) ? $rawRow : (array)$rawRow);

                    Log::debug('➡️ Row', [
                        'idx' => $i,
                        'total_amount_key_exists' => array_key_exists('total_amount', $row),
                        'total_amount_raw' => $row['total_amount'] ?? null,
                    ]);

                    $pf = \App\Models\Proforma::create([
                        'subject'           => $row['subject'] ?? null,
                        'proforma_date'     => $row['proforma_date'] ?? null,
                        'organization_name' => $row['organization_name'] ?? null,
                        'contact_name'      => $row['contact_name'] ?? null,
                        'sales_opportunity' => $row['sales_opportunity'] ?? null,
                        'assigned_to'       => $row['assigned_to'] ?? null,
                        'city'              => $row['city'] ?? null,
                        'state'             => $row['state'] ?? null,
                        'customer_address'  => $row['customer_address'] ?? null,
                        'proforma_stage'    => $row['proforma_stage'] ?? null,

                        'total_amount'      => $row['total_amount'] ?? null,
                    ]);

                    // مقدار خام attribute در مدل (دور زدن اکسسورها)
                    $rawAttr = $pf->getAttributes()['total_amount'] ?? null;
                    // مقدار از طریق اِلـوکونت (ممکن است اکسسور/کست اعمال شود)
                    $castVal = $pf->total_amount;
                    // مقدار خام از DB
                    $dbVal = DB::table('proformas')->where('id', $pf->id)->value('total_amount');

                    Log::debug('✅ ذخیره شد', [
                        'idx'            => $i,
                        'proforma_id'    => $pf->id,
                        'attr_raw'       => $rawAttr,
                        'attr_cast'      => $castVal,
                        'db_value'       => $dbVal,
                    ]);

                    $ok++;
                } catch (\Throwable $e) {
                    $fail++;
                    Log::error('❌ خطا در ردیف', [
                        'idx' => $i,
                        'msg' => $e->getMessage(),
                        'row' => isset($row) ? $row : $rawRow,
                    ]);
                }
            }
        });
    });

    Log::debug('🎉 پایان ایمپورت', ['ok' => $ok, 'fail' => $fail]);

    return back()->with('status', "Import done. Success: {$ok}, Failed: {$fail}");
}
    

    private function normalizeKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            // مثال‌هایی که همه به total_amount می‌خورند: "Total Amount", "Total-Amount", "total.amount"
            $normKey = Str::of($key)
                ->lower()
                ->replace([' ', '-', '.', 'ـ'], '_')  // کاراکترهای رایج عنوان‌ها
                ->replace(['__'], '_')
                ->value();

            $normalized[$normKey] = $value;
        }
        return $normalized;
    }

    private function toDecimal($val): ?float
{
    if ($val === null || $val === '') return null;

    // ارقام فارسی/عربی → لاتین
    $val = strtr((string)$val, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);

    // هندل نوتیشن علمی مثل 5.55E+7
    if (preg_match('/^[\+\-]?\d+(\.\d+)?e[\+\-]?\d+$/i', trim($val))) {
        $val = (string)((float)$val);
    }

    // حذف جداکننده‌ها/نماد ارز
    $val = preg_replace('/[^\d\.\-]/', '', str_replace(',', '', trim($val)));

    return $val === '' ? null : round((float)$val, 2);
}

}


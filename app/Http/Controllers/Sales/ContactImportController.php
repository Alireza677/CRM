<?php

namespace App\Http\Controllers\Sales;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Support\Str;




class ContactImportController extends Controller
{
    public function showForm()
    {
        return view('sales.contacts.import');
    }

    public function import(Request $request)
{
    $file = $request->file('contacts_file');

    if (!$file || !$file->isValid()) {
        Log::error('فایل معتبر نیست یا وجود ندارد');
        return back()->withErrors(['file' => 'فایل معتبر نیست']);
    }

    // ذخیره فایل با move
    $filename    = uniqid() . '.' . $file->getClientOriginalExtension();
    $destination = storage_path('app/tmp/' . $filename);

    try {
        $file->move(storage_path('app/tmp'), $filename);
        Log::info('فایل با move منتقل شد', ['path' => $destination]);
    } catch (\Throwable $e) {
        Log::error('خطا در move فایل', ['message' => $e->getMessage()]);
        return back()->withErrors(['file' => 'خطا در ذخیره فایل: ' . $e->getMessage()]);
    }

    // شروع ایمپورت
    $imported = 0;
    $skipped  = 0;

    try {
        $reader = SimpleExcelReader::create($destination);
        $rows   = $reader->getRows();

        $rowCount = 0;

        // ✅ هلسپر: مقدار ستون را از بین چند کلید برگردان (با trim)
        $val = function (array $row, string ...$keys) {
            foreach ($keys as $k) {
                if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
                    return trim((string)$row[$k]);
                }
            }
            return null;
        };

        foreach ($rows as $row) {
            $rowCount++;
            Log::debug("سطر $rowCount", $row);

            // رد کردن رکورد با ایمیل تکراری (اگر ایمیل دارد)
            $email = $val($row, 'email');
            if (!empty($email) && Contact::where('email', $email)->exists()) {
                $skipped++;
                continue;
            }

            // سازمان
            $organization_id = null;
            $company = $val($row, 'company');
            if (!empty($company)) {
                $organization   = Organization::firstOrCreate(['name' => $company]);
                $organization_id = $organization->id;
            }

            // کاربر ارجاع
            $assignedUser = null;
            $assigneeEmail = $val($row, 'assigned_to_email');
            if (!empty($assigneeEmail)) {
                $assignedUser = User::where('email', $assigneeEmail)->first();
                Log::debug('📌 بررسی کاربر واگذار شده:', ['user' => $assignedUser?->only(['id','email','name'])]);
            }

            Contact::create([
                'first_name'      => $val($row, 'first_name'),
                'last_name'       => $val($row, 'last_name'),
                'email'           => $email,
                'phone'           => $val($row, 'phone'),
                'mobile'          => $val($row, 'mobile'),
                'company'         => $company,
                'organization_id' => $organization_id,
                'city'            => $val($row, 'city', 'shahr'),
                // ✅ استان: از هر دو کلید 'province' و 'state' پشتیبانی، و در DB در فیلد state ذخیره می‌کنیم
                'state'           => $val($row, 'province', 'state'),
                'address'         => $val($row, 'address'),
                'assigned_to'     => $assignedUser?->id,
            ]);

            $imported++;
        }

        Log::info("تعداد کل سطرها: $rowCount");
        Log::info("تعداد ایمپورت شده: $imported");
        Log::info("تعداد رد شده (تکراری): $skipped");

        return back()->with('success', "$imported مخاطب با موفقیت ایمپورت شد. $skipped مورد تکراری نادیده گرفته شد.");
    } catch (\Throwable $e) {
        Log::error('خطا در ایمپورت مخاطبین', ['message' => $e->getMessage()]);
        return back()->withErrors(['file' => 'خطا در پردازش فایل: ' . $e->getMessage()]);
    }
}


public function export(Request $request)
{
    // در صورت نیاز: $this->authorize('viewAny', Contact::class);

    $format = strtolower((string)($request->route('format') ?? $request->get('format', 'csv')));
    $format = in_array($format, ['csv','xlsx'], true) ? $format : 'csv';

    $filename = 'contacts-' . now()->format('Ymd-His') . '.' . $format;
    $tmpDir   = storage_path('app/tmp');
    if (!is_dir($tmpDir)) { @mkdir($tmpDir, 0775, true); }
    $tmpPath  = $tmpDir . '/' . \Illuminate\Support\Str::uuid()->toString() . '.' . $format;

    // --- Writer ---
    $writer = \Spatie\SimpleExcel\SimpleExcelWriter::create($tmpPath)
        ->addHeader([
            'first_name','last_name','email','phone','mobile','company',
            'organization','city','province','address','assigned_to_name','assigned_to_email',
        ]);

    // --- Base query (بدون limit/paginate) ---
    $query = \DB::table('contacts')
        ->leftJoin('organizations', 'organizations.id', '=', 'contacts.organization_id')
        ->leftJoin('users as u', 'u.id', '=', 'contacts.assigned_to')
        ->select([
            'contacts.id as id', // ⬅️ alias ثابت برای پیمایش
            'contacts.first_name','contacts.last_name','contacts.email',
            'contacts.phone','contacts.mobile','contacts.company',
            'organizations.name as organization_name',
            'contacts.city','contacts.state','contacts.address',
            'contacts.assigned_to',
            'u.name as assigned_to_name','u.email as assigned_to_email',
        ])
        ->orderBy('contacts.id'); // ⬅️ ضروری برای lazyById

    // --- Optional filters ---
    if ($ids = $request->get('ids')) {
        $ids = collect(explode(',', $ids))->map(fn($v)=>(int)trim($v))->filter()->all();
        if (!empty($ids)) {
            $query->whereIn('contacts.id', $ids);
        }
    }

    if ($q = trim((string)$request->get('q'))) {
        $query->where(function($w) use ($q){
            $w->where('contacts.first_name', 'like', "%{$q}%")
              ->orWhere('contacts.last_name',  'like', "%{$q}%")
              ->orWhere('contacts.email',      'like', "%{$q}%")
              ->orWhere('contacts.mobile',     'like', "%{$q}%")
              ->orWhere('contacts.company',    'like', "%{$q}%")
              ->orWhere('organizations.name',  'like', "%{$q}%");
        });
    }

    if ($assigned = $request->get('assigned_to')) {
        $query->where('contacts.assigned_to', (int)$assigned);
    }

    if ($orgId = $request->get('org_id')) {
        $query->where('contacts.organization_id', (int)$orgId);
    }
// چند تا رکورد کل داری (بدون فیلتر)
\Log::info('contacts_total_all', ['count' => \App\Models\Contact::count()]);

// چند تا رکورد همین کوئری فیلترشده‌ی شما برمی‌گردونه
$debugCount = (clone $query)->count();   // قبل از lazyById
\Log::info('contacts_total_filtered', ['count' => $debugCount]);
    // --- Stream all rows بدون جا افتادن ---
    foreach ($query->lazyById(2000, 'contacts.id', 'id') as $c) {
        $writer->addRow([
            'first_name'        => $c->first_name,
            'last_name'         => $c->last_name,
            'email'             => $c->email,
            'phone'             => $c->phone,
            'mobile'            => $c->mobile,
            'company'           => $c->company,
            'organization'      => $c->organization_name,
            'city'              => $c->city,
            'province'          => $c->state,
            'address'           => $c->address,
            'assigned_to_name'  => $c->assigned_to_name,
            'assigned_to_email' => $c->assigned_to_email,
        ]);
    }

    $writer->close();

    $headers = $format === 'xlsx'
        ? ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        : ['Content-Type' => 'text/csv; charset=UTF-8'];

    return response()->download($tmpPath, $filename, $headers)
        ->deleteFileAfterSend(true);
}



    
}

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


    
}

<?php

namespace App\Http\Controllers\Sales;

use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Http\Controllers\Controller;

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
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
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
        $skipped = 0;

        try {
            $reader = SimpleExcelReader::create($destination);
            $rows = $reader->getRows();

            $rowCount = 0;

            foreach ($rows as $row) {
                $rowCount++;
                Log::debug("سطر $rowCount", $row);

                // بررسی وجود ستون ایمیل
                if (!empty($row['email']) && Contact::where('email', $row['email'])->exists()) {
                    $skipped++;
                    continue;
                }

                $organization_id = null;
                if (!empty($row['company'])) {
                    $organization = Organization::firstOrCreate(['name' => $row['company']]);
                    $organization_id = $organization->id;
                }

                Contact::create([
                    'first_name'      => $row['first_name'] ?? null,
                    'last_name'       => $row['last_name'] ?? null,
                    'email'           => $row['email'] ?? null,
                    'phone'           => $row['phone'] ?? null,
                    'mobile'          => $row['mobile'] ?? null,
                    'company'         => $row['company'] ?? null,
                    'organization_id' => $organization_id,
                    'city'            => $row['city'] ?? null,
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

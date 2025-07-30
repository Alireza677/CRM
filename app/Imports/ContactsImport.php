<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\SimpleExcel\SimpleExcelReader;

class ContactImportController extends Controller
{
    public function import(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            Log::error("❌ هیچ فایلی برای ایمپورت ارسال نشد.");
            return back()->withErrors(['file' => 'فایل ارسال نشده است.']);
        }

        $path = $file->getRealPath();
        Log::info("📂 فایل با move منتقل شد", ['path' => $path]);

        $totalRows = 0;
        $imported = 0;
        $duplicates = 0;
        $missingAssignee = 0;

        SimpleExcelReader::create($path)->getRows()->each(function (array $row) use (&$totalRows, &$imported, &$duplicates, &$missingAssignee) {
            $totalRows++;

            Log::debug("📄 سطر $totalRows:", $row);

            $firstName = $row['first_name'] ?? '';
            $lastName  = $row['last_name'] ?? '';
            $email     = $row['email'] ?? null;
            $mobile    = $row['mobile'] ?? null;

            $duplicate = Contact::query()
                ->when($email, fn($q) => $q->orWhere('email', $email))
                ->when($mobile, fn($q) => $q->orWhere('mobile', $mobile))
                ->exists();

                if ($duplicate) {
                    $duplicates++;
                    Log::warning("🔁 مخاطب تکراری: {$firstName} {$lastName}");
                    return;
                }
                
                Log::debug("⏩ مخاطب جدید ادامه دارد: {$firstName} {$lastName}");
                

            // پیدا کردن کاربر ارجاع‌دهنده
            $assignedUser = null;
            if (!empty($row['assigned_to_email'])) {
                $assignedToEmail = trim($row['assigned_to_email']);
                $assignedUser = User::where('email', $assignedToEmail)->first();

                if ($assignedUser) {
                    Log::info("✅ کاربر ارجاع‌دهنده یافت شد", [
                        'email' => $assignedToEmail,
                        'user_id' => $assignedUser->id,
                    ]);
                } else {
                    Log::warning("⚠️ کاربر با ایمیل '{$assignedToEmail}' پیدا نشد.");
                    $missingAssignee++;
                }
            } else {
                Log::info("ℹ️ مخاطب {$firstName} بدون ایمیل ارجاع‌دهنده ثبت می‌شود.");
                $missingAssignee++;
            }

            // ساخت یا یافتن سازمان
            $organizationId = null;
            $organizationName = trim($row['company'] ?? '');
            if (!empty($organizationName)) {
                $organization = Organization::firstOrCreate(
                    ['name' => $organizationName],
                    [
                        'slug' => Str::slug($organizationName),
                        'phone' => $row['organization_phone'] ?? null,
                        'city' => $row['city'] ?? null,
                    ]
                );
                $organizationId = $organization->id;

                Log::info("🏢 سازمان پیدا یا ساخته شد:", [
                    'name' => $organizationName,
                    'id' => $organizationId,
                ]);
            }
            Log::debug("🎯 آماده ساخت مخاطب", [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'mobile' => $mobile,
                'organization_id' => $organizationId,
                'assigned_user_id' => $assignedUser?->id,
            ]);
            
            // ساخت مخاطب
            try {
                $contact = Contact::create([
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'email'           => $email,
                    'phone'           => $row['phone'] ?? null,
                    'mobile'          => $mobile,
                    'city'            => $row['city'] ?? null,
                    'organization_id' => $organizationId,
                    'assigned_to'     => $assignedUser?->id,
                ]);
            
                Log::info("✅ مخاطب ساخته شد:", [
                    'id' => $contact->id,
                    'assigned_to' => $contact->assigned_to,
                ]);
            } catch (\Throwable $e) {
                Log::error("❌ خطا در ذخیره مخاطب", [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'trace' => collect($e->getTrace())->take(3)->all(), // فقط ۳ تا خط اول برای خلاصه
                ]);
            }
            
            
        });

        Log::info("📊 گزارش نهایی ایمپورت:");
        Log::info("📌 تعداد کل سطرها: $totalRows");
        Log::info("📥 تعداد ایمپورت شده: $imported");
        Log::info("🔁 تعداد تکراری‌ها: $duplicates");
        Log::info("❓ تعداد با ارجاع‌دهنده نامشخص: $missingAssignee");

        return redirect()->back()->with('success', "ایمپورت انجام شد. $imported مخاطب ذخیره شد.");
    }
}

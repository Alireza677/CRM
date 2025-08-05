<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Carbon;


class OrganizationImportController extends Controller
{
    public function importForm()
    {
        return view('sales.organizations.import');
    }

    
    public function import(Request $request)
    {
        $file = $request->file('file');

        if (!$file || !$file->isValid()) {
            return back()->withErrors(['file' => 'فایل معتبر نیست']);
        }

        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $destination = storage_path('app/tmp/' . $filename);
        $file->move(storage_path('app/tmp'), $filename);

        $rows = SimpleExcelReader::create($destination)->getRows();
        $importedCount = 0;
        $duplicateCount = 0;
        $failedCount = 0;
        foreach ($rows as $row) {
            try {
                $name = trim($row['name'] ?? '');
        
                if (!$name) {
                    $failedCount++;
                    continue;
                }
        
                $organization = Organization::where('name', $name)->first();
        
                if (!$organization) {
                    $organization = new Organization();
                    $organization->name = $name;
                    $importedCount++; // مورد جدید
                } else {
                    $duplicateCount++; // مورد تکراری
                }
        
                // فیلدهای دیگر
                $organization->phone        = $organization->phone        ?? ($row['phone']        ?? null);
                $organization->website      = $organization->website      ?? ($row['website']      ?? null);
                $organization->industry     = $organization->industry     ?? ($row['industry']     ?? null);
                $organization->state        = $organization->state        ?? ($row['state']        ?? null);
                $organization->city         = $organization->city         ?? ($row['city']         ?? null);
                $organization->address      = $organization->address      ?? ($row['address']      ?? null);
                $organization->description  = $organization->description  ?? ($row['description']  ?? null);
        
                if (!$organization->assigned_to && !empty($row['assigned_to'])) {
                    $user = User::where('email', trim($row['assigned_to']))->first();
                    $organization->assigned_to = $user?->id;
                }
        
                if (!$organization->created_at && !empty($row['created_at'])) {
                    try {
                        $cleanDate = str_replace('/', '-', $row['created_at']);
                        $organization->created_at = Jalalian::fromFormat('Y-m-d H:i:s', $cleanDate)->toCarbon();
                    } catch (\Exception $e) {
                        Log::error('⛔ خطا در تبدیل تاریخ شمسی به میلادی', [
                            'value' => $row['created_at'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
        
                $organization->save();
            } catch (\Throwable $e) {
                $failedCount++; // خطای کلی در این ردیف
                Log::error('⛔ خطا در پردازش ردیف سازمان', [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }
        

            return redirect()->back()->with('success', [
                'message' => 'ایمپورت با موفقیت انجام شد!',
                'imported' => $importedCount,         // تعداد سطرهایی که ایمپورت شدن
                'duplicates' => $duplicateCount,      // تعداد سطرهای تکراری
                'failed' => $failedCount              // تعداد سطرهایی که خطا داشتن
            ]);
    }


}

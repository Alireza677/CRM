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

        foreach ($rows as $row) {
            $name = trim($row['name']);

            if (!$name) continue;

            $organization = Organization::where('name', $name)->first();

            if (!$organization) {
                $organization = new Organization();
                $organization->name = $name;
            }

            // فقط فیلدهایی که مقدار ندارند را بروزرسانی کن
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
                    // فرض بر این است که تاریخ به‌صورت 1401-07-20 یا 1401/07/20 است
                    $cleanDate = str_replace('/', '-', $row['created_at']); // تبدیل / به -
                    $organization->created_at = Jalalian::fromFormat('Y-m-d H:i:s', $cleanDate)->toCarbon();
                } catch (\Exception $e) {
                    Log::error('⛔ خطا در تبدیل تاریخ شمسی به میلادی', [
                        'value' => $row['created_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            

            $organization->save();
        }

        return back()->with('success', 'بروزرسانی سازمان‌ها با موفقیت انجام شد.');
    }


}

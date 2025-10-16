<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Imports\SupplierImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SupplierImportController extends Controller
{
    public function create()
    {
        return view('inventory.suppliers.import');
    }

    public function dryRun(Request $request, SupplierImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt',
        ]);

        $path = $request->file('file')->store('imports');

        $report = $service->dryRun(
            Storage::path($path),
            $this->rules(),
            $this->aliases()
        );

        return back()->with([
            'dry_run' => $report,
            'uploaded_path' => $path,
        ]);
    }

    public function store(Request $request, SupplierImportService $service)
    {
        $request->validate([
            'uploaded_path' => 'required|string',
        ]);

        $result = $service->import(
            Storage::path($request->uploaded_path),
            $this->rules(),
            $this->aliases()
        );

        return back()->with('import_result', $result);
    }

    protected function rules(): array
    {
        return [
            'name'         => ['required','string','max:255'],
            'phone'        => ['nullable','string','max:255'],
            'email'        => ['nullable','email','max:255'],
            'website'      => ['nullable','url','max:255'],
            'address'      => ['nullable','string'],
            'province'     => ['nullable','string','max:255'],
            'city'         => ['nullable','string','max:255'],
            'postal_code'  => ['nullable','string','max:255'],
            'description'  => ['nullable','string'],
            'is_active'    => ['nullable','boolean'],
            'category_id'  => ['nullable','exists:categories,id'],
            'assigned_to'  => ['nullable','exists:users,id'],
            'created_at'   => ['nullable','date'],
            'updated_at'   => ['nullable','date'],
        ];
    }

    protected function aliases(): array
    {
        return [
            // Persian headers -> DB fields
            'نام تأمین‌کننده' => 'name',
            'تلفن'            => 'phone',
            'ایمیل'           => 'email',
            'آدرس'            => 'address',
            'استان'           => 'province',
            'شهر'             => 'city',
            'کد پستی'         => 'postal_code',
            'کدپستی'          => 'postal_code',
            'وبسایت'          => 'website',
            'وب سایت'         => 'website',
            'منبع'            => 'description',
            'دسته‌بندی'       => 'category_id',
            'دسته بندی'       => 'category_id',
            'ارجاع به'        => 'assigned_to',
            'زمان ایجاد'      => 'created_at',
            'زمان ویرایش'     => 'updated_at',

            // Optional alternates
            'نام تامین کننده' => 'name',
            'نام تأمین کننده' => 'name',
        ];
    }
}

<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Imports\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImportController extends Controller
{
    public function create()
    {
        $breadcrumb = [
            ['title' => 'داشبورد', 'url' => route('dashboard')],
            ['title' => 'محصولات', 'url' => route('inventory.products.index')],
            ['title' => 'ایمپورت محصولات'],
        ];

        // فایل: resources/views/inventory/products/import.blade.php
        return view('inventory.products.import', compact('breadcrumb'));
        // یا مقاوم‌تر:
        // return view()->first(['inventory.products.import','products.import'], compact('breadcrumb'));
    }

    public function dryRun(Request $request, ProductImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt'
        ]);

        $path = $request->file('file')->store('imports');

        $report = $service->dryRun(
            Storage::path($path),
            $this->rules(),
            $this->aliases()
        );

        // برگرد به همان فرم و گزارش Dry Run را نشان بده
        return back()->with([
            'dry_run' => $report,
            'uploaded_path' => $path,
        ]);
    }

    public function store(Request $request, ProductImportService $service)
    {
        $request->validate([
            'uploaded_path' => 'required|string'
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
            'name'               => ['required','string','max:255'],
            'unit_price'         => ['required','numeric','min:0'],

            'sales_start_date'   => ['nullable','date'],
            'sales_end_date'     => ['nullable','date'],
            'support_start_date' => ['nullable','date'],
            'support_end_date'   => ['nullable','date'],

            'category_id'        => ['nullable','exists:categories,id'],
            'supplier_id'        => ['nullable','exists:suppliers,id'],

            'manufacturer'       => ['nullable','string','max:255'],
            'series'             => ['nullable','string','max:255'],
            'length'             => ['nullable','numeric','min:0'],
            'has_vat'            => ['nullable','boolean'],
            'is_active'          => ['nullable','boolean'],
            'website'            => ['nullable','url','max:255'],
            'part_number'        => ['nullable','string','max:255'],
            'type'               => ['nullable','string','max:255'],
            'thermal_power'      => ['nullable','numeric','min:0'],
            'commission'         => ['nullable','numeric','min:0'],
            'purchase_cost'      => ['nullable','numeric','min:0'],
        ];
    }

    protected function aliases(): array
    {
        return [
            'نام محصول'         => 'name',
            'قیمت واحد'         => 'unit_price',
            'قیمت'              => 'unit_price',
            'سری'               => 'series',
            'سازنده'            => 'manufacturer',
            'وبسایت'            => 'website',
            'شماره قطعه'        => 'part_number',
            'نوع'               => 'type',
            'طول'               => 'length',
            'توان حرارتی'       => 'thermal_power',
            'پورسانت'           => 'commission',
            'قیمت خرید'         => 'purchase_cost',
            'مشمول مالیات'      => 'has_vat',
            'فعال'              => 'is_active',

            'تاریخ شروع فروش'   => 'sales_start_date',
            'تاریخ پایان فروش'  => 'sales_end_date',
            'شروع پشتیبانی'     => 'support_start_date',
            'پایان پشتیبانی'    => 'support_end_date',

            'شناسه دسته'        => 'category_id',
            'شناسه تامین‌کننده' => 'supplier_id',
        ];
    }
}

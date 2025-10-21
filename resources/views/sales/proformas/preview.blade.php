@extends('layouts.app')

@section('content')
@php
    /** @var \App\Models\Proforma $proforma */
    $proforma->loadMissing(['items.product', 'organization', 'contact']);

    $seller = [
        'name'        => config('app.name'),
        'economic_id' => null,
        'national_id' => null,
        'postal_code' => null,
        'address'     => null,
        'phone'       => null,
        'fax'         => null,
        'city'        => null,
        'state'       => null,
    ];

    $buyerName = $proforma->organization->name ?? ($proforma->organization_name ?? '-');
    $buyer = [
        'name'        => $buyerName,
        'economic_id' => $proforma->organization->economic_id ?? null,
        'national_id' => $proforma->organization->national_id ?? null,
        'postal_code' => $proforma->postal_code ?? ($proforma->organization->postal_code ?? null),
        'address'     => $proforma->customer_address ?: ($proforma->organization->address ?? null),
        'city'        => $proforma->city ?: ($proforma->organization->city ?? null),
        'state'       => $proforma->state ?: ($proforma->organization->state ?? null),
        'phone'       => $proforma->organization->phone ?? null,
        'fax'         => $proforma->organization->fax ?? null,
        'contact'     => trim(($proforma->contact->full_name ?? '') ?: ($proforma->contact_name ?? '')),
    ];

    try {
        $dateFa = $proforma->proforma_date
            ? \App\Helpers\DateHelper::toJalali($proforma->proforma_date, 'Y/m/d')
            : (isset($proforma->created_at) ? \App\Helpers\DateHelper::toJalali($proforma->created_at, 'Y/m/d') : '-');
    } catch (\Throwable $e) { $dateFa = '-'; }

    $subtotal = (float) ($proforma->items?->sum('total_price') ?? 0);
    if (isset($proforma->global_discount_amount)) {
        $discount = (float) $proforma->global_discount_amount;
    } else {
        $dt = $proforma->global_discount_type;
        $dv = (float) ($proforma->global_discount_value ?? 0);
        $discount = $dt === 'percentage' ? ($subtotal * $dv) / 100 : ($dt === 'fixed' ? $dv : 0);
    }
    $discount = min($discount, $subtotal);
    $afterDiscount = $subtotal - $discount;

    if (isset($proforma->global_tax_amount)) {
        $tax = (float) $proforma->global_tax_amount;
    } else {
        $tt = $proforma->global_tax_type;
        $tv = (float) ($proforma->global_tax_value ?? 0);
        $tax = $tt === 'percentage' ? ($afterDiscount * $tv) / 100 : ($tt === 'fixed' ? $tv : 0);
    }
    $tax = max($tax, 0);
    $grand = isset($proforma->total_amount) ? (float) $proforma->total_amount : ($afterDiscount + $tax);
@endphp

<div class="container py-6" dir="rtl">
    <div class="flex items-center justify-between mb-4 no-print">
        <a href="{{ route('sales.proformas.show', $proforma) }}" class="btn btn-secondary">بازگشت</a>
        <button onclick="window.print()" class="btn btn-primary">پرینت</button>
    </div>

    <div class="invoice-a4">
        <div class="invoice-header">
            <div class="title">پیش‌فاکتور فروش کالا و خدمات</div>
            <div class="meta">
                <div><span>شماره:</span> {{ $proforma->proforma_number ?: ('PF-' . $proforma->id) }}</div>
                <div><span>تاریخ:</span> {{ $dateFa }}</div>
                <div><span>تاریخ اعتبار:</span> {{ $proforma->valid_until_shamsi ?? '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">مشخصات فروشنده</div>
            <div class="grid-2">
                <div><span>نام شخص/حقوقی:</span> {{ $seller['name'] }}</div>
                <div><span>شماره اقتصادی:</span> {{ $seller['economic_id'] ?: '—' }}</div>
                <div><span>شماره ثبت/شناسه ملی:</span> {{ $seller['national_id'] ?: '—' }}</div>
                <div><span>کد پستی:</span> {{ $seller['postal_code'] ?: '—' }}</div>
                <div class="full"><span>نشانی:</span> {{ $seller['address'] ?: '—' }}</div>
                <div><span>شماره تلفن:</span> {{ $seller['phone'] ?: '—' }}</div>
                <div><span>شماره فکس:</span> {{ $seller['fax'] ?: '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">مشخصات خریدار</div>
            <div class="grid-2">
                <div><span>نام شخص/حقوقی:</span> {{ $buyer['name'] ?: '—' }}</div>
                <div><span>شماره اقتصادی:</span> {{ $buyer['economic_id'] ?: '—' }}</div>
                <div><span>شهر:</span> {{ $buyer['city'] ?: '—' }}</div>
                <div><span>استان:</span> {{ $buyer['state'] ?: '—' }}</div>
                <div class="full"><span>نشانی:</span> {{ $buyer['address'] ?: '—' }}</div>
                <div><span>کد پستی:</span> {{ $buyer['postal_code'] ?: '—' }}</div>
                @if($buyer['contact'])
                    <div class="full"><span>شخص مخاطب:</span> {{ $buyer['contact'] }}</div>
                @endif
                <div><span>شماره تلفن:</span> {{ $buyer['phone'] ?: '—' }}</div>
                <div><span>شماره فکس:</span> {{ $buyer['fax'] ?: '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">مشخصات کالا یا خدمات مورد معامله</div>
            <table class="items-table">
                <thead>
                <tr>
                    <th style="width: 40px">ردیف</th>
                    <th>کد کالا/خدمت</th>
                    <th>شرح کالا/خدمت</th>
                    <th style="width: 70px">واحد</th>
                    <th style="width: 80px">تعداد/مقدار</th>
                    <th style="width: 120px">مبلغ واحد</th>
                    <th style="width: 120px">مبلغ کل</th>
                </tr>
                </thead>
                <tbody>
                @forelse($proforma->items as $i => $item)
                    @php
                        $code = $item->product?->sku ?? $item->product?->code ?? '';
                        $unit = match($item->unit_of_use){
                            'device' => 'دستگاه', 'piece' => 'عدد', 'meter' => 'متر', default => ($item->unit_of_use ?: '-')
                        };
                        $qty = (float) ($item->quantity ?? 0);
                        $unitPrice = (float) ($item->unit_price ?? 0);
                        $rowTotal  = (float) ($item->total_price ?? ($qty * $unitPrice));
                    @endphp
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-center">{{ $code ?: '—' }}</td>
                        <td class="text-right">{{ $item->name }}</td>
                        <td class="text-center">{{ $unit }}</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format($qty, 2), '0'), '.') }}</td>
                        <td class="text-center">{{ number_format($unitPrice, 0) }}</td>
                        <td class="text-center">{{ number_format($rowTotal, 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">ردیفی ثبت نشده است.</td>
                    </tr>
                @endforelse
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="6" class="text-left">جمع کل</td>
                    <td class="text-center">{{ number_format($subtotal, 0) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-left">تخفیف</td>
                    <td class="text-center text-red-600">{{ number_format($discount, 0) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-left">مالیات و عوارض</td>
                    <td class="text-center text-green-700">{{ number_format($tax, 0) }}</td>
                </tr>
                <tr>
                    <td colspan="6" class="text-left font-bold">مبلغ کل پس از تخفیف و اضافات</td>
                    <td class="text-center font-bold">{{ number_format($grand, 0) }}</td>
                </tr>
                </tfoot>
            </table>
        </div>

        <div class="section">
            <div class="grid-2">
                <div>
                    <span>شرایط و نحوه فروش:</span>
                    <span>{{ $proforma->terms ?? '—' }}</span>
                </div>
                <div>
                    <span>توضیحات:</span>
                    <span>{{ $proforma->notes ?? '—' }}</span>
                </div>
            </div>
        </div>

        <div class="sign-row">
            <div>مهر و امضاء فروشنده</div>
            <div>مهر و امضاء خریدار</div>
        </div>
    </div>
</div>

<style>
    .invoice-a4 { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; color: #111; padding: 12mm; border: 1px solid #111; }
    .invoice-header { display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
    .invoice-header .title { font-size: 20px; font-weight: 700; text-align: center; flex: 1; }
    .invoice-header .meta { width: 220px; border: 1px solid #111; padding: 6px 8px; line-height: 1.8; }
    .invoice-header .meta span { color: #555; margin-left: 6px; }

    .section { border: 1px solid #111; margin-bottom: 10px; }
    .section-title { background: #f8f8f8; padding: 6px 8px; border-bottom: 1px solid #111; font-weight: 600; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; padding: 8px; }
    .grid-2 .full { grid-column: 1 / span 2; }
    .grid-2 span { color: #444; margin-left: 4px; }

    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th, .items-table td { border: 1px solid #111; padding: 6px; font-size: 12px; }
    .items-table thead th { background: #f0f0f0; }
    .items-table tfoot td { background: #fafafa; font-weight: 600; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }

    .sign-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; }
    .sign-row > div { border: 1px solid #111; padding: 16px; text-align: center; }

    .btn { display: inline-block; font-weight: 500; text-align: center; padding: 0.5rem 1rem; font-size: 0.875rem; border-radius: 0.375rem; transition: background-color 0.2s ease-in-out; }
    .btn-primary { color: white; background-color: #2563eb; }
    .btn-primary:hover { background-color: #1d4ed8; }
    .btn-secondary { color: white; background-color: #6b7280; }
    .btn-secondary:hover { background-color: #4b5563; }

    @media print {
        body { background: #fff; }
        .no-print, header, aside, nav, .x-header, .x-sidebar, .btn { display: none !important; }
        .invoice-a4 { border: none; padding: 0; }
        @page { size: A4; margin: 10mm; }
    }
</style>
@endsection

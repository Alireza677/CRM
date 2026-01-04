@php
    $stageColors = [
        // Generic/legacy keys
        'created' => 'bg-blue-100 text-blue-800',
        'accepted' => 'bg-green-100 text-green-800',
        'delivered' => 'bg-purple-100 text-purple-800',
        'rejected' => 'bg-red-100 text-red-800',
        'expired' => 'bg-gray-100 text-gray-800',

        // Proforma stages
        'send_for_approval' => 'bg-amber-100 text-amber-800',
        'awaiting_second_approval' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
    ];
@endphp

@forelse($proformas as $proforma)
@php
    $stageKey   = $proforma->proforma_stage ?? 'unknown';
    $stageClass = $stageColors[$stageKey] ?? 'bg-gray-100 text-gray-800';
    $stageLabel = \App\Helpers\FormOptionsHelper::proformaStages()[$stageKey] ?? $stageKey;

    $locked = ($proforma->proforma_stage === 'send_for_approval'); // قابل حذف نیست
@endphp

    <tr>
        <td class="px-6 py-4">
            <input
                type="checkbox"
                class="row-check rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-200"
                name="ids[]"
                value="{{ $proforma->id }}"
                {{ $locked ? 'disabled' : '' }}
                title="{{ $locked ? 'در وضعیت تایید: قابل حذف نیست' : 'انتخاب' }}"
            >
        </td>

        <td class="px-6 py-4 font-mono text-sm text-gray-700">
            {{ $proforma->proforma_number ?? '-' }}
        </td>

        <td class="px-6 py-4">
            <a href="{{ route('sales.proformas.show', $proforma) }}" class="text-blue-600 hover:text-blue-900">
                {{ $proforma->subject }}
            </a>
        </td>

        <td class="px-6 py-4">
            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $stageClass }}">
                {{ $stageLabel }}
            </span>
        </td>

        <td class="px-6 py-4">{{ $proforma->organization_name ?? '-' }}</td>
        <td class="px-6 py-4">{{ $proforma->contact_name ?? '-' }}</td>
        <td class="px-6 py-4">{{ number_format($proforma->total_amount) }} ریال</td>
        <td class="px-6 py-4">
            @php
                $dateOut = '-';
                if ($proforma->proforma_date) {
                    try {
                        $c = \Carbon\Carbon::parse($proforma->proforma_date);
                        if ($c->year >= 1700 && $c->year <= 2500) {
                            $dateOut = \Morilog\Jalali\Jalalian::fromCarbon($c)->format('Y/m/d');
                        }
                    } catch (\Throwable $e) {
                        $dateOut = '-';
                    }
                }
            @endphp
            {{ $dateOut }}
        </td>
        <td class="px-6 py-4">
            @if($proforma->opportunity)
                <a href="{{ route('sales.opportunities.show', $proforma->opportunity) }}" class="text-blue-600 hover:underline">
                    {{ $proforma->opportunity->name ?? ('فرصت #'.$proforma->opportunity->id) }}
                </a>
            @else
                -
            @endif
        </td>
        <td class="px-6 py-4">{{ $proforma->assignedTo->name ?? '-' }}</td>
        @php
            $canEdit = method_exists($proforma, 'canEdit')
                ? $proforma->canEdit()
                : (strtolower((string)($proforma->approval_stage ?? $proforma->proforma_stage ?? '')) === 'draft');
        @endphp

        <td class="px-6 py-4">
            <div class="flex items-center space-x-reverse space-x-3">
                <a href="{{ route('sales.proformas.show', $proforma->id) }}"
                class="text-blue-600 hover:text-blue-900">
                    مشاهده
                </a>

                @if($canEdit)
                    <a href="{{ route('sales.proformas.edit', $proforma->id) }}"
                    class="text-indigo-600 hover:text-indigo-900">
                        ویرایش
                    </a>
                @else
                    <button type="button"
                            onclick="showEditDeleteAlert('ویرایش فقط در وضعیت «پیش‌نویس» مجاز است.')"
                            class="text-gray-500 cursor-not-allowed opacity-60">
                        ویرایش
                    </button>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="11" class="px-6 py-4 text-center text-gray-400">
            هیچ پیش‌فاکتوری یافت نشد.
        </td>
    </tr>
@endforelse

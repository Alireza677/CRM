@forelse($leads as $lead)
    <tr class="hover:bg-gray-50 transition">
        <td class="px-4 py-2 text-gray-500 text-right">
            {{ $lead->lead_number ?? '—' }}
        </td>
        <td class="px-4 py-2">
            @php
                $showReengagedBadge = (bool) $lead->is_reengaged;
                $isWebsiteSource = $lead->lead_source === 'website';
            @endphp
            <a href="{{ route('marketing.leads.show', $lead) }}" class="text-blue-700 hover:underline">
                {{ $lead->full_name ?? '---' }}
            </a>
            @if($showReengagedBadge)
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $isWebsiteSource ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
                    بازگشتی از وب‌سایت
                </span>
            @endif
            <span class="ml-2 px-2 py-0.5 text-[10px] rounded-full bg-green-100 text-green-800 align-middle">تبدیل شده</span>
        </td>
        <td class="px-4 py-2 text-gray-500">
            {{ $lead->converted_at ? \Morilog\Jalali\Jalalian::forge($lead->converted_at)->format('Y/m/d') : '---' }}
        </td>
        <td class="px-4 py-2 text-gray-500">{{ $lead->mobile ?? $lead->phone ?? '---' }}</td>
        <td class="px-4 py-2 text-gray-500">
            {{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) }}
        </td>
        <td class="px-4 py-2">
            @php
                $leadStatusColors = [
                    'new' => 'bg-blue-100 text-blue-800',
                    'contacted' => 'bg-yellow-100 text-yellow-800',
                    'converted' => 'bg-emerald-100 text-emerald-800',
                    'discarded' => 'bg-red-100 text-red-800',
                ];
                $rawStatus = $lead->status ?? $lead->lead_status;
                $statusKey = \App\Models\SalesLead::normalizeStatus($rawStatus) ?? $rawStatus;
                $badgeClass = $leadStatusColors[$statusKey] ?? 'bg-gray-200 text-gray-800';
            @endphp
            <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $badgeClass }}">
                {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($statusKey) }}
            </span>
        </td>
        <td class="px-4 py-2 text-gray-500">
            @if($lead->assignedUser)
                {{ $lead->assignedUser->name }}
            @elseif($lead->assigned_to)
                (کاربر حذف شده) [ID: {{ $lead->assigned_to }}]
            @else
                بدون مسئول
            @endif
        </td>
        <td class="px-4 py-2 text-gray-500">
            @if($lead->convertedOpportunity)
                <a href="{{ route('sales.opportunities.show', $lead->convertedOpportunity) }}"
                   class="text-indigo-600 hover:underline text-xs">
                    {{ $lead->convertedOpportunity->name }}
                </a>
            @else
                ---
            @endif
        </td>
        <td class="px-4 py-2 text-center">
            @php
                $isFavorite = in_array($lead->id, $favoriteLeadIds);
            @endphp
            <div class="flex items-center gap-3 justify-center">
                <button
                    type="submit"
                    formmethod="POST"
                    formaction="{{ $isFavorite ? route('marketing.leads.favorites.destroy', $lead) : route('marketing.leads.favorites.store', $lead) }}"
                    @if($isFavorite) data-method="DELETE" @endif
                    class="inline-flex items-center text-xs px-2 py-1 rounded {{ $isFavorite ? 'bg-amber-100 text-amber-700 hover:bg-amber-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    aria-label="{{ $isFavorite ? 'حذف از علاقه‌مندی' : 'افزودن به علاقه‌مندی' }}">
                    <i class="{{ $isFavorite ? 'fas' : 'far' }} fa-star ml-1"></i>
                </button>
                <a href="{{ route('marketing.leads.show', $lead) }}" class="text-blue-500 hover:underline text-xs">مشاهده سرنخ</a>
                @if($lead->convertedOpportunity)
                    <a href="{{ route('sales.opportunities.show', $lead->convertedOpportunity) }}"
                       class="text-indigo-600 hover:underline text-xs">
                        مشاهده فرصت
                    </a>
                @endif
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">
            هیچ سرنخ تبدیل‌شده‌ای برای نمایش وجود ندارد.
        </td>
    </tr>
@endforelse


@php
    $contact = $contact ?? $model ?? null;
    $leads = $leads ?? ($contact?->leads ?? collect());
@endphp

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">سرنخ‌های مرتبط</h3>
        <a href="{{ route('marketing.leads.create', ['contact_id' => $contact->id]) }}"
           class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">
            ایجاد سرنخ
        </a>
    </div>

    @if($leads->count())
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-right">عنوان</th>
                        <th class="px-3 py-2 text-right">وضعیت</th>
                        <th class="px-3 py-2 text-right">منبع</th>
                        <th class="px-3 py-2 text-right">مسئول</th>
                        <th class="px-3 py-2 text-right">تاریخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($leads as $lead)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('marketing.leads.show', $lead) }}" class="text-blue-600 hover:underline">
                                    {{ $lead->full_name ?: ($lead->company ?: ('سرنخ #' . $lead->id)) }}
                                </a>
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $rawStatus = !empty($lead->lead_status) ? $lead->lead_status : $lead->status;
                                    $statusKey = \App\Models\SalesLead::normalizeStatus($rawStatus) ?? $rawStatus;
                                    $leadStatusColors = [
                                        'new' => 'bg-blue-100 text-blue-800',
                                        'contacted' => 'bg-yellow-100 text-yellow-800',
                                        'converted' => 'bg-emerald-100 text-emerald-800',
                                        'discarded' => 'bg-red-100 text-red-800',
                                    ];
                                    $badgeClass = $leadStatusColors[$statusKey] ?? 'bg-gray-200 text-gray-800';
                                @endphp
                                <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $badgeClass }}">
                                    {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($statusKey) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-gray-500">
                                {{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) }}
                            </td>
                            <td class="px-3 py-2 text-gray-500">
                                {{ $lead->assignedUser?->name ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-gray-500">
                                {{ jdate($lead->created_at)->format('Y/m/d') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-gray-500">سرنخی برای این مخاطب ثبت نشده است.</p>
    @endif
</div>

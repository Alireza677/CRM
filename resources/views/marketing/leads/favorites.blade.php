@extends('layouts.app')

@section('content')
@if(session('success'))
    <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
        {{ session('error') }}
    </div>
@endif

<div >
    <div class="px-4">
        <div class="flex flex-col gap-3 mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800">???????? ???? ?????</h2>
                <p class="text-sm text-gray-500 mt-1">???? ???? ?? ????????? ?? ???? ?????? ??????? ??????????? ????????.</p>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                @include('marketing.leads.partials.listing-tabs')
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('marketing.leads.create') }}"
                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md shadow hover:bg-blue-700">
                    <i class="fas fa-plus ml-1 text-sm"></i>
                    ????? ????
                </a>
                @role('admin')
                @include('marketing.leads.partials.export-dropdown')
                @endrole
            </div>
        </div>
    </div>

        @if($leads->count() === 0)
            <div class="bg-white border rounded-lg p-6 text-center text-sm text-gray-500">
                <p>?? ???? ??? ????? ?? ?? ????????????? ????? ?????????.</p>
                <p class="mt-2">?? ???? ??????? ??? ???? «?????? ?? ??????????» ????? ?? ????? ????? ???? ????.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-2 text-right">?????</th>
                            <th class="px-2 py-2 text-right">??? ????</th>
                            <th class="px-2 py-2 text-right">????? ?????</th>
                            <th class="px-2 py-2 text-right">??????</th>
                            <th class="px-2 py-2 text-right">???? ????</th>
                            <th class="px-2 py-2 text-right">?????</th>
                            <th class="px-2 py-2 text-right">????? ??</th>
                            <th class="px-2 py-2 text-center">??????</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
@foreach($leads as $lead)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-2 text-gray-500 text-right">
                                    {{ $lead->lead_number ?? '—' }}
                                </td>
                                <td class="px-4 py-2">
                                    @php
                                        $showReengagedBadge = (bool) $lead->is_reengaged;
                                        $isWebsiteSource = $lead->lead_source === 'website';
                                    @endphp
                                    <a href="{{ route('marketing.leads.show', $lead) }}" class="text-blue-700 hover:underline font-medium">
                                        {{ $lead->full_name ?? '---' }}
                                    </a>
                                    @if($showReengagedBadge)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium {{ $isWebsiteSource ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-700' }}">
                                            ??????? ?? ???????
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-500">
                                    {{ \Morilog\Jalali\Jalalian::forge($lead->created_at)->format('Y/m/d') }}
                                </td>
                                <td class="px-4 py-2 text-gray-500">{{ $lead->mobile ?? $lead->phone ?? '---' }}</td>
                                <td class="px-4 py-2 text-gray-500">
                                    {{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) }}
                                </td>
                                <td class="px-4 py-2">
                                    @php
                                        $leadStatusColors = [
                                            'new'       => 'bg-blue-100 text-blue-800',
                                            'contacted' => 'bg-amber-100 text-amber-800',
                                            'converted' => 'bg-green-100 text-green-800',
                                            'discarded' => 'bg-red-100 text-red-800',
                                        ];
                                        $rawStatus = $lead->status ?? $lead->lead_status;
                                        $statusKey = \App\Models\SalesLead::normalizeStatus($rawStatus) ?? $rawStatus;
                                        $badgeClass = $leadStatusColors[$statusKey] ?? 'bg-gray-100 text-gray-600';
                                    @endphp
                                    <span class="px-2 inline-flex text-xs font-semibold rounded-full {{ $badgeClass }}">
                                        {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($statusKey) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-500">
                                    @if($lead->assignedUser)
                                        {{ $lead->assignedUser->name }}
                                    @elseif($lead->assigned_to)
                                        (????? ??? ???) [ID: {{ $lead->assigned_to }}]
                                    @else
                                        ???? ?????
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <form method="POST" action="{{ route('marketing.leads.favorites.destroy', $lead) }}" class="inline-flex" onsubmit="return confirm('?? ???? ?????????? ??? ????');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="redirect_to" value="favorites">
                                        <button type="submit" class="text-sm px-3 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200">??? ?? ????</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection





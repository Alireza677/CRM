@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'یافتن موارد تکراری', 'url' => route('sales.organizations.duplicates.index')],
        ['title' => 'بررسی و ادغام']
    ];
    $winnerId = old('winner_id', $defaultWinnerId);
    $oldLosers = collect(old('loser_ids', collect($organizations)->pluck('id')->diff([$winnerId])->values()->all()));
@endphp

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">بررسی گروه تکراری</h2>
            <p class="text-sm text-gray-500 mt-1">سازمان اصلی را انتخاب کنید و قبل از ادغام، تعارض‌های فیلدها را مشخص کنید.</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('sales.organizations.merge') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="group_id" value="{{ $group->id }}">

            <div class="bg-white shadow-sm rounded p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">انتخاب سازمان اصلی</h3>

                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($organizations as $organization)
                        @php
                            $summary = $relationsSummary[$organization->id] ?? [
                                'contacts' => 0,
                                'opportunities' => 0,
                                'proformas' => 0,
                                'quotations' => 0,
                                'notes' => 0
                            ];
                        @endphp

                        <label class="border rounded p-3 flex gap-3 items-start cursor-pointer {{ (int) $winnerId === (int) $organization->id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}">
                            <input type="radio" name="winner_id" value="{{ $organization->id }}" class="mt-1" {{ (int) $winnerId === (int) $organization->id ? 'checked' : '' }}>

                            <div>
                                <div class="font-semibold text-gray-800">
                                    {{ $organization->name ?: ('سازمان #' . $organization->id) }}
                                </div>

                                <div class="text-sm text-gray-600">
                                    {{ $organization->phone ?? '-' }} | {{ $organization->website ?? '-' }}
                                </div>

                                <div class="mt-2 text-xs text-gray-500 flex flex-wrap gap-2">
                                    <span>مخاطبین: {{ $summary['contacts'] }}</span>
                                    <span>فرصت‌ها: {{ $summary['opportunities'] }}</span>
                                    <span>پیش‌فاکتورها: {{ $summary['proformas'] }}</span>
                                    <span>پیشنهادها: {{ $summary['quotations'] }}</span>
                                    <span>یادداشت‌ها: {{ $summary['notes'] }}</span>
                                </div>

                                <div class="mt-2">
                                    <label class="text-xs text-gray-600">
                                        <input type="checkbox" name="loser_ids[]" value="{{ $organization->id }}" class="mr-1 loser-checkbox" {{ $oldLosers->contains($organization->id) ? 'checked' : '' }}>
                                        ادغام این رکورد در سازمان اصلی
                                    </label>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="bg-white shadow-sm rounded p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">مقایسه فیلدها</h3>
                    @if(!empty($conflicts))
                        <span class="text-xs text-red-600 bg-red-50 border border-red-200 rounded px-2 py-1">تعارض شناسایی شد</span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">فیلد</th>
                                @foreach($organizations as $organization)
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                        {{ $organization->name ?: ('#' . $organization->id) }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($fields as $field)
                                @php
                                    $conflict = array_key_exists($field, $conflicts);
                                    $selectedId = old('field_resolution.' . $field);

                                    if (!$selectedId) {
                                        $winnerValue = optional($organizations->firstWhere('id', $winnerId))->{$field} ?? null;

                                        if ($winnerValue !== null && trim((string) $winnerValue) !== '') {
                                            $selectedId = $winnerId;
                                        } else {
                                            $selectedOrg = $organizations->first(function ($organization) use ($field) {
                                                $value = $organization->{$field} ?? null;
                                                return $value !== null && trim((string) $value) !== '';
                                            });
                                            $selectedId = $selectedOrg?->id ?? $winnerId;
                                        }
                                    }
                                @endphp

                                <tr class="{{ $conflict ? 'bg-red-50' : '' }}">
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium">{{ $field }}</span>
                                            @if($conflict)
                                                <span class="text-xs text-red-600">تعارض</span>
                                            @endif
                                        </div>
                                    </td>

                                    @foreach($organizations as $organization)
                                        @php
                                            $value = $organization->{$field} ?? '';
                                        @endphp
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <label class="flex items-start gap-2">
                                                <input type="radio" name="field_resolution[{{ $field }}]" value="{{ $organization->id }}" {{ (int) $selectedId === (int) $organization->id ? 'checked' : '' }}>
                                                <span class="break-all">{{ $value !== '' ? $value : '-' }}</span>
                                            </label>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <a href="{{ route('sales.organizations.duplicates.index') }}" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    بازگشت
                </a>
                <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">
                    ادغام سازمان‌ها
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const winnerRadios = document.querySelectorAll('input[name="winner_id"]');
    const loserCheckboxes = document.querySelectorAll('.loser-checkbox');

    function syncLosers() {
        const winner = document.querySelector('input[name="winner_id"]:checked');
        if (!winner) return;
        loserCheckboxes.forEach(cb => {
            if (cb.value === winner.value) {
                cb.checked = false;
                cb.disabled = true;
            } else {
                cb.disabled = false;
            }
        });
    }

    winnerRadios.forEach(radio => {
        radio.addEventListener('change', syncLosers);
    });

    syncLosers();
})();
</script>
@endsection

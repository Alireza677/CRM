@php
    $filters = $schema['filters'] ?? [];
    $filtersByColumn = [];
    foreach ($filters as $filter) {
        $columnKey = $filter['column'] ?? $filter['key'] ?? null;
        if ($columnKey) {
            $filtersByColumn[$columnKey] = $filter;
        }
    }
@endphp

<tr class="bg-white text-xs text-gray-500">
    <th class="px-3 py-2 sticky top-8 z-10 bg-white"></th>
    @foreach($schema['columns'] as $column)
        @php $filter = $filtersByColumn[$column['key']] ?? null; @endphp
        <th class="px-3 py-2 sticky top-8 z-10 bg-white">
            @if($filter)
                @php
                    $type = $filter['type'] ?? 'text';
                    $value = request($filter['key']);
                    $placeholder = $filter['placeholder'] ?? '';
                @endphp
                @if($type === 'select')
                    @php
                        $options = $filter['options'] ?? [];
                        if (is_callable($options)) {
                            $options = $options();
                        }
                    @endphp
                    <select name="{{ $filter['key'] }}" form="filters-form" class="w-full rounded-md border border-gray-200 bg-white px-2 py-1 text-xs">
                        <option value="">همه</option>
                        @foreach($options as $optValue => $optLabel)
                            <option value="{{ $optValue }}" @selected((string)$value === (string)$optValue)>{{ $optLabel }}</option>
                        @endforeach
                    </select>
                @elseif($type === 'multi')
                    @php
                        $options = $filter['options'] ?? [];
                        if (is_callable($options)) {
                            $options = $options();
                        }
                        $selectedValues = request($filter['key']);
                        if (!is_array($selectedValues)) {
                            $selectedValues = $selectedValues !== null && $selectedValues !== '' ? [$selectedValues] : [];
                        }
                        $selectedCount = count(array_filter($selectedValues, fn ($v) => $v !== null && $v !== ''));
                    @endphp
                    <details class="relative rounded-md border border-gray-200 bg-white px-2 py-1 text-xs">
                        <summary class="cursor-pointer select-none list-none flex items-center justify-between">
                            <span>{{ $placeholder ?: 'انتخاب' }}</span>
                            <span class="text-gray-500 text-[10px]">
                                {{ $selectedCount ? ($selectedCount . ' انتخاب') : 'باز کردن' }}
                            </span>
                        </summary>
                        <div class="absolute right-0 mt-2 w-48 z-30 flex flex-col gap-1 max-h-40 overflow-y-auto rounded-md border border-gray-200 bg-white p-2 shadow">
                            @foreach($options as $optValue => $optLabel)
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="{{ $filter['key'] }}[]" value="{{ $optValue }}" form="filters-form"
                                           @checked(in_array((string)$optValue, array_map('strval', $selectedValues), true))>
                                    <span>{{ $optLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </details>
                @elseif($type === 'date')
                    @php
                        $fromKey = $filter['key'] . '_from';
                        $toKey = $filter['key'] . '_to';
                        $fromValue = request($fromKey);
                        $toValue = request($toKey);
                    @endphp
                    <div class="flex items-center gap-1">
                        <input type="text" name="{{ $fromKey }}" value="{{ $fromValue }}" form="filters-form"
                               placeholder="{{ $filter['placeholder_from'] ?? 'از تاریخ' }}"
                               class="w-1/2 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs persian-datepicker"
                               data-skip-autofill="1" autocomplete="off" />
                        <input type="text" name="{{ $toKey }}" value="{{ $toValue }}" form="filters-form"
                               placeholder="{{ $filter['placeholder_to'] ?? 'تا تاریخ' }}"
                               class="w-1/2 rounded-md border border-gray-200 bg-white px-2 py-1 text-xs persian-datepicker"
                               data-skip-autofill="1" autocomplete="off" />
                    </div>
                @else
                    <input type="text" name="{{ $filter['key'] }}" value="{{ $value }}" form="filters-form" placeholder="{{ $placeholder }}" class="w-full rounded-md border border-gray-200 bg-white px-2 py-1 text-xs" />
                @endif
            @elseif(($column['type'] ?? '') === 'actions')
                <div class="flex items-center gap-2">
                    <button type="submit" form="filters-form" class="rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-600 hover:bg-gray-100">اعمال</button>
                    <button type="button" class="rounded-md border border-gray-200 bg-white px-2 py-1 text-xs text-gray-600 hover:bg-gray-100"
                            onclick="const f=document.getElementById('filters-form'); if(f){ window.location = f.action; }">
                        ریست
                    </button>
                </div>
            @endif
        </th>
    @endforeach
</tr>

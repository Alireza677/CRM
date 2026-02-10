@php
    use App\Helpers\DateHelper;
    $bulkRoute = $schema['routes']['bulkDestroy'] ?? null;
@endphp

@if($bulkRoute)
    <form id="bulk-form" method="POST" action="{{ route($bulkRoute) }}">
        @csrf
        @method('DELETE')
@endif

<div class="rounded-lg border border-gray-200 bg-white shadow-sm">

    <table class="min-w-full text-right text-sm">
        <thead class="bg-gray-50 text-xs text-gray-500">
            <tr>
                <th class="px-3 py-2 sticky top-0 z-20 bg-gray-50">
                    <input type="checkbox" id="select-all" class="rounded border-gray-300" />
                </th>
                @foreach($schema['columns'] as $column)
                    @php
                        $width = $column['width'] ?? '';
                        $sortable = !empty($column['sortable']);
                        $label = $column['label'] ?? '';
                        $sortKey = $column['key'] ?? '';
                    @endphp
                    <th class="px-3 py-2 font-medium {{ $width }} sticky top-0 z-20 bg-gray-50">
                        @if($sortable && is_string($sortKey) && !str_contains($sortKey, '.'))
                            @php
                                $nextDir = ($sort === $sortKey && $dir === 'asc') ? 'desc' : 'asc';
                                $url = route($schema['routes']['index'], array_merge(request()->query(), ['sort' => $sortKey, 'dir' => $nextDir]));
                            @endphp
                            <a href="{{ $url }}" class="inline-flex items-center gap-1 text-gray-600 hover:text-gray-900">
                                <span>{{ $label }}</span>
                                <span class="text-[10px]">{{ $sort === $sortKey ? ($dir === 'asc' ? '▲' : '▼') : '' }}</span>
                            </a>
                        @else
                            {{ $label }}
                        @endif
                    </th>
                @endforeach
            </tr>
            @include('crud.partials.filters-row')
        </thead>
        <tbody class="divide-y divide-gray-100 px-2">
            @forelse($rows as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2">
                        @if(($schema['key'] ?? null) === 'leads')
                            <input type="checkbox" name="selected_leads[]" value="{{ $row->getKey() }}" class="rounded border-gray-300 row-checkbox" />
                        @else
                            <input type="checkbox" name="ids[]" value="{{ $row->getKey() }}" class="rounded border-gray-300" />
                        @endif
                    </td>
                    @foreach($schema['columns'] as $column)
                        @php
                            $type = $column['type'] ?? 'text';
                            $raw = data_get($row, $column['key'] ?? '');
                            $value = $raw;
                            if (!empty($column['format'])) {
                                $formatter = $column['format'];
                                $value = is_callable($formatter) ? $formatter($row, $raw) : $raw;
                            }
                        @endphp
                        <td class="px-3 py-2 text-gray-700">
                            @switch($type)
                                @case('link')
                                    <a href="{{ route($schema['routes']['show'], $row) }}" class="text-indigo-600 hover:text-indigo-800">
                                        {{ $value ?: '—' }}
                                    </a>
                                    @break
                                @case('badge')
                                    @php
                                        $badgeClasses = $column['badges'][$raw] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ $badgeClasses }}">
                                        {{ $value ?: '—' }}
                                    </span>
                                    @break
                                @case('date')
                                    <span>{{ DateHelper::toJalali($raw) ?? '—' }}</span>
                                    @break
                                @case('datetime')
                                    <span>{{ DateHelper::toJalali($raw, 'Y/m/d H:i', true) ?? '—' }}</span>
                                    @break
                                @case('relation')
                                    <span>{{ $value ?: '—' }}</span>
                                    @break
                                @case('actions')
                                    @include('crud.partials.actions', ['context' => 'row', 'row' => $row])
                                    @break
                                @case('html')
                                    {!! $value ?: '—' !!}
                                    @break
                                @default
                                    <span>{{ $value ?: '—' }}</span>
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($schema['columns']) + 1 }}" class="px-3 py-6 text-center text-sm text-gray-500">
                        رکوردی یافت نشد.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($bulkRoute)
    </form>
@endif

@php
    $showPagination = $showPagination ?? true;
@endphp
@if($showPagination)
    <div class="mt-3">
        {{ $rows->links() }}
    </div>
@endif

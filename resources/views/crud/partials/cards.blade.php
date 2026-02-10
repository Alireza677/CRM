@php
    $blocks = $blocks ?? [];
@endphp

<div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2 lg:grid-cols-3">
    @forelse($blocks as $block)
        @php
            $type = $block['type'] ?? 'card';
            $title = $block['title'] ?? null;
            $tabKey = $block['tab'] ?? null;
            $linkUrl = $block['url'] ?? null;
            $isClickable = !empty($tabKey) || !empty($linkUrl);
            $clickAttrs = $tabKey ? 'data-card-tab=' . e($tabKey) . ' role=button tabindex=0' : '';
        @endphp

        @if($type === 'stat')
            @php
                $value = $block['value'] ?? '—';
                if (is_callable($value)) {
                    $value = $value($model);
                }
                $color = $block['color'] ?? 'bg-blue-50 text-blue-800';
                $icon = $block['icon'] ?? 'fas fa-chart-bar';
                $iconColor = $block['icon_color'] ?? 'text-blue-400';
                $wrapperClass = $block['class'] ?? $color;
            @endphp
            <div class="rounded-lg p-4 shadow flex items-center justify-between transition {{ $isClickable ? 'cursor-pointer hover:shadow-md' : '' }} {{ $wrapperClass }}" {!! $clickAttrs !!}>
                <div>
                    <h3 class="text-lg font-semibold">{{ $block['label'] ?? '' }}</h3>
                    @if(!empty($block['subtitle']))
                        <p class="text-sm mt-1 opacity-80">{{ $block['subtitle'] }}</p>
                    @endif
                    <div class="mt-1 text-sm opacity-80">{{ $value }}</div>
                </div>
                <i class="{{ $icon }} text-3xl {{ $iconColor }}"></i>
            </div>
        @elseif($type === 'table')
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                @if($title)
                    <div class="mb-3 text-sm font-semibold text-gray-700">{{ $title }}</div>
                @endif
                <div class="overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500">
                            <tr>
                                @foreach(($block['headers'] ?? []) as $header)
                                    <th class="px-3 py-2 font-medium">{{ $header }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse(($block['rows'] ?? []) as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td class="px-3 py-2 text-gray-700">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($block['headers'] ?? []) ?: 1 }}" class="px-3 py-4 text-center text-xs text-gray-500">
                                        داده‌ای برای نمایش وجود ندارد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($type === 'html')
            @php
                $wrapperClass = $block['class'] ?? '';
            @endphp
            <div class="rounded-lg border border-gray-100 bg-slate-50 p-4 shadow-sm {{ $wrapperClass }}">
                @if($title)
                    <div class="mb-2 text-sm font-semibold text-gray-700">{{ $title }}</div>
                @endif
                {!! $block['html'] ?? '' !!}
            </div>
        @else
            @php
                $lines = $block['lines'] ?? [];
                $rows = $block['rows'] ?? [];
            @endphp
            <div class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm {{ $isClickable ? 'cursor-pointer hover:shadow-md transition' : '' }}" {!! $clickAttrs !!}>
                @if($title)
                    <div class="mb-2 text-sm font-semibold text-gray-700">{{ $title }}</div>
                @endif
                @if(!empty($rows))
                    <div class="space-y-3">
                        @foreach($rows as $row)
                            @php
                                $label = $row['label'] ?? '';
                                $value = $row['value'] ?? '—';
                                if (is_callable($value)) {
                                    $value = $value($model);
                                }
                            @endphp
                            <div class="flex justify-between items-center bg-gray-50 rounded px-3 py-2 text-sm">
                                <span class="text-gray-600">{{ $label }}</span>
                                <span class="text-gray-900">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="space-y-1 text-sm text-gray-600">
                        @foreach($lines as $line)
                            @php
                                $text = $line;
                                if (is_callable($line)) {
                                    $text = $line($model);
                                }
                            @endphp
                            <div>{{ $text }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    @empty
        <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-500">موردی برای نمایش وجود ندارد.</div>
    @endforelse
</div>

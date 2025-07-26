@props(['items' => []])

@php
    // Default home item
    $defaultItems = [
        [
            'title' => 'خانه',
            'url' => '/dashboard',
            'icon' => true
        ]
    ];

    // Merge default items with provided items
    $items = array_merge($defaultItems, $items);
@endphp

<nav class="bg-white border-b border-gray-200 px-5 py-2" dir="rtl">
    <ol class="flex items-center space-x-2 space-x-reverse">
        @foreach($items as $index => $item)
            <li class="flex items-center">
                @if($index > 0)
                    <svg class="w-4 h-4 text-gray-400 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                @endif

                @if(isset($item['icon']) && $item['icon'])
                    <a href="{{ $item['url'] ?? '#' }}" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                    </a>
                @else
                    @if($index === count($items) - 1)
                        <span class="text-gray-700 font-medium">{{ $item['title'] }}</span>
                    @else
                        <a href="{{ $item['url'] ?? '#' }}" class="text-gray-500 hover:text-gray-700">{{ $item['title'] }}</a>
                    @endif
                @endif
            </li>
        @endforeach
    </ol>
</nav>

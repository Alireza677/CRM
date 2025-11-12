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
                        <img src="{{ asset('images/home.svg') }}" alt="Home" class="w-6 h-6">
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

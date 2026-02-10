@php
    $tabs = $schema['show']['tabs'] ?? [];
@endphp

<nav class="space-y-1">
    @php
        $defaultIcons = [
            'overview' => 'fas fa-th-large',
            'summary' => 'fas fa-th-large',
            'info' => 'fas fa-info-circle',
            'details' => 'fas fa-info-circle',
            'notes' => 'fas fa-sticky-note',
            'updates' => 'fas fa-sync-alt',
            'contact' => 'fas fa-user-friends',
            'contacts' => 'fas fa-user-friends',
            'activities' => 'fas fa-tasks',
            'files' => 'fas fa-paperclip',
            'documents' => 'fas fa-paperclip',
            'opportunities' => 'fas fa-bullseye',
            'proformas' => 'fas fa-file-invoice',
            'products' => 'fas fa-box',
            'orders' => 'fas fa-shopping-cart',
            'timeline' => 'fas fa-stream',
            'history' => 'fas fa-stream',
            'settings' => 'fas fa-cog',
        ];
    @endphp

    @foreach($tabs as $key => $tab)
        @php
            $active = $selectedTab === $key;
            $label = $tab['label'] ?? $key;
            $lookup = strtolower((string) $key);
            $icon = $tab['icon'] ?? ($defaultIcons[$lookup] ?? null);
        @endphp
        <a href="{{ route($schema['routes']['show'], $model) }}?tab={{ $key }}"
           class="flex items-center justify-between px-3 py-2 rounded {{ $active ? 'bg-blue-100 text-blue-800 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
            <span class="flex items-center space-x-2 rtl:space-x-reverse">
                @if($icon)
                    <i class="{{ $icon }}"></i>
                @else
                    <i class="fas fa-circle text-gray-400 text-[8px]"></i>
                @endif
                <span>{{ $label }}</span>
            </span>
        </a>
    @endforeach
</nav>

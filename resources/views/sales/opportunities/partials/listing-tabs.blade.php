@php
    $opportunityTabCounts = $opportunityTabCounts ?? [];
    $opportunityListingTabs = [
        ['key' => 'all', 'label' => 'همه فرصت‌ها', 'route' => 'sales.opportunities.index'],
        ['key' => 'favorites', 'label' => 'علاقه‌مندی‌ها', 'route' => 'sales.opportunities.favorites.index'],
    ];
@endphp

<nav class="flex flex-wrap items-center gap-2">
    @foreach($opportunityListingTabs as $tab)
        @php $isActive = request()->routeIs($tab['route']); @endphp
        <a href="{{ route($tab['route']) }}"
           class="inline-flex items-center px-4 py-2 rounded-full border text-sm transition {{ $isActive ? 'bg-blue-600 text-white border-blue-600 shadow' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
            {{ $tab['label'] }}
            <span class="ml-2 inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 text-[11px] font-semibold rounded-full {{ $isActive ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-700' }}">
                {{ $opportunityTabCounts[$tab['key']] ?? 0 }}
            </span>
        </a>
    @endforeach
</nav>

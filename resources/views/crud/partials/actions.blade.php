@php
    $context = $context ?? 'row';
@endphp

@if($context === 'header')
    @if(!empty($schema['routes']['create']))
        <a href="{{ route($schema['routes']['create']) }}" class="inline-flex items-center h-9 rounded-md border border-emerald-200 bg-emerald-50 px-3 text-sm text-emerald-700 hover:bg-emerald-100">ایجاد</a>
    @endif
    @if(!empty($schema['routes']['import']))
        <a href="{{ route($schema['routes']['import']) }}" class="inline-flex items-center h-9 rounded-md border border-indigo-200 bg-indigo-50 px-3 text-sm text-indigo-700 hover:bg-indigo-100">ایمپورت</a>
    @endif
    @if(!empty($schema['routes']['bulkDestroy']))
        <button type="submit" form="bulk-form" class="inline-flex items-center h-9 rounded-md border border-rose-200 bg-rose-50 px-3 text-sm text-rose-700 hover:bg-rose-100">حذف گروهی</button>
    @endif
@else
    <div class="flex items-center justify-end gap-2">
        @if(($schema['key'] ?? null) === 'activities')
            @if(!empty($schema['routes']['show']))
                <a href="{{ route($schema['routes']['show'], $row) }}" class="text-xs text-blue-600 hover:text-blue-800">نمایش</a>
            @endif
            @if(($row->status ?? null) !== 'completed')
                <form action="{{ route('activities.complete', $row->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="text-xs text-emerald-600 hover:text-emerald-800"
                            onclick="return confirm('وضعیت این وظیفه به تکمیل شده تغییر کند؟')">
                        تکمیل
                    </button>
                </form>
            @endif
        @endif
        @if(($schema['key'] ?? null) === 'suppliers')
            @if(!empty($schema['routes']['show']))
                <a href="{{ route($schema['routes']['show'], $row) }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده</a>
            @endif
        @endif
        @if(($schema['key'] ?? null) === 'after_sales_services')
            @if(!empty($schema['routes']['show']))
                <a href="{{ route($schema['routes']['show'], $row) }}" class="text-xs text-blue-600 hover:text-blue-800">مشاهده</a>
            @endif
        @endif
        @if(($schema['key'] ?? null) === 'leads')
            @php
                $favoriteIds = $favoriteLeadIds ?? [];
                $isFavorite = in_array($row->id, $favoriteIds);
                $assignedName = $row->assignedUser?->name ?? $row->assignedTo?->name ?? '';
            @endphp
            <form method="POST" action="{{ $isFavorite ? route('marketing.leads.favorites.destroy', $row) : route('marketing.leads.favorites.store', $row) }}">
                @csrf
                @if($isFavorite)
                    @method('DELETE')
                @endif
                <div class="relative group">
                    <button
                        type="submit"
                        class="inline-flex items-center text-xs px-2 py-1"
                        aria-label="{{ $isFavorite ? 'حذف از علاقه مندی' : 'افزودن به علاقه مندی' }}">
                    <img
                        src="{{ asset('images/star.png') }}"
                        alt=""
                        class="w-4 h-4 ml-1 {{ $isFavorite ? '' : 'opacity-60' }}"
                        style="{{ $isFavorite ? 'filter: invert(74%) sepia(83%) saturate(512%) hue-rotate(2deg) brightness(103%) contrast(101%);' : '' }}"
                    >
                    </button>
                    <div class="pointer-events-none absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-[11px] text-white opacity-0 transition-opacity duration-75 group-hover:opacity-100">
                        {{ $isFavorite ? 'حذف از علاقه مندی' : 'افزودن به علاقه مندی' }}
                    </div>
                </div>
            </form>
        @endif
        @if(($schema['key'] ?? null) === 'opportunities')
            @php
                $favoriteIds = $favoriteOpportunityIds ?? [];
                $isFavorite = in_array($row->id, $favoriteIds);
            @endphp
            <form method="POST" action="{{ $isFavorite ? route('sales.opportunities.favorites.destroy', $row) : route('sales.opportunities.favorites.store', $row) }}">
                @csrf
                @if($isFavorite)
                    @method('DELETE')
                @endif
                <div class="relative group">
                    <button
                        type="submit"
                        class="inline-flex items-center text-xs px-2 py-1"
                        aria-label="{{ $isFavorite ? 'حذف از علاقه مندی' : 'افزودن به علاقه مندی' }}">
                    <img
                        src="{{ asset('images/star.png') }}"
                        alt=""
                        class="w-4 h-4 ml-1 {{ $isFavorite ? '' : 'opacity-60' }}"
                        style="{{ $isFavorite ? 'filter: invert(74%) sepia(83%) saturate(512%) hue-rotate(2deg) brightness(103%) contrast(101%);' : '' }}"
                    >
                    </button>
                    <div class="pointer-events-none absolute -top-7 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-800 px-2 py-1 text-[11px] text-white opacity-0 transition-opacity duration-75 group-hover:opacity-100">
                        {{ $isFavorite ? 'حذف از علاقه مندی' : 'افزودن به علاقه مندی' }}
                    </div>
                </div>
            </form>
        @endif
        @if(!empty($schema['routes']['edit']) && !in_array(($schema['key'] ?? null), ['projects', 'projects_archive'], true))
            <a href="{{ route($schema['routes']['edit'], $row) }}" class="text-xs text-indigo-600 hover:text-indigo-800">ویرایش</a>
        @endif
        @if(($schema['key'] ?? null) === 'leads')
            @if(empty($row->converted_at))
                <button
                    type="button"
                    class="text-xs px-2 py-1 rounded bg-indigo-600 text-white hover:bg-indigo-700 js-open-convert-modal"
                    data-convert-action="{{ route('marketing.leads.convert', $row) }}"
                    data-lead-name="{{ $row->full_name }}"
                    data-assigned-user-name="{{ $assignedName }}"
                    data-lead-source="{{ $row->lead_source }}"
                    data-lead-source-owner="{{ \App\Helpers\FormOptionsHelper::getLeadSourceOwnerType($row->lead_source) }}"
                    data-default-acquirer-id="{{ $row->assigned_to ?? $row->owner_user_id ?? $row->created_by ?? '' }}"
                >
                    تبدیل به فرصت
                </button>
            @else
                <span class="text-green-700 text-xs">تبدیل شده</span>
            @endif
        @endif
        @if(!empty($schema['routes']['destroy']))
            <form method="POST" action="{{ route($schema['routes']['destroy'], $row) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800">حذف</button>
            </form>
        @endif
    </div>
@endif

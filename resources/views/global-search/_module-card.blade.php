@props([
    'title' => '',
    'icon' => null,          // blade view path like 'icons.contact'
    'tintClass' => 'text-slate-600',
    'items' => [],           // array of ['label' => '', 'url' => '']
    'count' => 0,
    'allUrl' => '#',
    'emptyCtaText' => 'مشاهده همه',
])

<div class="rounded-2xl bg-white/70 backdrop-blur-md shadow-sm hover:shadow-md transition ring-1 ring-black/5 p-5">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            @if($icon)
                @includeIf($icon, ['class' => 'w-5 h-5 '.$tintClass])
            @endif
            <h3 class="font-semibold text-slate-800">{{ $title }}</h3>
        </div>
        <span class="text-xs rounded-full bg-slate-100 text-slate-700 px-2 py-0.5">{{ $count }} نتیجه</span>
    </div>

    <div class="mt-4 space-y-2">
        <!-- Loading state (Alpine: parent should define isLoading) -->
        <template x-if="isLoading">
            <div class="space-y-2">
                <div class="h-3 bg-slate-200/70 rounded animate-pulse"></div>
                <div class="h-3 bg-slate-200/70 rounded animate-pulse"></div>
                <div class="h-3 bg-slate-200/70 rounded animate-pulse"></div>
                <div class="h-3 bg-slate-200/70 rounded animate-pulse"></div>
            </div>
        </template>

        <!-- Content (server-rendered states) -->
        @if(($count ?? 0) < 1)
            <div x-show="!isLoading" class="text-slate-500/80">
                <div class="flex items-center gap-2">
                    @if($icon)
                        @includeIf($icon, ['class' => 'w-5 h-5 '.$tintClass.' opacity-40'])
                    @endif
                    <span>موردی یافت نشد.</span>
                </div>
                <a href="{{ $allUrl }}" class="inline-flex items-center mt-3 text-sm {{ $tintClass }} hover:opacity-90 active:opacity-80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300/50 rounded px-2 py-1">
                    {{ $emptyCtaText }}
                </a>
            </div>
        @else
            <div x-show="!isLoading">
                <div x-data="{ expanded: false }" class="space-y-2">
                    @foreach($items as $idx => $it)
                        @if($idx < 3)
                            <a href="{{ $it['url'] }}" class="block text-sm text-slate-700 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100 transition rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300/50" title="{{ $it['label'] }}">
                                <span class="truncate inline-block max-w-full align-middle">{{ $it['label'] }}</span>
                            </a>
                        @else
                            <a href="{{ $it['url'] }}" x-show="expanded" x-cloak class="block text-sm text-slate-700 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100 transition rounded px-2 py-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300/50" title="{{ $it['label'] }}">
                                <span class="truncate inline-block max-w-full align-middle">{{ $it['label'] }}</span>
                            </a>
                        @endif
                    @endforeach

                    <div class="pt-1 flex items-center gap-4">
                        @if(($count ?? 0) > 3)
                            <button type="button" x-on:click="expanded = !expanded" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300/50 rounded px-2 py-1">
                                <span x-show="!expanded">مشاهده بیشتر</span>
                                <span x-show="expanded" x-cloak>مشاهده کمتر</span>
                            </button>
                        @endif

                        <a href="{{ $allUrl }}" class="inline-flex items-center text-sm {{ $tintClass }} hover:opacity-90 active:opacity-80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-300/50 rounded px-2 py-1">
                            مشاهده همه
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>


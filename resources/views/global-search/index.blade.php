@extends('layouts.app')

@section('content')
    @php
        // Normalize incoming controller payload: $results = [contacts, organizations, opportunities, proformas]
        $q = $q ?? request('q');
        $results = $results ?? [];

        $mapToTitleUrl = function ($collection, $type) {
            return collect($collection ?? [])->map(function ($item) use ($type) {
                // If already normalized
                if (is_array($item) && (isset($item['url']) || isset($item['title']))) {
                    return [
                        'title' => $item['title'] ?? ($item['label'] ?? ''),
                        'url'   => $item['url']   ?? '#',
                    ];
                }

                // Model normalization
                switch ($type) {
                    case 'leads':
                        $title = trim(((string)($item->prefix ?? '')) . ' ' . ((string)($item->full_name ?? '')));
                        if ($title === '') {
                            $title = $item->company ?? ('Lead #'.($item->id ?? ''));
                        }
                        $url = route('sales.leads.show', $item->id ?? $item);
                        break;
                    case 'contacts':
                        $title = $item->full_name
                            ?? trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? ''))
                            ?: ($item->name ?? ('Contact #'.($item->id ?? '')));
                        $url = route('sales.contacts.show', $item->id ?? $item);
                        break;
                    case 'organizations':
                        $title = $item->name ?? $item->title ?? ('Org #'.($item->id ?? ''));
                        $url = route('sales.organizations.show', $item->id ?? $item);
                        break;
                    case 'opportunities':
                        $title = $item->name ?? $item->subject ?? $item->title ?? ('Opportunity #'.($item->id ?? ''));
                        $url = route('sales.opportunities.show', $item->id ?? $item);
                        break;
                    case 'proformas':
                        $title = $item->subject ?? $item->title ?? ('PF-'.($item->id ?? ''));
                        $url = route('sales.proformas.show', $item->id ?? $item);
                        break;
                    default:
                        $title = (string) ($item->title ?? $item->name ?? $item->id ?? '');
                        $url = '#';
                }

                return [
                    'title' => $title,
                    'url'   => $url,
                ];
            })->values()->all();
        };

        $leads         = $mapToTitleUrl($results['leads']         ?? [], 'leads');
        $contacts      = $mapToTitleUrl($results['contacts']      ?? [], 'contacts');
        $organizations  = $mapToTitleUrl($results['organizations']  ?? [], 'organizations');
        $opportunities  = $mapToTitleUrl($results['opportunities']  ?? [], 'opportunities');
        $proformas      = $mapToTitleUrl($results['proformas']      ?? [], 'proformas');

        // Counts and totals
        $counts = [
            'leads'         => count($leads),
            'contacts'      => count($contacts),
            'organizations' => count($organizations),
            'opportunities' => count($opportunities),
            'proformas'     => count($proformas),
        ];
        $total = array_sum($counts);
        $moduleCount = collect($counts)->filter(fn ($c) => $c > 0)->count();

        // Convert to ['label','url'] for the card partial API
        $toCardItems = function (array $items) {
            return collect($items)->map(fn ($it) => [
                'label' => $it['title'] ?? '',
                'url'   => $it['url']   ?? '#',
            ])->all();
        };

        $leadItems         = $toCardItems($leads);
        $contactItems      = $toCardItems($contacts);
        $organizationItems = $toCardItems($organizations);
        $opportunityItems  = $toCardItems($opportunities);
        $proformaItems     = $toCardItems($proformas);
    @endphp

    <div x-data="{ isLoading: false }" class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
        <div class="flex flex-col gap-3">
            <h2 class="text-xl font-semibold">نتایج جستجو برای «{{ $q ?? '' }}»</h2>
            <p class="text-sm text-slate-600" aria-live="polite">🔍 {{ $total }} نتیجه در {{ $moduleCount }} ماژول</p>

            <form method="GET" action="{{ route('global.search') }}" x-on:submit="isLoading = true" class="relative">
                <input
                    type="text"
                    name="q"
                    value="{{ $q ?? '' }}"
                    dir="rtl"
                    placeholder="جستجو..."
                    class="w-full rounded-xl border border-slate-200 bg-white/70 backdrop-blur pe-10 ps-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-400/30 focus:border-sky-400/50 transition"
                />
                <div class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    @includeIf('icons.contact', ['class' => 'w-5 h-5'])
                </div>
            </form>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @include('global-search._module-card', [
                'title' => 'سرنخ‌ها',
                'icon' => 'icons.contact',
                'tintClass' => 'text-rose-600',
                'items' => $leadItems,
                'count' => $counts['leads'],
                'allUrl' => route('sales.leads.index'),
            ])

            @include('global-search._module-card', [
                'title' => 'مخاطبین',
                'icon' => 'icons.contact',
                'tintClass' => 'text-violet-600',
                'items' => $contactItems,
                'count' => $counts['contacts'],
                'allUrl' => route('sales.contacts.index'),
            ])

            @include('global-search._module-card', [
                'title' => 'سازمان‌ها',
                'icon' => 'icons.organization',
                'tintClass' => 'text-sky-600',
                'items' => $organizationItems,
                'count' => $counts['organizations'],
                'allUrl' => route('sales.organizations.index'),
            ])

            @include('global-search._module-card', [
                'title' => 'فرصت‌ها',
                'icon' => 'icons.opportunity',
                'tintClass' => 'text-emerald-600',
                'items' => $opportunityItems,
                'count' => $counts['opportunities'],
                'allUrl' => route('sales.opportunities.index'),
            ])

            @include('global-search._module-card', [
                'title' => 'پیش‌فاکتورها',
                'icon' => 'icons.proforma',
                'tintClass' => 'text-orange-600',
                'items' => $proformaItems,
                'count' => $counts['proformas'],
                'allUrl' => route('sales.proformas.index'),
            ])
        </div>
    </div>
@endsection


@php
    $stageColors = [
        'open'           => 'bg-blue-100 text-blue-700',
        'proposal_sent'  => 'bg-indigo-100 text-indigo-700',
        'negotiation'    => 'bg-amber-100 text-amber-700',
        'won'            => 'bg-green-100 text-green-700',
        'lost'           => 'bg-red-100 text-red-700',
        'dead'           => 'bg-gray-200 text-gray-800',
    ];
@endphp

@forelse($opportunities as $opportunity)
    <tr class="hover:bg-gray-50">
        @role('admin')
        <td class="px-3 py-4 text-center">
            <input type="checkbox" class="row-checkbox h-4 w-4" value="{{ $opportunity->id }}">
        </td>
        @endrole
        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
            {{ $opportunity->opportunity_number ?? '—' }}
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <a href="{{ route('sales.opportunities.show', $opportunity) }}" class="text-blue-600 hover:text-blue-900">
                {{ $opportunity->name ?? '-' }}
            </a>
        </td>

        <td class="px-6 py-4 whitespace-nowrap">
            {{ $opportunity->contact->name ?? '—' }}
        </td>

        <td class="px-6 py-4 whitespace-nowrap">
            @php
                $stageKey = $opportunity->getStageValue() ?? '—';
                $badgeClass = $stageColors[$stageKey] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badgeClass }}">
                {{ \App\Helpers\FormOptionsHelper::getOpportunityStageLabel($stageKey) }}
            </span>
        </td>

        <td class="px-6 py-4 whitespace-nowrap">
            {{ $opportunity->source ?? '—' }}
        </td>

        <td class="px-6 py-4 whitespace-nowrap">
            {{ $opportunity->assignedTo->name ?? '—' }}
        </td>

        <td class="px-6 py-4 whitespace-nowrap">
            {{ jdate($opportunity->created_at) }}
        </td>

        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
            <div class="flex gap-4">
                <a href="{{ route('sales.opportunities.edit', $opportunity) }}" class="text-indigo-600 hover:text-indigo-900">ویرایش</a>
                @role('admin')
                <form action="{{ route('sales.opportunities.destroy', $opportunity) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="text-red-600 hover:text-red-900"
                            onclick="return confirm('آیا از حذف این فرصت فروش اطمینان دارید؟')">
                        حذف
                    </button>
                </form>
                @endrole
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ auth()->user() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin') ? 9 : 8 }}" class="px-6 py-4 text-center text-gray-400">
            هیچ فرصتی یافت نشد.
        </td>
    </tr>
@endforelse


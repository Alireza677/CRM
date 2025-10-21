<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-800">فرصت‌های فروش مرتبط</h3>
        <a href="{{ route('sales.opportunities.create', ['organization_id' => $organization->id]) }}"
           class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700">
            ایجاد فرصت فروش
        </a>
    </div>

    @php($opps = $organization->opportunities ?? collect())
    @if($opps->count())
        <ul class="list-disc pr-5 text-sm text-gray-700">
            @foreach($opps as $op)
                <li>
                    <a href="{{ route('sales.opportunities.show', $op->id) }}" class="text-blue-600 hover:underline">
                        {{ $op->name ?? ('فرصت #' . $op->id) }}
                    </a>
                    <span class="text-gray-500">{{ jdate($op->created_at)->format('Y/m/d') }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500">فرصتی برای این سازمان ثبت نشده است.</p>
    @endif
</div>


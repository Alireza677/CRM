<div class="bg-white p-4 rounded shadow">
    <h4 class="text-lg font-semibold mb-3">فرصت‌های مرتبط</h4>

    @if(!empty($opportunities) && count($opportunities))
        <ul class="list-disc pl-5">
            @foreach($opportunities as $opp)
                <li>
                    <a href="{{ route('sales.opportunities.show', $opp->id) }}" class="text-blue-600 hover:underline">
                        {{ $opp->title ?? 'بدون عنوان' }}
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p>هیچ فرصت فروشی مرتبط یافت نشد.</p>
    @endif
</div>

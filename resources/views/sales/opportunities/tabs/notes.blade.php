<div class="space-y-6">

    <!-- فرم افزودن یادداشت -->
    <div class="bg-white p-4 shadow rounded-md">
        <h3 class="text-md font-semibold text-gray-700 mb-2">افزودن یادداشت</h3>

        <form method="POST" action="{{ route('sales.opportunities.notes.store', $opportunity->id) }}">


    @csrf
    <textarea name="content" rows="3" class="w-full border rounded p-2 text-sm"
              placeholder="یادداشتی ارسال کنید و برای اطلاع رسانی به @کاربر / @گروه همه به آن ها اشاره کنید">{{ old('content') }}</textarea>

    <div class="flex justify-end mt-2">
        <button type="submit"
                class="bg-blue-600 text-white text-sm px-4 py-1.5 rounded hover:bg-blue-700 transition">
            ذخیره یادداشت
        </button>
    </div>
</form>

    </div>

    <!-- لیست یادداشت‌ها -->
    @foreach($opportunity->notes()->latest()->get() as $note)
        <div class="bg-gradient-to-b from-white to-gray-50 p-4 shadow rounded-md">
            <div class="flex items-center justify-between mb-2 text-sm text-gray-500">
                <span>عمومی</span>
                <span>{{ jdate($note->created_at, 'relative') }}</span>
            </div>

            <div class="text-sm text-gray-800 mb-2 whitespace-pre-wrap">
                {!! nl2br(e($note->content)) !!}
            </div>

            <div class="flex items-center justify-between text-xs text-gray-500 border-t pt-2">
                <div>
                    <span>رها میرزایی</span> {{-- یا: {{ $note->user->name ?? 'کاربر' }} --}}
                </div>
                <div>
                    مخاطب: <span class="text-blue-600">{{ $opportunity->contact->name ?? '—' }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

@extends('layouts.app')

@section('content')
@php
    $breadcrumb = [
        ['title' => 'جلسات آنلاین', 'url' => route('sales.online-meetings.index')],
        ['title' => 'ایجاد جلسه'],
    ];
    $relatedOptions = [
        'opportunity' => $opportunities->map(fn($o) => [
            'id' => $o->id,
            'label' => $o->name ?: ('فرصت #' . $o->id),
        ]),
        'contact' => $contacts->map(fn($c) => [
            'id' => $c->id,
            'label' => trim($c->name ?: ($c->company ?? 'بدون نام')) . ' (#' . $c->id . ')',
        ]),
        'organization' => $organizations->map(fn($org) => [
            'id' => $org->id,
            'label' => trim($org->name ?: 'سازمان') . ' (#' . $org->id . ')',
        ]),
    ];
    $scheduledValue = old('scheduled_at');
    if ($scheduledValue) {
        try { $scheduledValue = \Carbon\Carbon::parse($scheduledValue)->format('Y-m-d\TH:i'); }
        catch (\Throwable $e) { $scheduledValue = old('scheduled_at'); }
    } else {
        $scheduledValue = now()->format('Y-m-d\TH:i');
    }
@endphp

<div class="max-w-4xl mx-auto p-4" dir="rtl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">ایجاد جلسه آنلاین</h1>
            <p class="text-sm text-gray-500 mt-1">ROOM_NAME یونیک + لینک https://meet.jit.si/ROOM_NAME</p>
        </div>
        <a href="{{ route('sales.online-meetings.index') }}" class="text-sm text-blue-600 hover:underline">بازگشت به لیست</a>
    </div>

    <div class="bg-white shadow-sm rounded-xl p-6 space-y-6">
        <form method="POST" action="{{ route('sales.online-meetings.store') }}" class="space-y-6">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">عنوان جلسه <span class="text-red-600">*</span></label>
                <input type="text" id="title" name="title" required
                       value="{{ old('title') }}"
                       class="mt-2 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                @error('title') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700">تاریخ و ساعت جلسه</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                           value="{{ $scheduledValue }}"
                           class="mt-2 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    @error('scheduled_at') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="duration_minutes" class="block text-sm font-medium text-gray-700">مدت جلسه (دقیقه)</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="10080"
                           value="{{ old('duration_minutes') }}"
                           class="mt-2 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    @error('duration_minutes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="related_type" class="block text-sm font-medium text-gray-700">ارتباط با</label>
                    <select id="related_type" name="related_type"
                            class="mt-2 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">بدون ارتباط</option>
                        <option value="opportunity" @selected(old('related_type')==='opportunity')>فرصت فروش</option>
                        <option value="contact" @selected(old('related_type')==='contact')>مخاطب</option>
                        <option value="organization" @selected(old('related_type')==='organization')>سازمان</option>
                    </select>
                    @error('related_type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="related_id" class="block text-sm font-medium text-gray-700">انتخاب رکورد مرتبط</label>
                    <select id="related_id" name="related_id"
                            class="mt-2 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            @if(!old('related_type')) disabled @endif>
                        <option value="">{{ old('related_type') ? 'انتخاب کنید' : 'ابتدا نوع ارتباط را انتخاب کنید' }}</option>
                    </select>
                    @error('related_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">یادداشت</label>
                <textarea id="notes" name="notes" rows="4"
                          class="mt-2 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                @error('notes') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('sales.online-meetings.index') }}"
                   class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">انصراف</a>
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-sm">ذخیره جلسه</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const data = @json($relatedOptions);
        const typeSelect = document.getElementById('related_type');
        const idSelect = document.getElementById('related_id');
        const initialType = @json(old('related_type'));
        const initialId = @json((string) old('related_id', ''));

        if (initialType && !typeSelect.value) {
            typeSelect.value = initialType;
        }

        function renderOptions() {
            const type = typeSelect.value || '';
            idSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = type ? 'انتخاب کنید' : 'ابتدا نوع ارتباط را انتخاب کنید';
            idSelect.appendChild(placeholder);

            if (!type || !data[type]) {
                idSelect.disabled = true;
                return;
            }

            idSelect.disabled = false;
            data[type].forEach(function (item) {
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.label;
                if (initialId && String(item.id) === String(initialId)) {
                    opt.selected = true;
                }
                idSelect.appendChild(opt);
            });
        }

        renderOptions();
        typeSelect.addEventListener('change', function () {
            idSelect.value = '';
            renderOptions();
        });
    });
</script>
@endpush

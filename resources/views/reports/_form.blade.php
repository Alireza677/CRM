@php
    /** @var \App\Models\Report|null $report */
    $report = $report ?? null;
    $selectedShared = old('shared_user_ids', $report?->sharedUsers->pluck('id')->all() ?? []);
    $selectedCanEdit = old('shared_can_edit_ids', $report?->sharedUsers->where('pivot.can_edit', true)->pluck('id')->all() ?? []);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4" dir="rtl">
    <div class="col-span-2">
        <label class="block mb-1">عنوان <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title', $report->title ?? '') }}" class="w-full border rounded p-2" required maxlength="190">
        @error('title')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-span-2">
        <label class="block mb-1">توضیحات</label>
        <textarea name="description" rows="3" class="w-full border rounded p-2">{{ old('description', $report->description ?? '') }}</textarea>
        @error('description')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
    </div>

    <div>
        <label class="block mb-1">محدوده نمایش (visibility) <span class="text-red-500">*</span></label>
        <select name="visibility" class="w-full border rounded p-2" required>
            @php $vis = old('visibility', $report->visibility ?? 'private'); @endphp
            <option value="private" @selected($vis==='private')>خصوصی</option>
            <option value="public" @selected($vis==='public')>عمومی</option>
            <option value="shared" @selected($vis==='shared')>اشتراکی</option>
        </select>
        @error('visibility')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
    </div>

    <div>
        <label class="block mb-1">مدل (اختیاری)</label>
        <input type="text" name="model" value="{{ old('model', $report->model ?? '') }}" class="w-full border rounded p-2" maxlength="190">
        @error('model')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-span-2">
        <label class="block mb-1">کاربران اشتراک‌گذاری (در حالت اشتراکی)</label>
        <select name="shared_user_ids[]" multiple class="w-full border rounded p-2">
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(in_array($u->id, $selectedShared))>{{ $u->name }}</option>
            @endforeach
        </select>
        @error('shared_user_ids')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-span-2">
        <label class="block mb-1">دسترسی ویرایش (برای کاربران انتخاب‌شده)</label>
        <select name="shared_can_edit_ids[]" multiple class="w-full border rounded p-2">
            @foreach($users as $u)
                <option value="{{ $u->id }}" @selected(in_array($u->id, $selectedCanEdit))>{{ $u->name }}</option>
            @endforeach
        </select>
        <small class="text-gray-500">کاربرانی که در این لیست باشند، امکان ویرایش گزارش را دارند.</small>
    </div>

    <div class="col-span-2">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1" class="mr-2" @checked(old('is_active', ($report->is_active ?? true)) )>
            <span>فعال</span>
        </label>
    </div>
</div>


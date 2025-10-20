@php
    /** @var \App\Models\Report|null $report */
    $report = $report ?? null;
    $selectedShared = old('shared_user_ids', $report?->sharedUsers->pluck('id')->all() ?? []);
    $selectedCanEdit = old('shared_can_edit_ids', $report?->sharedUsers->where('pivot.can_edit', true)->pluck('id')->all() ?? []);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4" dir="rtl"
     x-data="{
        users: @js($users->mapWithKeys(fn($u)=>[$u->id=>$u->name])) ,
        selectedShared: @js($selectedShared),
        selectedCanEdit: @js($selectedCanEdit),
        pickerShared: '',
        pickerCanEdit: '',
        addShared(){ const id = Number(this.pickerShared); if(!id) return; if(!this.selectedShared.includes(id)) this.selectedShared.push(id); this.pickerShared=''; },
        addCanEdit(){ const id = Number(this.pickerCanEdit); if(!id) return; if(!this.selectedCanEdit.includes(id)) this.selectedCanEdit.push(id); if(!this.selectedShared.includes(id)) this.selectedShared.push(id); this.pickerCanEdit=''; },
        removeShared(id){ this.selectedShared = this.selectedShared.filter(x=>x!==id); this.selectedCanEdit = this.selectedCanEdit.filter(x=>x!==id); },
        removeCanEdit(id){ this.selectedCanEdit = this.selectedCanEdit.filter(x=>x!==id); }
     }">
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

    <div></div>

    <div class="col-span-2">
        <label class="block mb-1">کاربران اشتراک‌گذاری</label>
        <select x-model="pickerShared" @change="addShared()" class="w-full border rounded p-2">
            <option value="">افزودن کاربر...</option>
            <template x-for="([id, name]) in Object.entries(users)" :key="id">
                <option :value="id" x-text="name" :disabled="selectedShared.includes(Number(id))"></option>
            </template>
        </select>
        <div class="mt-2 flex flex-wrap gap-2">
            <template x-for="id in selectedShared" :key="id">
                <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded">
                    <span x-text="users[id]"></span>
                    <button type="button" class="text-blue-700 hover:text-blue-900" @click="removeShared(id)">×</button>
                    <input type="hidden" name="shared_user_ids[]" :value="id">
                </span>
            </template>
        </div>
        @error('shared_user_ids')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-span-2">
        <label class="block mb-1">دسترسی ویرایش برای کاربران انتخاب‌شده</label>
        <select x-model="pickerCanEdit" @change="addCanEdit()" class="w-full border rounded p-2">
            <option value="">افزودن کاربر...</option>
            <template x-for="([id, name]) in Object.entries(users)" :key="'e'+id">
                <option :value="id" x-text="name" :disabled="selectedCanEdit.includes(Number(id))"></option>
            </template>
        </select>
        <div class="mt-2 flex flex-wrap gap-2">
            <template x-for="id in selectedCanEdit" :key="'sel'+id">
                <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 px-2 py-1 rounded">
                    <span x-text="users[id]"></span>
                    <button type="button" class="text-purple-700 hover:text-purple-900" @click="removeCanEdit(id)">×</button>
                    <input type="hidden" name="shared_can_edit_ids[]" :value="id">
                </span>
            </template>
        </div>
        <small class="text-gray-500">کاربرانی که اینجا انتخاب شوند علاوه‌بر مشاهده، امکان ویرایش گزارش را هم دارند.</small>
    </div>

    <div class="col-span-2">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_active" value="1" class="mr-2" @checked(old('is_active', ($report->is_active ?? true)) )>
            <span>فعال</span>
        </label>
    </div>
</div>

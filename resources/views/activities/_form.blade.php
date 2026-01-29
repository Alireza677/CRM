@php
  $isEdit = isset($activity) && $activity && method_exists($activity, 'getKey') && $activity->getKey();

  $startRaw = $isEdit ? $activity->getRawOriginal('start_at') : null;
  $dueRaw   = $isEdit ? $activity->getRawOriginal('due_at')   : null;

  $startValue = old('start_at', $startRaw ? \Carbon\Carbon::parse($startRaw)->format('Y-m-d H:i:s') : '');
  $dueValue   = old('due_at',   $dueRaw   ? \Carbon\Carbon::parse($dueRaw)->format('Y-m-d H:i:s')   : '');

  $startPrefill = $startValue ? '1' : '0';
  $duePrefill   = $dueValue   ? '1' : '0';

  $assignedDefault = old('assigned_to_id', $isEdit ? $activity->assigned_to_id : auth()->id());
  $statusDefault   = old('status',   $isEdit ? $activity->status   : '');
  $priorityDefault = old('priority', $isEdit ? $activity->priority : '');

  $prefillRelated = $prefillRelated ?? [];
  $relatedMap = [
      'contact'      => \App\Models\Contact::class,
      'organization' => \App\Models\Organization::class,
      'sales_lead'   => \App\Models\SalesLead::class,
      'opportunity'  => \App\Models\Opportunity::class,
  ];

  $normalizeRelatedType = function ($value) use ($relatedMap) {
      if (empty($value)) return null;
      if (isset($relatedMap[$value])) return $value;
      $slug = array_search($value, $relatedMap, true);
      return $slug === false ? null : $slug;
  };

  $rtRaw = old('related_type', $isEdit ? ($activity->related_type ?? '') : ($prefillRelated['type'] ?? ''));
  $rt = $normalizeRelatedType($rtRaw);
  $rid = old('related_id',  $isEdit ? ($activity->related_id  ?? '') : ($prefillRelated['id'] ?? ''));

  $relatedDisplay = $prefillRelated['label'] ?? '';
  if ($rt && $rid && $relatedDisplay === '') {
      $labels = [
          'contact'      => 'مخاطب',
          'organization' => 'سازمان',
          'sales_lead'   => 'سرنخ',
          'opportunity'  => 'فرصت',
      ];
      $prefix = $labels[$rt] ?? '#';
      $relatedDisplay = ($prefix === '#') ? "#{$rid}" : "{$prefix} #{$rid}";
  }
@endphp

{{-- موضوع --}}
<div>
  <label class="block text-sm mb-1">موضوع</label>
  <input name="subject" class="w-full rounded-md border p-2" required
         value="{{ old('subject', $isEdit ? ($activity->subject ?? '') : '') }}">
  @error('subject')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

{{-- تاریخ‌ها (با انتخاب ساعت) --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm font-medium mb-1">تاریخ شروع</label>
    <input type="hidden" id="start_at" name="start_at" value="{{ $startValue }}">
    <input
      type="text"
      id="start_at_display"
      class="persian-datepicker w-full rounded-md border p-2"
      data-alt-field="start_at"
      data-prefill="{{ $startPrefill }}"
      autocomplete="off"
      value="">
    @error('start_at')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>

  <div>
    <label class="block text-sm font-medium mb-1">موعد/پایان</label>
    <input type="hidden" id="due_at" name="due_at" value="{{ $dueValue }}">
    <input
      type="text"
      id="due_at_display"
      class="persian-datepicker w-full rounded-md border p-2"
      data-alt-field="due_at"
      data-prefill="{{ $duePrefill }}"
      autocomplete="off"
      value="">
    @error('due_at')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>
</div>

{{-- یادآوری‌ها --}}
<div class="border rounded-md p-3">
  <div class="flex items-center justify-between mb-2">
    <label class="block text-sm font-medium">یادآوری‌ها</label>
    <button type="button" id="btnAddReminder" class="px-3 py-1.5 text-sm rounded-md bg-slate-100 hover:bg-slate-200">+ ایجاد یادآوری</button>
  </div>
  <p class="text-xs text-gray-500 mb-2">می‌توانید چند یادآوری اضافه کنید. برای یادآوری‌های نسبی، تعیین «موعد/پایان» الزامی است. برای یادآوری زمان مشخص، تاریخ و ساعت را وارد کنید.</p>
  <input type="hidden" name="reminders_present" value="1">

  <div id="remindersContainer" class="space-y-2">
    {{-- ردیف‌های پویا اینجا اضافه می‌شوند --}}
  </div>

  @error('reminders')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  @error('reminders.*.type')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  @error('reminders.*.time')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  @error('reminders.*.datetime')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
</div>

@php
  $reminderPresets = [];
  if (old('reminders')) {
      $reminderPresets = old('reminders');
  } elseif ($isEdit && isset($activity) && $activity->relationLoaded('reminders')) {
      $reminderPresets = $activity->reminders->map(function ($r) {
          if ($r->kind === 'relative') {
              $map = [ -30 => '30m_before', -60 => '1h_before', -1440 => '1d_before' ];
              $type = $map[(int) ($r->offset_minutes ?? 0)] ?? null;
              if (!$type) return null;
              return ['type' => $type];
          }
          if ($r->kind === 'same_day') {
              return ['type' => 'same_day', 'time' => $r->time_of_day];
          }
          if ($r->kind === 'absolute') {
              return ['type' => 'absolute', 'datetime' => $r->remind_at ? $r->remind_at->format('Y-m-d H:i') : null];
          }
          return null;
      })->filter()->values()->all();
  }
@endphp
@if(!empty($reminderPresets))
  <script>window.__activityReminders = @json($reminderPresets);</script>
@endif

{{-- ارجاع به --}}
<div>
  <label class="block text-sm mb-1">ارجاع به</label>
  <select name="assigned_to_id" class="w-full rounded-md border p-2" required>
    @foreach($users as $u)
      <option value="{{ $u->id }}" {{ (string)$assignedDefault === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
    @endforeach
  </select>
  @error('assigned_to_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
</div>

{{-- مربوط به (انتخاب با مودال‌ها) --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm mb-1">مربوط به</label>
    <div class="flex gap-2">
      <button type="button" onclick="openContactModal()" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200">مخاطب +</button>
      <button type="button" onclick="openOrganizationModal()" class="px-3 py-2 rounded-md bg-slate-100 hover:bg-slate-200">سازمان +</button>
    </div>
  </div>
  <div>
    <label class="block text-sm mb-1">آیتم انتخاب‌شده</label>
    <input id="related_display" type="text" class="w-full rounded-md border p-2 bg-gray-50" placeholder="— انتخاب نشده —" readonly value="{{ $relatedDisplay }}">
  </div>
</div>

{{-- فیلدهای واقعی فرم برای مربوط به --}}
<input type="hidden" name="related_type" id="related_type" value="{{ $rt }}">
<input type="hidden" name="related_id"   id="related_id"   value="{{ $rid }}">
@error('related_type')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
@error('related_id')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror

{{-- وضعیت / اولویت --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm mb-1">وضعیت</label>
    <select name="status" class="w-full rounded-md border p-2" required>
      <option value="not_started" {{ $statusDefault==='not_started' ? 'selected' : '' }}>شروع نشده</option>
      <option value="in_progress" {{ $statusDefault==='in_progress' ? 'selected' : '' }}>در حال انجام</option>
      <option value="completed"   {{ $statusDefault==='completed'   ? 'selected' : '' }}>تکمیل شده</option>
      <option value="scheduled"   {{ $statusDefault==='scheduled'   ? 'selected' : '' }}>برنامه‌ریزی شده</option>
    </select>
    @error('status')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>
  <div>
    <label class="block text-sm mb-1">اولویت</label>
    <select name="priority" class="w-full rounded-md border p-2" required>
      <option value="normal" {{ $priorityDefault==='normal' ? 'selected' : '' }}>معمولی</option>
      <option value="medium" {{ $priorityDefault==='medium' ? 'selected' : '' }}>متوسط</option>
      <option value="high"   {{ $priorityDefault==='high'   ? 'selected' : '' }}>زیاد</option>
    </select>
    @error('priority')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
  </div>
</div>

{{-- پیشرفت --}}
@php
  $progressDefault = old('progress', $isEdit ? ($activity->progress ?? 0) : 0);
@endphp
<div>
  <label class="block text-sm mb-1">پیشرفت (٪)</label>
  <div class="flex items-center gap-3">
    <input type="range" id="progress_range" name="progress" min="0" max="100"
           value="{{ $progressDefault }}" class="w-full">
    <input type="number" id="progress_input" min="0" max="100"
           value="{{ $progressDefault }}" class="w-20 rounded-md border p-2 text-sm">
  </div>
  @error('progress')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
</div>

{{-- توضیحات --}}
<div>
  <label class="block text-sm mb-1">توضیحات</label>
  <textarea name="description" rows="4" class="w-full rounded-md border p-2">{{ old('description', $isEdit ? ($activity->description ?? '') : '') }}</textarea>
  @error('description')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
</div>

{{-- خصوصی --}}
<label class="inline-flex items-center gap-2">
  <input type="checkbox" name="is_private" value="1" {{ old('is_private', $isEdit ? ($activity->is_private ?? false) : false) ? 'checked' : '' }}>
  <span>خصوصی (عدم نمایش برای سایر کاربران)</span>
</label>


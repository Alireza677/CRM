@php
    $rowId = $row['id'] ?? null;
    $formId = $rowId ? 'po-rule-'.$rowId : 'po-rule-create';
    $formAction = $row['form_action'] ?? ($rowId
        ? route('settings.notifications.update', $rowId)
        : route('settings.notifications.store'));
    $deleteAction = $row['delete_action'] ?? ($rowId ? route('settings.notifications.destroy', $rowId) : null);
    $conditions = $row['conditions'] ?? ['from_status' => '', 'to_status' => ''];
@endphp

<div class="p-4 bg-white" @keydown.escape.window="closeEditor()" x-data="notificationRuleRow({
        enabled: {{ ($row['enabled'] ?? false) ? 'true' : 'false' }},
        channels: @js($row['channels'] ?? []),
        conditions: @js($conditions),
        subject: @js($row['subject_template'] ?? ''),
        body: @js($row['body_template'] ?? ''),
        internal: @js($row['internal_template'] ?? ($row['body_template'] ?? '')),
        sms: @js($row['sms_template'] ?? ''),
        placeholders: @js($row['placeholders'] ?? []),
        formAction: @js($formAction),
        hasId: @js((bool) $rowId),
        module: @js($row['module']),
        event: @js($row['event']),
        previewUrl: @js(route('settings.notifications.preview')),
        csrfToken: @js(csrf_token()),
    })">
    <form x-ref="rowForm" :action="formAction" method="post" class="space-y-4" id="{{ $formId }}">
        @csrf
        @if($rowId)
            @method('PUT')
        @else
            <input type="hidden" name="module" value="{{ $row['module'] }}">
            <input type="hidden" name="event" value="{{ $row['event'] }}">
        @endif
        <input type="hidden" name="enabled" :value="enabled ? 1 : 0" x-effect="$el.value = enabled ? 1 : 0">
        <input type="hidden" name="subject_template" :value="subject">
        <input type="hidden" name="body_template" :value="body">
        <input type="hidden" name="internal_template" :value="internal">
        <input type="hidden" name="sms_template" :value="sms">

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 lg:col-span-4 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">متن اعلان</span>
                    <button type="button" class="px-3 py-1.5 bg-blue-50 border border-blue-200 rounded text-sm hover:bg-blue-100" @click="openEditor()">ویرایش متن</button>
                </div>
                <div class="text-xs text-gray-500">پیش‌نمایش کوتاه</div>
                <div class="p-3 border rounded bg-gray-50 text-sm space-y-2">
                    <div class="font-semibold" x-text="previewLocal().subject"></div>
                    <div x-text="previewLocal().body"></div>
                    <template x-if="(channels||[]).includes('database')">
                        <div class="text-xs text-gray-600">
                            <div class="font-semibold mt-2">داخلی (سیستم)</div>
                            <div x-text="previewLocal().internal"></div>
                        </div>
                    </template>
                    <template x-if="(channels||[]).includes('sms')">
                        <div class="text-xs text-gray-600">
                            <div class="font-semibold mt-2">SMS</div>
                            <div x-text="previewLocal().sms"></div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-4 space-y-2">
                <div class="text-xs text-gray-500">شرایط رویداد</div>
                <div class="text-sm text-gray-700">رویداد: {{ $section['event_label'] }}</div>
                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">از وضعیت</label>
                        <select name="conditions[from_status]" x-model="conditions.from_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">(همه وضعیت‌ها)</option>
                            @foreach($poStatuses as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-gray-500 mt-6">←</span>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">به وضعیت</label>
                        <select name="conditions[to_status]" x-model="conditions.to_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">(همه وضعیت‌ها)</option>
                            @foreach($poStatuses as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-span-12 lg:col-span-2 space-y-2">
                <div class="text-xs text-gray-500">کانال‌ها</div>
                @foreach($channelOptions as $chKey => $chLabel)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="channels[]" value="{{ $chKey }}" x-model="channels" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>{{ $chLabel }}</span>
                    </label>
                @endforeach
            </div>
            <div class="col-span-12 lg:col-span-2 space-y-4">
                <div>
                    <div class="text-xs text-gray-500 mb-2">وضعیت فعال‌سازی</div>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="enabled" class="sr-only peer">
                        <div class="w-12 h-6 bg-gray-200 rounded-full peer peer-checked:bg-green-600 relative transition"></div>
                    </label>
                </div>

                <div class="flex flex-col gap-2 lg:items-end">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">ذخیره</button>
                    @unless($rowId)
                        <button type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200" @click="$dispatch('po-rules-cancel')">انصراف</button>
                    @endunless
                </div>
            </div>
        </div>
    </form>
    @if($rowId)
        <div class="mt-3 text-left">
            <form action="{{ $deleteAction }}" method="post" onsubmit="return confirm('این اعلان حذف شود؟');" class="inline-block">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">حذف</button>
            </form>
        </div>
    @endif
    @include('settings.notifications.partials.notification-template-editor')
</div>



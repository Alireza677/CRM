@php
    $editorConfig = $editorConfig ?? [];
    $panelClasses = $editorConfig['panelClasses']
        ?? 'relative max-w-4xl mx-auto my-10 bg-white border rounded-lg p-4 shadow max-h-[90vh] overflow-y-auto';

    // عناوین و برچسب‌ها (فارسی)
    $modalTitle = $editorConfig['modalTitle'] ?? 'ویرایش متن اعلان';
    $subjectLabel = $editorConfig['subjectLabel'] ?? 'موضوع اعلان';
    $bodyLabel = $editorConfig['bodyLabel'] ?? 'متن اعلان (ایمیل / اعلان داخلی)';
    $smsLabel = $editorConfig['smsLabel'] ?? 'متن پیامک (SMS)';
    $bodyPlaceholder = $editorConfig['bodyPlaceholder'] ?? null;
    $smsPlaceholder = $editorConfig['smsPlaceholder'] ?? null;

    $showTokenHelperText = $editorConfig['showTokenHelperText'] ?? false;
    $tokenHelperText = $editorConfig['tokenHelperText'] ?? 'برای شخصی‌سازی متن‌ها می‌توانید از توکن‌های زیر استفاده کنید. توکن‌ها هنگام ارسال، با مقادیر واقعی جایگزین می‌شوند.';

    $showTokenFooter = $editorConfig['showTokenFooter'] ?? false;
    $tokenFooterText = $editorConfig['tokenFooterText']
        ?? 'برای استفاده از هر توکن، آن را دقیقاً به همان صورت که در لیست بالا نمایش داده می‌شود در متن خود قرار دهید. نمونه‌هایی از توکن‌های پرکاربرد:';

    $buttonsLayout = $editorConfig['buttonsLayout'] ?? 'flex';

    $renderButtonText = $editorConfig['renderButtonText'] ?? 'نمایش پیش‌نمایش اعلان';
    $saveButtonText = $editorConfig['saveButtonText'] ?? 'ذخیره قالب اعلان';
    $cancelButtonText = $editorConfig['cancelButtonText'] ?? 'انصراف';
    $previewTitle = $editorConfig['previewTitle'] ?? 'پیش‌نمایش اعلان';
@endphp

<template x-teleport="body">
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-black/30" @click="closeEditor()" x-transition.opacity></div>

        <div class="{{ $panelClasses }}" dir="rtl" x-show="showModal" x-transition.opacity.scale.origin-top>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">{{ $modalTitle }}</h3>
                <button
                    type="button"
                    class="text-gray-500 hover:text-gray-700"
                    @click="closeEditor()"
                >
                    بستن
                </button>
            </div>

            <div class="grid grid-cols-1 gap-3">
                {{-- موضوع اعلان --}}
                <div>
                    <label class="block text-sm text-gray-700 mb-1">{{ $subjectLabel }}</label>
                    <input
                        type="text"
                        x-ref="subjectArea"
                        @focus="focusedField='subject'"
                        x-model="subject"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                {{-- متن اعلان (ایمیل / داخلی) --}}
                <div>
                    <label class="block text-sm text-gray-700 mb-1">{{ $bodyLabel }}</label>
                    <textarea
                        x-ref="bodyArea"
                        @focus="focusedField='body'"
                        x-model="body"
                        rows="8"
                        @if($bodyPlaceholder) placeholder="{{ $bodyPlaceholder }}" @endif
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                {{-- متن پیامک --}}
                <div>
                    <label class="block text-sm text-gray-700 mb-1">{{ $smsLabel }}</label>
                    <textarea
                        x-ref="smsArea"
                        @focus="focusedField='sms'"
                        x-model="sms"
                        rows="4"
                        @if($smsPlaceholder) placeholder="{{ $smsPlaceholder }}" @endif
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                {{-- متن راهنما برای توکن‌ها --}}
                @if($showTokenHelperText)
                    <div class="text-xs text-gray-500 mb-1">
                        {{ $tokenHelperText }}
                    </div>
                @endif

                {{-- جستجوی توکن --}}
                <div class="flex items-center gap-2 {{ $showTokenHelperText ? 'mb-2' : '' }}">
                    <input
                        type="text"
                        x-model="pQ"
                        @keydown="onKeyNav($event)"
                        placeholder="جستجوی توکن‌ها بر اساس نام..."
                        class="w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                </div>

                {{-- لیست توکن‌ها --}}
                <div class="flex flex-wrap gap-2" @keydown="onKeyNav($event)" tabindex="0">
                    <template x-for="(tok, idx) in filteredTokens()" :key="tok">
                        <div
                            :class="'px-2 py-1 rounded border text-xs cursor-pointer flex items-center gap-2 ' + (idx===selectedIdx ? 'bg-blue-50 border-blue-300' : 'bg-gray-50 border-gray-200')"
                            @click="insertToken(tok)"
                            @mouseenter="selectedIdx=idx"
                        >
                            <span x-text="tok"></span>
                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-600"
                                @click.stop="copyToken(tok)"
                            >
                                کپی
                            </button>
                        </div>
                    </template>
                </div>

                {{-- توضیحات انتهایی توکن‌ها --}}
                @if($showTokenFooter)
                    <div class="text-xs text-gray-500">
                        {{ $tokenFooterText }}
                        <span x-text="placeholders.join('، ')"></span> و
                        <code>{url}</code> یا <code>@{{ url }}</code>،
                        <code>@{{ actor.name }}</code>
                        و سایر فیلدهای مربوط به بازیگر (کاربر انجام‌دهنده عمل)
                        را نیز می‌توانید در متن اعلان استفاده کنید.
                    </div>
                @endif

                {{-- دکمه‌ها --}}
                @if($buttonsLayout === 'text-right')
                    <div class="text-right mt-2">
                        <button
                            type="button"
                            class="px-4 py-2 bg-gray-200 rounded mr-2"
                            @click="renderPreview()"
                        >
                            {{ $renderButtonText }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            @click="saveTemplates()"
                        >
                            {{ $saveButtonText }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 bg-gray-200 rounded mr-2"
                            @click="closeEditor()"
                        >
                            {{ $cancelButtonText }}
                        </button>
                    </div>
                @else
                    <div class="flex justify-end gap-2 mt-2">
                        <button
                            type="button"
                            class="px-4 py-2 bg-gray-200 rounded"
                            @click="renderPreview()"
                        >
                            {{ $renderButtonText }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            @click="saveTemplates()"
                        >
                            {{ $saveButtonText }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 bg-gray-200 rounded"
                            @click="closeEditor()"
                        >
                            {{ $cancelButtonText }}
                        </button>
                    </div>
                @endif

                {{-- پیش‌نمایش اعلان --}}
                <div class="text-sm font-medium mb-1 mt-3">
                    {{ $previewTitle }}
                </div>
                <div class="p-3 bg-gray-50 rounded border text-sm whitespace-pre-line">
                    <div class="font-semibold" x-text="preview.subject"></div>
                    <div x-text="preview.body"></div>

                    <template x-if="(channels || []).includes('sms') && (sms || '').trim() !== ''">
                        <div class="mt-2">
                            <div class="font-semibold">SMS:</div>
                            <div x-text="preview.sms"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

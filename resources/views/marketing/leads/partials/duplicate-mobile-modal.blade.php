@php
    $alert = session('duplicate_mobile_alert');
    $intent = $alert['intent'] ?? 'block';
    $contactInfo = data_get($alert, 'meta.contact', []);
@endphp

@if (session()->has('duplicate_mobile_alert'))
    <div id="duplicateMobileModal"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center px-4">
        <div class="bg-white w-full max-w-xl rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    @if ($intent === 'confirm_contact')
                        تایید مخاطب موجود
                    @else
                        شماره موبایل تکراری
                    @endif
                </h3>
                <button type="button"
                        onclick="closeDuplicateMobileModal()"
                        class="text-gray-500 hover:text-red-500 text-2xl leading-none">&times;</button>
            </div>

            @if ($intent === 'confirm_contact')
                <div class="text-sm text-gray-700 space-y-4">
                    <p>این شماره قبلا به عنوان مخاطب ثبت شده، می‌خواهید با همین مخاطب ادامه دهید؟</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-500">نام مخاطب</div>
                            <div class="font-medium text-gray-800">{{ $contactInfo['name'] ?? '-' }}</div>
                        </div>
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-500">شماره موبایل</div>
                            <div class="font-medium text-gray-800">{{ $contactInfo['mobile'] ?? ($alert['mobile'] ?? '-') }}</div>
                        </div>
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-500">استان</div>
                            <div class="font-medium text-gray-800">{{ $contactInfo['state'] ?? '-' }}</div>
                        </div>
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-500">شهر</div>
                            <div class="font-medium text-gray-800">{{ $contactInfo['city'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-sm text-gray-700 space-y-3">
                    <p>
                        این شماره موبایل در
                        <span class="font-semibold">{{ $alert['module_fa'] ?? 'ماژول' }}</span>
                        قبلا ثبت شده است.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-500">عنوان</div>
                            <div class="font-medium text-gray-800">{{ $alert['title'] ?? '-' }}</div>
                        </div>
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-500">شماره موبایل</div>
                            <div class="font-medium text-gray-800">{{ $alert['mobile'] ?? '-' }}</div>
                        </div>
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-500">تاریخ ثبت</div>
                            <div class="font-medium text-gray-800">{{ $alert['created_at_fa'] ?? '-' }}</div>
                        </div>
                        <div class="bg-gray-50 rounded p-3">
                            <div class="text-xs text-gray-500">شماره</div>
                            <div class="font-medium text-gray-800">#{{ $alert['record_id'] ?? '-' }}</div>
                        </div>
                    </div>

                    @php
                        $extraStatus = data_get($alert, 'extra.status');
                        $extraSource = data_get($alert, 'extra.source');
                    @endphp

                    @if ($extraStatus || $extraSource)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="bg-gray-50 rounded p-3">
                                <div class="text-xs text-gray-500">وضعیت</div>
                                <div class="font-medium text-gray-800">{{ $extraStatus ?? '-' }}</div>
                            </div>
                            <div class="bg-gray-50 rounded p-3">
                                <div class="text-xs text-gray-500">منبع</div>
                                <div class="font-medium text-gray-800">{{ $extraSource ?? '-' }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                @if ($intent === 'confirm_contact')
                    <button type="button"
                            class="btn btn-primary"
                            onclick="submitUseExistingContact({{ (int) ($contactInfo['id'] ?? 0) }})">
                        ادامه با همین مخاطب
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeDuplicateMobileModal()">انصراف</button>
                @else
                    @if (!empty($alert['show_url']))
                        <a href="{{ $alert['show_url'] }}" class="btn btn-primary">مشاهده مورد قبلی</a>
                    @endif
                    <button type="button" class="btn btn-secondary" onclick="closeDuplicateMobileModal()">انصراف</button>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
        function openDuplicateMobileModal() {
            const modal = document.getElementById('duplicateMobileModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDuplicateMobileModal() {
            const modal = document.getElementById('duplicateMobileModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function submitUseExistingContact(contactId) {
            if (!contactId) return;
            const form = document.getElementById('leadForm');
            if (!form) return;

            let confirmInput = form.querySelector('input[name="confirm_use_existing_contact"]');
            if (!confirmInput) {
                confirmInput = document.createElement('input');
                confirmInput.type = 'hidden';
                confirmInput.name = 'confirm_use_existing_contact';
                form.appendChild(confirmInput);
            }
            confirmInput.value = '1';

            let contactInput = form.querySelector('input[name="existing_contact_id"]');
            if (!contactInput) {
                contactInput = document.createElement('input');
                contactInput.type = 'hidden';
                contactInput.name = 'existing_contact_id';
                form.appendChild(contactInput);
            }
            contactInput.value = String(contactId);

            form.submit();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', openDuplicateMobileModal);
        } else {
            openDuplicateMobileModal();
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDuplicateMobileModal();
        });

        const modalEl = document.getElementById('duplicateMobileModal');
        if (modalEl) {
            modalEl.addEventListener('click', function (e) {
                if (e.target === modalEl) closeDuplicateMobileModal();
            });
        }
        </script>
    @endpush
@endif

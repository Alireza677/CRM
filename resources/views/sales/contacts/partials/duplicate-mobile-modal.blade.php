@php
    $alert = session('duplicate_mobile_alert');
    $contactInfo = data_get($alert, 'meta.contact', []);
@endphp

@if (session()->has('duplicate_mobile_alert'))
    <div id="duplicateMobileModal"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center px-4">
        <div class="bg-white w-full max-w-xl rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">مخاطب تکراری</h3>
                <button type="button"
                        onclick="closeDuplicateMobileModal()"
                        class="text-gray-500 hover:text-red-500 text-2xl leading-none">&times;</button>
            </div>

            <div class="text-sm text-gray-700 space-y-3">
                <p>
                    این شماره موبایل قبلاً برای یک مخاطب ثبت شده است.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded p-3">
                        <div class="text-xs text-gray-500">نام مخاطب</div>
                        <div class="font-medium text-gray-800">{{ $contactInfo['name'] ?? ($alert['title'] ?? '-') }}</div>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <div class="text-xs text-gray-500">موبایل</div>
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

            <div class="flex justify-end space-x-3 space-x-reverse mt-6">
                @if (!empty($contactInfo['show_url']) || !empty($alert['show_url']))
                    <a href="{{ $contactInfo['show_url'] ?? $alert['show_url'] }}" class="btn btn-primary">مشاهده مخاطب</a>
                @endif
                @if (!empty($contactInfo['edit_url']))
                    <a href="{{ $contactInfo['edit_url'] }}" class="btn btn-secondary">ویرایش مخاطب</a>
                @endif
                <button type="button" class="btn btn-secondary" onclick="closeDuplicateMobileModal()">بستن</button>
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

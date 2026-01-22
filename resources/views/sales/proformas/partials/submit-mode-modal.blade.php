<div id="submitModeModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" data-modal-root aria-labelledby="submitModeModalLabel" aria-hidden="true" aria-modal="true" role="dialog" hidden>
    <div class="w-full max-w-2xl mx-4">
        <div class="bg-white rounded-lg shadow-lg text-end">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h5 class="modal-title" id="submitModeModalLabel">نحوه ثبت پیش‌فاکتور</h5>
                <button type="button" class="btn-close text-gray-500 hover:text-gray-700 text-xl leading-none" data-modal-close aria-label="بستن">&times;</button>
            </div>
            <div class="modal-body space-y-2 px-4 py-3">
                <p class="mb-2">انتخاب کنید می‌خواهید این پیش‌فاکتور به صورت پیش‌نویس ذخیره شود یا برای تأیید ارسال گردد.</p>
                <ul class="list-unstyled text-sm text-gray-600 space-y-1">
                    <li>• پیش‌نویس: وضعیت در حالت «draft» باقی می‌ماند و گردش تأیید شروع نمی‌شود.</li>
                    <li>• ارسال برای تأیید: مرحله به «send_for_approval» می‌رود و فرآیند تأیید فعال می‌شود.</li>
                </ul>
            </div>
            <div class="modal-footer flex justify-between px-4 py-3 border-t">
                <button type="button" class="btn btn-outline-secondary" data-modal-close>انصراف</button>
                <div class="space-x-2 space-x-reverse">
                    <button type="button" class="btn btn-secondary" id="submit-as-draft">ذخیره به‌عنوان پیش‌نویس</button>
                    <button type="button" class="btn btn-primary" id="submit-send-for-approval">ارسال برای تأیید</button>
                </div>
            </div>
        </div>
    </div>
</div>

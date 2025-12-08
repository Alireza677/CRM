<div class="modal fade" id="submitModeModal" tabindex="-1" aria-labelledby="submitModeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-end">
            <div class="modal-header">
                <h5 class="modal-title" id="submitModeModalLabel">نحوه ثبت پیش‌فاکتور</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body space-y-2">
                <p class="mb-2">انتخاب کنید می‌خواهید این پیش‌فاکتور به صورت پیش‌نویس ذخیره شود یا برای تأیید ارسال گردد.</p>
                <ul class="list-unstyled text-sm text-gray-600 space-y-1">
                    <li>• پیش‌نویس: وضعیت در حالت «draft» باقی می‌ماند و گردش تأیید شروع نمی‌شود.</li>
                    <li>• ارسال برای تأیید: مرحله به «send_for_approval» می‌رود و فرآیند تأیید فعال می‌شود.</li>
                </ul>
            </div>
            <div class="modal-footer flex justify-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">انصراف</button>
                <div class="space-x-2 space-x-reverse">
                    <button type="button" class="btn btn-secondary" id="submit-as-draft">ذخیره به‌عنوان پیش‌نویس</button>
                    <button type="button" class="btn btn-primary" id="submit-send-for-approval">ارسال برای تأیید</button>
                </div>
            </div>
        </div>
    </div>
</div>

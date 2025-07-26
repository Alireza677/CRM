<div class="p-4" dir="rtl">

    <h3 class="text-lg font-semibold mb-4">اطلاعات کامل سرنخ</h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">

        <div><strong>پیشوند:</strong> {{ $lead->prefix ?? '-' }}</div>
        <div><strong>نام و نام خانوادگی :</strong> {{ $lead->full_name ?? '-' }}</div>


        <div><strong>سازمان:</strong> {{ $lead->company }}</div>
        <div><strong>ایمیل:</strong> {{ $lead->email }}</div>
        <div><strong>موبایل:</strong> {{ $lead->mobile }}</div>

        <div><strong>تلفن:</strong> {{ $lead->phone }}</div>
        <div><strong>صنعت:</strong> {{ $lead->industry }}</div>


        <div><strong>استان:</strong> {{ $lead->state }}</div>
        <div><strong>شهر:</strong> {{ $lead->city }}</div>

        <div><strong>یادداشت‌ها:</strong> {{ $lead->notes }}</div>
        <div><strong>توضیحات:</strong> {{ $lead->description }}</div>

        <div><strong>تاریخ ثبت:</strong>
            {{ \App\Helpers\DateHelper::toJalali($lead->lead_date) ?? '—' }}
        </div>
        <div><strong>تاریخ پیگیری بعدی:</strong>
            {{ \App\Helpers\DateHelper::toJalali($lead->next_follow_up_date) ?? '—' }}
        </div>

        <div><strong>منبع سرنخ:</strong> {{ \App\Helpers\FormOptionsHelper::getLeadSourceLabel($lead->lead_source) ?? '-' }}</div>

        <div><strong>وضعیت:</strong> {{ \App\Helpers\FormOptionsHelper::getLeadStatusLabel($lead->lead_status) }}</div>
        <div><strong>نوع مشتری:</strong> {{ $lead->customer_type }}</div>
        <div><strong>ارجاع به :</strong> {{ optional($lead->assignedUser)->name ?? '-' }}</div>


    </div>
</div>

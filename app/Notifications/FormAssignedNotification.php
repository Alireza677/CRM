<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Proforma;
use App\Models\Opportunity;
use App\Models\Lead;

class FormAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $form;
    protected $assignedBy;
    protected $customMessage;
    protected $customTitle;

    public function __construct($form, $assignedBy = null, $customMessage = null, $customTitle = null)
    {
        $this->form          = $form;
        $this->assignedBy    = $assignedBy;
        $this->customMessage = $customMessage;
        $this->customTitle   = $customTitle;

        // (اختیاری) صف مخصوص اعلان‌ها
        $this->onQueue('notifications');
    }

    public function via($notifiable): array
    {
        $channels = ['database'];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toDatabase($notifiable): array
    {

        [$routeName, $label, $param] = $this->routeMeta();
        $formTitle = $this->formTitle();
        
        return [
            'message'      => $this->customMessage ?? "{$label} «{$formTitle}» به شما ارجاع داده شد.",
            'form_id'      => (string) $this->form->getKey(),
            'form_type'    => get_class($this->form),
            'assigned_by'  => $this->assignedBy ? $this->assignedBy->name : null,
            'title'        => $this->customTitle ?? null,
            'route_name'   => $routeName,
            'url'          => $routeName ? route($routeName, [$param => $this->form->getRouteKey()]) : null,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        [$routeName, $label] = $this->routeMeta();
        $formTitle = $this->formTitle();
        $url       = $routeName ? route($routeName, $this->form) : url('/');

        $subject = $this->customTitle ?: 'مورد جدید به شما ارجاع شد';
        $intro   = $this->customMessage ?: "یک {$label} جدید برای شما ارجاع شد:";

        return (new MailMessage)
            ->subject($subject)
            ->greeting('سلام ' . ($notifiable->name ?? ''))
            ->line($intro)
            ->line("«{$formTitle}»")
            ->action('مشاهده  ', $url)
            ->line('این ایمیل به صورت خودکار ارسال شده است.');
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * تعیین نام روت و لیبل بر اساس نوع مدل (ایمن‌تر از class_basename).
     * @return array{0:?string,1:string} [routeName, label]
     */
    protected function routeMeta(): array
    {
        if ($this->form instanceof \App\Models\Proforma) {
            return ['sales.proformas.show', 'پیش‌فاکتور', 'proforma'];
        }

        if ($this->form instanceof \App\Models\Opportunity) {
            return ['sales.opportunities.show', 'فرصت فروش', 'opportunity'];
        }

        // پوشش هر دو کلاس ممکن برای سرنخ
        if (
            $this->form instanceof \App\Models\Lead ||
            $this->form instanceof \App\Models\SalesLead
        ) {
            return ['sales.leads.show', 'سرنخ', 'lead'];
        }

        // پیش‌فرض: «مورد» (دیگه «فرم» نمایش داده نشه)
        return [null, 'مورد', 'id'];
    }


    protected function formTitle(): string
    {
        if (method_exists($this->form, 'getNotificationTitle')) {
            return (string) $this->form->getNotificationTitle();
        }

        foreach (['subject', 'name', 'title'] as $key) {
            $v = $this->form->{$key} ?? null;
            if (!empty($v)) return (string) $v;
        }
        return 'بدون عنوان';
    }
}

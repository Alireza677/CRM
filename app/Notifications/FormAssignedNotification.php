<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FormAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $form;
    protected $assignedBy;
    protected $customMessage;
    protected $customTitle;

    /**
     * اعلان ارجاع فرم یا اعلان سفارشی.
     *
     * @param  mixed  $form  // مدل Proforma یا Opportunity یا Lead
     * @param  \App\Models\User|null  $assignedBy
     * @param  string|null  $customMessage
     * @param  string|null  $customTitle
     */
    public function __construct($form, $assignedBy = null, $customMessage = null, $customTitle = null)
    {
        $this->form          = $form;
        $this->assignedBy    = $assignedBy;
        $this->customMessage = $customMessage;
        $this->customTitle   = $customTitle;
    }

    public function via($notifiable): array
    {
        // اگر کاربر ایمیل ندارد فقط دیتابیس
        $channels = ['database'];
        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }
        return $channels;
    }

    public function toDatabase($notifiable): array
    {
        $modelName = class_basename($this->form); // مثل Proforma
        $label     = $this->modelLabel($modelName);
        $formTitle = $this->formTitle();
        $url       = $this->generateFormUrl($modelName, $this->form->id);

        return [
            'message'     => $this->customMessage ?? "{$label} «{$formTitle}» به شما ارجاع داده شد.",
            'form_id'     => $this->form->id,
            'assigned_by' => $this->assignedBy ? $this->assignedBy->name : null,
            'title'       => $this->customTitle ?? null,
            'url'         => $url,
            'model'       => $modelName,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $modelName = class_basename($this->form);
        $label     = $this->modelLabel($modelName);
        $formTitle = $this->formTitle();
        $url       = $this->generateFormUrl($modelName, $this->form->id);

        $subject = $this->customTitle
            ? $this->customTitle
            : "مورد جدید به شما ارجاع شد";

        $introLine = $this->customMessage
            ? $this->customMessage
            : "یک {$label} جدید برای شما ارجاع شد:";

        return (new MailMessage)
            ->subject($subject)
            ->greeting('سلام ' . ($notifiable->name ?? ''))
            ->line($introLine)
            ->line("«{$formTitle}»")
            ->action('مشاهده در CRM', $url)
            ->line('این ایمیل به صورت خودکار ارسال شده است.');
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    protected function generateFormUrl($modelName, $id): string
    {
        switch ($modelName) {
            case 'Proforma':
                return route('sales.proformas.show', $id);
            case 'Opportunity':
                return route('sales.opportunities.show', $id);
            case 'Lead':
            case 'SalesLead': // اگر نام مدل متفاوت است
                return route('sales.leads.show', $id);
            default:
                return url('/');
        }
    }

    protected function modelLabel(string $modelName): string
    {
        $labels = [
            'Proforma'    => 'پیش‌فاکتور',
            'Opportunity' => 'فرصت فروش',
            'Lead'        => 'سرنخ',
            'SalesLead'   => 'سرنخ',
        ];
        return $labels[$modelName] ?? 'فرم';
    }

    protected function formTitle(): string
    {
        if (method_exists($this->form, 'getNotificationTitle')) {
            return (string) $this->form->getNotificationTitle();
        }

        // ترتیب فیلدهای متداول عنوان
        foreach (['subject', 'name', 'title'] as $key) {
            if (!empty($this->form->{$key})) {
                return (string) $this->form->{$key};
            }
        }
        return 'بدون عنوان';
    }
}

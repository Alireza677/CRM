<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class FormApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $form;
    protected $sentBy;

    public function __construct($form, $sentBy)
    {
        $this->form = $form;
        $this->sentBy = $sentBy;
    }

    public function via($notifiable)
    {
        return ['database']; // اگه ایمیل یا SMS هم خواستی، اضافه کن
    }

    public function toDatabase($notifiable)
    {
        $modelName = class_basename($this->form);
        $labels = [
            'Proforma' => 'پیش‌فاکتور',
            'Opportunity' => 'فرصت فروش',
        ];
        $label = $labels[$modelName] ?? 'فرم';

        $formTitle = method_exists($this->form, 'getNotificationTitle')
            ? $this->form->getNotificationTitle()
            : ($this->form->subject ?? $this->form->name ?? $this->form->title ?? 'بدون عنوان');

        return [
            'message' => "{$label} «{$formTitle}» برای تایید شما ارسال شده است.",
            'form_id' => $this->form->id,
            'form_type' => $modelName,
            'sent_by' => $this->sentBy->name,
            'url' => route('sales.proformas.show', $this->form->id), // اگر نوع‌های دیگر داری شرط بگذار
        ];
    }
}

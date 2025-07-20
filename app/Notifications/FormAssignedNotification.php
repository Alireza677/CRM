<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

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
     * @param  mixed  $form  // مدل Proforma یا Opportunity
     * @param  \App\Models\User|null  $assignedBy  // در اعلان‌های ارجاع دستی استفاده می‌شود
     * @param  string|null  $customMessage  // در اعلان‌های اتوماسیون استفاده می‌شود
     * @param  string|null  $customTitle
     */
    public function __construct($form, $assignedBy = null, $customMessage = null, $customTitle = null)
    {
        $this->form = $form;
        $this->assignedBy = $assignedBy;
        $this->customMessage = $customMessage;
        $this->customTitle = $customTitle;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $modelName = class_basename($this->form); // مثلاً Proforma
        $labels = [
            'Proforma' => 'پیش‌فاکتور',
            'Opportunity' => 'فرصت فروش',
        ];
        $label = $labels[$modelName] ?? 'فرم';

        $formTitle = method_exists($this->form, 'getNotificationTitle')
            ? $this->form->getNotificationTitle()
            : ($this->form->subject ?? $this->form->name ?? $this->form->title ?? 'بدون عنوان');

        return [
            'message' => $this->customMessage
                ?? "{$label} \"{$formTitle}\" به شما ارجاع داده شد.",
            'form_id' => $this->form->id,
            'assigned_by' => $this->assignedBy ? $this->assignedBy->name : null,
            'title' => $this->customTitle ?? null,
            'url' => $this->generateFormUrl($modelName, $this->form->id),
        ];
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }

    protected function generateFormUrl($modelName, $id)
    {
        switch ($modelName) {
            case 'Proforma':
                return route('sales.proformas.show', $id);
            case 'Opportunity':
                return route('sales.opportunities.show', $id);
            default:
                return url('/');
        }
    }
}

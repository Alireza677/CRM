<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Proforma;
use App\Models\Opportunity;
use App\Models\Lead;
use App\Mail\RoutedNotificationMail;

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

        $url       = $routeName ? route($routeName, [$param => $this->form->getRouteKey()]) : null;

        $module    = $this->resolveModule();

        $event     = 'assigned.changed';



        try {

            $ctx = $this->buildTemplateContext($notifiable, $url, $formTitle);

            $tpl = \App\Support\NotificationTemplateResolver::resolve($module, $event, 'database', $ctx);

            $subject = trim((string) ($tpl['subject'] ?? ''));

            $body    = trim((string) ($tpl['body'] ?? ''));

            if ($subject !== '' || $body !== '') {

                return array_filter([

                    'module'     => $module,

                    'event'      => $event,

                    'title'      => $subject ?: null,

                    'message'    => $body !== '' ? $body : $subject,

                    'body'       => $body ?: null,

                    'form_id'    => (string) $this->form->getKey(),

                    'form_type'  => get_class($this->form),

                    'assigned_by'=> $this->assignedBy ? $this->assignedBy->name : null,

                    'route_name' => $routeName,

                    'url'        => $url,

                ]);

            }

        } catch (\Throwable $e) {

            // fall through to legacy payload

        }



        return [

            'message'      => $this->customMessage ?? "{$label} A?{$formTitle}A? O"U? O'U.O OO?O?OO1 O_OO_U? O'O_.",

            'form_id'      => (string) $this->form->getKey(),

            'form_type'    => get_class($this->form),

            'assigned_by'  => $this->assignedBy ? $this->assignedBy->name : null,

            'title'        => $this->customTitle ?? null,

            'route_name'   => $routeName,

            'url'          => $url,

        ];

    }



    public function toMail($notifiable): MailMessage
    {
        [$routeName, $label] = $this->routeMeta();
        $formTitle = $this->formTitle();
        $url       = $routeName ? route($routeName, $this->form) : url('/');

        $subject = $this->customTitle ?: 'مورد جدید به شما ارجاع شد';
        $intro   = $this->customMessage ?: "یک {$label} جدید برای شما ارجاع شد:";

        // Try DB/email template for leads/opportunities/proformas assigned.changed first
        try {
            $module = $this->resolveModule();
            $event  = 'assigned.changed';
            $ctx = $this->buildTemplateContext($notifiable, $url, $formTitle);
            $tpl = \App\Support\NotificationTemplateResolver::resolve($module, $event, 'email', $ctx);
            $subj = trim((string) ($tpl['subject'] ?? ''));
            $body = trim((string) ($tpl['body'] ?? ''));
            if ($subj !== '' || $body !== '') {
                /** @var \Illuminate\Mail\Mailable $m */
                $m = new RoutedNotificationMail($subj, $body, $url);
                return $m;
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }

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


    protected function resolveModule(): string
    {
        if ($this->form instanceof \App\Models\Opportunity) {
            return 'opportunities';
        }
        if ($this->form instanceof \App\Models\Proforma) {
            return 'proformas';
        }
        return 'leads';
    }

    protected function buildTemplateContext($notifiable, ?string $url, ?string $formTitle = null): array
    {
        $title   = $formTitle ?? $this->formTitle();
        $oldName = (string) ($this->assignedBy?->name ?? '');
        $newName = (string) ($notifiable->name ?? '');

        return [
            'model'        => $this->form,
            'form_title'   => $title,
            'lead_name'    => $title,
            'old_user'     => $oldName,
            'new_user'     => $newName,
            'old_assignee' => $oldName,
            'new_assignee' => $newName,
            'sender_name'  => $oldName,
            'actor'        => $this->assignedBy,
            'url'          => $url ?: url('/'),
        ];
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

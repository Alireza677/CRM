<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Proforma;
use App\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class FormApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $formType,   // e.g. 'Proforma' | 'Opportunity'
        public int    $formId,     // e.g. 346
        public int    $sentById    // e.g. auth()->id()
    ) {}

    // سازنده‌ی کمکی برای راحتی کال‌سایت‌ها
    public static function fromModel(object $form, int $sentById): self
    {
        return new self(class_basename($form), (int) $form->id, $sentById);
    }

    public function via($notifiable): array
    {
        return ['database', 'mail']; // ← اضافه شد
    }

    public function toDatabase($notifiable): array
    {
        $label = $this->labelFor($this->formType);
        [$title, $url] = $this->resolveTitleAndUrl($this->formType, $this->formId);

        $sender = User::query()->find($this->sentById);
        $senderName = $sender?->name ?? 'سیستم';

        return [
            'message'    => "{$label} «{$title}» برای تایید شما ارسال شده است.",
            'form_id'    => $this->formId,
            'form_type'  => $this->formType,
            'sent_by'    => $senderName,
            'sent_by_id' => $this->sentById,
            'url'        => $url,
        ];
    }

    protected function labelFor(string $formType): string
    {
        return match ($formType) {
            'Proforma'      => 'پیش‌فاکتور',
            'Opportunity'   => 'فرصت فروش',
            'PurchaseOrder' => 'سفارش خرید',
            default         => 'فرم',
        };
    }
    public function toMail($notifiable): MailMessage
    {
        $label = $this->labelFor($this->formType);              // پیش‌فاکتور | فرصت فروش | ...
        [$title, $url] = $this->resolveTitleAndUrl($this->formType, $this->formId);

        $sender = User::query()->find($this->sentById);
        $senderName = $sender?->name ?? 'سیستم';

        return (new MailMessage)
            ->subject("درخواست تأیید {$label}")
            ->greeting('سلام ' . ($notifiable->name ?? ''))
            ->line("{$label} زیر برای تأیید شما ارسال شده است:")
            ->line("«{$title}»")
            ->line("ارسال‌کننده: {$senderName}")
            ->action('مشاهده در CRM', $url ?: url('/'))
            ->line('این ایمیل به صورت خودکار ارسال شده است.');
    }

    /**
     * عنوان و لینک نمایش را بر اساس نوع فرم برمی‌گرداند.
     */
    protected function resolveTitleAndUrl(string $formType, int $id): array
    {
        if ($formType === 'Proforma') {
            // قبلاً: select('id','subject','title','name')
            $m = \App\Models\Proforma::query()->find($id);
            $title = $this->pickTitle($m);
            $url   = route('sales.proformas.show', $id);
            return [$title, $url];
        }

        if ($formType === 'Opportunity') {
            // قبلاً: select('id','name','title','subject')
            $m = \App\Models\Opportunity::query()->find($id);
            $title = $this->pickTitle($m);
            $url   = route('sales.opportunities.show', $id);
            return [$title, $url];
        }

        if ($formType === 'PurchaseOrder') {
            $m = \App\Models\PurchaseOrder::query()->find($id);
            $title = $this->pickTitle($m);
            $url   = route('inventory.purchase-orders.show', $id);
            return [$title, $url];
        }

        return ['بدون عنوان', url('/')];
    }


    protected function pickTitle($model): string
    {
        if (!$model) return 'بدون عنوان';

        if (method_exists($model, 'getNotificationTitle')) {
            return (string) $model->getNotificationTitle();
        }

        foreach (['subject','name','title'] as $f) {
            if (!empty($model->{$f})) return (string) $model->{$f};
        }
        return 'بدون عنوان';
    }
}

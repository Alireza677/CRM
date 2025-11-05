<?php

namespace App\Notifications;

use App\Models\Opportunity;
use App\Models\Proforma;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FormApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $formType,
        public int $formId,
        public int $sentById
    ) {}

    public static function fromModel(object $form, int $sentById): self
    {
        return new self(class_basename($form), (int) $form->id, $sentById);
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        $label = $this->labelFor($this->formType);
        [$title, $url] = $this->resolveTitleAndUrl($this->formType, $this->formId);

        $sender = User::query()->find($this->sentById);
        $senderName = $sender?->name ?? '---';

        // Prefer template-based content if available (database channel)
        if (class_exists(\App\Support\NotificationTemplateResolver::class)) {
            [$module, $event] = $this->resolveModuleEvent();
            $ctx = [
                'form_title' => $title ?: '---',
                'sender_name'=> $senderName,
                'url'        => $url,
                'actor.name' => $senderName,
            ];
            // Try database template first, then fall back to email template
            $tpl = \App\Support\NotificationTemplateResolver::resolve($module, $event, 'database', $ctx)
                ?? \App\Support\NotificationTemplateResolver::resolve($module, $event, 'email', $ctx)
                ?? [];
            if (!empty($tpl['subject']) || !empty($tpl['body'])) {
                return [
                    'module'      => $module,
                    'event'       => $event,
                    'title'       => $tpl['subject'] ?? null,
                    'body'        => $tpl['body'] ?? null,
                    'message'     => $tpl['body'] ?? ($tpl['subject'] ?? null),
                    'form_id'     => $this->formId,
                    'form_type'   => $this->formType,
                    'sent_by'     => $senderName,
                    'sent_by_id'  => $this->sentById,
                    'actor_id'    => optional(auth()->user())->id ?? null,
                    'actor_name'  => optional(auth()->user())->name ?? null,
                    'url'         => $url,
                ];
            }
        }

        return [
            'message'    => "{$label} با عنوان {$title} برای بررسی ارسال شد.",
            'form_id'    => $this->formId,
            'form_type'  => $this->formType,
            'sent_by'    => $senderName,
            'sent_by_id' => $this->sentById,
            'url'        => $url,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
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
        $label = $this->labelFor($this->formType);
        [$title, $url] = $this->resolveTitleAndUrl($this->formType, $this->formId);

        $sender = User::query()->find($this->sentById);
        $senderName = $sender?->name ?? '---';

        $model = null;
        if ($this->formType === 'Proforma') {
            $model = Proforma::query()->find($this->formId);
        } elseif ($this->formType === 'Opportunity') {
            $model = Opportunity::query()->find($this->formId);
        } elseif ($this->formType === 'PurchaseOrder') {
            $model = PurchaseOrder::query()->find($this->formId);
        }

        $get = function (array $keys, $default = '---') use ($model) {
            if (! $model) {
                return $default;
            }
            foreach ($keys as $k) {
                if (isset($model->{$k}) && $model->{$k} !== null && $model->{$k} !== '') {
                    $val = $model->{$k};
                    if (is_object($val) && isset($val->name)) {
                        return (string) $val->name;
                    }
                    return (string) $val;
                }
            }
            return $default;
        };

        $resolveUserName = function ($value) {
            if (is_numeric($value)) {
                $u = User::query()->find((int) $value);
                return $u?->name ?? null;
            }
            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
            return null;
        };

        $formCode = $get(['code', 'form_code', 'number', 'ref_number', 'po_number', 'proforma_number']);
        $recipientName = $notifiable->name ?? '---';

        $referrerName = '---';
        if ($model) {
            foreach ([
                'referrer_name', 'referrer', 'referred_by_name', 'referred_by',
                'requested_by_name', 'requested_by', 'created_by_name', 'created_by',
            ] as $key) {
                if (isset($model->{$key}) && $model->{$key} !== null && $model->{$key} !== '') {
                    $name = $resolveUserName($model->{$key});
                    if ($name) { $referrerName = $name; break; }
                }
            }
        }

        $stage = $get(['stage', 'current_stage', 'status', 'state']);

        $organization = $get(['organization_name', 'organization', 'account_name', 'company', 'company_name', 'customer', 'customer_name', 'supplier', 'supplier_name']);
        if ($organization === '---' && $model && isset($model->supplier_id) && $model->supplier_id) {
            $organization = optional(Supplier::query()->find($model->supplier_id))->name ?? '---';
        }

        $contactName = $get(['contact_name', 'contact', 'person_name', 'contact_full_name']);
        $priority = $get(['priority', 'priority_label', 'priority_text']);
        $comment = $get(['comment', 'comments', 'description', 'note', 'notes']);

        $creationDate = '---';
        if ($model && isset($model->created_at) && $model->created_at) {
            try {
                $carbon = Carbon::parse($model->created_at);
                if (class_exists(\Morilog\Jalali\Jalalian::class)) {
                    $creationDate = \Morilog\Jalali\Jalalian::fromCarbon($carbon)->format('Y/m/d H:i');
                } else {
                    $creationDate = $carbon->format('Y-m-d H:i');
                }
            } catch (\Throwable $e) {
                $creationDate = (string) $model->created_at;
            }
        }

        $title = $title ?: '---';

        return (new MailMessage)
            ->subject("درخواست تأیید {$label}")
            ->greeting('سلام' . ($recipientName !== '---' ? ' ' . $recipientName : ''))
            ->line("درخواست تأیید برای {$label} ثبت شده است.")
            ->line("نوع فرم: {$label}")
            ->line("عنوان: {$title}")
            ->line("کد فرم: {$formCode}")
            ->line("ارسال‌کننده: {$senderName}")
            ->line("دریافت‌کننده: {$recipientName}")
            ->line("ارجاع‌دهنده: {$referrerName}")
            ->line("مرحله فعلی: {$stage}")
            ->line("سازمان: {$organization}")
            ->line("شخص تماس: {$contactName}")
            ->line("اولویت: {$priority}")
            ->line("توضیحات: {$comment}")
            ->line("تاریخ ایجاد: {$creationDate}")
            ->action('مشاهده فرم در CRM', $url ?: url('/'))
            ->line('لطفاً پس از بررسی اقدام لازم را انجام دهید.');
    }

    protected function resolveTitleAndUrl(string $formType, int $id): array
    {
        if ($formType === 'Proforma') {
            $m = Proforma::query()->find($id);
            $title = $this->pickTitle($m);
            $url   = route('sales.proformas.show', $id);
            return [$title, $url];
        }

        if ($formType === 'Opportunity') {
            $m = Opportunity::query()->find($id);
            $title = $this->pickTitle($m);
            $url   = route('sales.opportunities.show', $id);
            return [$title, $url];
        }

        if ($formType === 'PurchaseOrder') {
            $m = PurchaseOrder::query()->find($id);
            $title = $this->pickTitle($m);
            $url   = route('inventory.purchase-orders.show', $id);
            return [$title, $url];
        }

        return ['---', url('/')];
    }

    protected function pickTitle($model): string
    {
        if (! $model) {
            return '---';
        }

        if (method_exists($model, 'getNotificationTitle')) {
            return (string) $model->getNotificationTitle();
        }

        foreach (['subject', 'name', 'title'] as $f) {
            if (! empty($model->{$f})) {
                return (string) $model->{$f};
            }
        }
        return '---';
    }

    private function resolveModuleEvent(): array
    {
        switch ($this->formType) {
            case 'Proforma':
                return ['proformas', 'approval.sent'];
            case 'PurchaseOrder':
                return ['purchase_orders', 'status.changed'];
            case 'Opportunity':
            default:
                return ['proformas', 'approval.sent'];
        }
    }
}

<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Mail\RoutedNotificationMail;

class PurchaseOrderReadyForDeliveryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $purchaseOrderId,
        public int $sentById
    ) {}

    public static function fromModel(PurchaseOrder $po, int $sentById): self
    {
        return new self((int) $po->id, $sentById);
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

        public function toDatabase($notifiable): array
    {
        $po = PurchaseOrder::query()->with('requestedByUser')->find($this->purchaseOrderId);
        $title = $po?->subject ?: ($po?->po_number ?: '---');
        $url = route('inventory.purchase-orders.show', $this->purchaseOrderId);

        $sender = User::query()->find($this->sentById);
        $senderName = $sender?->name ?? '---';

        try {
            $ctx = $this->buildTemplateContext($notifiable, $po, $sender, $url, (string) $title);
            $tpl = \App\Support\NotificationTemplateResolver::resolve('purchase_orders', 'ready_for_delivery', 'database', $ctx);
            $subject = trim((string) ($tpl['subject'] ?? ''));
            $body    = trim((string) ($tpl['body'] ?? ''));
            if ($subject !== '' || $body !== '') {
                return [
                    'module'      => 'purchase_orders',
                    'event'       => 'ready_for_delivery',
                    'title'       => $subject ?: null,
                    'body'        => $body ?: null,
                    'message'     => $body !== '' ? $body : $subject,
                    'form_id'     => $this->purchaseOrderId,
                    'form_type'   => 'PurchaseOrder',
                    'sent_by'     => $senderName,
                    'sent_by_id'  => $this->sentById,
                    'url'         => $url,
                ];
            }
        } catch (\Throwable $e) {
            // fallback to legacy payload
        }

        return [
            'message'    => 'O3U?OO?O' OrO?UOO_ O'U.O O?OUOUOO_ O'O_. U,O?U?OU< U^OO1UOO? O?O O"U? A?O?O-U^UOU, O"U? OU+O"OO?A? O?O?UOUOO? O_U?UOO_.',
            'form_id'    => $this->purchaseOrderId,
            'form_type'  => 'PurchaseOrder',
            'sent_by'    => $senderName,
            'sent_by_id' => $this->sentById,
            'title'      => (string) $title,
            'url'        => $url,
        ];
    }



    public function toMail($notifiable): MailMessage
    {
        $po = PurchaseOrder::query()->with('requestedByUser')->find($this->purchaseOrderId);
        $title = $po?->subject ?: ($po?->po_number ?: '---');
        $url = route('inventory.purchase-orders.show', $this->purchaseOrderId);
        $recipientName = $notifiable->name ?? '';

        $sender = User::query()->find($this->sentById);
        $senderName = $sender?->name ?? '---';

        // Try DB/email template first: purchase_orders.ready_for_delivery
        try {
            $ctx = $this->buildTemplateContext($notifiable, $po, $sender, $url, (string) $title);
            $tpl = \App\Support\NotificationTemplateResolver::resolve('purchase_orders', 'ready_for_delivery', 'email', $ctx);
            $subj = trim((string) ($tpl['subject'] ?? ''));
            $body = trim((string) ($tpl['body'] ?? ''));
            if ($subj !== '' || $body !== '') {
                /** @var \Illuminate\Mail\Mailable $m */
                $m = new RoutedNotificationMail($subj, $body, $url);
                return $m;
            }
        } catch (\Throwable $e) {
            // ignore and fallback below
        }

        return (new MailMessage)
            ->subject('سفارش شما تأیید شد')
            ->greeting('سلام' . ($recipientName ? ' ' . $recipientName : ''))
            ->line('سفارش خرید شما تایید شد.')
            ->line('لطفاً وضعیت سفارش را به «تحویل به انبار» تغییر دهید.')
            ->line('عنوان سفارش: ' . (string) $title)
            ->line('ارسال کننده: ' . $senderName)
            ->action('مشاهده سفارش در CRM', $url);
    }

    protected function buildTemplateContext($notifiable, ?PurchaseOrder $po, ?User $sender, string $url, string $title): array
    {
        $requester = (string) optional($po?->requestedByUser)->name;
        $recipientName = (string) ($notifiable->name ?? '');

        return [
            'purchase_order' => $po,
            'po'             => $po,
            'po_number'      => (string) ($po?->po_number ?? ('#'.(string)($po?->id ?? ''))),
            'po_subject'     => (string) ($po?->subject ?? ($po?->po_number ?? ('#'.(string)($po?->id ?? '')))),
            'requester_name' => $requester !== '' ? $requester : $recipientName,
            'form_title'     => $title,
            'sender_name'    => (string) ($sender?->name ?? ''),
            'actor'          => $sender,
            'url'            => $url,
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        $po = PurchaseOrder::query()->find($this->purchaseOrderId);
        $title = $po?->subject ?: ($po?->po_number ?: '---');
        $url = route('inventory.purchase-orders.show', $this->purchaseOrderId);

        $sender = User::query()->find($this->sentById);
        $senderName = $sender?->name ?? '---';

        return [
            'message'    => 'سفارش خرید شما تایید شد. لطفاً وضعیت را به «تحویل به انبار» تغییر دهید.',
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
        $po = PurchaseOrder::query()->find($this->purchaseOrderId);
        $title = $po?->subject ?: ($po?->po_number ?: '---');
        $url = route('inventory.purchase-orders.show', $this->purchaseOrderId);
        $recipientName = $notifiable->name ?? '';

        $sender = User::query()->find($this->sentById);
        $senderName = $sender?->name ?? '---';

        return (new MailMessage)
            ->subject('سفارش شما تأیید شد')
            ->greeting('سلام' . ($recipientName ? ' ' . $recipientName : ''))
            ->line('سفارش خرید شما تایید شد.')
            ->line('لطفاً وضعیت سفارش را به «تحویل به انبار» تغییر دهید.')
            ->line('عنوان سفارش: ' . (string) $title)
            ->line('ارسال کننده: ' . $senderName)
            ->action('مشاهده سفارش در CRM', $url);
    }
}


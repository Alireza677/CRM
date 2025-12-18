<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeadRotationWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $leadId,
        public string $leadTitle,
        public float $hoursLeft
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $url = route('marketing.leads.show', $this->leadId);
        $timeLabel = $this->readableTimeLeft();

        $body = "سرنخ {$this->leadTitle} نیاز به پیگیری دارد. اگر اقدامی انجام ندهید، ارجاع آن در {$timeLabel} آینده تغییر خواهد کرد.";

        return [
            'title'   => 'یادآوری چرخش سرنخ',
            'message' => $body,
            'body'    => $body,
            'url'     => $url,
            'module'  => 'leads',
            'event'   => 'rotation.warning',
            'lead_id' => $this->leadId,
        ];
    }

    protected function readableTimeLeft(): string
    {
        if ($this->hoursLeft < 1) {
            $minutes = max(1, (int) round($this->hoursLeft * 60));
            return $minutes . ' دقیقه';
        }

        $hours = max(1, (int) round($this->hoursLeft));
        return $hours . ' ساعت';
    }
}

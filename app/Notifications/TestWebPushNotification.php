<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class TestWebPushNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['webpush'];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return WebPushMessage::create()
            ->title('تست اعلان PWA')
            ->icon('/icons/icon-192.png')
            ->body('این یک اعلان تست از CRM است.')
            ->action('باز کردن داشبورد', route('dashboard'))
            ->data([
                'url' => route('dashboard'),
            ]);
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomRoutedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $module,
        public string $event,
        public string $subject,
        public string $body,
        public ?string $url = null,
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title'  => $this->subject,
            // compatibility for UIs expecting 'message'
            'message'=> $this->subject,
            'body'   => $this->body,
            'url'    => $this->url,
            'module' => $this->module,
            'event'  => $this->event,
        ];
    }
}

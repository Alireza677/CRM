<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RoutedNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectText,
        public string $bodyText,
        public ?string $actionUrl = null,
    ) {}

    public function build()
    {
        return $this->subject($this->subjectText)
            ->view('emails.routed-notification', [
                'subject' => $this->subjectText,
                'body' => $this->bodyText,
                'url' => $this->actionUrl,
            ]);
    }
}


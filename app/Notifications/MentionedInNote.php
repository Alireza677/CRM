<?php

namespace App\Notifications;

use App\Models\Note;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;

class MentionedInNote extends Notification implements ShouldQueue
{
    use Queueable;

    public $note;

    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    public function via($notifiable)
    {
        return ['database']; // می‌تونی 'mail' هم اضافه کنی اگر بخوای ایمیل بفرستی
    }

    public function toDatabase($notifiable)
    {
        logger("در حال ارسال نوتیفیکیشن به: " . $notifiable->username);

        return [
            'message' => "{$this->note->user->name} شما را در یک یادداشت منشن کرد.",
            'note_id' => $this->note->id,
            'lead_id' => $this->note->lead_id,
            'by_user_id' => $this->note->user_id,
            'by_user_name' => $this->note->user->name,
        ];
    }

    // اگر خواستی ایمیل هم ارسال بشه:
    /*
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line("شما در یک یادداشت منشن شده‌اید.")
            ->action('مشاهده یادداشت', route('marketing.leads.show', $this->note->lead_id))
            ->line('با تشکر!');
    }
    */
}

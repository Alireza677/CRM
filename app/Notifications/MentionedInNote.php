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

    public function __construct($note)  // برای پشتیبانی از انواع مدل‌های Note
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

        $note = $this->note;

        // اطمینان از بارگذاری ریلیشن
        $note->loadMissing('notable');

        $lead = $note->notable;

        return [
            'message' => "{$note->user->name} شما را در یک یادداشت منشن کرد.",
            'note_id' => $note->id,
            'lead_id' => $lead->id,
            'by_user_id' => $note->user_id,
            'by_user_name' => $note->user->name,
            'url' => route('marketing.leads.show', ['lead' => $lead->id]),
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

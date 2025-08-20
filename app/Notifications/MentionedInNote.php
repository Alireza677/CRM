<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use App\Models\Note;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Support\Str;

class MentionedInNote extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var \App\Models\Note
     */
    public Note $note;

    /**
     * @param \App\Models\Note $note
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    /**
     * Notification channels.
     */
    public function via($notifiable): array
    {
        // در صورت نیاز می‌توانید 'mail' یا 'broadcast' را هم اضافه کنید.
        return ['database'];
    }

    /**
     * Database payload.
     */
    public function toDatabase($notifiable): array
    {
        // برای لاگ بهتر (در صورت داشتن name یا email)
        $who = $notifiable->name ?? $notifiable->email ?? $notifiable->id;
        logger()->info("Sending MentionedInNote to: {$who}");

        // اطمینان از بارگذاری روابطِ لازم
        $this->note->loadMissing(['noteable', 'author']); // توجه: نام مرف صحیح: noteable

        $entity = $this->note->noteable;  // می‌تواند Task/Lead/Opportunity یا هر مدل دیگری باشد
        $author = $this->note->author ?? $this->note->user ?? null; // سازگاری با هر دو نام رابطه

        $authorName = $author?->name ?? $author?->email ?? 'یک کاربر';
        $bodyShort  = Str::limit((string) $this->note->body, 140);

        // ساخت URL مناسب بر اساس نوع موجودیت
        $url = $this->buildEntityUrl($entity);

        // پیام نمایشی
        $message = "{$authorName} شما را در یک یادداشت منشن کرد.";

        // اگر موجودیت «تسک» بود، اطلاعات پروژه/تسک هم ضمیمه می‌کنیم
        $extra = [];
        if ($entity instanceof Task) {
            $entity->loadMissing('project');
            $extra = [
                'project_id'   => $entity->project?->id,
                'project_name' => $entity->project?->name,
                'task_id'      => $entity->id,
                'task_title'   => $entity->title,
            ];
        }

        return array_filter([
            'type'            => 'mention',
            'message'         => $message,
            'note_id'         => $this->note->id,
            'noteable_type'   => $this->note->noteable_type,
            'noteable_id'     => $this->note->noteable_id,
            'by_user_id'      => $author?->id,
            'by_user_name'    => $authorName,
            'body'            => $bodyShort,
            'url'             => $url ? ($url . '#note-' . $this->note->id) : null,
        ] + $extra);
    }

    /**
     * اگر بخواهید نوع سفارشی برای دیتابیس ثبت شود.
     */
    public function databaseType($notifiable): string
    {
        return 'mention';
    }

    /**
     * ساخت URL مناسب بر مبنای نوع موجودیت نوت.
     * در صورت لزوم نام روت‌ها را با پروژه‌ی خودتان تنظیم کنید.
     */
    protected function buildEntityUrl($entity): ?string
    {
        if (!$entity) {
            return null;
        }

        // ---- حالت تسک (پروژه‌ها) ----
        if ($entity instanceof Task) {
            // نیازمند route با نام: projects.tasks.show
            // و پارامترها: [project, task]
            $project = $entity->project ?? null;
            if ($project instanceof Project) {
                return route('projects.tasks.show', [$project, $entity]);
            }
            return null;
        }

        // ---- حالت سرنخ فروش (Lead) ----
        // اگر مدل Lead دارید، این بخش را فعال کنید و نام روت را مطابق پروژه خود بگذارید.
        /*
        if ($entity instanceof \App\Models\Lead) {
            return route('marketing.leads.show', ['lead' => $entity->id]);
        }
        */

        // ---- حالت فرصت فروش (Opportunity) ----
        // اگر مدل Opportunity دارید، این بخش را فعال کنید و نام روت را مطابق پروژه خود بگذارید.
        /*
        if ($entity instanceof \App\Models\Opportunity) {
            return route('sales.opportunities.show', ['opportunity' => $entity->id]);
        }
        */

        // برای سایر مدل‌های احتمالی، در صورت لزوم الگوهای بالا را کپی و ویرایش کنید.
        return null;
    }

    /*
    // اگر خواستید ایمیل هم ارسال شود:
    public function toMail($notifiable)
    {
        $this->note->loadMissing('noteable', 'author');
        $entity = $this->note->noteable;
        $url    = $this->buildEntityUrl($entity);

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('شما منشن شده‌اید')
            ->line('شما در یک یادداشت منشن شده‌اید.')
            ->action('مشاهده یادداشت', $url ? ($url . '#note-' . $this->note->id) : url('/'))
            ->line('با تشکر!');
    }
    */
}

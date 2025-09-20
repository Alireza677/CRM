<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use App\Models\Note;
use App\Models\SalesLead;
use App\Models\SalesOpportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;

class MentionedInNote extends Notification implements ShouldQueue
{
    use Queueable;

    public Note $note;

    public function __construct(Note $note)
    {
        $this->note = $note;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail']; // ← اضافه شد
    }

    public function toDatabase($notifiable): array
    {
        $who = $notifiable->name ?? $notifiable->email ?? $notifiable->id;
        logger()->info("Sending MentionedInNote to: {$who}");

        // توجه: در مدل شما اسم رابطه ظاهراً "noteable" است
        $this->note->loadMissing(['noteable', 'author', 'user']);

        $entity = $this->note->noteable;     // می‌تواند Task / SalesLead / SalesOpportunity / Project و ...
        $author = $this->note->author ?? $this->note->user ?? null;

        $authorName = $author?->name ?? $author?->email ?? 'یک کاربر';
        $bodyShort  = Str::limit((string) $this->note->body, 140);

        // URL مقصد براساس نوع موجودیت
        $baseUrl = $this->buildEntityUrl($entity);

        // پیام نمایشی
        $message = "{$authorName} شما را در یک یادداشت منشن کرد.";

        // متادیتای اضافه برای تسک/پروژه
        $extra = [];
        if ($entity instanceof Task) {
            $entity->loadMissing('project');
            $extra = [
                'project_id'   => $entity->project?->id,
                'project_name' => $entity->project?->name,
                'task_id'      => $entity->id,
                'task_title'   => $entity->title,
            ];
        } elseif ($entity instanceof SalesLead) {
            $extra = [
                'lead_id'   => $entity->id,
                'lead_name' => $entity->name ?? null,
            ];
        } elseif ($entity instanceof SalesOpportunity) {
            $extra = [
                'opportunity_id'   => $entity->id,
                'opportunity_title'=> $entity->title ?? null,
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
            'url'             => $baseUrl ? ($baseUrl . '#note-' . $this->note->id) : null, // *** حیاتی ***
        ] + $extra);
    }

    public function toMail($notifiable): MailMessage
    {
        // مطمئن شو رابطه‌ها لود هستند
        $this->note->loadMissing(['noteable', 'author', 'user']);

        $entity = $this->note->noteable;
        [$modelType, $title] = $this->resolveMailMeta($entity);

        $url = $this->buildEntityUrl($entity);
        if ($url) {
            // برای اسکرول دقیق روی نوت
            $url .= '#note-' . $this->note->id;
        }

        return (new MailMessage)
            ->subject('منشن جدید در پروژه‌ها')
            ->greeting('سلام ' . ($notifiable->name ?? ''))
            ->line("شما در {$modelType} منشن شدید:")
            ->line("«{$title}»")
            ->action('مشاهده در CRM', $url ?: url('/'))
            ->line('این ایمیل به صورت خودکار ارسال شده است.');
    }
    protected function resolveMailMeta($entity): array
    {
        $modelType = 'سیستم';
        $title     = (string) \Illuminate\Support\Str::limit((string) $this->note->body, 70);

        if ($entity instanceof \App\Models\Task) {
            $modelType = 'تسک';
            $title     = $entity->title ?? $title;
        } elseif ($entity instanceof \App\Models\Project) {
            $modelType = 'پروژه';
            $title     = $entity->name ?? $title;
        } elseif ($entity instanceof \App\Models\SalesLead) {
            $modelType = 'سرنخ فروش';
            $title     = $entity->name ?? $title;
        } elseif ($entity instanceof \App\Models\SalesOpportunity) {
            $modelType = 'فرصت فروش';
            $title     = $entity->title ?? $title;
        } else {
            // اگر morph map یا کلاس دیگری بود
            $base      = strtolower(class_basename($entity));
            $modelType = $base ?: $modelType;
            $title     = $entity->title ?? $entity->name ?? $title;
        }

        return [$modelType, (string) $title];
    }
    public function databaseType($notifiable): string
    {
        return 'mention';
    }

    /**
     * ساخت URL مقصد برای انواع موجودیت‌های نوت
     */
    protected function buildEntityUrl($entity): ?string
    {
        if (!$entity) {
            return null;
        }

        // ---- Task (پروژه‌ها) ----
        if ($entity instanceof Task) {
            $entity->loadMissing('project');
            $project = $entity->project;
            if ($project instanceof Project && Route::has('projects.tasks.show')) {
                return route('projects.tasks.show', [$project, $entity]);
            }
            return null;
        }

        // ---- SalesLead (سرنخ فروش) ----
        if ($entity instanceof SalesLead) {
            // اولویت با روت‌های بخش مارکتینگ؛ اگر نبود به روت‌های Sales برگرد
            if (Route::has('marketing.leads.show')) {
                return route('marketing.leads.show', $entity->id);
            }
            if (Route::has('sales.leads.show')) {
                return route('sales.leads.show', $entity->id);
            }
            return null;
        }

        // ---- SalesOpportunity (فرصت فروش) ----
        if ($entity instanceof SalesOpportunity) {
            if (Route::has('sales.opportunities.show')) {
                return route('sales.opportunities.show', $entity->id);
            }
            if (Route::has('sales.opportunities.show')) {
                return route('sales.opportunities.show', $entity->id);
            }
            return null;
        }

        // اگر به‌جای کلاس‌های بالا، FQCN یا نام ساده‌ای در morph map دارید:
        // می‌توان با class_basename سوییچ کرد. نمونه:
        $base = strtolower(class_basename($entity));
        switch ($base) {
            case 'saleslead':
            case 'lead':
                if (Route::has('marketing.leads.show')) {
                    return route('marketing.leads.show', $entity->id);
                }
                if (Route::has('sales.leads.show')) {
                    return route('sales.leads.show', $entity->id);
                }
                break;

            case 'salesopportunity':
            case 'opportunity':
                if (Route::has('marketing.opportunities.show')) {
                    return route('marketing.opportunities.show', $entity->id);
                }
                if (Route::has('sales.opportunities.show')) {
                    return route('sales.opportunities.show', $entity->id);
                }
                break;

            case 'project':
                if (Route::has('projects.show')) {
                    return route('projects.show', $entity->id);
                }
                break;
        }

        return null;
    }
}

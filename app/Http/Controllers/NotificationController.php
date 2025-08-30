<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(10);
        return view('notifications.index', compact('notifications'));
    }

    public function read($notificationId)
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        // فقط اگر نخونده بوده، علامت‌گذاری کن
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // 1) اگر url صریح در دیتا هست، همونو برو
        $url = Arr::get($notification->data, 'url');

        // 2) در غیر این صورت، از روی کلیدها مقصد رو بساز
        if (!$url) {
            $url = $this->resolveNotificationUrl($notification->data);
        }

        // 3) اگر بازم چیزی پیدا نشد، برگرد به لیست اعلان‌ها
        return $url
            ? redirect($url)
            : redirect()->route('notifications.index')
                ->with('success', 'اعلان خوانده شد، اما لینک مقصد یافت نشد.');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return redirect()->route('notifications.index')->with('success', 'همه اعلان‌ها خوانده شدند.');
    }

    public function bulkAction(Request $request)
    {
        $ids = $request->input('selected', []);
        $action = $request->input('action');

        $notifications = auth()->user()->notifications()->whereIn('id', $ids)->get();

        if ($action === 'markAsRead') {
            foreach ($notifications as $notification) {
                $notification->markAsRead();
            }
            return back()->with('success', 'اعلان‌ها به عنوان خوانده‌شده علامت‌گذاری شدند.');
        }

        if ($action === 'delete') {
            foreach ($notifications as $notification) {
                $notification->delete();
            }
            return back()->with('success', 'اعلان‌های انتخاب‌شده حذف شدند.');
        }

        return back()->with('error', 'هیچ عملیاتی انتخاب نشد.');
    }

    /**
     * تلاش برای ساختن لینک مقصد از روی داده‌های اعلان
     */
    private function resolveNotificationUrl(array $data): ?string
    {
        // پشتیبانی از چند نام کلید رایج
        $leadId    = Arr::get($data, 'lead_id') ?? Arr::get($data, 'sales_lead_id');
        $oppId     = Arr::get($data, 'opportunity_id') ?? Arr::get($data, 'sales_opportunity_id');
        $projectId = Arr::get($data, 'project_id');
        $noteId    = Arr::get($data, 'note_id') ?? Arr::get($data, 'note.id') ?? Arr::get($data, 'id');

        // اگر مدل هم اومده (FQCN یا نام ساده)، برای اولویت‌دهی استفاده می‌کنیم
        $model = Arr::get($data, 'model') ?? Arr::get($data, 'notable_type');
        if ($model && str_contains((string)$model, '\\')) {
            $model = class_basename($model); // SalesLead / Opportunity / Project
        }
        $model = $model ? strtolower($model) : null;

        // اولویت با مدل مشخص‌شده
        if ($model === 'saleslead' || $model === 'lead') {
            if ($leadId) {
                $u = route('sales.leads.show', $leadId);
                return $noteId ? "{$u}#note-{$noteId}" : "{$u}#notes";
            }
        }
        if ($model === 'salesopportunity' || $model === 'opportunity') {
            if ($oppId) {
                $u = route('sales.opportunities.show', $oppId);
                return $noteId ? "{$u}#note-{$noteId}" : "{$u}#notes";
            }
        }
        if ($model === 'project') {
            if ($projectId) {
                $u = route('projects.show', $projectId);
                return $noteId ? "{$u}#note-{$noteId}" : "{$u}#notes";
            }
        }

        // اگر مدل نیامده بود، از شناسه‌های موجود یکی را انتخاب کن
        if ($leadId) {
            $u = route('sales.leads.show', $leadId);
            return $noteId ? "{$u}#note-{$noteId}" : "{$u}#notes";
        }
        if ($oppId) {
            $u = route('sales.opportunities.show', $oppId);
            return $noteId ? "{$u}#note-{$noteId}" : "{$u}#notes";
        }
        if ($projectId) {
            $u = route('projects.show', $projectId);
            return $noteId ? "{$u}#note-{$noteId}" : "{$u}#notes";
        }

        // لینک مقصد قابل‌تشخیص نبود
        return null;
    }
}

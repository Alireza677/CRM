<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * لیست اعلان‌ها (صفحه‌بندی شده)
     */
    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * باز کردن یک اعلان: خواندن + ریدایرکت به مقصد
     */
    public function read(string $notificationId)
    {
        /** @var DatabaseNotification $notification */
        $notification = Auth::user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // 1) اگر url صریح در دیتا ذخیره شده، همان را برو
        $url = Arr::get($notification->data, 'url');

        // 2) در غیر این صورت از روی داده‌ها مقصد را بساز
        if (!$url) {
            $url = $this->resolveNotificationUrl($notification->data);
        }

        // 3) اگر مقصد پیدا شد، برو؛ وگرنه برگرد به لیست اعلان‌ها
        return $url
            ? redirect()->to($url)
            : redirect()->route('notifications.index')
                ->with('success', 'اعلان خوانده شد، اما لینک مقصد یافت نشد.');
    }

    /**
     * علامت‌گذاری همه اعلان‌های خوانده‌نشده به‌عنوان خوانده‌شده
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->route('notifications.index')
            ->with('success', 'همه اعلان‌ها خوانده شدند.');
    }

    /**
     * عملیات گروهی روی اعلان‌ها (خواندن/حذف)
     */
    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'selected' => ['array'],
            'selected.*' => ['string'],
            'action' => ['required', 'in:markAsRead,delete'],
        ]);

        $ids = $data['selected'] ?? [];

        if (empty($ids)) {
            return back()->with('error', 'هیچ اعلانى انتخاب نشد.');
        }

        $notifications = Auth::user()->notifications()->whereIn('id', $ids)->get();

        if ($data['action'] === 'markAsRead') {
            foreach ($notifications as $notification) {
                /** @var DatabaseNotification $notification */
                $notification->markAsRead();
            }
            return back()->with('success', 'اعلان‌ها به عنوان خوانده‌شده علامت‌گذاری شدند.');
        }

        if ($data['action'] === 'delete') {
            foreach ($notifications as $notification) {
                $notification->delete();
            }
            return back()->with('success', 'اعلان‌های انتخاب‌شده حذف شدند.');
        }

        return back()->with('error', 'هیچ عملیاتی انجام نشد.');
    }

    
    private function resolveNotificationUrl(array $data): ?string
    {
        // 0) اگر url صریح ذخیره شده بود
        if ($direct = Arr::get($data, 'url')) {
            return $direct;
        }

        $routeName = Arr::get($data, 'route_name');
        $formId    = Arr::get($data, 'form_id');
        $formType  = Arr::get($data, 'form_type')
                    ?? Arr::get($data, 'model')
                    ?? Arr::get($data, 'notable_type');
        $noteId    = Arr::get($data, 'note_id') ?? Arr::get($data, 'note.id');

        // 1) اگر route_name + form_id داریم
        if ($routeName && $formId) {
            $param = $this->guessRouteParamName($formType, $routeName);
            try {
                $u = route($routeName, [$param => $formId]);
                return $noteId ? "{$u}#note-{$noteId}" : $u;
            } catch (\Throwable $e) {
                // ادامه‌ی فالبک
            }
        }

        // 2) حالت استانداردِ دیتای تو: فقط form_type + form_id
        $model = $this->normalizeModelName($formType); // proforma/opportunity/lead/project
        if ($formId && $model) {
            $u = match ($model) {
                'lead'        => route('sales.leads.show',         ['lead'        => $formId]),
                'opportunity' => route('sales.opportunities.show', ['opportunity' => $formId]),
                'proforma'    => route('sales.proformas.show',     ['proforma'    => $formId]),
                'project'     => route('projects.show',            ['project'     => $formId]),
                default       => null,
            };
            return $u ? ($noteId ? "{$u}#note-{$noteId}" : $u) : null;
        }

        // 3) سازگاری با کلیدهای قدیمی/متفرقه
        $leadId     = Arr::get($data, 'lead_id') ?? Arr::get($data, 'sales_lead_id');
        $oppId      = Arr::get($data, 'opportunity_id') ?? Arr::get($data, 'sales_opportunity_id');
        $proformaId = Arr::get($data, 'proforma_id');
        $projectId  = Arr::get($data, 'project_id');

        if ($proformaId) { $u = route('sales.proformas.show',     ['proforma'    => $proformaId]); return $noteId ? "{$u}#note-{$noteId}" : $u; }
        if ($oppId)      { $u = route('sales.opportunities.show', ['opportunity' => $oppId]);      return $noteId ? "{$u}#note-{$noteId}" : $u; }
        if ($leadId)     { $u = route('sales.leads.show',         ['lead'        => $leadId]);     return $noteId ? "{$u}#note-{$noteId}" : $u; }
        if ($projectId)  { $u = route('projects.show',            ['project'     => $projectId]);  return $noteId ? "{$u}#note-{$noteId}" : $u; }

        return null;
    }

        /**
         * حدس نام پارامتر روت بر اساس نوع مدل یا نام روت
         * مثال‌ها:
         *  - sales.proformas.show  => proforma
         *  - sales.opportunities.show => opportunity
         *  - sales.leads.show => lead
         */
        private function guessRouteParamName(?string $formType, ?string $routeName): string
        {
            $model = $this->normalizeModelName($formType);

            // بر اساس مدل
            if ($model === 'proforma')     return 'proforma';
            if ($model === 'opportunity')  return 'opportunity';
            if ($model === 'lead')         return 'lead';
            if ($model === 'project')      return 'project';

            // بر اساس نام روت
            if (is_string($routeName)) {
                if (str_contains($routeName, 'proforma'))     return 'proforma';
                if (str_contains($routeName, 'opportunit'))   return 'opportunity';
                if (str_contains($routeName, 'lead'))         return 'lead';
                if (str_contains($routeName, 'project'))      return 'project';
            }

            // پیش‌فرض
            return 'id';
        }

    /**
     * نرمال‌سازی نام مدل به فرم ساده (proforma/opportunity/lead/project)
     */
    private function normalizeModelName(?string $formType): ?string
    {
        if (!$formType) return null;

        $base = $formType;
        if (str_contains($base, '\\')) {
            $base = class_basename($base);
        }
        $base = strtolower($base);

        // نگاشت‌های رایج
        return match (true) {
            str_contains($base, 'proforma')     => 'proforma',
            str_contains($base, 'opportunit')   => 'opportunity',
            str_contains($base, 'lead')         => 'lead',
            str_contains($base, 'project')      => 'project',
            default                             => null,
        };
    }

    /**
     * آیا نوع مدل شبیه Proforma به‌نظر می‌رسد؟
     */
    private function looksLikeProforma(?string $formType): bool
    {
        $name = $this->normalizeModelName($formType);
        return $name === 'proforma';
    }
}

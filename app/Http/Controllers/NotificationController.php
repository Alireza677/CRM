<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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

    /**
     * فید آخرین اعلان‌های خوانده‌نشده برای نمایش به‌صورت Toast (JSON)
     */
    public function latestFeed(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['data' => []], 401);
        }

        $notifications = $user->unreadNotifications()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $data = $notifications->map(function (DatabaseNotification $notification) {
            $payload = (array) ($notification->data ?? []);
            return [
                'id'         => $notification->id,
                'title'      => $payload['title'] ?? $payload['message'] ?? 'OO1U,OOU+ OUO_UO O_OOUOO_',
                'body'       => $payload['body'] ?? '',
                'message'    => $payload['title'] ?? $payload['message'] ?? 'OO1U,OOU+ OUO_UO O_OOUOO_',
                'module'     => $payload['module'] ?? null,
                'event'      => $payload['event'] ?? null,
                'created_at' => $notification->created_at?->toIso8601String(),
                'url'        => route('notifications.read', ['notification' => $notification->id]),
                'is_read'    => (bool) $notification->read_at,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    public function unreadCount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['count' => 0], 401);
        }

        return response()->json([
            'count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function stream(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        @set_time_limit(0);
        @ignore_user_abort(true);

        $since = $request->query('since');
        try {
            $cursor = $since ? Carbon::parse($since) : now();
        } catch (\Throwable $e) {
            $cursor = now();
        }

        return response()->stream(function () use ($user, $cursor) {
            $lastPing = time();
            $currentCursor = $cursor;

            echo "retry: 5000\n\n";
            $this->flushStream();

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $notifications = $user->unreadNotifications()
                    ->where('created_at', '>', $currentCursor)
                    ->orderBy('created_at')
                    ->limit(10)
                    ->get();

                foreach ($notifications as $notification) {
                    /** @var DatabaseNotification $notification */
                    $data = (array) ($notification->data ?? []);
            $payload = [
                        'id' => $notification->id,
                        'title' => $data['title'] ?? $data['message'] ?? 'OO1U,OOU+ OUO_UO O_OOUOO_',
                        'body' => $data['body'] ?? '',
                        'module' => $data['module'] ?? null,
                        'event' => $data['event'] ?? null,
                        'created_at' => $notification->created_at?->toIso8601String(),
                        'url' => route('notifications.read', ['notification' => $notification->id]),
                        'is_read' => (bool) $notification->read_at,
                    ];

                    echo "event: notification\n";
                    echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                    $this->flushStream();

                    if ($notification->created_at) {
                        $currentCursor = $notification->created_at;
                    }
                }

                if (time() - $lastPing >= 15) {
                    echo "event: ping\n";
                    echo "data: {}\n\n";
                    $this->flushStream();
                    $lastPing = time();
                }

                usleep(500000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function flushStream(): void
    {
        if (function_exists('ob_get_level') && ob_get_level() > 0) {
            @ob_flush();
        }
        @flush();
    }

    
    private function resolveNotificationUrl(array $data): ?string
    {
        // 0) اگر url صریح ذخیره شده بود
        if ($direct = Arr::get($data, 'url')) {
            return $direct;
        }

        $routeName = Arr::get($data, 'route_name');
        $formId    = Arr::get($data, 'form_id')
                    ?? Arr::get($data, 'noteable_id')
                    ?? Arr::get($data, 'notable_id');
        $formType  = Arr::get($data, 'form_type')
                    ?? Arr::get($data, 'model')
                    ?? Arr::get($data, 'notable_type')
                    ?? Arr::get($data, 'noteable_type');
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
                'purchaseorder' => route('inventory.purchase-orders.show', $formId),
                default       => null,
            };
            return $u ? ($noteId ? "{$u}#note-{$noteId}" : $u) : null;
        }

        // 3) سازگاری با کلیدهای قدیمی/متفرقه
        $leadId     = Arr::get($data, 'lead_id') ?? Arr::get($data, 'sales_lead_id');
        $oppId      = Arr::get($data, 'opportunity_id') ?? Arr::get($data, 'sales_opportunity_id');
        $proformaId = Arr::get($data, 'proforma_id');
        $projectId  = Arr::get($data, 'project_id');
        $poId       = Arr::get($data, 'purchase_order_id') ?? Arr::get($data, 'po_id');

        if ($proformaId) { $u = route('sales.proformas.show',     ['proforma'    => $proformaId]); return $noteId ? "{$u}#note-{$noteId}" : $u; }
        if ($oppId)      { $u = route('sales.opportunities.show', ['opportunity' => $oppId]);      return $noteId ? "{$u}#note-{$noteId}" : $u; }
        if ($leadId)     { $u = route('sales.leads.show',         ['lead'        => $leadId]);     return $noteId ? "{$u}#note-{$noteId}" : $u; }
        if ($projectId)  { $u = route('projects.show',            ['project'     => $projectId]);  return $noteId ? "{$u}#note-{$noteId}" : $u; }
        if ($poId)       { $u = route('inventory.purchase-orders.show', $poId); return $noteId ? "{$u}#note-{$noteId}" : $u; }

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
            if ($model === 'proforma')       return 'proforma';
            if ($model === 'opportunity')    return 'opportunity';
            if ($model === 'lead')           return 'lead';
            if ($model === 'project')        return 'project';
            if ($model === 'purchaseorder')  return 'purchaseOrder';

            // بر اساس نام روت
            if (is_string($routeName)) {
                if (str_contains($routeName, 'proforma'))     return 'proforma';
                if (str_contains($routeName, 'opportunit'))   return 'opportunity';
                if (str_contains($routeName, 'lead'))         return 'lead';
                if (str_contains($routeName, 'project'))      return 'project';
                if (str_contains($routeName, 'purchase-order')) return 'purchaseOrder';
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
            str_contains($base, 'purchase')     => 'purchaseorder',
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

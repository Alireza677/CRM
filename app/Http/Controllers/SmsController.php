<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SmsLog;
use App\Models\SmsBlacklist;
use App\Models\SmsList;
use App\Models\Contact;
use App\Services\Sms\FarazEdgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Support\LogHelpers;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SmsController extends Controller
{
    /**
     * فرم ارسال پیامک
     */
    public function create()
    {
        $users = User::query()
            ->select(['id', 'name', 'mobile'])
            ->whereNotNull('mobile')
            ->orderBy('name')
            ->get();

        // Contacts for building lists (show only those with mobile number)
        $contacts = Contact::query()
            ->select(['id', 'first_name', 'last_name', 'mobile'])
            ->whereNotNull('mobile')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Existing lists with contact counts and a small subset of contacts
        $lists = SmsList::withCount('contacts')
            ->with(['contacts' => function ($q) {
                $q->select('contacts.id', 'first_name', 'last_name', 'mobile');
            }])
            ->orderByDesc('created_at')
            ->get();

        return view('tools.sms.send', compact('users', 'contacts', 'lists'));
    }

    /**
     * ارسال پیامک و ثبت لاگ‌ها
     */
    public function send(Request $request, FarazEdgeService $sms): RedirectResponse
    {
        $data = $request->validate([
            'message'      => ['required', 'string', 'max:700'],
            'users'        => ['nullable', 'array'],
            'users.*'      => ['integer', 'exists:users,id'],
            'mobiles'      => ['nullable', 'string'],
            'send_to_all'  => ['nullable', 'boolean'],
        ]);

        $traceId = bin2hex(random_bytes(6));
        $t0 = microtime(true);

        $recipients = [];
        $blacklisted = [];

        // دریافت مخاطبین از کاربران انتخابی یا همه
        if (!empty($data['send_to_all'])) {
            $recipients = array_merge(
                $recipients,
                User::whereNotNull('mobile')->pluck('mobile')->all()
            );
        } elseif (!empty($data['users'])) {
            $recipients = array_merge(
                $recipients,
                User::whereIn('id', $data['users'])->pluck('mobile')->all()
            );
        }

        // دریافت شماره‌ها از textarea آزاد
        if (!empty($data['mobiles'])) {
            $raw = preg_split('/[\s,;\n\r]+/u', $data['mobiles']);
            foreach ($raw as $m) {
                $m = trim((string) $m);
                if ($m !== '') {
                    $recipients[] = $m;
                }
            }
        }

        // نرمال‌سازی به فرمت E.164 و حذف تکراری‌ها
        $recipients = array_values(array_unique(array_filter(array_map(function ($m) {
            $m = trim((string) $m);
            if ($m === '') return null;
            $m = preg_replace('/[^\d+]/', '', $m);

            if (str_starts_with($m, '0098')) $m = '+98' . substr($m, 4);
            if (str_starts_with($m, '00'))   $m = '+' . substr($m, 2);

            if (preg_match('/^0(9\d{9})$/', $m, $mm)) return '+98' . $mm[1];
            if (preg_match('/^(9\d{9})$/', $m, $mm))  return '+98' . $mm[1];

            if (preg_match('/^\+98\d{10}$/', $m))     return $m;
            if (preg_match('/^\+\d{8,15}$/', $m))     return $m;

            return null;
        }, $recipients))));

        // حذف شماره‌های بلک‌لیست
        if (!empty($recipients)) {
            $blacklisted = SmsBlacklist::query()
                ->whereIn('mobile', $recipients)
                ->pluck('mobile')
                ->all();

            if (!empty($blacklisted)) {
                $recipients = array_values(array_diff($recipients, $blacklisted));
                Log::channel('sms')->warning("[SMS][REQ][$traceId] Blacklist filtered", [
                    'blacklisted_cnt' => count($blacklisted),
                    'blacklisted'     => array_slice($blacklisted, 0, 50),
                ]);
            }
        }

        // لاگ پیش‌پرواز
        Log::channel('sms')->info("[SMS][REQ][$traceId] Controller preflight", [
            'send_to_all'       => (bool) ($data['send_to_all'] ?? false),
            'selected_users'    => isset($data['users']) ? count($data['users']) : 0,
            'raw_mobiles_len'   => isset($data['mobiles']) ? mb_strlen($data['mobiles']) : 0,
            'recipients_cnt'    => count($recipients),
            'recipients_sample' => array_slice($recipients, 0, 20),
            'message_len'       => mb_strlen($data['message']),
        ]);

        if (empty($recipients)) {
            Log::channel('sms')->warning("[SMS][REQ][$traceId] No valid recipients after normalization");
            return back()->withInput()->withErrors(['recipients' => 'هیچ شماره معتبری یافت نشد.']);
        }

        try {
            // ارسال به سرویس فرراز (لایه سرویس خودتان)
            $result  = $sms->sendWebservice($recipients, $data['message']);
            $ok      = (bool) ($result['ok'] ?? false);
            $bulkIds = $result['bulk_ids'] ?? [];
            $meta    = $result['meta'] ?? null;

            // لاگ پس از پاسخ
            Log::channel('sms')->info("[SMS][RES][$traceId] Controller response", [
                'ok'           => $ok,
                'bulk_ids_cnt' => is_array($bulkIds) ? count($bulkIds) : 0,
                'meta'         => LogHelpers::clip($meta),
                'raw_clip'     => LogHelpers::clip($result['raw'] ?? null),
                'duration_ms'  => (int) round((microtime(true) - $t0) * 1000),
            ]);

            // نگاشت اختیاری bulk_id ها به هر گیرنده
            $mapIds = is_array($bulkIds) && count($bulkIds) === count($recipients);
            $senderId = Auth::id();

            foreach ($recipients as $idx => $to) {
                SmsLog::create([
                    'to'                  => $to,
                    'type'                => 'text',
                    'message'             => $data['message'],
                    'status_code'         => $ok ? 200 : null,
                    'status_text'         => $ok ? 'OK' : 'ERROR',
                    'provider_message_id' => $mapIds ? ($bulkIds[$idx] ?? null) : null,
                    'provider_response'   => $result['raw'] ?? ($result['meta'] ?? []),
                    'sent_by'             => $senderId, // ✅ ثبت ارسال‌کننده
                ]);
            }

            $count   = count($recipients);
            $skipped = count($blacklisted);

            return redirect()
                ->route('tools.sms.create')
                ->with(
                    'success',
                    "پیامک برای {$count} شماره ارسال شد." . ($skipped ? " ({$skipped} شماره به دلیل بلک‌لیست رد شدند)" : '')
                );
        } catch (\Throwable $e) {
            Log::channel('sms')->error("[SMS][ERR][$traceId] Controller exception", [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ]);

            return back()->withInput()->withErrors([
                'message' => 'ارسال پیامک با خطا مواجه شد: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * گزارش آماری + لیست لاگ‌ها با فیلتر تاریخ
     */
    public function report(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to   = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        $base = SmsLog::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));

        $delivered = (clone $base)->where('status_code', 200)->count();
        $failed = (clone $base)->where(function ($q) {
            $q->whereNull('status_code')
              ->orWhere('status_code', '>=', 400)
              ->orWhere('status_text', 'ERROR');
        })->count();
        $pending = (clone $base)->whereNull('status_code')->whereNull('status_text')->count();
        $total = (clone $base)->count();

        $logs = (clone $base)
            ->with(['sender:id,name']) // برای نمایش نام ارسال‌کننده
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $blacklistedSet = SmsBlacklist::query()
            ->whereIn('mobile', $logs->pluck('to')->unique()->values())
            ->pluck('mobile')
            ->all();

        $stats = compact('total', 'delivered', 'failed', 'pending');
        $filters = [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];

        return view('tools.sms.report', compact('stats', 'logs', 'filters', 'blacklistedSet'));
    }

    /**
     * خروجی CSV از لاگ‌ها با فیلتر تاریخ
     *
     * @return StreamedResponse
     */
    public function exportCsv(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to   = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        $rows = SmsLog::query()
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->orderBy('created_at', 'desc')
            ->get(['created_at', 'to', 'message', 'status_code', 'status_text', 'provider_message_id', 'sent_by']);

        $filename = 'sms_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM برای سازگاری بهتر با Excel
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, ['Date', 'To', 'Message', 'StatusCode', 'StatusText', 'ProviderMessageId', 'SentBy']);

            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->created_at,
                    $r->to,
                    mb_substr((string) $r->message, 0, 500),
                    $r->status_code,
                    $r->status_text,
                    $r->provider_message_id,
                    $r->sent_by,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * افزودن شماره به بلک‌لیست (با نرمال‌سازی)
     */
    public function addToBlacklist(Request $request)
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        // نرمال‌سازی مشابه مرحله‌ی ارسال
        $m = trim(preg_replace('/[^\d+]/', '', $data['mobile']));
        if (str_starts_with($m, '0098')) $m = '+98' . substr($m, 4);
        if (str_starts_with($m, '00'))   $m = '+' . substr($m, 2);
        if (preg_match('/^0(9\d{9})$/', $m, $mm)) $m = '+98' . $mm[1];
        if (preg_match('/^(9\d{9})$/', $m, $mm))  $m = '+98' . $mm[1];

        if (!preg_match('/^\+\d{8,15}$/', $m)) {
            return back()->withErrors(['mobile' => 'فرمت شماره معتبر نیست.']);
        }

        SmsBlacklist::firstOrCreate(['mobile' => $m], ['note' => $data['note'] ?? null]);

        return back()->with('success', 'شماره به بلک‌لیست افزوده شد.');
    }

    // =============================
    // Lists CRUD + Send per list
    // =============================

    public function storeList(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        SmsList::create([
            'name'       => $data['name'],
            'created_by' => Auth::id(),
        ]);

        return back()->with('success', 'لیست پیامک با موفقیت ایجاد شد.');
    }

    public function destroyList(SmsList $list): RedirectResponse
    {
        $list->delete();
        return back()->with('success', 'لیست حذف شد.');
    }

    public function addContactsToList(Request $request, SmsList $list): RedirectResponse
    {
        $data = $request->validate([
            'contact_ids'   => ['required', 'array'],
            'contact_ids.*' => ['integer', 'exists:contacts,id'],
        ]);

        $list->contacts()->syncWithoutDetaching($data['contact_ids']);
        return back()->with('success', 'مخاطبین به لیست اضافه شدند.');
    }

    public function removeContactFromList(SmsList $list, Contact $contact): RedirectResponse
    {
        $list->contacts()->detach($contact->id);
        return back()->with('success', 'مخاطب از لیست حذف شد.');
    }

    public function sendToList(Request $request, SmsList $list, FarazEdgeService $sms): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:700'],
        ]);

        $traceId = bin2hex(random_bytes(6));
        $t0 = microtime(true);

        // Collect mobiles from list contacts
        $recipients = $list->contacts()
            ->whereNotNull('mobile')
            ->pluck('mobile')
            ->all();

        // Normalize and unique
        $recipients = array_values(array_unique(array_filter(array_map(function ($m) {
            $m = trim((string) $m);
            if ($m === '') return null;
            $m = preg_replace('/[^\d+]/', '', $m);

            if (str_starts_with($m, '0098')) $m = '+98' . substr($m, 4);
            if (str_starts_with($m, '00'))   $m = '+' . substr($m, 2);

            if (preg_match('/^0(9\d{9})$/', $m, $mm)) return '+98' . $mm[1];
            if (preg_match('/^(9\d{9})$/', $m, $mm))  return '+98' . $mm[1];

            if (preg_match('/^\+98\d{10}$/', $m))     return $m;
            if (preg_match('/^\+\d{8,15}$/', $m))     return $m;

            return null;
        }, $recipients))));

        // Blacklist filtering
        $blacklisted = [];
        if (!empty($recipients)) {
            $blacklisted = SmsBlacklist::query()
                ->whereIn('mobile', $recipients)
                ->pluck('mobile')
                ->all();
            if (!empty($blacklisted)) {
                $recipients = array_values(array_diff($recipients, $blacklisted));
            }
        }

        Log::channel('sms')->info("[SMS][REQ][$traceId] SendToList preflight", [
            'list_id'          => $list->id,
            'recipients_cnt'   => count($recipients),
            'message_len'      => mb_strlen($data['message']),
            'blacklisted_cnt'  => count($blacklisted),
        ]);

        if (empty($recipients)) {
            return back()->withErrors(['recipients' => 'هیچ شماره معتبری در این لیست یافت نشد.']);
        }

        try {
            $result  = $sms->sendWebservice($recipients, $data['message']);
            $ok      = (bool) ($result['ok'] ?? false);
            $bulkIds = $result['bulk_ids'] ?? [];

            $mapIds = is_array($bulkIds) && count($bulkIds) === count($recipients);
            $senderId = Auth::id();

            foreach ($recipients as $idx => $to) {
                SmsLog::create([
                    'to'                  => $to,
                    'type'                => 'text',
                    'message'             => $data['message'],
                    'status_code'         => $ok ? 200 : null,
                    'status_text'         => $ok ? 'OK' : 'ERROR',
                    'provider_message_id' => $mapIds ? ($bulkIds[$idx] ?? null) : null,
                    'provider_response'   => $result['raw'] ?? ($result['meta'] ?? []),
                    'sent_by'             => $senderId,
                ]);
            }

            $count   = count($recipients);
            $skipped = count($blacklisted);

            return back()->with('success', "پیامک به لیست '{$list->name}' برای {$count} شماره ارسال شد." . ($skipped ? " ({$skipped} شماره در لیست سیاه بودند)" : ''));
        } catch (\Throwable $e) {
            Log::channel('sms')->error("[SMS][ERR][$traceId] SendToList exception", [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ]);

            return back()->withInput()->withErrors([
                'message' => 'ارسال پیامک با خطا مواجه شد: ' . $e->getMessage(),
            ]);
        }
    }
}

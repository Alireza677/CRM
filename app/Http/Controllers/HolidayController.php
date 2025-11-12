<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\User;
use App\Models\SmsLog;
use App\Models\SmsBlacklist;
use App\Services\Sms\FarazEdgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::query()->orderByDesc('date')->paginate(20);

        // Users for SMS recipient modal
        $users = User::query()
            ->select(['id','name','mobile'])
            ->with(['roles:id,name'])
            ->orderBy('name')
            ->get();

        return view('holidays.index', compact('holidays', 'users'));
    }

    public function store(Request $request, FarazEdgeService $sms)
    {
        $data = $request->validate([
            'date'   => ['required','string'], // accepts Jalali (Y/m/d) or Gregorian (Y-m-d)
            'title'  => ['nullable','string','max:255'],
            'notify' => ['sometimes','boolean'],
            'notify_message' => ['nullable','string','max:1000'],
            'notify_user_ids' => ['sometimes','array'],
            'notify_user_ids.*' => ['integer','exists:users,id'],
        ]);

        $data['notify'] = (bool)($data['notify'] ?? false);
        $data['date'] = $this->parseDateToYmd($data['date']);
        $data['created_by_id'] = Auth::id();
        if (!$data['notify']) {
            $data['notify_message'] = null;
        }

        $holiday = Holiday::create($data);

        // ارسال پیامک در صورت انتخاب گزینه اعلان و داشتن گیرندگان
        if ($holiday->notify) {
            $userIds = array_values($request->input('notify_user_ids', []));
            if (!empty($userIds)) {
                $mobiles = User::query()
                    ->whereIn('id', $userIds)
                    ->whereNotNull('mobile')
                    ->pluck('mobile')
                    ->all();

                // نرمال‌سازی شماره‌ها به E.164 و حذف تکراری‌ها
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
                }, $mobiles))));

                // فیلتر بلک‌لیست
                if (!empty($recipients)) {
                    $blacklisted = SmsBlacklist::query()
                        ->whereIn('mobile', $recipients)
                        ->pluck('mobile')
                        ->all();
                    if (!empty($blacklisted)) {
                        $recipients = array_values(array_diff($recipients, $blacklisted));
                        Log::channel('sms')->warning('[SMS][Holiday] Blacklist filtered', [
                            'holiday_id' => $holiday->id,
                            'blacklisted_cnt' => count($blacklisted),
                            'blacklisted_sample' => array_slice($blacklisted, 0, 50),
                        ]);
                    }
                }

                if (!empty($recipients)) {
                    // تولید متن پیش‌فرض اگر خالی بود
                    $msg = trim((string) ($holiday->notify_message ?? ''));
                    if ($msg === '') {
                        $greg = optional($holiday->date)->format('Y-m-d');
                        try {
                            $shamsi = $holiday->date ? Jalalian::fromCarbon($holiday->date)->format('Y/m/d') : null;
                        } catch (\Throwable $e) { $shamsi = null; }
                        $title = $holiday->title ?: 'تعطیلی شرکت';
                        $msg = "اطلاعیه {$title}: در تاریخ " . ($shamsi ?: $greg) . " تعطیل هستیم.";
                    }

                    try {
                        $result  = $sms->sendWebservice($recipients, $msg);
                        $ok      = (bool) ($result['ok'] ?? false);
                        $bulkIds = $result['bulk_ids'] ?? [];
                        $mapIds  = is_array($bulkIds) && count($bulkIds) === count($recipients);
                        $senderId = Auth::id();

                        foreach ($recipients as $idx => $to) {
                            SmsLog::create([
                                'to'                  => $to,
                                'type'                => 'text',
                                'message'             => $msg,
                                'status_code'         => $ok ? 200 : null,
                                'status_text'         => $ok ? 'OK' : 'ERROR',
                                'status'              => $ok ? 'accepted' : 'failed',
                                'status_updated_at'   => now(),
                                'error_code'          => $ok ? null : (string)($result['meta']['code'] ?? null),
                                'error_message'       => $ok ? null : (string)($result['meta']['message'] ?? 'send error'),
                                'provider_message_id' => $mapIds ? ($bulkIds[$idx] ?? null) : null,
                                'provider_response'   => $result['raw'] ?? ($result['meta'] ?? []),
                                'sent_by'             => $senderId,
                                'values'              => ['context' => 'holiday', 'holiday_id' => $holiday->id],
                            ]);
                        }

                        if ($ok) {
                            $holiday->notify_sent_at = now();
                            $holiday->save();
                        }
                    } catch (\Throwable $e) {
                        Log::channel('sms')->error('[SMS][Holiday] Send failed', [
                            'holiday_id' => $holiday->id,
                            'code'       => $e->getCode(),
                            'message'    => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return back()->with('status', 'تعطیلی ثبت شد' . ($holiday->notify_sent_at ? ' و پیامک ارسال گردید.' : '.'));
    }

    public function edit(Holiday $holiday)
    {
        // Users for optional resend SMS modal
        $users = User::query()
            ->select(['id','name','mobile'])
            ->with(['roles:id,name'])
            ->orderBy('name')
            ->get();

        return view('holidays.edit', compact('holiday', 'users'));
    }

    public function update(Request $request, Holiday $holiday, FarazEdgeService $sms)
    {
        $data = $request->validate([
            'date'   => ['required','string'],
            'title'  => ['nullable','string','max:255'],
            'notify' => ['sometimes','boolean'],
            'notify_message' => ['nullable','string','max:1000'],
            'resend_sms' => ['sometimes','boolean'],
            'notify_user_ids' => ['sometimes','array'],
            'notify_user_ids.*' => ['integer','exists:users,id'],
        ]);

        // Build payload without forcing notify when field is absent on the form
        $payload = [
            'date'  => $this->parseDateToYmd($data['date']),
            'title' => $data['title'] ?? null,
        ];
        if ($request->has('notify')) {
            $payload['notify'] = (bool) $request->boolean('notify');
        }
        if ($request->has('notify_message')) {
            $payload['notify_message'] = $data['notify_message'];
        }

        $holiday->update($payload);

        // Optional: resend SMS immediately if requested
        $resend = (bool)($data['resend_sms'] ?? false);
        if ($resend) {
            $userIds = array_values($request->input('notify_user_ids', []));
            if (!empty($userIds)) {
                $mobiles = User::query()
                    ->whereIn('id', $userIds)
                    ->whereNotNull('mobile')
                    ->pluck('mobile')
                    ->all();

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
                }, $mobiles))));

                if (!empty($recipients)) {
                    $blacklisted = SmsBlacklist::query()
                        ->whereIn('mobile', $recipients)
                        ->pluck('mobile')
                        ->all();
                    if (!empty($blacklisted)) {
                        $recipients = array_values(array_diff($recipients, $blacklisted));
                        Log::channel('sms')->warning('[SMS][Holiday][Resend] Blacklist filtered', [
                            'holiday_id' => $holiday->id,
                            'blacklisted_cnt' => count($blacklisted),
                            'blacklisted_sample' => array_slice($blacklisted, 0, 50),
                        ]);
                    }
                }

                if (!empty($recipients)) {
                    $msg = trim((string) ($holiday->notify_message ?? ''));
                    if ($msg === '') {
                        $greg = optional($holiday->date)->format('Y-m-d');
                        try { $shamsi = $holiday->date ? Jalalian::fromCarbon($holiday->date)->format('Y/m/d') : null; }
                        catch (\Throwable $e) { $shamsi = null; }
                        $title = $holiday->title ?: 'تعطیلی شرکت';
                        $msg = "اطلاعیه {$title}: در تاریخ " . ($shamsi ?: $greg) . " تعطیل هستیم.";
                    }

                    try {
                        $result  = $sms->sendWebservice($recipients, $msg);
                        $ok      = (bool) ($result['ok'] ?? false);
                        $bulkIds = $result['bulk_ids'] ?? [];
                        $mapIds  = is_array($bulkIds) && count($bulkIds) === count($recipients);
                        $senderId = Auth::id();

                        foreach ($recipients as $idx => $to) {
                            SmsLog::create([
                                'to'                  => $to,
                                'type'                => 'text',
                                'message'             => $msg,
                                'status_code'         => $ok ? 200 : null,
                                'status_text'         => $ok ? 'OK' : 'ERROR',
                                'status'              => $ok ? 'accepted' : 'failed',
                                'status_updated_at'   => now(),
                                'error_code'          => $ok ? null : (string)($result['meta']['code'] ?? null),
                                'error_message'       => $ok ? null : (string)($result['meta']['message'] ?? 'send error'),
                                'provider_message_id' => $mapIds ? ($bulkIds[$idx] ?? null) : null,
                                'provider_response'   => $result['raw'] ?? ($result['meta'] ?? []),
                                'sent_by'             => $senderId,
                                'values'              => ['context' => 'holiday', 'holiday_id' => $holiday->id],
                            ]);
                        }

                        if ($ok) {
                            $holiday->notify_sent_at = now();
                            $holiday->save();
                        }
                    } catch (\Throwable $e) {
                        Log::channel('sms')->error('[SMS][Holiday][Resend] Send failed', [
                            'holiday_id' => $holiday->id,
                            'code'       => $e->getCode(),
                            'message'    => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return redirect()->route('holidays.index')->with('status', 'ویرایش ذخیره شد' . ($resend ? ' و پیامک مجدداً ارسال شد.' : '.'));
    }

    public function show(Holiday $holiday)
    {
        // دریافت لاگ‌های پیامک مرتبط با این تعطیلی (بر اساس values JSON)
        $logs = SmsLog::query()
            ->where('values->context', 'holiday')
            ->where('values->holiday_id', $holiday->id)
            ->latest()
            ->get();

        // نگاشت شماره‌ها به کاربران موجود با نرمال‌سازی به فرمت E.164
        // لاگ‌های ارسال همیشه به صورت E.164 هستند (مثلاً +98912xxxxxxx)
        // بنابراین شماره موبایل کاربران را نیز به همان فرمت تبدیل می‌کنیم تا مچ شود.
        $normalizeToE164 = function (?string $m): ?string {
            $m = trim((string) $m);
            if ($m === '') return null;
            // فقط اعداد و +
            $m = preg_replace('/[^\d+]/', '', $m);
            if (str_starts_with($m, '0098')) $m = '+98' . substr($m, 4);
            if (str_starts_with($m, '00'))   $m = '+' . substr($m, 2);
            if (preg_match('/^0(9\d{9})$/', $m, $mm)) return '+98' . $mm[1];
            if (preg_match('/^(9\d{9})$/', $m, $mm))  return '+98' . $mm[1];
            if (preg_match('/^\+98\d{10}$/', $m))     return $m;
            if (preg_match('/^\+\d{8,15}$/', $m))     return $m;
            return null;
        };

        $usersByMobile = User::query()
            ->whereNotNull('mobile')
            ->get(['id','name','mobile'])
            ->mapWithKeys(function ($u) use ($normalizeToE164) {
                $e164 = $normalizeToE164($u->mobile);
                return $e164 ? [$e164 => $u] : [];
            });

        return view('holidays.show', [
            'holiday' => $holiday,
            'logs'    => $logs,
            'usersByMobile' => $usersByMobile,
            'uniqueMessages' => $logs->pluck('message')->unique()->values(),
        ]);
    }

    protected function parseDateToYmd(string $value): string
    {
        $v = $this->toEnDigits(trim($value));
        // Try Gregorian Y-m-d first
        try {
            return Carbon::createFromFormat('Y-m-d', $v)->toDateString();
        } catch (\Throwable $e) { /* continue */ }

        // Accept Jalali Y/m/d (with '-' accepted)
        $v2 = str_replace('-', '/', $v);
        try {
            return Jalalian::fromFormat('Y/m/d', $v2)->toCarbon()->toDateString();
        } catch (\Throwable $e) { /* continue */ }

        // Fallback to Carbon parse if very permissive input provided
        return Carbon::parse($v)->toDateString();
    }

    protected function toEnDigits(?string $s): ?string
    {
        if ($s === null) return null;
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($ar, $en, str_replace($fa, $en, $s));
    }
}

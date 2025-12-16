<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationSetting;
use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use App\Models\PurchaseOrder; // برای دسترسی به لیست وضعیت‌های سفارش خرید
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Support\NotificationPlaceholderRenderer;

class NotificationRuleController extends Controller
{
    private function logCtx(?string $reqId = null): array
    {
        return [
            'request_id' => $reqId,
            'user_id'    => Auth::id(),
            'ip'         => request()->ip(),
            'url'        => request()->fullUrl(),
            'ua'         => request()->userAgent(),
        ];
    }

    public function index()
    {
        $reqId = (string) Str::uuid();
        $t0 = microtime(true);

        // جمع‌آوری آمار کوئری‌ها
        DB::enableQueryLog();
        Log::channel('notifications')->info('notifications.index start', array_merge($this->logCtx($reqId), [
            'method' => 'index',
        ]));

        $config = config('notification_events');
        $modules = $config['modules'] ?? [];
        $channelOptions = $config['channels'] ?? ['database' => 'داخلی (سیستم)', 'email' => 'ایمیل'];

        $channelOptions['sms'] = $channelOptions['sms'] ?? 'SMS';
        $excludedMatrixEvents = [
            'purchase_orders' => ['ready_for_delivery'],
        ];
        $rules = NotificationRule::query()
            ->orderByDesc('updated_at')
            ->get()
            ->unique(fn($r) => $r->module.'.'.$r->event)
            ->keyBy(fn($r) => $r->module.'.'.$r->event);
        $templates = NotificationTemplate::query()
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy(fn($t) => $t->module.'.'.$t->event);

        $matrix = [];
        $dynamicSections = [];
        foreach ($modules as $moduleKey => $module) {
            $moduleLabel = $module['label'] ?? $moduleKey;
            foreach (($module['events'] ?? []) as $eventKey => $eventDef) {
                if (in_array($eventKey, $excludedMatrixEvents[$moduleKey] ?? [], true)) {
                    continue;
                }
                $key = $moduleKey.'.'.$eventKey;

                if ($this->supportsMultipleRules($moduleKey, $eventKey)) {
                    $existingRules = NotificationRule::query()
                        ->where('module', $moduleKey)
                        ->where('event', $eventKey)
                        ->orderBy('created_at')
                        ->get();

                    $dynamicSections[$key] = [
                        'module' => $moduleKey,
                        'module_label' => $moduleLabel,
                        'event' => $eventKey,
                        'event_label' => $eventDef['label'] ?? $eventKey,
                        'rules' => $existingRules->map(fn($rule) => $this->buildRuleRowPayload(
                            $moduleKey,
                            $moduleLabel,
                            $eventKey,
                            $eventDef,
                            $rule,
                            $templates
                        ))->values()->all(),
                        'defaults' => $this->buildRuleRowPayload(
                            $moduleKey,
                            $moduleLabel,
                            $eventKey,
                            $eventDef,
                            null,
                            $templates
                        ),
                    ];
                    continue;
                }

                $existing = $rules->get($key);

                $tplGroup = $templates->get($key) ?? collect();
                $tplByChannel = $tplGroup->keyBy('channel');
                $emailTpl = $tplByChannel->get('email');
                $dbTpl    = $tplByChannel->get('database');
                $smsTpl   = $tplByChannel->get('sms');

                $matrix[] = [
                    'module' => $moduleKey,
                    'module_label' => $moduleLabel,
                    'event' => $eventKey,
                    'event_label' => $eventDef['label'] ?? $eventKey,
                    'supports' => $eventDef['supports'] ?? [],
                    'placeholders' => $eventDef['placeholders'] ?? [],
                    'allowed_placeholders' => $eventDef['placeholders'] ?? [],
                    'channels' => $existing?->channels ?? ($eventDef['default_channels'] ?? ['database']),
                    'enabled' => $existing?->enabled ?? false,
                    'conditions' => $existing?->conditions ?? null,
                    'subject_template' => $emailTpl?->subject_template
                        ?? $existing?->subject_template
                        ?? ($eventDef['default_subject'] ?? ''),
                    'body_template' => $emailTpl?->body_template
                        ?? $existing?->body_template
                        ?? ($eventDef['default_body'] ?? ''),
                    'sms_template' => $smsTpl?->body_template
                        ?? $existing?->sms_template
                        ?? ($eventDef['default_sms'] ?? ''),
                    'internal_template' => $dbTpl?->body_template
                        ?? $existing?->body_template
                        ?? ($eventDef['default_body'] ?? ''),
                    'id' => $existing?->id,
                ];
            }
        }

        // آمار کوئری‌ها و زمان اجرا
        $queries = DB::getQueryLog();
        $qCount = count($queries);
        $slowestMs = 0.0;
        foreach ($queries as $q) {
            $slowestMs = max($slowestMs, (float)($q['time'] ?? 0));
        }
        $durationMs = (int) round((microtime(true) - $t0) * 1000);

        Log::channel('notifications')->info('notifications.index matrix built', array_merge($this->logCtx($reqId), [
            'modules_count'        => count($modules),
            'rules_loaded'         => $rules->count(),
            'template_rows_loaded' => $templates->flatten(1)->count(),
            'matrix_rows'          => count($matrix),
            'by_channel_counts'    => [
                'email'    => $templates->flatten(1)->where('channel','email')->count(),
                'database' => $templates->flatten(1)->where('channel','database')->count(),
                'sms'      => $templates->flatten(1)->where('channel','sms')->count(),
            ],
            'perf' => [
                'duration_ms' => $durationMs,
                'db_query_count' => $qCount,
                'db_slowest_ms' => $slowestMs,
            ],
        ]));

        // تهیه گزینه‌های وضعیت سفارش خرید برای استفاده در دراپ‌داون شروط
        $poStatuses = PurchaseOrder::statuses(); // ['code' => 'label']

        $purchaseOrderSection = $dynamicSections['purchase_orders.status.changed'] ?? null;

        $emailNotificationEnabled = true;
        if (Auth::id()) {
                $emailNotificationEnabled = UserNotificationSetting::getBool(
                    Auth::id(),
                    UserNotificationSetting::EMAIL_RECEIVED_KEY,
                    true
                );
            }

        return view('settings.notifications.index', [
            'matrix' => $matrix,
            'channelOptions' => $channelOptions,
            'poStatuses' => $poStatuses,
            'dynamicSections' => $dynamicSections,
            'purchaseOrderSection' => $purchaseOrderSection,
            'emailNotificationEnabled' => $emailNotificationEnabled,
        ]);
    }

    public function updateEmailPreference(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'email_notifications_enabled' => ['required', 'boolean'],
        ]);

        UserNotificationSetting::setBool(
            $user->id,
            UserNotificationSetting::EMAIL_RECEIVED_KEY,
            (bool) $data['email_notifications_enabled']
        );

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'key' => UserNotificationSetting::EMAIL_RECEIVED_KEY,
                'enabled' => (bool) $data['email_notifications_enabled'],
            ]);
        }

        return redirect()
            ->route('settings.notifications.index')
            ->with('status', 'تنظیم اعلان ایمیل بروزرسانی شد.');
    }

    public function store(Request $request)
    {
        $reqId = (string) Str::uuid();
        $t0 = microtime(true);
        DB::enableQueryLog();

        Log::channel('notifications')->info('notifications.store start', array_merge($this->logCtx($reqId), [
            'method' => 'store',
        ]));

        try {
            $data = $request->validate([
                'module'            => 'required|string',
                'event'             => 'required|string',
                'enabled'           => 'required|boolean',
                'channels'          => 'sometimes|array',
                'channels.*'        => 'in:database,email,sms',
                'conditions'        => 'sometimes|array',
                'subject_template'  => 'sometimes|nullable|string|max:5000',
                'body_template'     => 'sometimes|nullable|string|max:5000',
                'sms_template'      => 'sometimes|nullable|string|max:1000',
                'internal_template' => 'sometimes|nullable|string|max:5000',
            ]);

            $selectedChannels = array_values($data['channels'] ?? ['database']);
            Log::channel('notifications')->info('notifications.store validated.base', array_merge($this->logCtx($reqId), [
                'module'   => $data['module'] ?? null,
                'event'    => $data['event'] ?? null,
                'enabled'  => (bool) ($data['enabled'] ?? false),
                'channels' => $selectedChannels,
                'sizes'    => [
                    'subject'  => isset($data['subject_template'])  ? mb_strlen($data['subject_template'])  : null,
                    'body'     => isset($data['body_template'])     ? mb_strlen($data['body_template'])     : null,
                    'internal' => isset($data['internal_template']) ? mb_strlen($data['internal_template']) : null,
                    'sms'      => isset($data['sms_template'])      ? mb_strlen($data['sms_template'])      : null,
                ],
            ]));

            // اعتبارسنجی شرطی بر اساس کانال‌های انتخاب‌شده
            $conditional = [];
            if (in_array('email', $selectedChannels, true)) {
                $conditional['subject_template'] = 'required|string|max:5000';
                $conditional['body_template']    = 'required|string|max:5000';
            }
            if (in_array('sms', $selectedChannels, true)) {
                $conditional['sms_template'] = 'required|string|max:1000';
            }
            if (!empty($conditional)) {
                $request->validate($conditional);
                Log::channel('notifications')->info('notifications.store validated.conditional', array_merge($this->logCtx($reqId), [
                    'fields' => array_keys($conditional),
                ]));
            }

            try {
                $modules = config('notification_events.modules', []);
                abort_unless(array_key_exists($data['module'], $modules), 422, 'ماژول نامعتبر است.');
                $events = $modules[$data['module']]['events'] ?? [];
                abort_unless(array_key_exists($data['event'], $events), 422, 'رویداد نامعتبر است.');

                // اعتبارسنجی شروط با توجه به پشتیبانی رویداد
                $cfg = config('notification_events');
                $moduleKey = $data['module'];
                $eventKey  = $data['event'];
                $supports = $cfg['modules'][$moduleKey]['events'][$eventKey]['supports'] ?? [];
                $supportsConditions = (bool)($supports['conditions'] ?? false);
                $supportsMultiple = $this->supportsMultipleRules($moduleKey, $eventKey);
                Log::channel('notifications')->info('notifications.store supports.computed', array_merge($this->logCtx($reqId), [
                    'module' => $moduleKey,
                    'event'  => $eventKey,
                    'supports_conditions' => $supportsConditions,
                    'supports_multiple' => $supportsMultiple,
                ]));

                $this->validateConditionsSchema(
                    $data['module'],
                    $data['event'],
                    $request->input('conditions', null),
                    $supportsConditions
                );
                if (!$supportsConditions && !empty(array_filter((array) $request->input('conditions', []), fn($v)=>$v!==null&&$v!==''))) {
                    Log::channel('notifications')->warning('notifications.store conditions_ignored_not_supported', array_merge($this->logCtx($reqId), [
                        'module' => $moduleKey,
                        'event' => $eventKey,
                    ]));
                }

                // اعتبارسنجی placeholderها فقط برای کانال‌های فعال
                $placeholderTexts = [];
                if (in_array('email', $selectedChannels, true)) {
                    $placeholderTexts[] = $data['subject_template'] ?? '';
                    $placeholderTexts[] = $data['body_template'] ?? '';
                }
                if (in_array('sms', $selectedChannels, true)) {
                    $placeholderTexts[] = $data['sms_template'] ?? '';
                }
                if (!empty($placeholderTexts)) {
                    Log::channel('notifications')->debug('notifications.store placeholders.validate.start', array_merge($this->logCtx($reqId), [
                        'texts_count' => count($placeholderTexts),
                    ]));
                    $this->validateTemplatesPlaceholders($data['module'], $data['event'], ...$placeholderTexts);
                    Log::channel('notifications')->debug('notifications.store placeholders.validate.ok', $this->logCtx($reqId));
                }

                $userId = Auth::id();

                $payload = [
                    'enabled' => $request->boolean('enabled'),
                    'channels' => $selectedChannels,
                    'conditions' => $request->has('conditions')
                        ? (function ($in) {
                            $filtered = array_filter((array) $in, fn($v) => $v !== null && $v !== '');
                            return empty($filtered) ? null : $filtered;
                        })($request->input('conditions'))
                        : null,
                    'subject_template' => $data['subject_template'] ?? null,
                    'body_template' => $data['body_template'] ?? null,
                    'sms_template' => $data['sms_template'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ];

                if ($supportsMultiple) {
                    $rule = NotificationRule::create(array_merge([
                        'module' => $data['module'],
                        'event' => $data['event'],
                    ], $payload));
                } else {
                    $rule = NotificationRule::updateOrCreate(
                        ['module' => $data['module'], 'event' => $data['event']],
                        $payload
                    );
                }

                Log::channel('notifications')->info('notifications.store rule upserted', [
                    'request_id' => $reqId,
                    'rule_id' => $rule->id,
                ]);

                if (!$supportsMultiple) {
                    foreach ($selectedChannels as $ch) {
                        if ($ch === 'email') {
                            $this->upsertChannelTemplate(
                                $data['module'], $data['event'], 'email',
                                $data['subject_template'] ?? null,
                                $data['body_template'] ?? null,
                                $userId,
                                $reqId
                            );
                        } elseif ($ch === 'database') {
                            $this->upsertChannelTemplate(
                                $data['module'], $data['event'], 'database',
                                null,
                                ($data['internal_template'] ?? null) ?: ($data['body_template'] ?? null),
                                $userId,
                                $reqId
                            );
                        } elseif ($ch === 'sms') {
                            $this->upsertChannelTemplate(
                                $data['module'], $data['event'], 'sms',
                                null,
                                $data['sms_template'] ?? null,
                                $userId,
                                $reqId
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::channel('notifications')->error('notifications.store error', [
                    'request_id' => $reqId,
                    'module' => $data['module'] ?? null,
                    'event' => $data['event'] ?? null,
                    'enabled' => $request->boolean('enabled'),
                    'channels' => $selectedChannels ?? [],
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                if ($request->wantsJson()) {
                    // ختم مسیر JSON با لاگ عملکرد
                    $queries = DB::getQueryLog();
                    $durationMs = (int) round((microtime(true) - $t0) * 1000);
                    Log::channel('notifications')->info('notifications.store finish', array_merge($this->logCtx($reqId), [
                        'result' => 'error.json',
                        'perf' => [
                            'duration_ms' => $durationMs,
                            'db_query_count' => count($queries),
                            'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
                        ],
                    ]));

                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'request_id' => $reqId,
                    ], 422);
                }
                // مسیر web معمولی
                $queries = DB::getQueryLog();
                $durationMs = (int) round((microtime(true) - $t0) * 1000);
                Log::channel('notifications')->info('notifications.store finish', array_merge($this->logCtx($reqId), [
                    'result' => 'error.redirect',
                    'perf' => [
                        'duration_ms' => $durationMs,
                        'db_query_count' => count($queries),
                        'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
                    ],
                ]));

                return back()->withErrors(['error' => $e->getMessage()])->with('request_id', $reqId);
            }

            // موفقیت
            if ($request->wantsJson()) {
                $rule = $rule->fresh();
                $queries = DB::getQueryLog();
                $durationMs = (int) round((microtime(true) - $t0) * 1000);
                Log::channel('notifications')->info('notifications.store finish', array_merge($this->logCtx($reqId), [
                    'result' => 'ok.json',
                    'perf' => [
                        'duration_ms' => $durationMs,
                        'db_query_count' => count($queries),
                        'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
                    ],
                ]));

                return response()->json([
                    'status' => 'ok',
                    'rule' => [
                        'id' => $rule?->id,
                        'module' => $rule?->module,
                        'event' => $rule?->event,
                        'subject_template' => $rule?->subject_template,
                        'body_template' => $rule?->body_template,
                        'sms_template' => $rule?->sms_template,
                    ],
                    'request_id' => $reqId,
                ]);
            }

            $queries = DB::getQueryLog();
            $durationMs = (int) round((microtime(true) - $t0) * 1000);
            Log::channel('notifications')->info('notifications.store finish', array_merge($this->logCtx($reqId), [
                'result' => 'ok.redirect',
                'perf' => [
                    'duration_ms' => $durationMs,
                    'db_query_count' => count($queries),
                    'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
                ],
            ]));

            return redirect()->route('settings.notifications.index')
                ->with('status', 'تنظیمات اعلان ذخیره شد.');
        } catch (ValidationException $ve) {
            // لاگ ویژه‌ی خطای اعتبارسنجی ریشه‌ای
            Log::channel('notifications')->warning('notifications.store validation.failed', array_merge($this->logCtx($reqId), [
                'errors' => $ve->errors(),
            ]));

            $queries = DB::getQueryLog();
            $durationMs = (int) round((microtime(true) - $t0) * 1000);
            Log::channel('notifications')->info('notifications.store finish', array_merge($this->logCtx($reqId), [
                'result' => 'validation.failed',
                'perf' => [
                    'duration_ms' => $durationMs,
                    'db_query_count' => count($queries),
                    'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
                ],
            ]));

            throw $ve;
        }
    }

    public function update(Request $request, NotificationRule $notificationRule)
    {
        $reqId = (string) Str::uuid();
        $t0 = microtime(true);
        DB::enableQueryLog();

        Log::channel('notifications')->info('notifications.update start', [
            'request_id' => $reqId,
            'rule_id' => $notificationRule->id,
            'module' => $notificationRule->module,
            'event' => $notificationRule->event,
        ]);

        $data = $request->validate([
            'enabled' => 'required|boolean',
            'channels' => 'sometimes|array',
            'channels.*' => 'in:database,email,sms',
            'conditions' => 'sometimes|array',
            'subject_template' => 'sometimes|nullable|string|max:5000',
            'body_template' => 'sometimes|nullable|string|max:5000',
            'sms_template' => 'sometimes|nullable|string|max:1000',
            'internal_template' => 'sometimes|nullable|string|max:5000',
        ]);

        // اعتبارسنجی شرطی فقط زمانی که channels ارسال شده باشد
        if (isset($data['channels']) && is_array($data['channels'])) {
            $conditional = [];
            if (in_array('email', $data['channels'], true)) {
                $conditional['subject_template'] = 'required|string|max:5000';
                $conditional['body_template'] = 'required|string|max:5000';
            }
            if (in_array('sms', $data['channels'], true)) {
                $conditional['sms_template'] = 'required|string|max:1000';
            }
            if (!empty($conditional)) {
                $request->validate($conditional);
                Log::channel('notifications')->info('notifications.update validated.conditional', array_merge($this->logCtx($reqId), [
                    'fields' => array_keys($conditional),
                ]));
            }
        }

        try {
            $cfg = config('notification_events');
            $moduleKey = $notificationRule->module;
            $eventKey  = $notificationRule->event; // literal key may contain dots
            $supports = $cfg['modules'][$moduleKey]['events'][$eventKey]['supports'] ?? [];
            $supportsConditions = (bool)($supports['conditions'] ?? false);
            $supportsMultiple = $this->supportsMultipleRules($moduleKey, $eventKey);

            // Normalize incoming condition fields from flat or nested keys
            $from = $request->input('conditions.from_status', $request->input('from_status'));
            $to   = $request->input('conditions.to_status', $request->input('to_status'));
            $condCandidate = array_filter([
                'from_status' => $from,
                'to_status'   => $to,
            ], fn($v) => $v !== null && $v !== '');

            Log::channel('notifications')->info('notifications.update fields.read', array_merge($this->logCtx($reqId), [
                'module' => $notificationRule->module,
                'event' => $notificationRule->event,
                'from_status' => $from,
                'to_status' => $to,
                'supports_conditions' => $supportsConditions,
                'supports_multiple' => $supportsMultiple,
            ]));

            $this->validateConditionsSchema(
                $notificationRule->module,
                $notificationRule->event,
                empty($condCandidate) ? ($request->input('conditions', null)) : $condCandidate,
                $supportsConditions
            );
            if (!$supportsConditions && !empty($condCandidate)) {
                Log::channel('notifications')->warning('notifications.update conditions_ignored_not_supported', array_merge($this->logCtx($reqId), [
                    'module' => $notificationRule->module,
                    'event' => $notificationRule->event,
                    'attempted_conditions' => $condCandidate,
                ]));
            }
            if ($request->hasAny(['subject_template','body_template','sms_template'])) {
                $tplTexts = [];
                if ($request->has('subject_template')) { $tplTexts[] = $data['subject_template'] ?? ''; }
                if ($request->has('body_template')) { $tplTexts[] = $data['body_template'] ?? ''; }
                if ($request->has('sms_template')) { $tplTexts[] = $data['sms_template'] ?? ''; }
                $this->validateTemplatesPlaceholders($notificationRule->module, $notificationRule->event, ...$tplTexts);
            }

            $condPayload = null;
            $shouldUpdateConditions = $request->has('conditions') || $request->has('from_status') || $request->has('to_status');
            if ($shouldUpdateConditions) {
                $incoming = $request->has('conditions') ? (array) $request->input('conditions', []) : [];
                $merged = array_merge([
                    'from_status' => $from,
                    'to_status'   => $to,
                ], $incoming);
                $filtered = array_filter($merged, fn($v) => $v !== null && $v !== '');
                $condPayload = empty($filtered) ? null : $filtered;
            }

            $notificationRule->fill([
                'enabled' => $request->boolean('enabled'),
                'channels' => $data['channels'] ?? $notificationRule->channels,
                'conditions' => $shouldUpdateConditions ? $condPayload : $notificationRule->conditions,
                // فقط در صورت ارسال، ستون‌های legacy به‌روزرسانی شوند
                'subject_template' => array_key_exists('subject_template', $data) ? ($data['subject_template'] ?? null) : $notificationRule->subject_template,
                'body_template' => array_key_exists('body_template', $data) ? ($data['body_template'] ?? null) : $notificationRule->body_template,
                'sms_template' => array_key_exists('sms_template', $data) ? ($data['sms_template'] ?? null) : $notificationRule->sms_template,
                'updated_by' => Auth::id(),
            ])->save();

            Log::channel('notifications')->info('notifications.update rule saved', [
                'request_id' => $reqId,
                'rule_id' => $notificationRule->id,
                'enabled' => $notificationRule->enabled,
                'channels' => $notificationRule->channels,
                'conditions_saved' => $notificationRule->conditions,
            ]);

            // Upsert per-channel فقط در صورت وجود فیلدهای مربوط به همان کانال
            if (!$supportsMultiple) {
                if ($request->hasAny(['subject_template','body_template'])) {
                    $this->upsertChannelTemplate(
                        $notificationRule->module, $notificationRule->event, 'email',
                        $data['subject_template'] ?? $notificationRule->subject_template,
                        $data['body_template'] ?? $notificationRule->body_template,
                        Auth::id(),
                        $reqId
                    );
                }
                if (array_key_exists('internal_template', $data)) {
                    $this->upsertChannelTemplate(
                        $notificationRule->module, $notificationRule->event, 'database',
                        null,
                        ($data['internal_template'] ?? null) ?: ($data['body_template'] ?? $notificationRule->body_template),
                        Auth::id(),
                        $reqId
                    );
                }
                if (array_key_exists('sms_template', $data)) {
                    $this->upsertChannelTemplate(
                        $notificationRule->module, $notificationRule->event, 'sms',
                        null,
                        $data['sms_template'] ?? $notificationRule->sms_template,
                        Auth::id(),
                        $reqId
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::channel('notifications')->error('notifications.update error', [
                'request_id' => $reqId,
                'rule_id' => $notificationRule->id,
                'module' => $notificationRule->module,
                'event' => $notificationRule->event,
                'enabled' => $request->boolean('enabled'),
                'channels' => $data['channels'] ?? $notificationRule->channels,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $queries = DB::getQueryLog();
            $durationMs = (int) round((microtime(true) - $t0) * 1000);
            Log::channel('notifications')->info('notifications.update finish', array_merge($this->logCtx($reqId), [
                'result' => 'error',
                'perf' => [
                    'duration_ms' => $durationMs,
                    'db_query_count' => count($queries),
                    'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
                ],
            ]));

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'request_id' => $reqId,
                ], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()])->with('request_id', $reqId);
        }

        if ($request->wantsJson()) {
            $queries = DB::getQueryLog();
            $durationMs = (int) round((microtime(true) - $t0) * 1000);
            Log::channel('notifications')->info('notifications.update finish', array_merge($this->logCtx($reqId), [
                'result' => 'ok.json',
                'perf' => [
                    'duration_ms' => $durationMs,
                    'db_query_count' => count($queries),
                    'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
                ],
            ]));

            return response()->json([
                'status' => 'ok',
                'rule' => [
                    'id' => $notificationRule->id,
                    'module' => $notificationRule->module,
                    'event' => $notificationRule->event,
                    'subject_template' => $notificationRule->subject_template,
                    'body_template' => $notificationRule->body_template,
                    'sms_template' => $notificationRule->sms_template,
                ],
                'request_id' => $reqId,
            ]);
        }

        $queries = DB::getQueryLog();
        $durationMs = (int) round((microtime(true) - $t0) * 1000);
        Log::channel('notifications')->info('notifications.update finish', array_merge($this->logCtx($reqId), [
            'result' => 'ok.redirect',
            'perf' => [
                'duration_ms' => $durationMs,
                'db_query_count' => count($queries),
                'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
            ],
        ]));

        return redirect()->route('settings.notifications.index')
            ->with('status', 'تنظیمات اعلان به‌روزرسانی شد.');
    }

    public function destroy(NotificationRule $notificationRule)
    {
        $reqId = (string) Str::uuid();
        $t0 = microtime(true);
        DB::enableQueryLog();

        Log::channel('notifications')->info('notifications.destroy start', array_merge($this->logCtx($reqId), [
            'rule_id' => $notificationRule->id,
            'module' => $notificationRule->module,
            'event' => $notificationRule->event,
        ]));

        $notificationRule->delete();

        $queries = DB::getQueryLog();
        $durationMs = (int) round((microtime(true) - $t0) * 1000);
        Log::channel('notifications')->info('notifications.destroy finish', array_merge($this->logCtx($reqId), [
            'result' => 'ok',
            'perf' => [
                'duration_ms' => $durationMs,
                'db_query_count' => count($queries),
                'db_slowest_ms' => collect($queries)->max(fn($q) => (float)($q['time'] ?? 0)) ?: 0.0,
            ],
        ]));

        return redirect()->route('settings.notifications.index')
            ->with('status', 'قانون حذف شد.');
    }

    private function supportsMultipleRules(string $module, string $event): bool
    {
        $modules = config('notification_events.modules', []);
        $eventDef = $modules[$module]['events'][$event] ?? [];
        $supports = $eventDef['supports'] ?? [];

        return (bool) ($supports['multiple_rules'] ?? false);
    }

    private function buildRuleRowPayload(string $moduleKey, string $moduleLabel, string $eventKey, array $eventDef, ?NotificationRule $rule, Collection $templates): array
    {
        $key = $moduleKey.'.'.$eventKey;
        $tplGroup = $templates->get($key) ?? collect();
        $tplByChannel = $tplGroup->keyBy('channel');
        $emailTpl = $tplByChannel->get('email');
        $dbTpl    = $tplByChannel->get('database');
        $smsTpl   = $tplByChannel->get('sms');

        $subject = $rule?->subject_template
            ?? $emailTpl?->subject_template
            ?? ($eventDef['default_subject'] ?? '');
        $body = $rule?->body_template
            ?? $emailTpl?->body_template
            ?? ($eventDef['default_body'] ?? '');
        $internal = $rule?->body_template
            ?? $dbTpl?->body_template
            ?? ($eventDef['default_body'] ?? '');
        $sms = $rule?->sms_template
            ?? $smsTpl?->body_template
            ?? ($eventDef['default_sms'] ?? ($eventDef['default_body'] ?? ''));

        return [
            'id' => $rule?->id,
            'module' => $moduleKey,
            'module_label' => $moduleLabel,
            'event' => $eventKey,
            'event_label' => $eventDef['label'] ?? $eventKey,
            'enabled' => $rule?->enabled ?? false,
            'channels' => $rule?->channels ?? ($eventDef['default_channels'] ?? ['database']),
            'conditions' => [
                'from_status' => data_get($rule?->conditions, 'from_status', ''),
                'to_status' => data_get($rule?->conditions, 'to_status', ''),
            ],
            'subject_template' => $subject,
            'body_template' => $body,
            'internal_template' => $internal,
            'sms_template' => $sms,
            'placeholders' => $eventDef['placeholders'] ?? [],
        ];
    }

    private function validateConditionsSchema(string $module, string $event, $conditions, bool $supportsConditions = false): void
    {
        // اگر پستی نیامده، چیزی برای اعتبارسنجی نیست
        if ($conditions === null) return;

        // اگر رویداد پشتیبانی نمی‌کند، فقط آرایهٔ خالی مجاز است
        if (!$supportsConditions) {
            validator(['conditions' => $conditions], [
                'conditions' => 'nullable|array|size:0',
            ], [
                'conditions.size' => 'این رویداد شروط سفارشی پشتیبانی نمی‌کند.',
            ])->validate();
            return;
        }

        // رویدادهایی که از شروط پشتیبانی می‌کنند
        if ($module === 'purchase_orders' && $event === 'status.changed') {
            // اعتبارسنجی مقادیر انتخاب‌شده بر اساس وضعیت‌های مجاز سفارش خرید
            $allowed = array_keys(PurchaseOrder::statuses());
            validator(['conditions' => $conditions], [
                'conditions' => 'array',
                'conditions.from_status' => ['nullable','string','max:100','in:'.implode(',', $allowed)],
                'conditions.to_status'   => ['nullable','string','max:100','in:'.implode(',', $allowed)],
            ], [
                'conditions.array' => 'قالب شروط نامعتبر است.',
            ])->validate();
            return;
        }

        // حالت پیش‌فرض برای رویدادهای پشتیبان اما بدون طرح مشخص: فقط آرایه بودن را چک کن
        validator(['conditions' => $conditions], [
            'conditions' => 'array',
        ], [
            'conditions.array' => 'قالب شروط نامعتبر است.',
        ])->validate();
    }

    private function validateTemplatesPlaceholders(string $module, string $event, string ...$texts): void
    {
        $modules = config('notification_events.modules', []);
        $events = $modules[$module]['events'] ?? [];
        $eventDef = $events[$event] ?? [];
        $allowedCurly = collect($eventDef['placeholders'] ?? [])
            ->merge(['{url}', '{form_title}', '{sender_name}', '{status}'])
            ->map(fn($p) => trim($p))
            ->filter()->values()->all();
        // Allow limited moustache tokens
        $allowedMustacheNames = ['url', 'actor.name'];

        $unknown = [];
        foreach ($texts as $text) {
            // Curly: {token}
            if (preg_match_all('/\{[a-zA-Z0-9_\.]+\}/', $text, $m1)) {
                foreach ($m1[0] as $tok) {
                    if (!in_array($tok, $allowedCurly, true)) {
                        $unknown[$tok] = true;
                    }
                }
            }
            // Mustache: {{ token }} or @{{ token }}
            if (preg_match_all('/@?\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', $text, $m2)) {
                foreach ($m2[1] as $name) {
                    if (!in_array($name, $allowedMustacheNames, true)) {
                        $unknown['{{ '.$name.' }}'] = true;
                    }
                }
            }
        }

        if (!empty($unknown)) {
            validator([], [])->after(function ($v) use ($unknown) {
                $v->errors()->add('template', 'Placeholders not allowed: '.implode(', ', array_keys($unknown)));
            })->validate();
        }
    }

    private function upsertChannelTemplate(string $module, string $event, string $channel, ?string $subject, ?string $body, ?int $userId, ?string $requestId = null): void
    {
        $payload = [
            'subject_template' => $subject,
            'body_template'    => $body,
            'updated_by'       => $userId,
        ];
        $existing = NotificationTemplate::query()
            ->where('module', $module)
            ->where('event', $event)
            ->where('channel', $channel)
            ->first();
        if ($existing) {
            $existing->fill($payload)->save();
            Log::channel('notifications')->info('notifications.template upsert', [
                'request_id' => $requestId,
                'action' => 'updated',
                'module' => $module,
                'event' => $event,
                'channel' => $channel,
                'has_subject' => $subject !== null && $subject !== '',
                'has_body' => $body !== null && $body !== '',
            ]);
            return;
        }
        NotificationTemplate::create([
            'module' => $module,
            'event'  => $event,
            'channel'=> $channel,
            'subject_template' => $subject,
            'body_template'    => $body,
            'created_by'       => $userId,
            'updated_by'       => $userId,
        ]);
        Log::channel('notifications')->info('notifications.template upsert', [
            'request_id' => $requestId,
            'action' => 'created',
            'module' => $module,
            'event' => $event,
            'channel' => $channel,
            'has_subject' => $subject !== null && $subject !== '',
            'has_body' => $body !== null && $body !== '',
        ]);
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'module'  => 'required|string',
            'event'   => 'required|string',
            'subject' => 'nullable|string',
            'body'    => 'nullable|string',
            'sms'     => 'nullable|string',
        ]);

        $templates = [
            'subject' => $data['subject'] ?? '',
            'body'    => $data['body'] ?? '',
            'sms'     => $data['sms'] ?? '',
        ];

        $rendered = NotificationPlaceholderRenderer::render(
            $data['module'],
            $data['event'],
            $templates,
            $this->buildPreviewContext($data['module'], $data['event'])
        );

        return response()->json([
            'preview' => [
                'subject' => $rendered['subject'] ?? '',
                'body'    => $rendered['body'] ?? '',
                'sms'     => $rendered['sms'] ?? '',
            ],
        ]);
    }

    
    protected function buildPreviewContext(string $module, string $event): array
    {
        $actor = Auth::user();

        $context = [
            'actor'       => $actor,
            'sender_name' => (string) ($actor->name ?? 'کاربر سامانه'),
            'url'         => url('/preview'),
        ];

        if ($module === 'purchase_orders') {
            $po = PurchaseOrder::query()
                ->with('requestedByUser')
                ->latest('id')
                ->first();

            if (! $po) {
                $po = PurchaseOrder::make([
                    'id'        => 1001,
                    'po_number' => 'PO-1001',
                    'subject'   => 'سفارش خرید نمونه',
                    'status'    => 'supervisor_approval',
                ]);

                $requester = $actor
                    ? tap($actor->replicate(), function ($clone) {
                        $clone->name = $clone->name ?: 'درخواست‌کننده نمونه';
                    })
                    : new User(['name' => 'درخواست‌کننده نمونه']);

                $po->setRelation('requestedByUser', $requester);
            }

            $context['purchase_order'] = $po;
            $context['po']             = $po;
            $context['prev_status']    = $po->status ?: 'created';
            $context['new_status']     = $po->status === 'created'
                ? 'supervisor_approval'
                : ($po->status ?? 'supervisor_approval');
            $context['form_title']     = (string) ($po->subject ?? $po->po_number ?? 'سفارش خرید نمونه');
            $context['url']            = $po->id
                ? route('inventory.purchase-orders.show', $po->id)
                : route('inventory.purchase-orders.index');
        } elseif ($module === 'proformas') {
            $pf = \App\Models\Proforma::query()
                ->with('organization')
                ->latest('id')
                ->first() ?: \App\Models\Proforma::make([
                    'id'               => 2001,
                    'proforma_number'  => 'PF-2001',
                    'subject'          => 'پیش‌فاکتور نمونه',
                    'organization_name'=> 'مشتری نمونه',
                ]);

            $context['proforma']        = $pf;
            $context['model']           = $pf;
            $context['proforma_number'] = $pf->proforma_number ?? 'PF-2001';
            $context['customer_name']   = $pf->organization_name ?? optional($pf->organization)->name ?? 'مشتری نمونه';
            $context['approver_name']   = (string) ($actor->name ?? 'تأییدکننده نمونه');
            $context['form_title']      = (string) ($pf->subject ?? $pf->proforma_number ?? 'پیش‌فاکتور نمونه');
            $context['url']             = route('sales.proformas.show', $pf->id ?? 0);
        } elseif (in_array($module, ['leads', 'opportunities'], true)) {
            $lead = \App\Models\SalesLead::query()->latest('id')->first()
                ?: \App\Models\SalesLead::make(['name' => 'سرنخ فروش نمونه']);

            $name = $lead->name ?? 'سرنخ فروش نمونه';

            $context['model']        = $lead;
            $context['lead_name']    = $name;
            $context['old_assignee'] = 'کاربر قبلی';
            $context['new_assignee'] = 'کاربر جدید';
            $context['old_user']     = 'کاربر قبلی';
            $context['new_user']     = 'کاربر جدید';
            $context['form_title']   = $name;
        } elseif ($module === 'notes') {
            $context['note_body']           = 'این یک متن نمونه برای بدنه یادداشت است.';
            $context['note_excerpt']        = 'این یک متن نمونه برای بدنه یادداشت است.';
            $context['mentioned_user']      = 'کاربر یادشده';
            $context['mentioned_user_name'] = 'کاربر یادشده';
            $context['context_label']       = 'زمینه مرتبط';
            $context['form_title']          = 'نمونه عنوان فرم';
        } elseif ($module === 'activities') {
            $activity = new \App\Models\Activity([
                'subject'       => 'فعالیت نمونه',
                'due_at_jalali' => '1404/01/15 10:00',
            ]);

            $context['activity']   = $activity;
            $context['form_title'] = $activity->subject;
        } elseif ($module === 'reports') {
            $context['report_title'] = 'گزارش نمونه سیستم';
            $context['form_title']   = $context['report_title'];
        } elseif ($module === 'emails') {
            $context['email_subject'] = 'موضوع نمونه ایمیل';
            $context['from_name']     = 'فرستنده نمونه';
            $context['from_email']    = 'sender@example.com';
            $context['received_at']   = now()->format('Y-m-d H:i');
            $context['form_title']    = $context['email_subject'];
            $context['url']           = route('mail.index');
        }

        if (empty($context['form_title'])) {
            $context['form_title'] = 'فرم نمونه';
        }

        return $context;
    }



}

<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationRuleController extends Controller
{
    public function index()
    {
        $config = config('notification_events');
        $modules = $config['modules'] ?? [];
        $channelOptions = $config['channels'] ?? ['database' => 'داخلی (سیستم)', 'email' => 'ایمیل'];

        $rules = NotificationRule::query()->get()->keyBy(fn($r) => $r->module.'.'.$r->event);

        $matrix = [];
        foreach ($modules as $moduleKey => $module) {
            $moduleLabel = $module['label'] ?? $moduleKey;
            foreach (($module['events'] ?? []) as $eventKey => $eventDef) {
                $key = $moduleKey.'.'.$eventKey;
                $existing = $rules->get($key);

                $matrix[] = [
                    'module' => $moduleKey,
                    'module_label' => $moduleLabel,
                    'event' => $eventKey,
                    'event_label' => $eventDef['label'] ?? $eventKey,
                    'placeholders' => $eventDef['placeholders'] ?? [],
                    'allowed_placeholders' => $eventDef['placeholders'] ?? [],
                    'channels' => $existing?->channels ?? ($eventDef['default_channels'] ?? ['database']),
                    'enabled' => $existing?->enabled ?? false,
                    'conditions' => $existing?->conditions ?? null,
                    'subject_template' => $existing?->subject_template ?? ($eventDef['default_subject'] ?? ''),
                    'body_template' => $existing?->body_template ?? ($eventDef['default_body'] ?? ''),
                    'id' => $existing?->id,
                ];
            }
        }

        return view('settings.notifications.index', compact('matrix','channelOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'module' => 'required|string',
            'event' => 'required|string',
            'enabled' => 'sometimes|boolean',
            'channels' => 'sometimes|array',
            'channels.*' => 'in:database,email',
            'conditions' => 'sometimes|array',
            'subject_template' => 'required|string|max:5000',
            'body_template' => 'required|string|max:5000',
        ]);

        $modules = config('notification_events.modules', []);
        abort_unless(array_key_exists($data['module'], $modules), 422, 'ماژول نامعتبر است.');
        $events = $modules[$data['module']]['events'] ?? [];
        abort_unless(array_key_exists($data['event'], $events), 422, 'رویداد نامعتبر است.');

        $this->validateConditionsSchema($data['module'], $data['event'], $request->input('conditions', null));
        $this->validateTemplatesPlaceholders($data['module'], $data['event'], $data['subject_template'] ?? '', $data['body_template'] ?? '');

        $userId = Auth::id();

        NotificationRule::updateOrCreate(
            ['module' => $data['module'], 'event' => $data['event']],
            [
                'enabled' => (bool)($data['enabled'] ?? false),
                'channels' => array_values($data['channels'] ?? ['database']),
                'conditions' => $data['conditions'] ?? null,
                'subject_template' => $data['subject_template'],
                'body_template' => $data['body_template'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        if ($request->wantsJson()) {
            $rule = NotificationRule::where('module', $data['module'])->where('event', $data['event'])->first();
            return response()->json([
                'status' => 'ok',
                'rule' => [
                    'id' => $rule?->id,
                    'module' => $rule?->module,
                    'event' => $rule?->event,
                    'subject_template' => $rule?->subject_template,
                    'body_template' => $rule?->body_template,
                ],
            ]);
        }

        return redirect()->route('settings.notifications.index')
            ->with('status', 'تنظیمات اعلان ذخیره شد.');
    }

    public function update(Request $request, NotificationRule $notificationRule)
    {
        $data = $request->validate([
            'enabled' => 'sometimes|boolean',
            'channels' => 'sometimes|array',
            'channels.*' => 'in:database,email',
            'conditions' => 'sometimes|array',
            'subject_template' => 'sometimes|string|max:5000',
            'body_template' => 'sometimes|string|max:5000',
        ]);

        $this->validateConditionsSchema($notificationRule->module, $notificationRule->event, $request->input('conditions', null));
        if ($request->hasAny(['subject_template','body_template'])) {
            $this->validateTemplatesPlaceholders(
                $notificationRule->module,
                $notificationRule->event,
                $data['subject_template'] ?? $notificationRule->subject_template,
                $data['body_template'] ?? $notificationRule->body_template,
            );
        }

        $notificationRule->fill([
            'enabled' => array_key_exists('enabled', $data) ? (bool)$data['enabled'] : $notificationRule->enabled,
            'channels' => $data['channels'] ?? $notificationRule->channels,
            'conditions' => $data['conditions'] ?? $notificationRule->conditions,
            'subject_template' => $data['subject_template'] ?? $notificationRule->subject_template,
            'body_template' => $data['body_template'] ?? $notificationRule->body_template,
            'updated_by' => Auth::id(),
        ])->save();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'rule' => [
                    'id' => $notificationRule->id,
                    'module' => $notificationRule->module,
                    'event' => $notificationRule->event,
                    'subject_template' => $notificationRule->subject_template,
                    'body_template' => $notificationRule->body_template,
                ],
            ]);
        }

        return redirect()->route('settings.notifications.index')
            ->with('status', 'تنظیمات اعلان به‌روزرسانی شد.');
    }

    public function destroy(NotificationRule $notificationRule)
    {
        $notificationRule->delete();
        return redirect()->route('settings.notifications.index')
            ->with('status', 'قانون حذف شد.');
    }

    private function validateConditionsSchema(string $module, string $event, $conditions): void
    {
        if ($conditions === null) return;

        if ($module === 'purchase_orders' && $event === 'status.changed') {
            validator(['conditions' => $conditions], [
                'conditions' => 'array',
                'conditions.from_status' => 'nullable|string|max:100',
                'conditions.to_status' => 'nullable|string|max:100',
            ], [
                'conditions.array' => 'قالب شروط نامعتبر است.',
            ])->validate();
            return;
        }

        validator(['conditions' => $conditions], [
            'conditions' => 'nullable|array|size:0',
        ], [
            'conditions.size' => 'این رویداد شروط سفارشی پشتیبانی نمی‌کند.',
        ])->validate();
    }

    private function validateTemplatesPlaceholders(string $module, string $event, string $subject, string $body): void
    {
        $modules = config('notification_events.modules', []);
        $events = $modules[$module]['events'] ?? [];
        $eventDef = $events[$event] ?? [];
        $allowedCurly = collect($eventDef['placeholders'] ?? [])->map(fn($p) => trim($p))->filter()->values()->all();
        // Allow limited moustache tokens
        $allowedMustacheNames = ['url', 'actor.name'];

        $unknown = [];
        foreach ([$subject, $body] as $text) {
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
}

<?php

namespace App\Services\Notifications;

use App\Models\NotificationRule;
use App\Models\User;
use App\Support\NotificationTemplateResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use App\Services\Sms\FarazEdgeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationRouter
{
    public function findRule(string $module, string $event, array $context): ?NotificationRule
    {
        Log::channel('notifications')->debug('notification_router.find_rule.start', [
            'module' => $module,
            'event' => $event,
            'context_keys' => array_keys($context),
        ]);

        $rules = NotificationRule::query()
            ->where('module', $module)
            ->where('event', $event)
            ->where('enabled', true)
            ->orderBy('id')
            ->get();

        $match = null;
        $bestScore = -1;

        Log::channel('notifications')->debug('notification_router.find_rule.candidates', [
            'module' => $module,
            'event' => $event,
            'rules_count' => $rules->count(),
        ]);

        foreach ($rules as $rule) {
            if (!$this->ruleMatchesConditions($rule, $module, $event, $context)) {
                Log::channel('notifications')->debug('notification_router.find_rule.skip_conditions', [
                    'rule_id' => $rule->id,
                    'conditions' => $rule->conditions,
                    'context' => [
                        'prev_status' => $context['prev_status'] ?? null,
                        'new_status' => $context['new_status'] ?? null,
                    ],
                ]);
                continue;
            }

            $score = $this->ruleSpecificityScore($rule);
            if ($score > $bestScore || ($score === $bestScore && ($match === null || $rule->id > $match->id))) {
                $match = $rule;
                $bestScore = $score;
            }
        }

        Log::channel('notifications')->info('notification_router.find_rule.result', [
            'module' => $module,
            'event' => $event,
            'rule_id' => $match?->id,
            'score' => $bestScore,
        ]);

        return $match;
    }

    public function renderTemplates(NotificationRule $rule, array $context): array
    {
        Log::channel('notifications')->debug('notification_router.render.start', [
            'rule_id' => $rule->id,
            'module' => $rule->module,
            'event' => $rule->event,
        ]);

        $config = config('notification_events');
        $placeholders = Arr::get($config, "modules.{$rule->module}.events.{$rule->event}.placeholders", []);

        $map = $this->buildPlaceholderMap($rule->module, $rule->event, $context);

        $subject = $rule->subject_template ?? '';
        $body    = $rule->body_template ?? '';
        $sms     = $rule->sms_template ?? '';

        // Replace only whitelisted placeholders
        foreach ($placeholders as $ph) {
            if (!is_string($ph) || $ph === '') continue;
            $val = (string) ($map[$ph] ?? '');
            $subject = str_replace($ph, $val, $subject);
            $body    = str_replace($ph, $val, $body);
            $sms     = str_replace($ph, $val, $sms);
        }

        // Also support minimal mustache-style for safe globals: {{ url }}, {{ actor.name }}
        $globals = [
            'url' => (string) ($context['url'] ?? ''),
            'actor.name' => (string) (optional($context['actor'] ?? null)->name ?? ''),
        ];
        foreach ($globals as $key => $val) {
            $token = '{{ '.$key.' }}';
            $subject = str_replace($token, $val, $subject);
            $body    = str_replace($token, $val, $body);
            $sms     = str_replace($token, $val, $sms);
        }

        return [
            'subject' => trim($subject),
            'body'    => trim($body),
            'sms'     => trim($sms),
        ];
    }

    public function dispatch(NotificationRule $rule, array $rendered, array $recipients, array $extra = []): void
    {
        $recipients = array_values(array_filter(array_map(function ($r) {
            if ($r instanceof User) return $r;
            if (is_numeric($r)) return User::find((int) $r);
            return null;
        }, $recipients)));

        if (empty($recipients)) {
            Log::channel('notifications')->warning('notification_router.dispatch.no_recipients', [
                'rule_id' => $rule->id,
                'module' => $rule->module,
                'event' => $rule->event,
            ]);
            return;
        }

        $channels = (array) ($rule->channels ?? ['database']);

        Log::channel('notifications')->info('notification_router.dispatch.start', [
            'rule_id' => $rule->id,
            'module' => $rule->module,
            'event' => $rule->event,
            'channels' => $channels,
            'recipients' => collect($recipients)->map(fn($u) => $u->id ?? $u)->all(),
        ]);

        foreach ($recipients as $user) {
            if (in_array('database', $channels, true)) {
                try {
                    $user->notify(new \App\Notifications\CustomRoutedNotification(
                        $rule->module,
                        $rule->event,
                        $rendered['subject'] ?? '',
                        $rendered['body'] ?? '',
                        $extra['url'] ?? null,
                    ));
                } catch (\Throwable $e) {
                    Log::error('NotificationRouter: failed database notify', [
                        'user_id' => $user->id,
                        'module' => $rule->module,
                        'event'  => $rule->event,
                        'error'  => $e->getMessage(),
                    ]);
                }
            }

            if (in_array('email', $channels, true) && $user->email) {
                try {
                    Mail::to($user->email)
                        ->queue(new \App\Mail\RoutedNotificationMail(
                            $rendered['subject'] ?? '',
                            $rendered['body'] ?? '',
                            $extra['url'] ?? null
                        ));
                } catch (\Throwable $e) {
                    Log::error('NotificationRouter: failed email', [
                        'user_id' => $user->id,
                        'email'   => $user->email,
                        'module' => $rule->module,
                        'event'  => $rule->event,
                        'error'  => $e->getMessage(),
                    ]);
                }
            }

            if (in_array('sms', $channels, true) && $user->mobile) {
                $text = (string) ($rendered['sms'] ?? '');
                if ($text !== '') {
                    try {
                        /** @var FarazEdgeService $sms */
                        $sms = app(FarazEdgeService::class);
                        $sms->sendWebservice($user->mobile, $text);
                    } catch (\Throwable $e) {
                        Log::error('NotificationRouter: failed sms', [
                            'user_id' => $user->id,
                            'mobile'  => $user->mobile,
                            'module' => $rule->module,
                            'event'  => $rule->event,
                            'error'  => $e->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    private function ruleMatchesConditions(NotificationRule $rule, string $module, string $event, array $context): bool
    {
        $conditions = $rule->conditions ?? [];
        if (!$conditions || empty(array_filter((array) $conditions, fn($v) => $v !== null && $v !== ''))) {
            return true;
        }

        try {
            if ($module === 'purchase_orders' && $event === 'status.changed') {
                $from = (string) ($conditions['from_status'] ?? '');
                $to   = (string) ($conditions['to_status'] ?? '');
                $prev = (string) ($context['prev_status'] ?? '');
                $next = (string) ($context['new_status'] ?? '');

                if ($from !== '' && strtolower($from) !== strtolower($prev)) return false;
                if ($to !== '' && strtolower($to) !== strtolower($next)) return false;
                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('NotificationRouter: condition evaluation failed', [
                'module' => $module,
                'event'  => $event,
                'error'  => $e->getMessage(),
            ]);
            return false;
        }

        Log::channel('notifications')->debug('notification_router.rule_matched', [
            'rule_id' => $rule->id,
            'module' => $module,
            'event' => $event,
            'conditions' => $rule->conditions,
        ]);
        return true;
    }
    public function route(string $module, string $event, array $context, array $recipients): void
    {
        $rule = $this->findRule($module, $event, $context);
        if (!$rule) {
            Log::channel('notifications')->info('notification_router.route.fallback', [
                'module' => $module,
                'event' => $event,
                'recipients' => array_map(fn($r) => $r instanceof User ? $r->id : $r, $recipients),
            ]);
            // Fallback: use template resolver + default channels from config
            $def = config("notification_events.modules.$module.events.$event");
            if (!$def) return;

            $channels = (array) ($def['default_channels'] ?? ['database']);

            // Normalize recipients to User models
            $recipients = array_values(array_filter(array_map(function ($r) {
                if ($r instanceof User) return $r;
                if (is_numeric($r)) return User::find((int) $r);
                return null;
            }, $recipients)));

            $url = $context['url'] ?? null;

            foreach ($recipients as $user) {
                if (in_array('database', $channels, true)) {
                    try {
                        $tpl = NotificationTemplateResolver::resolve($module, $event, 'database', $context);
                        $body = (string) ($tpl['body'] ?? '');
                        if ($body !== '') {
                            $user->notify(new \App\Notifications\CustomRoutedNotification(
                                $module,
                                $event,
                                (string) ($tpl['subject'] ?? ''),
                                $body,
                                $url
                            ));
                        }
                    } catch (\Throwable $e) {
                        Log::error('NotificationRouter: fallback database notify failed', [
                            'user_id' => $user->id,
                            'module'  => $module,
                            'event'   => $event,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }

                if (in_array('email', $channels, true) && $user->email) {
                    try {
                        $tpl = NotificationTemplateResolver::resolve($module, $event, 'email', $context);
                        $subj = (string) ($tpl['subject'] ?? '');
                        $body = (string) ($tpl['body'] ?? '');
                        if ($subj !== '' || $body !== '') {
                            Mail::to($user->email)->queue(new \App\Mail\RoutedNotificationMail($subj, $body, $url));
                        }
                    } catch (\Throwable $e) {
                        Log::error('NotificationRouter: fallback email failed', [
                            'user_id' => $user->id,
                            'email'   => $user->email,
                            'module'  => $module,
                            'event'   => $event,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }

                if (in_array('sms', $channels, true) && $user->mobile) {
                    try {
                        $tpl = NotificationTemplateResolver::resolve($module, $event, 'sms', $context);
                        $text = (string) ($tpl['body'] ?? '');
                        if ($text !== '') {
                            /** @var FarazEdgeService $sms */
                            $sms = app(FarazEdgeService::class);
                            $sms->sendWebservice($user->mobile, $text);
                        }
                    } catch (\Throwable $e) {
                        Log::error('NotificationRouter: fallback sms failed', [
                            'user_id' => $user->id,
                            'mobile'  => $user->mobile,
                            'module'  => $module,
                            'event'   => $event,
                            'error'   => $e->getMessage(),
                        ]);
                    }
                }
            }
            return;
        }

        $rendered = $this->renderTemplates($rule, $context);
        $this->dispatch($rule, $rendered, $recipients, [
            'url' => $context['url'] ?? null,
        ]);
    }

    private function buildPlaceholderMap(string $module, string $event, array $context): array
    {
        $map = [];

        if ($module === 'purchase_orders' && $event === 'status.changed') {
            $po   = $context['purchase_order'] ?? null;
            $map['{po_number}']   = (string) ($po->po_number ?? ('#'.($po->id ?? '')));
            $map['{from_status}'] = (string) ($context['prev_status'] ?? '');
            $map['{to_status}']   = (string) ($context['new_status'] ?? '');
            $map['{requester_name}'] = (string) optional($po?->requestedByUser)->name;
            // mustache aliases
            $map['{{ po.number }}'] = $map['{po_number}'];
        }

        if ($module === 'proformas' && $event === 'approval.sent') {
            $pf   = $context['proforma'] ?? null;
            $map['{proforma_number}'] = (string) ($pf->proforma_number ?? ('#'.($pf->id ?? '')));
            $map['{customer_name}']   = (string) ($pf->organization_name ?? optional($pf?->organization)->name ?? '');
            $map['{approver_name}']   = (string) ($context['approver_name'] ?? optional($context['approver'] ?? null)->name ?? '');
            $map['{{ proforma.number }}'] = $map['{proforma_number}'];
        }

        if (in_array($module, ['leads','opportunities','proformas'], true) && $event === 'assigned.changed') {
            $model = $context['model'] ?? null;
            $map['{lead_name}'] = (string) ($model->getNotificationTitle() ?? $model->name ?? $model->subject ?? ('#'.($model->id ?? '')));
            $map['{old_user}']  = (string) ($context['old_assignee'] ?? '');
            $map['{new_user}']  = (string) ($context['new_assignee'] ?? '');
        }

        if ($module === 'notes' && $event === 'note.mentioned') {
            $map['{note_excerpt}']   = Str::limit(strip_tags((string) ($context['note_body'] ?? '')), 120);
            $map['{mentioned_user}'] = (string) ($context['mentioned_user_name'] ?? '');
            $map['{context}']        = (string) ($context['context_label'] ?? '');
        }

        if ($module === 'activities' && $event === 'reminder.due') {
            $act = $context['activity'] ?? null;
            $map['{activity_subject}'] = (string) ($act->subject ?? ('#'.($act->id ?? '')));
            $map['{due_at}'] = (string) ($act->due_at_jalali ?? $act->start_at_jalali ?? '');
        }

        return $map;
    }

    private function ruleSpecificityScore(NotificationRule $rule): int
    {
        $conditions = (array) ($rule->conditions ?? []);
        $filled = array_filter($conditions, fn($value) => $value !== null && $value !== '');

        return count($filled);
    }
}

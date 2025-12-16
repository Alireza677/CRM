<?php

namespace App\Services\Notifications;

use App\Models\NotificationRule;
use App\Models\User;
use App\Support\NotificationTemplateResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Sms\FarazEdgeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Support\NotificationPlaceholderRenderer;

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

        return NotificationPlaceholderRenderer::render(
            $rule->module,
            $rule->event,
            [
                'title'   => $rule->subject_template ?? '',
                'subject' => $rule->subject_template ?? '',
                'body'    => $rule->body_template ?? '',
                'sms'     => $rule->sms_template ?? '',
            ],
            $context
        );
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
            $title = (string) ($rendered['title'] ?? $rendered['subject'] ?? $rendered['body'] ?? '');
            $body  = (string) ($rendered['body'] ?? '');

            if (in_array('database', $channels, true)) {
                try {
                    $user->notify(new \App\Notifications\CustomRoutedNotification(
                        $rule->module,
                        $rule->event,
                        $title,
                        $body,
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
                            $rendered['subject'] ?? $title,
                            $body,
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
                        $title = (string) ($tpl['title'] ?? $tpl['subject'] ?? '');
                        $body  = (string) ($tpl['body'] ?? '');
                        if ($body !== '' || $title !== '') {
                            $user->notify(new \App\Notifications\CustomRoutedNotification(
                                $module,
                                $event,
                                $title !== '' ? $title : ((string) ($tpl['subject'] ?? '')),
                                $body !== '' ? $body : ((string) ($tpl['subject'] ?? '')),
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
                        $subj = (string) ($tpl['subject'] ?? $tpl['title'] ?? '');
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

    private function ruleSpecificityScore(NotificationRule $rule): int
    {
        $conditions = (array) ($rule->conditions ?? []);
        $filled = array_filter($conditions, fn($value) => $value !== null && $value !== '');

        return count($filled);
    }
}

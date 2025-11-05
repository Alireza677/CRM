<?php

namespace App\Services\Notifications;

use App\Models\NotificationRule;
use App\Models\User;
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
        $rule = NotificationRule::query()
            ->where('module', $module)
            ->where('event', $event)
            ->where('enabled', true)
            ->first();

        if (!$rule) return null;

        $conditions = $rule->conditions ?? [];
        if (!$conditions) return $rule; // No conditions, matches

        try {
            if ($module === 'purchase_orders' && $event === 'status.changed') {
                $from = (string) ($conditions['from_status'] ?? '');
                $to   = (string) ($conditions['to_status'] ?? '');
                $prev = (string) ($context['prev_status'] ?? '');
                $next = (string) ($context['new_status'] ?? '');

                if ($from !== '' && strtolower($from) !== strtolower($prev)) return null;
                if ($to   !== '' && strtolower($to)   !== strtolower($next)) return null;
                return $rule;
            }
        } catch (\Throwable $e) {
            Log::warning('NotificationRouter: condition evaluation failed', [
                'module' => $module,
                'event'  => $event,
                'error'  => $e->getMessage(),
            ]);
            return null;
        }

        // For events without supported conditions, just accept
        return $rule;
    }

    public function renderTemplates(NotificationRule $rule, array $context): array
    {
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
            return;
        }

        $channels = (array) ($rule->channels ?? ['database']);

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

    public function route(string $module, string $event, array $context, array $recipients): void
    {
        $rule = $this->findRule($module, $event, $context);
        if (!$rule) return;

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

        return $map;
    }
}

<?php

namespace App\Support;

use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Cache;
use App\Support\NotificationPlaceholderRenderer;

class NotificationTemplateResolver
{
    /**
     * Resolve a template by module/event/channel with fallbacks to
     * NotificationRule and config defaults, then render with context.
     *
     * @return array{subject:?string, body:?string}
     */
    public static function resolve(string $module, string $event, string $channel, array $context = []): array
    {
        $subject = null;
        $body    = null;

        // Cache template rows briefly to reduce DB chatter
        $tpl = Cache::remember(
            sprintf('ntpl:%s:%s:%s', $module, $event, $channel),
            now()->addSeconds(60),
            function () use ($module, $event, $channel) {
                return NotificationTemplate::query()
                    ->where('module', $module)
                    ->where('event', $event)
                    ->where('channel', $channel)
                    ->first();
            }
        );

        if ($tpl) {
            $subject = $tpl->subject_template ?: null;
            $body    = $tpl->body_template ?: null;
        }

        if ($subject === null && $body === null) {
            $rule = Cache::remember(
                sprintf('nrule:%s:%s', $module, $event),
                now()->addSeconds(60),
                function () use ($module, $event) {
                    return NotificationRule::query()
                        ->where('module', $module)
                        ->where('event', $event)
                        ->first();
                }
            );
            if ($rule) {
                if ($channel === 'email') {
                    $subject = $rule->subject_template ?: null;
                    $body    = $rule->body_template ?: null;
                } elseif ($channel === 'database') {
                    $body = $rule->body_template ?: null;
                } elseif ($channel === 'sms') {
                    $body = $rule->sms_template ?: null;
                }
            }
        }

        if ($subject === null && $body === null) {
            $cfg = config('notification_events.modules', []);
            $eventDef = $cfg[$module]['events'][$event] ?? [];
            if ($channel === 'email') {
                $subject = $eventDef['default_subject'] ?? null;
                $body    = $eventDef['default_body'] ?? null;
            } elseif ($channel === 'database') {
                $body    = $eventDef['default_body'] ?? null;
            } elseif ($channel === 'sms') {
                $body    = $eventDef['default_sms'] ?? ($eventDef['default_body'] ?? null);
            }
        }

        $templates = [];
        if ($subject !== null) {
            $templates['subject'] = $subject;
        }
        if ($body !== null) {
            $templates['body'] = $body;
        }

        $rendered = NotificationPlaceholderRenderer::render(
            $module,
            $event,
            $templates,
            $context
        );

        return [
            'subject' => $templates ? ($rendered['subject'] ?? null) : null,
            'body'    => $templates ? ($rendered['body'] ?? null) : null,
        ];
    }
}

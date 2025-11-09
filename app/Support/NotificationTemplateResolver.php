<?php

namespace App\Support;

use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Cache;

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

        // Render placeholders if any text is present
        $render = function (?string $text) use ($context) {
            if ($text === null || $text === '') return $text;
            $out = $text;
            foreach ($context as $k => $v) {
                // Support both {token} and raw token without braces, plus safe string cast
                $val = is_string($v) ? $v : (is_scalar($v) ? (string) $v : '');
                $out = str_replace('{'.$k.'}', $val, $out);
            }
            // Allow legacy moustache-like tokens
            if (isset($context['url'])) {
                $out = str_replace(['{{ url }}','@{{ url }}'], (string) $context['url'], $out);
            }
            if (isset($context['actor.name'])) {
                $out = str_replace(['{{ actor.name }}','@{{ actor.name }}'], (string) $context['actor.name'], $out);
            }
            return $out;
        };

        return [
            'subject' => $render($subject),
            'body'    => $render($body),
        ];
    }
}

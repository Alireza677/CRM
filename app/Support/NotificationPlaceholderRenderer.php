<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NotificationPlaceholderRenderer
{
    /**
     * Render the provided templates using a safe placeholder map.
     *
     * @param  array{title?:?string, subject?:?string, body?:?string, sms?:?string}  $templates
     * @return array{title?:string, subject?:string, body?:string, sms?:string}
     */
    public static function render(
        ?string $module,
        ?string $event,
        array $templates,
        array $context = [],
        ?array $allowedPlaceholders = null
    ): array {
        $module = (string) ($module ?? '');
        $event  = (string) ($event ?? '');

        $result = [];
        $fields = [];
        foreach (['title', 'subject', 'body', 'sms'] as $key) {
            if (array_key_exists($key, $templates)) {
                $fields[$key] = (string) ($templates[$key] ?? '');
            }
        }
        if (empty($fields)) {
            return $result;
        }

        $config = config('notification_events');
        $placeholders = $allowedPlaceholders
            ?? Arr::get($config, "modules.{$module}.events.{$event}.placeholders", []);

        $map = static::buildPlaceholderMap($module, $event, $context);

        // Fall back to context scalars so {status}, {requester_name}, etc. still work.
        foreach ($context as $key => $value) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $map['{'.$key.'}'] = (string) $value;
            }
        }

        // Global helpers available everywhere.
        $map['{url}'] = (string) ($context['url'] ?? '');
        $map['{{ url }}'] = $map['{url}'];
        $map['@{{ url }}'] = $map['{url}'];

        $actorName = (string) (optional($context['actor'] ?? null)->name ?? '');
        $map['{actor.name}'] = $actorName;
        $map['{{ actor.name }}'] = $actorName;
        $map['@{{ actor.name }}'] = $actorName;
        if (!isset($map['{sender_name}']) || $map['{sender_name}'] === '') {
            $map['{sender_name}'] = $actorName;
        }

        $formTitle = static::guessFormTitle($context);
        if ($formTitle !== null && $formTitle !== '') {
            $map['{form_title}'] = $formTitle;
        }

        if (!isset($map['{from_status}']) && isset($context['prev_status'])) {
            $map['{from_status}'] = (string) $context['prev_status'];
        }
        if (!isset($map['{to_status}']) && isset($context['new_status'])) {
            $map['{to_status}'] = (string) $context['new_status'];
        }
        if (!isset($map['{status}'])) {
            $map['{status}'] = (string) ($context['status']
                ?? $context['new_status']
                ?? $context['prev_status']
                ?? '');
        }

        $tokens = array_unique(array_filter(
            array_merge($placeholders ?? [], array_keys($map)),
            fn ($token) => is_string($token) && $token !== ''
        ));

        foreach ($fields as $key => $text) {
            $rendered = $text;
            foreach ($tokens as $token) {
                $rendered = str_replace($token, (string) ($map[$token] ?? ''), $rendered);
            }
            $result[$key] = trim($rendered);
        }

        return $result;
    }

    public static function buildPlaceholderMap(string $module, string $event, array $context): array
    {
        $map = [];

        if ($module === 'purchase_orders') {
            $po = $context['purchase_order'] ?? $context['po'] ?? null;
            $map['{po_number}'] = (string) ($po->po_number ?? ('#'.($po->id ?? '')));
            $map['{po_subject}'] = (string) ($po->subject ?? $map['{po_number}'] ?? '');
            $map['{requester_name}'] = (string) optional($po?->requestedByUser)->name;

            if ($event === 'status.changed') {
                $map['{from_status}'] = (string) ($context['prev_status'] ?? '');
                $map['{to_status}']   = (string) ($context['new_status'] ?? '');
            }

            $map['{status}'] = (string) ($context['new_status']
                ?? $po->status
                ?? $context['status']
                ?? '');

            // mustache aliases for backwards compatibility
            $map['{{ po.number }}'] = $map['{po_number}'];
            $map['{{ po.subject }}'] = $map['{po_subject}'];
        }

        if ($module === 'proformas' && $event === 'approval.sent') {
            $pf = $context['proforma'] ?? $context['model'] ?? null;
            $map['{proforma_number}'] = (string) ($pf->proforma_number ?? ('#'.($pf->id ?? '')));
            $map['{customer_name}'] = (string) ($pf->organization_name ?? optional($pf?->organization)->name ?? '');
            $map['{approver_name}'] = (string) ($context['approver_name'] ?? optional($context['approver'] ?? null)->name ?? '');
            $map['{{ proforma.number }}'] = $map['{proforma_number}'];
        }

        if (in_array($module, ['leads', 'opportunities', 'proformas'], true) && $event === 'assigned.changed') {
            $model = $context['model'] ?? null;
            $map['{lead_name}'] = (string) ($model->getNotificationTitle()
                ?? $model->name
                ?? $model->subject
                ?? ('#'.($model->id ?? '')));
            $map['{old_user}'] = (string) ($context['old_assignee'] ?? '');
            $map['{new_user}'] = (string) ($context['new_assignee'] ?? '');
        }

        if ($module === 'notes' && $event === 'note.mentioned') {
            $map['{note_excerpt}'] = Str::limit(strip_tags((string) ($context['note_body'] ?? '')), 120);
            $map['{mentioned_user}'] = (string) ($context['mentioned_user_name'] ?? '');
            $map['{context}'] = (string) ($context['context_label'] ?? '');
        }

        if ($module === 'activities' && $event === 'reminder.due') {
            $act = $context['activity'] ?? null;
            $map['{activity_subject}'] = (string) ($act->subject ?? ('#'.($act->id ?? '')));
            $map['{due_at}'] = (string) ($act->due_at_jalali ?? $act->start_at_jalali ?? '');
        }

        if ($module === 'emails' && $event === 'received') {
            $map['{email_subject}'] = (string) ($context['email_subject'] ?? $context['subject'] ?? '');
            $map['{from_name}'] = (string) ($context['from_name'] ?? '');
            $map['{from_email}'] = (string) ($context['from_email'] ?? '');
            $map['{received_at}'] = (string) ($context['received_at'] ?? '');

            if (empty($map['{form_title}'])) {
                $map['{form_title}'] = $map['{email_subject}'] ?: (string) ($context['form_title'] ?? '');
            }
        }

        return $map;
    }

    protected static function guessFormTitle(array $context): ?string
    {
        if (!empty($context['form_title'])) {
            return (string) $context['form_title'];
        }

        if (!empty($context['purchase_order'])) {
            $po = $context['purchase_order'];
            return (string) ($po->subject ?? $po->po_number ?? '');
        }

        if (!empty($context['po'])) {
            $po = $context['po'];
            return (string) ($po->subject ?? $po->po_number ?? '');
        }

        if (!empty($context['proforma'])) {
            return static::extractTitleFromModel($context['proforma']);
        }

        if (!empty($context['model'])) {
            $title = static::extractTitleFromModel($context['model']);
            if ($title !== null) {
                return $title;
            }
        }

        if (!empty($context['lead_name'])) {
            return (string) $context['lead_name'];
        }

        if (!empty($context['activity']) && isset($context['activity']->subject)) {
            return (string) $context['activity']->subject;
        }

        if (!empty($context['context_label'])) {
            return (string) $context['context_label'];
        }

        if (!empty($context['report_title'])) {
            return (string) $context['report_title'];
        }

        return null;
    }

    protected static function extractTitleFromModel($model): ?string
    {
        if (!is_object($model)) {
            return null;
        }

        if (method_exists($model, 'getNotificationTitle')) {
            $title = $model->getNotificationTitle();
            if ($title) {
                return (string) $title;
            }
        }

        foreach (['subject', 'title', 'name'] as $prop) {
            if (isset($model->{$prop}) && $model->{$prop} !== null && $model->{$prop} !== '') {
                return (string) $model->{$prop};
            }
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityNoteController extends Controller
{
    public function store(Request $request, Activity $activity)
    {
        $this->authorizeVisibility($activity, $request);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'mentions' => ['nullable'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,gif,webp,pdf,zip,rar,doc,docx,xls,xlsx,ppt,pptx,txt'],
        ]);

        $note = $activity->notes()->create([
            'body' => $validated['body'],
            'user_id' => $request->user()?->id,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ((array) $request->file('attachments') as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }
                $path = $file->store('note-attachments', 'public');
                $note->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'file_mime' => $file->getClientMimeType(),
                ]);
            }
        }

        $formTitle = trim((string) ($activity->subject ?? ''));
        if ($formTitle === '') {
            $formTitle = $activity->id ? ('Activity #' . $activity->id) : 'Activity';
        }

        $tokens = $this->extractMentions($validated['mentions'] ?? null, $note->body);
        if (!empty($tokens)) {
            $mentionedUsers = User::query()
                ->whereIn('username', $tokens)
                ->orWhereIn('name', $tokens)
                ->get()
                ->unique('id');
            foreach ($mentionedUsers as $user) {
                try {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $context = [
                        'note_body' => $note->body,
                        'mentioned_user' => $user,
                        'mentioned_user_name' => $user->name,
                        'context_label' => 'فعالیت',
                        'form_title' => $formTitle,
                        'actor' => $request->user(),
                        'url' => route('activities.show', $activity->id) . '#note-' . $note->id,
                    ];
                    $router->route('notes', 'note.mentioned', $context, [$user]);
                } catch (\Throwable $e) {
                    Log::warning('ActivityNoteController: notification failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return redirect()->route('activities.show', $activity)->with('success', 'یادداشت ثبت شد.');
    }

    public function destroy(Activity $activity, Note $note)
    {
        $this->authorizeVisibility($activity, request());

        if (!($note->noteable instanceof Activity) || (int) $note->noteable->id !== (int) $activity->id) {
            abort(404);
        }

        $note->delete();
        return back()->with('success', 'یادداشت حذف شد.');
    }

    private function authorizeVisibility(Activity $activity, Request $request): void
    {
        $user = $request->user();
        abort_unless(
            !$activity->is_private || $activity->created_by_id === $user?->id || $activity->assigned_to_id === $user?->id,
            403,
            'اجازه دسترسی ندارید.'
        );
    }

    private function extractMentions($rawMentions, string $body): array
    {
        $list = [];

        if (is_array($rawMentions)) {
            foreach ($rawMentions as $item) {
                if (is_string($item)) {
                    $parts = array_map('trim', explode(',', $item));
                    $list = array_merge($list, $parts);
                }
            }
        } elseif (is_string($rawMentions) && $rawMentions !== '') {
            $list = array_map('trim', explode(',', $rawMentions));
        }

        if (preg_match_all('/@([^\s@]+)/u', $body, $m)) {
            $list = array_merge($list, $m[1] ?? []);
        }

        $list = array_filter(array_unique(array_map(function ($v) {
            $v = trim((string) $v);
            return Str::startsWith($v, '@') ? ltrim($v, '@') : $v;
        }, $list)));

        return array_values($list);
    }
}

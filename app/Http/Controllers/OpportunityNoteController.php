<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use App\Models\Opportunity;
use App\Notifications\MentionedInNote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OpportunityNoteController extends Controller
{
    public function store(Request $request, Opportunity $opportunity)
    {
        $validated = $request->validate([
            'content'  => ['required','string','max:2000'],
            'mentions' => ['nullable'], // ممکن است آرایه یا CSV باشد
        ]);

        // ذخیره یادداشت
        $note = $opportunity->notes()->create([
            'body'    => $validated['content'],
            'user_id' => $request->user()->id,
        ]);

        // استخراج username ها (آرایه یا CSV یا از متن با @)
        $usernames = $this->extractMentions($validated['mentions'] ?? null, $note->body);

        // ارسال نوتیفیکیشن
        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('username', $usernames)->get();
            foreach ($mentionedUsers as $user) {
                // unified via NotificationRouter template system
                try {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $context = [
                        'note_body' => $note->body,
                        'mentioned_user' => $user,
                        'mentioned_user_name' => $user->name,
                        'context_label' => 'فرصت فروش',
                        'url' => route('sales.opportunities.show', $opportunity->id) . '#note-' . $note->id,
                    ];
                    $router->route('notes', 'note.mentioned', $context, [$user]);
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // آدرس نمایش فرصت + اسکرول به همین نوت
        $url = route('sales.opportunities.show', $opportunity->id) . '#note-' . $note->id;

        if ($request->ajax()) {
            return response()->json(['success' => true, 'url' => $url, 'note_id' => $note->id]);
        }

        return redirect($url)->with('success', 'یادداشت با موفقیت ذخیره شد.');
    }

    private function extractMentions($rawMentions, string $body): array
    {
        $list = [];

        // اگر mentions آرایه بود
        if (is_array($rawMentions)) {
            foreach ($rawMentions as $item) {
                if (is_string($item)) {
                    $parts = array_map('trim', explode(',', $item));
                    $list = array_merge($list, $parts);
                }
            }
        }
        // اگر یک رشته CSV بود
        elseif (is_string($rawMentions) && $rawMentions !== '') {
            $list = array_map('trim', explode(',', $rawMentions));
        }

        // از داخل متن هم @username ها را بگیر
        if (preg_match_all('/@([^\s@]+)/u', $body, $m)) {
            $list = array_merge($list, $m[1] ?? []);
        }

        // تمیزسازی: حذف @ اول، یکتا، حذف خالی
        $list = array_filter(array_unique(array_map(function ($v) {
            $v = trim((string)$v);
            return Str::startsWith($v, '@') ? ltrim($v, '@') : $v;
        }, $list)));

        return array_values($list);
    }
}

<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesLead;
use App\Models\User;
use App\Notifications\MentionedInNote;

class LeadNoteController extends Controller
{
    public function store(Request $request, SalesLead $lead)
    {
        $request->validate([
            'body'     => 'required|string|max:1000',
            'mentions' => 'nullable|array',
        ]);

        // لیست username ها (از hidden های mentions[])
        $usernames = collect($request->input('mentions', []))
            ->filter()->unique()->values()->toArray();

        // متن نهایی یادداشت (اختیاری: اضافه کردن لیست منشن‌ها)
        $finalBody = $request->body;
        if (!empty($usernames)) {
            $mentionText = collect($usernames)->map(fn($u) => "@{$u}")->implode(' ');
            $finalBody  .= "\n\nمنشن‌شده‌ها: {$mentionText}";
        }

        // ذخیره یادداشت
        $note = $lead->notes()->create([
            'body'    => $finalBody,
            'user_id' => auth()->id(),
        ]);

        // ساخت URL مقصد برای همین یادداشت
        $noteUrl = route('marketing.leads.show', $lead->id) . '#note-' . $note->id;

        // ارسال نوتیفیکیشن به کاربران منشن‌شده (فقط اگر username معتبر دارند)
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
                        'context_label' => 'سرنخ',
                        'url' => route('marketing.leads.show', $lead->id) . '#note-' . $note->id,
                    ];
                    $router->route('notes', 'note.mentioned', $context, [$user]);
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // بعد از ثبت، برگرد به همان لنگرِ یادداشت
        return redirect($noteUrl)->with('success', 'یادداشت با موفقیت ثبت شد.');
    }

    public function show(SalesLead $lead)
    {
        $allUsers = User::whereNotNull('username')->get();
        return view('marketing.leads.show', compact('lead', 'allUsers'));
    }
}

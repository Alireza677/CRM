<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesLead;
use App\Models\Note;
use App\Models\User;
use App\Notifications\MentionedInNote;
use Illuminate\Support\Facades\Notification;

class LeadNoteController extends Controller
{
    public function store(Request $request, SalesLead $lead)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
            'mentions' => 'nullable|array',
        ]);

        // دریافت منشن‌ها
        $usernames = collect($request->input('mentions', []))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // لاگ بگیریم برای تست
        logger('Parsed mentions:', $usernames);

        // ساخت متن یادداشت
        $finalBody = $request->body;

        if (!empty($usernames)) {
            $mentionText = collect($usernames)->map(fn($u) => "@{$u}")->implode(' ');
            $finalBody .= "\n\nمنشن‌شده‌ها: {$mentionText}";
        }
        // ذخیره یادداشت
        $note = $lead->notes()->create([
            'body' => $finalBody,
            'user_id' => auth()->id(),
        ]);

        // نوتیفیکیشن به کاربران منشن‌شده
        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('username', $usernames)->get();
            foreach ($mentionedUsers as $user) {
                $user->notify(new MentionedInNote($note));
            }
        }

        return redirect()->route('marketing.leads.show', ['lead' => $lead->id])
            ->with('success', 'یادداشت با موفقیت ثبت شد.');
    }




    public function show(SalesLead $lead)
    {
        $allUsers = User::whereNotNull('username')->get();
        return view('marketing.leads.show', compact('lead', 'allUsers'));
    }
}

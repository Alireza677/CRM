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
        logger('Mentions from request: ' . json_encode($request->mentions));
        $usernames = $request->mentions ?? [];

        // ساختن متن نهایی یادداشت
        $finalBody = $request->body;
        if (!empty($usernames)) {
            $finalBody .= "\n\nمنشن: ";
            $finalBody .= collect($usernames)->map(fn($u) => "@{$u}")->implode(' ');
        }

        // ذخیره یادداشت
        $note = $lead->notes()->create([
            'body' => $finalBody,
            'user_id' => auth()->id(),
        ]);

        // ارسال نوتیفیکیشن به کاربران منشن‌شده
        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('username', $usernames)
                                  ->whereNotNull('username')
                                  ->get();
        
            foreach ($mentionedUsers as $user) {
                logger("Sending mention notification to: " . $user->username);
                $user->notify(new MentionedInNote($note));
            }
        }
        

        return back()->with('success', 'یادداشت با موفقیت ثبت شد.');
    }

    public function show(SalesLead $lead)
    {
        $allUsers = User::whereNotNull('username')->get();
        return view('marketing.leads.show', compact('lead', 'allUsers'));
    }
}

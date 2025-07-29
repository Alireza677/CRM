<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use App\Models\Opportunity;
use App\Notifications\MentionedInNote;
use Illuminate\Http\Request;

class OpportunityNoteController extends Controller
{
    public function store(Request $request, Opportunity $opportunity)
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'mentions' => 'nullable|array',
        ]);

        $note = $opportunity->notes()->create([
            'body' => $request->input('content'), // 🔁 تغییر content به body
            'user_id' => auth()->id(),
        ]);

        $usernames = collect($request->input('mentions'))->filter()->unique()->toArray();
        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('username', $usernames)->get();
            foreach ($mentionedUsers as $user) {
                $user->notify(new MentionedInNote($note));
            }
        }

        return $request->ajax()
            ? response()->json(['success' => true])
            : back()->with('success', 'یادداشت با موفقیت ذخیره شد.');
    }
}

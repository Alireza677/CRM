<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;

class ContactNoteController extends Controller
{
    public function store(Request $request, Contact $contact)
    {
        $request->validate([
            'body'     => 'required|string|max:1000',
            'mentions' => 'nullable|array',
        ]);

        $usernames = collect($request->input('mentions', []))
            ->filter()->unique()->values()->toArray();

        $finalBody = $request->body;
        if (!empty($usernames)) {
            $mentionText = collect($usernames)->map(fn ($u) => "@{$u}")->implode(' ');
            $finalBody .= "\n\nمنشن‌شده‌ها: {$mentionText}";
        }

        $note = $contact->notes()->create([
            'body'    => $finalBody,
            'user_id' => auth()->id(),
        ]);

        $formTitle = trim((string) ($contact->full_name ?? $contact->name ?? ''));
        if ($formTitle === '') {
            $formTitle = $contact->id ? ('مخاطب #' . $contact->id) : 'مخاطب';
        }

        $noteUrl = route('sales.contacts.show', $contact->id) . '#note-' . $note->id;

        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('username', $usernames)->get();
            foreach ($mentionedUsers as $user) {
                try {
                    $router = app(\App\Services\Notifications\NotificationRouter::class);
                    $context = [
                        'note_body' => $note->body,
                        'mentioned_user' => $user,
                        'mentioned_user_name' => $user->name,
                        'context_label' => 'مخاطب',
                        'form_title' => $formTitle,
                        'actor' => auth()->user(),
                        'url' => $noteUrl,
                    ];
                    $router->route('notes', 'note.mentioned', $context, [$user]);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        return redirect($noteUrl)->with('success', 'یادداشت با موفقیت ثبت شد.');
    }
}

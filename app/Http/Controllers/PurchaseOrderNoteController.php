<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Notifications\MentionedInNote;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseOrderNoteController extends Controller
{
    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'content'  => ['required','string','max:2000'],
            'mentions' => ['nullable'],
        ]);

        $note = $purchaseOrder->notes()->create([
            'body'    => $validated['content'],
            'user_id' => $request->user()->id,
        ]);

        $usernames = $this->extractMentions($validated['mentions'] ?? null, $note->body);
        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('username', $usernames)->get();
            foreach ($mentionedUsers as $user) {
                $user->notify(new MentionedInNote($note));
            }
        }

        $url = route('inventory.purchase-orders.show', $purchaseOrder->id) . '#note-' . $note->id;
        if ($request->ajax()) {
            return response()->json(['success' => true, 'url' => $url, 'note_id' => $note->id]);
        }
        return redirect($url)->with('success', 'یادداشت با موفقیت ذخیره شد.');
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
            $v = trim((string)$v);
            return Str::startsWith($v, '@') ? ltrim($v, '@') : $v;
        }, $list)));

        return array_values($list);
    }
}


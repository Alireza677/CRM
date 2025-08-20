<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\MentionedInNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskNoteController extends Controller
{
    public function store(Request $request, Project $project, Task $task)
    {
        $this->authorize('comment', [$task, $project]);

        $data = $request->validate([
            'body'       => 'required|string|max:5000',
            'mentions'   => 'array',
            'mentions.*' => 'integer|exists:users,id',
        ]);

        DB::transaction(function () use ($request, $project, $task, $data) {
            $author = $request->user();

            $note = new Note([
                'body'    => $data['body'],
                'user_id' => $author?->id,
            ]);

            // ذخیره نوت روی تسک با نام مرف صحیح (noteable)
            $task->notes()->save($note);

            // فقط اجازه منشنِ اعضای همین پروژه
            $mentionIds = collect($data['mentions'] ?? [])
                ->intersect($project->members()->pluck('users.id'))
                ->unique()
                ->values();

            if ($mentionIds->isNotEmpty()) {
                $note->mentions()->sync($mentionIds);

                $users = User::whereIn('id', $mentionIds)->get();
                foreach ($users as $u) {
                    $u->notify(new MentionedInNote($project, $task, $note));
                    $note->mentions()->updateExistingPivot($u->id, ['notified_at' => now()]);
                }
            }
        });

        return back()->with('success', 'یادداشت ثبت شد.');
    }

    public function destroy(Project $project, Task $task, Note $note)
    {
        $this->authorize('delete', $note);

        // اطمینان از تعلق نوت به همین تسک (با مرف noteable)
        if (!($note->noteable instanceof Task) || (int)$note->noteable->id !== (int)$task->id) {
            abort(404);
        }

        $note->delete();
        return back()->with('success', 'یادداشت حذف شد.');
    }
}

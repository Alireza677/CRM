<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\MentionedInNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\Validator;

class TaskNoteController extends Controller
{
   
public function store(Request $request, Project $project, Task $task)
{
    Log::info('TaskNoteController@store: incoming', [
        'route'     => $request->route()?->getName(),
        'method'    => $request->method(),
        'url'       => $request->fullUrl(),
        'projectId' => $project->id ?? null,
        'taskId'    => $task->id ?? null,
        'userId'    => $request->user()?->id,
        'input'     => $request->only(['body','mentions']),
    ]);

    // 1) Authorize با لاگ
    try {
        $this->authorize('comment', [$task, $project]);
        Log::info('TaskNoteController@store: authorized');
    } catch (AuthorizationException $e) {
        Log::warning('TaskNoteController@store: authorization failed', [
            'projectId' => $project->id ?? null,
            'taskId'    => $task->id ?? null,
            'userId'    => $request->user()?->id,
            'message'   => $e->getMessage(),
        ]);
        throw $e; // 403
    }

    // 2) Validation با لاگ (بدون exists؛ خودمان بعداً فیلتر می‌کنیم)
    $validator = Validator::make($request->all(), [
        'body'       => 'required|string|max:5000',
        'mentions'   => 'nullable|array',
        'mentions.*' => 'nullable|distinct|integer',
    ]);

    if ($validator->fails()) {
        Log::warning('TaskNoteController@store: validation failed', [
            'errors' => $validator->errors()->toArray(),
        ]);
        return back()->withErrors($validator)->withInput();
    }

    $data = $validator->validated();

    try {
        DB::transaction(function () use ($request, $project, $task, $data) {
            Log::info('TaskNoteController@store: transaction start', [
                'projectId' => $project->id,
                'taskId'    => $task->id,
            ]);

            $author = $request->user();

            $note = new Note([
                'body'    => $data['body'],
                'user_id' => $author?->id,
            ]);
            $task->notes()->save($note);

            // فیلتر منشن‌ها: فقط کاربران موجود و عضو پروژه
            $incomingIds = collect($data['mentions'] ?? [])
                ->map(fn($v) => (int)$v)
                ->filter();

            $existingIds      = User::whereIn('id', $incomingIds)->pluck('id');
            $projectMemberIds = $project->members()->pluck('users.id');
            $mentionIds       = $existingIds->intersect($projectMemberIds)->unique()->values();

            Log::info('TaskNoteController@store: mentions resolved', [
                'incoming'        => $incomingIds->values()->all(),
                'existingUsers'   => $existingIds->values()->all(),
                'projectMembers'  => $projectMemberIds->values()->all(),
                'finalMentionIds' => $mentionIds->values()->all(),
            ]);

            if ($mentionIds->isNotEmpty()) {
                $note->mentions()->sync($mentionIds);
                $users = User::whereIn('id', $mentionIds)->get();
                foreach ($users as $u) {
                    $u->notify(new MentionedInNote($note, $project, $task));
                    $note->mentions()->updateExistingPivot($u->id, ['notified_at' => now()]);
                }
            }

            Log::info('TaskNoteController@store: transaction end', [
                'noteId' => $note->id,
            ]);
        });
    } catch (\Throwable $e) {
        Log::error('TaskNoteController@store: exception', [
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
            'trace'   => $e->getTraceAsString(),
        ]);
        return back()->withErrors('خطا در ذخیره یادداشت.')->withInput();
    }

    $url = route('projects.tasks.show', [$project, $task]);
    \Log::info('TaskNoteController@store: redirecting to show', ['url' => $url]);
    
    return redirect()->route('projects.tasks.show', [$project, $task], 303)
                     ->with('success', 'یادداشت ثبت شد.');
}

    public function show(Request $request, Project $project, Task $task)
    {
        Log::info('TaskController@show: entered', [
            'route'     => $request->route()?->getName(),
            'url'       => $request->fullUrl(),
            'projectId' => $project->id ?? null,
            'taskId'    => $task->id ?? null,
            'userId'    => $request->user()?->id,
        ]);

        // اگر scoped bindings خاموش بود، این چک مانع mismatch می‌شود
        if ((int) $task->project_id !== (int) $project->id) {
            Log::warning('TaskController@show: task not in project', [
                'task_project_id' => $task->project_id,
                'route_project_id'=> $project->id,
            ]);
            abort(404);
        }

        // اگر پالیسی دیدن تسک داری، فعالش کن (مثل الگوی comment)
        // اگر نداری فعلاً می‌تونی این خط را موقتاً کامنت کنی تا 403 نگیری
        $this->authorize('view', [$task, $project]);
        Log::info('TaskController@show: authorized');

        // (اختیاری) اگر روابط زیر را داری، eager load کن تا نمایش سریع‌تر شود
        // try {
        //     $task->loadMissing([
        //         'notes.user:id,name',
        //         'notes.mentions:id,name',
        //     ]);
        //     Log::info('TaskController@show: relations loaded');
        // } catch (\Throwable $e) {
        //     Log::warning('TaskController@show: relation load skipped', ['err' => $e->getMessage()]);
        // }

        Log::info('TaskController@show: rendering view');
        return view('tasks.show', compact('project', 'task'));
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

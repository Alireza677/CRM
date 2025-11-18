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

                $formTitle = trim((string) ($task->title ?? ''));
                if ($formTitle === '') {
                    $formTitle = $task->id ? ('Task #' . $task->id) : 'Task';
                }

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
                        try {
                            $router = app(\App\Services\Notifications\NotificationRouter::class);
                            $context = [
                                'note_body' => $note->body,
                                'mentioned_user' => $u,
                                'mentioned_user_name' => $u->name,
                                'context_label' => 'Task',
                                'form_title' => $formTitle,
                                'actor' => auth()->user(),
                                'url' => route('projects.tasks.show', [$project, $task]) . '#note-' . $note->id,
                            ];
                            $router->route('notes', 'note.mentioned', $context, [$u]);
                        } catch (\Throwable $e) { /* ignore router errors */ }

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

        // همان چک فعلی
        if ((int) $task->project_id !== (int) $project->id) {
            abort(404);
        }

        $this->authorize('view', [$task, $project]);

        // روابط لازم:
        $task->load([
            'assignee:id,name,email',
            'notes.author:id,name,email',
            'notes.mentions' => function ($q) {
                // اگر روی User گلوبال‌اسکوپ (مثل فیلتر اعضای پروژه/active) دارید،
                // این خط ضروری است تا لیست منشن‌ها تهی نشود
                $q->withoutGlobalScopes()
                ->select('users.id','users.name','users.email');
            },
        ]);

        // برای نمایش لیست کاربران جهت منشن
        $project->load(['members:id,name,email']);

        // دقت کن آدرس ویو با ساختار فایل‌هات یکی باشد:
        // اگر فایل شما همان قبلی است، 'projects.tasks.show' بگذارید
        return view('projects.tasks.show', [
            'project' => $project,
            'task'    => $task,
            'users'   => $project->members,
        ]);

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

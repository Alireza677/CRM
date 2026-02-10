<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\TaskNote;
use App\Models\Contact;
use App\Models\Organization;

class TaskController extends Controller
{
    // ساخت تسک داخل صفحه پروژه
    public function store(Request $request, Project $project)
    {
        if ($project->status === Project::STATUS_COMPLETED) {
            return back()->with('error', 'پروژه تمام شده است و امکان ثبت تسک وجود ندارد.');
        }

        $validated = $request->validate([
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'priority'    => ['required','in:normal,urgent'],
            'assigned_to' => [
                'nullable','integer',
                Rule::in($project->members()->pluck('users.id')->toArray()),
            ],
            'assigned_to_ids' => ['nullable','array'],
            'assigned_to_ids.*' => [
                'integer',
                Rule::in($project->members()->pluck('users.id')->toArray()),
            ],
            'start_at'    => ['nullable','date'],
            'due_at'      => ['nullable','date','after_or_equal:start_at'],
            'related_type' => ['nullable','in:contact,organization','required_with:related_id'],
            'related_id'   => ['nullable','integer','required_with:related_type'],
        ]);
        $request->user()->can('view', $project) || abort(403);

        $relatedType = $validated['related_type'] ?? null;
        $relatedId = $validated['related_id'] ?? null;

        $relatedMap = [
            'contact' => Contact::class,
            'organization' => Organization::class,
        ];

        $relatedModel = $relatedType ? ($relatedMap[$relatedType] ?? null) : null;
        if ($relatedModel && $relatedId) {
            if (!$relatedModel::whereKey($relatedId)->exists()) {
                return back()
                    ->withErrors(['related_id' => 'رکورد مرتبط انتخاب‌شده معتبر نیست.'])
                    ->withInput();
            }
        } else {
            $relatedModel = null;
            $relatedId = null;
        }

        $assigneeIds = $validated['assigned_to_ids'] ?? [];
        if (empty($assigneeIds) && !empty($validated['assigned_to'])) {
            $assigneeIds = [(int) $validated['assigned_to']];
        }
        $assigneeIds = collect($assigneeIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $primaryAssignee = $assigneeIds[0] ?? null;

        $taskData = collect($validated)->except(['assigned_to_ids'])->toArray();
        $taskData['assigned_to'] = $primaryAssignee;

        $task = $project->tasks()->create(array_merge($taskData, [
            'status' => Task::STATUS_PENDING,
            'related_type' => $relatedModel,
            'related_id' => $relatedId,
        ]));

        $task->assignees()->sync($assigneeIds);
        $this->syncActivityForTask($task, (int) $request->user()->id);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'تسک با موفقیت ایجاد شد.');
    }

    public function show(Project $project, Task $task)
    {
        $this->authorize('view', [$task, $project]);
    
        $task = \App\Models\Task::query()
            ->with([
                'assignee:id,name,email',
                'assignees:id,name,email',
                'notes' => function ($q) {
                    $q->latest()
                      ->with([
                          'author:id,name,email',
                          // انتخاب ستون برای users تا ابهام id پیش نیاید
                          'mentions:id,name'
                      ]);
                },
            ])
            ->findOrFail($task->id);
    
        $project->load('members:id,name,email');

        return view('projects.tasks.show', [
            'project' => $project,
            'task'    => $task,
            'users'   => $project->members,
        ]);

    }
    


    // مارک کردن تسک به «انجام شد»
    public function markDone(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);

        if ($project->status === Project::STATUS_COMPLETED) {
            return back()->with('error', 'پروژه تمام شده است و امکان تغییر وضعیت تسک وجود ندارد.');
        }

        // اطمینان از تعلق تسک به پروژه
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        if ($task->status !== Task::STATUS_DONE) {
            $task->status = Task::STATUS_DONE;
            $task->save();
        }

        $this->syncActivityForTask($task, (int) $request->user()->id);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'تسک به وضعیت «انجام شد» تغییر کرد.');
    }
    public function edit(Project $project, Task $task)
    {
        $this->authorize('update', [$task, $project]);
        $project->load('members');
        return view('projects.tasks.edit', compact('project','task'));
    }

    public function update(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', [$task, $project]);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'priority'    => 'required|in:normal,urgent',
            'status'      => 'required|in:pending,done',
            'assigned_to' => 'nullable|exists:users,id',
            'assigned_to_ids' => 'nullable|array',
            'assigned_to_ids.*' => 'integer|exists:users,id',
            'description' => 'nullable|string',
        ]);

        $assigneeIds = $data['assigned_to_ids'] ?? null;
        if (is_array($assigneeIds)) {
            $assigneeIds = collect($assigneeIds)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $data['assigned_to'] = $assigneeIds[0] ?? null;
        }

        $task->update(collect($data)->except(['assigned_to_ids'])->toArray());

        if (is_array($assigneeIds)) {
            $task->assignees()->sync($assigneeIds);
        }

        $this->syncActivityForTask($task, (int) $request->user()->id);

        return redirect()
            ->route('projects.tasks.show', [$project, $task])
            ->with('success', 'تسک با موفقیت ویرایش شد.');
    }

    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', [$task, $project]);
        $this->syncActivityForTask($task, (int) $request->user()->id, true);
        $task->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'تسک حذف شد.');
    }
    public function __construct()
    {
        $this->authorizeResource(\App\Models\Task::class, 'task');
    }

    private function syncActivityForTask(Task $task, ?int $actorId = null, bool $forceDelete = false): void
    {
        $existing = Activity::query()
            ->where('related_type', Task::class)
            ->where('related_id', $task->id)
            ->get()
            ->keyBy('assigned_to_id');

        if ($forceDelete) {
            foreach ($existing as $row) {
                $row->delete();
            }
            return;
        }

        $assigneeIds = $task->assignees()->pluck('users.id')->all();
        if (empty($assigneeIds)) {
            foreach ($existing as $row) {
                $row->delete();
            }
            return;
        }

        $status = $task->status === Task::STATUS_DONE ? 'completed' : 'not_started';
        $progress = $task->status === Task::STATUS_DONE ? 100 : 0;
        $priority = $task->priority === Task::PRIORITY_URGENT ? 'high' : 'normal';
        $startAt = $task->start_at ?? $task->due_at ?? now();

        $projectName = $task->project()->value('name');
        $subject = $projectName ? ("پروژه {$projectName} : {$task->title}") : $task->title;

        $basePayload = [
            'subject' => $subject,
            'start_at' => $startAt,
            'due_at' => $task->due_at,
            'status' => $status,
            'priority' => $priority,
            'progress' => $progress,
            'description' => $task->description,
            'is_private' => true,
            'source' => 'project_task',
            'related_type' => Task::class,
            'related_id' => $task->id,
        ];

        foreach ($assigneeIds as $assigneeId) {
            $payload = array_merge($basePayload, [
                'assigned_to_id' => (int) $assigneeId,
            ]);
            $row = $existing->get((int) $assigneeId);
            if (!$row) {
                $row = new Activity();
                $row->fill($payload);
                $row->created_by_id = $actorId ?: (int) $assigneeId;
                $row->updated_by_id = $actorId ?: (int) $assigneeId;
                $row->save();
                continue;
            }

            $row->fill($payload);
            if ($actorId) {
                $row->updated_by_id = $actorId;
            }
            $row->save();
        }

        foreach ($existing as $assigneeId => $row) {
            if (!in_array((int) $assigneeId, $assigneeIds, true)) {
                $row->delete();
            }
        }
    }
}

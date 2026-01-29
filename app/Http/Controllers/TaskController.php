<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
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
        $validated = $request->validate([
            'title'       => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'priority'    => ['required','in:normal,urgent'],
            'assigned_to' => [
                'nullable','integer',
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

        $project->tasks()->create(array_merge($validated, [
            'status' => Task::STATUS_PENDING,
            'related_type' => $relatedModel,
            'related_id' => $relatedId,
        ]));

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

        // اطمینان از تعلق تسک به پروژه
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        if ($task->status !== Task::STATUS_DONE) {
            $task->status = Task::STATUS_DONE;
            $task->save();
        }

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
            'description' => 'nullable|string',
        ]);

        $task->update($data);

        return redirect()
            ->route('projects.tasks.show', [$project, $task])
            ->with('success', 'تسک با موفقیت ویرایش شد.');
    }

    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', [$task, $project]);
        $task->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'تسک حذف شد.');
    }
    public function __construct()
    {
        $this->authorizeResource(\App\Models\Task::class, 'task');
    }
}

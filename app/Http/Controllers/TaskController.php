<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\TaskNote;

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
        ]);
        $request->user()->can('view', $project) || abort(403);

        $project->tasks()->create($validated + ['status' => Task::STATUS_PENDING]);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'تسک با موفقیت ایجاد شد.');
    }

    public function show(Project $project, Task $task)
    {
        $this->authorize('view', [$task, $project]);

        $task->load(['assignee', 'notes.author', 'notes.mentions']);
        $project->load('members');

        return view('projects.tasks.show', [
            'project' => $project,
            'task'    => $task,
            'users'   => $project->members, // برای منشن
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

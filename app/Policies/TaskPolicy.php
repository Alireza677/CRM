<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    // اگر ادمین داری
    public function before(User $user)
    {
        if (property_exists($user, 'is_admin') && $user->is_admin) {
            return true;
        }
    }

    /**
     * کاربر باید عضو همین پروژه باشد و تسک هم متعلق به همان پروژه.
     * این نسخه طوری نوشته شده که هم با authorize('view', $task)
     * و هم با authorize('view', [$task, $project]) کار کند.
     */
    public function view(User $user, Task $task, ?Project $project = null): bool
    {
        $project ??= $task->project;

        if (!$project || (int)$task->project_id !== (int)$project->id) {
            return false;
        }

        return $project->owner_id === $user->id
            || $project->members()->whereKey($user->id)->exists();
    }

    /**
     * اجازه‌ی ثبت یادداشت روی تسک (Ability که در کنترلر استفاده می‌کنی)
     * امضا باید دقیقاً با [$task, $project] هماهنگ باشد.
     */
    public function comment(User $user, Task $task, Project $project): bool
    {
        if ($project->status === Project::STATUS_COMPLETED) {
            return false;
        }

        // همان منطق view کافی است
        return $this->view($user, $task, $project);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task, ?Project $project = null): bool
    {
        $project ??= $task->project;
        if ($project && $project->status === Project::STATUS_COMPLETED) {
            return false;
        }

        return $this->view($user, $task, $project);
    }

    public function delete(User $user, Task $task, ?Project $project = null): bool
    {
        $project ??= $task->project;
        if ($project && $project->status === Project::STATUS_COMPLETED) {
            return false;
        }

        return $this->view($user, $task, $project);
    }
}

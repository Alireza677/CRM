<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Task;

class TaskPolicy
{
    // اگر لازم داری، می‌تونی viewAny/create را هم تنظیم کنی
    public function view(User $user, Task $task): bool
    {
        $project = $task->project; // رابطه Task -> project باید تعریف شده باشد
        if (!$project) {
            return false;
        }

        // فرض: پروژه یک owner_id دارد و رابطه members() روی Project تعریف شده
        return $project->owner_id === $user->id
            || $project->members()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        // ساخت تسک معمولاً در کانتکست پروژه چک می‌شود (can:view,project).
        // اگر خواستی اینجا هم قید بگذار (مثلاً فقط اعضای پروژه‌ای خاص).
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }
}

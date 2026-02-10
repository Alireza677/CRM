<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool {
        return $project->owner_id === $user->id
            || $project->members()->whereKey($user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        if ($project->status === Project::STATUS_COMPLETED) {
            return false;
        }

        if ($project->manager_id === $user->id) {
            return true;
        }

        return $user->can('projects.manage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        if ($project->status === Project::STATUS_COMPLETED) {
            return false;
        }

        if ($project->manager_id === $user->id) {
            return true;
        }

        if (!is_null($project->owner_id ?? null) && $project->owner_id === $user->id) {
            return true;
        }

        return $user->can('projects.manage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
    public function manageMembers(User $user, Project $project): bool
    {
        if ($project->status === Project::STATUS_COMPLETED) {
            return false;
        }

        if ($project->manager_id === $user->id) {
            return true;
        }
        // اگر Permission دارید و خواستید مدیر سیستم هم بتواند
        return $user->can('projects.manage');
    }

    public function complete(User $user, Project $project): bool
    {
        if ($project->status === Project::STATUS_COMPLETED) {
            return false;
        }

        return $project->manager_id === $user->id;
    }
}

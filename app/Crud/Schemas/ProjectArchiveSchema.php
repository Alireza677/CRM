<?php

namespace App\Crud\Schemas;

use App\Models\Project;
use App\Models\Task;

class ProjectArchiveSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'projects_archive',
            'title' => 'بایگانی پروژه‌ها',
            'model' => Project::class,
            'routes' => [
                'index' => 'projects.archive',
                'create' => 'projects.create',
                'show' => 'projects.show',
                'edit' => 'projects.edit',
                'destroy' => 'projects.destroy',
                'bulkDestroy' => 'projects.bulkDestroy',
            ],
            'per_page' => 15,
            'per_page_options' => [15, 30, 50, 100],
            'query' => function ($query, $request) {
                $user = $request->user();

                $query->where('status', Project::STATUS_COMPLETED)
                ->when($user && !$user->isAdmin(), function ($q) use ($user) {
                    $q->where(function ($sub) use ($user) {
                        $sub->whereHas('members', function ($memberQuery) use ($user) {
                            $memberQuery->where('users.id', $user->id);
                        })
                        ->orWhere('manager_id', $user->id);
                    });
                })
                ->with(['manager', 'members'])
                ->withCount([
                    'members',
                    'tasks',
                    'tasks as tasks_done_count' => function ($q) {
                        $q->where('status', Task::STATUS_DONE);
                    },
                ])
                ->orderByDesc('id');
            },
            'columns' => ProjectSchema::schema()['columns'],
            'filters' => ProjectSchema::schema()['filters'],
        ];
    }
}

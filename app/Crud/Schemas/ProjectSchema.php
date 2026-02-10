<?php

namespace App\Crud\Schemas;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class ProjectSchema
{
    public static function schema(): array
    {
        return [
            'key' => 'projects',
            'title' => 'پروژه‌های جاری',
            'model' => Project::class,
            'routes' => [
                'index' => 'projects.index',
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

                $query->where(function ($q) {
                    $q->where('status', Project::STATUS_ACTIVE)
                      ->orWhereNull('status');
                })
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
            'columns' => [
                [
                    'key' => 'id',
                    'label' => '#',
                    'type' => 'text',
                    'sortable' => true,
                    'width' => 'w-16',
                ],
                [
                    'key' => 'name',
                    'label' => 'نام',
                    'type' => 'link',
                    'sortable' => true,
                ],
                [
                    'key' => 'manager.name',
                    'label' => 'مسئول پروژه',
                    'type' => 'relation',
                ],
                [
                    'key' => 'created_at',
                    'label' => 'تاریخ ایجاد',
                    'type' => 'date',
                    'sortable' => true,
                    'width' => 'w-28',
                ],
                [
                    'key' => 'due_date',
                    'label' => 'موعد مقرر',
                    'type' => 'date',
                    'width' => 'w-28',
                ],
                [
                    'key' => 'members_count',
                    'label' => 'اعضا',
                    'type' => 'text',
                    'width' => 'w-20',
                ],
                [
                    'key' => 'members_names',
                    'label' => 'اعضای پروژه',
                    'type' => 'text',
                    'format' => function ($row) {
                        $names = $row->members?->pluck('name')->filter()->values() ?? collect();
                        return $names->isEmpty() ? '—' : $names->join('، ');
                    },
                ],
                [
                    'key' => 'tasks_progress',
                    'label' => 'پیشرفت تسک‌ها',
                    'type' => 'text',
                    'format' => function ($row) {
                        $tasksCount = (int) ($row->tasks_count ?? 0);
                        $doneCount = (int) ($row->tasks_done_count ?? 0);
                        $progress = $tasksCount > 0 ? round(($doneCount / $tasksCount) * 100) : 0;
                        return $progress . '٪ (' . $doneCount . '/' . $tasksCount . ')';
                    },
                ],
                [
                    'key' => 'actions',
                    'label' => 'اقدامات',
                    'type' => 'actions',
                ],
            ],
            'filters' => [
                [
                    'key' => 'name',
                    'type' => 'text',
                    'placeholder' => 'نام پروژه',
                ],
                [
                    'key' => 'manager',
                    'column' => 'manager.name',
                    'type' => 'text',
                    'columns' => ['manager.name'],
                    'placeholder' => 'مسئول پروژه',
                ],
                [
                    'key' => 'member',
                    'column' => 'members_names',
                    'type' => 'multi',
                    'relation' => 'members',
                    'field' => 'id',
                    'match_all' => true,
                    'options' => function () {
                        return User::query()->orderBy('name')->pluck('name', 'id')->toArray();
                    },
                    'placeholder' => 'اعضای پروژه',
                ],
                [
                    'key' => 'created_at',
                    'type' => 'date',
                    'placeholder' => 'تاریخ ایجاد',
                ],
                [
                    'key' => 'due_date',
                    'type' => 'date',
                    'placeholder' => 'موعد مقرر',
                ],
            ],
        ];
    }
}

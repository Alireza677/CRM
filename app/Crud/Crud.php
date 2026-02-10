<?php

namespace App\Crud;

use App\Helpers\DateHelper;
use App\Helpers\FormOptionsHelper;
use App\Models\SalesLead;
use App\Models\User;
use App\Models\LeadRoundRobinSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Crud
{
    public static function index(string $key, Request $request)
    {
        $schema = SchemaRegistry::get($key);
        $modelClass = $schema['model'] ?? null;

        if (!$modelClass || !class_exists($modelClass)) {
            abort(500, 'Invalid CRUD model.');
        }

        if ($key === 'leads') {
            if ($request->routeIs('marketing.leads.favorites.index')) {
                $schema['title'] = 'علاقه‌مندی‌ها';
                $schema['routes']['index'] = 'marketing.leads.favorites.index';
            } elseif ($request->routeIs('marketing.leads.converted')) {
                $schema['title'] = 'سرنخ‌های تبدیل‌شده';
                $schema['routes']['index'] = 'marketing.leads.converted';
                $convertedColumn = [
                    'key' => 'converted_opportunity_id',
                    'label' => 'فرصت ایجاد شده',
                    'type' => 'html',
                    'format' => function ($row) {
                        $opportunity = $row->convertedOpportunity ?? null;
                        if (!$opportunity) {
                            return '—';
                        }

                        $name = $opportunity->name ?: ('#' . $opportunity->id);
                        $url = route('sales.opportunities.show', $opportunity);

                        return '<a href="' . $url . '" class="text-indigo-600 hover:underline">' . e($name) . '</a>';
                    },
                ];

                $columns = $schema['columns'] ?? [];
                $actionsIndex = array_search('actions', array_column($columns, 'key'), true);
                if ($actionsIndex === false) {
                    $columns[] = $convertedColumn;
                } else {
                    array_splice($columns, $actionsIndex, 0, [$convertedColumn]);
                }
                $schema['columns'] = $columns;
            } elseif ($request->routeIs('sales.leads.junk')) {
                $schema['title'] = 'سرکاری‌ها';
                $schema['routes']['index'] = 'sales.leads.junk';
            }
        } elseif ($key === 'opportunities') {
            if ($request->routeIs('sales.opportunities.favorites.index')) {
                $schema['title'] = 'علاقه‌مندی‌ها';
                $schema['routes']['index'] = 'sales.opportunities.favorites.index';
            }
        }

        $query = $modelClass::query();

        if (isset($schema['query']) && is_callable($schema['query'])) {
            ($schema['query'])($query, $request);
        }

        self::applyFilters($query, $schema, $request);

        [$sort, $dir] = self::applySorting($query, $schema, $request);

        $perPage = self::resolvePerPage($schema, $request);
        $rows = $query->paginate($perPage)->appends($request->query());

        $payload = [
            'schema' => $schema,
            'rows' => $rows,
            'sort' => $sort,
            'dir' => $dir,
            'perPage' => $perPage,
            'key' => $key,
        ];

        if ($key === 'leads') {
            $user = $request->user();
            $favoriteLeadIds = [];
            if ($user) {
                $favoriteLeadIds = DB::table('lead_favorites')
                    ->where('user_id', $user->id)
                    ->whereIn('lead_id', $rows->pluck('id'))
                    ->pluck('lead_id')
                    ->toArray();
            }

            $users = User::all();
            $companyAcquirerUserId = FormOptionsHelper::resolveCompanyAcquirerUserId();
            $companyAcquirerUserName = null;
            if (!empty($companyAcquirerUserId)) {
                $companyAcquirerUserName = $users->firstWhere('id', (int) $companyAcquirerUserId)?->name;
            }

            $payload['leadTabCounts'] = SalesLead::tabCountsFor($user);
            $payload['favoriteLeadIds'] = $favoriteLeadIds;
            $payload['users'] = $users;
            $payload['companyAcquirerUserId'] = $companyAcquirerUserId;
            $payload['companyAcquirerUserName'] = $companyAcquirerUserName;
            $payload['leadPoolRules'] = self::leadPoolRulesData();
        } elseif ($key === 'opportunities') {
            $user = $request->user();
            $favoriteOpportunityIds = [];
            if ($user) {
                $favoriteOpportunityIds = DB::table('opportunity_favorites')
                    ->where('user_id', $user->id)
                    ->whereIn('opportunity_id', $rows->pluck('id'))
                    ->pluck('opportunity_id')
                    ->toArray();
            }

            $payload['favoriteOpportunityIds'] = $favoriteOpportunityIds;
            $payload['opportunityTabCounts'] = $user ? \App\Models\Opportunity::tabCountsFor($user) : ['all' => 0, 'favorites' => 0];
        }

        return view('crud.index', $payload);
    }

    protected static function leadPoolRulesData(): array
    {
        $settings = LeadRoundRobinSetting::query()->first();

        $firstActivityValue = $settings?->sla_duration_value ?? 24;
        $firstActivityUnit = $settings?->sla_duration_unit ?? 'hours';
        $firstActivityLabel = $firstActivityUnit === 'minutes'
            ? $firstActivityValue . ' دقیقه'
            : $firstActivityValue . ' ساعت';

        $maxReassignments = $settings?->max_reassign_count ?? 3;
        $finalDecisionDays = data_get($settings, 'final_decision_days') ?? 14;

        return [
            'first_activity_deadline_label' => $firstActivityLabel,
            'max_reassignments' => $maxReassignments,
            'final_decision_days' => $finalDecisionDays,
        ];
    }

    public static function show(string $key, Model $model, ?Request $request = null)
    {
        $schema = SchemaRegistry::get($key);
        $modelClass = $schema['model'] ?? null;

        if (!$modelClass || !($model instanceof $modelClass)) {
            abort(404);
        }

        $show = $schema['show'] ?? [];
        if (!empty($show['load'])) {
            $model->loadMissing($show['load']);
        }
        if (!empty($show['withCount']) && method_exists($model, 'loadCount')) {
            $model->loadCount($show['withCount']);
        }

        $request = $request ?? request();
        $tabs = $show['tabs'] ?? [];
        $selectedTab = (string) $request->query('tab', array_key_first($tabs) ?? '');
        if (!array_key_exists($selectedTab, $tabs)) {
            $selectedTab = array_key_first($tabs) ?? '';
        }

        $activeTab = $tabs[$selectedTab] ?? null;
        $tabData = [];
        if (is_array($activeTab) && !empty($activeTab['data']) && is_callable($activeTab['data'])) {
            $tabData = (array) ($activeTab['data'])($model, $request);
        }

        return view('crud.show', [
            'schema' => $schema,
            'model' => $model,
            'selectedTab' => $selectedTab,
            'tabData' => $tabData,
            'key' => $key,
        ]);
    }

    protected static function applyFilters($query, array $schema, Request $request): void
    {
        foreach ($schema['filters'] ?? [] as $filter) {
            $key = $filter['key'] ?? null;
            if (!$key) {
                continue;
            }

            $value = $request->query($key);
            $type = $filter['type'] ?? 'text';

            if ($type === 'date') {
                $fromValue = $request->query($key . '_from');
                $toValue = $request->query($key . '_to');

                if (($fromValue !== null && $fromValue !== '') || ($toValue !== null && $toValue !== '')) {
                    $fromNormalized = DateHelper::normalizeDateInput($fromValue);
                    $toNormalized = DateHelper::normalizeDateInput($toValue);

                    if ($fromNormalized) {
                        $query->whereDate($key, '>=', $fromNormalized);
                    }
                    if ($toNormalized) {
                        $query->whereDate($key, '<=', $toNormalized);
                    }
                    continue;
                }
            }

            if ($value === null || $value === '' || (is_array($value) && empty(array_filter($value, fn ($v) => $v !== null && $v !== '')))) {
                continue;
            }

            if (is_array($value)) {
                $values = array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
                if (empty($values)) {
                    continue;
                }

                $columns = $filter['columns'] ?? null;
                if (is_array($columns) && !empty($columns)) {
                    $operator = $filter['operator'] ?? '=';
                    $query->where(function ($q) use ($columns, $values, $operator) {
                        foreach ($columns as $column) {
                            if (!is_string($column) || $column === '') {
                                continue;
                            }

                            if (str_contains($column, '.')) {
                                [$relation, $field] = explode('.', $column, 2);
                                $q->orWhereHas($relation, function ($rq) use ($field, $values, $operator) {
                                    if ($operator === '=') {
                                        $rq->whereIn($field, $values);
                                    } else {
                                        $rq->where(function ($sub) use ($field, $values) {
                                            foreach ($values as $v) {
                                                $sub->orWhere($field, 'like', '%' . $v . '%');
                                            }
                                        });
                                    }
                                });
                                continue;
                            }

                            if ($operator === '=') {
                                $q->orWhereIn($column, $values);
                            } else {
                                $q->orWhere(function ($sub) use ($column, $values) {
                                    foreach ($values as $v) {
                                        $sub->orWhere($column, 'like', '%' . $v . '%');
                                    }
                                });
                            }
                        }
                    });
                    continue;
                }

                if (!empty($filter['relation'])) {
                    $relation = $filter['relation'];
                    $field = $filter['field'] ?? $key;
                    $matchAll = (bool) ($filter['match_all'] ?? false);
                    if ($matchAll) {
                        foreach ($values as $v) {
                            $query->whereHas($relation, function ($q) use ($field, $v) {
                                $qualified = str_contains($field, '.') ? $field : $q->qualifyColumn($field);
                                $q->where($qualified, $v);
                            });
                        }
                    } else {
                        $query->whereHas($relation, function ($q) use ($field, $values) {
                            $qualified = str_contains($field, '.') ? $field : $q->qualifyColumn($field);
                            $q->whereIn($qualified, $values);
                        });
                    }
                    continue;
                }

                $column = $filter['column'] ?? $key;
                $column = (is_string($column) && !str_contains($column, '.')) ? $column : $key;
                $query->whereIn($column, $values);
                continue;
            }

            $columns = $filter['columns'] ?? null;
            if (is_array($columns) && !empty($columns)) {
                $operator = $filter['operator'] ?? 'like';
                $query->where(function ($q) use ($columns, $value, $operator) {
                    foreach ($columns as $column) {
                        if (!is_string($column) || $column === '') {
                            continue;
                        }

                        if (str_contains($column, '.')) {
                            [$relation, $field] = explode('.', $column, 2);
                            $q->orWhereHas($relation, function ($rq) use ($field, $value, $operator) {
                                if ($operator === '=') {
                                    $rq->where($field, $value);
                                } else {
                                    $rq->where($field, 'like', '%' . $value . '%');
                                }
                            });
                            continue;
                        }

                        if ($operator === '=') {
                            $q->orWhere($column, $value);
                        } else {
                            $q->orWhere($column, 'like', '%' . $value . '%');
                        }
                    }
                });
                continue;
            }

            if (!empty($filter['relation'])) {
                $relation = $filter['relation'];
                $field = $filter['field'] ?? $key;
                $query->whereHas($relation, function ($q) use ($field, $value) {
                    $q->where($field, 'like', '%' . $value . '%');
                });
                continue;
            }

            $column = $filter['column'] ?? $key;
            $column = (is_string($column) && !str_contains($column, '.')) ? $column : $key;

            if ($type === 'select') {
                $query->where($column, $value);
                continue;
            }

            if ($type === 'date') {
                $normalized = DateHelper::normalizeDateInput($value);
                if ($normalized) {
                    $query->whereDate($column, $normalized);
                }
                continue;
            }

            $operator = $filter['operator'] ?? 'like';
            if ($operator === '=') {
                $query->where($column, $value);
            } else {
                $query->where($column, 'like', '%' . $value . '%');
            }
        }
    }

    protected static function applySorting($query, array $schema, Request $request): array
    {
        $sort = (string) $request->query('sort', '');
        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortable = collect($schema['columns'] ?? [])
            ->filter(fn ($col) => !empty($col['sortable']))
            ->pluck('key')
            ->filter(fn ($key) => is_string($key) && !str_contains($key, '.'))
            ->all();

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $dir);
        }

        return [$sort, $dir];
    }

    protected static function resolvePerPage(array $schema, Request $request): int
    {
        $default = (int) ($schema['per_page'] ?? 15);
        $perPage = (int) $request->query('per_page', $default);
        $perPage = max(5, min($perPage, 100));

        return $perPage;
    }
}

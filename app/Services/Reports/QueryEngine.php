<?php

namespace App\Services\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QueryEngine
{
    protected array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('reports');
    }

    public function getModelConfig(string $modelKey): array
    {
        $models = $this->config['models'] ?? [];
        if (!isset($models[$modelKey])) {
            throw new InvalidArgumentException('Invalid model');
        }
        return $models[$modelKey];
    }

    public function normalize(array $query): array
    {
        $query['selects'] = array_values(array_unique(array_filter((array)($query['selects'] ?? []))));
        $query['filters'] = array_values(array_filter((array)($query['filters'] ?? [])));
        $query['group_by'] = array_values(array_unique(array_filter((array)($query['group_by'] ?? []))));
        $query['aggregates'] = array_values(array_filter((array)($query['aggregates'] ?? [])));
        $query['sorts'] = array_values(array_filter((array)($query['sorts'] ?? [])));
        $defaultLimit = (int) config('reports_ui.default_export_limit', 15);
        $query['limit'] = (int)($query['limit'] ?? $defaultLimit);
        $query['page'] = max(1, (int)($query['page'] ?? 1));
        $query['model'] = $query['model'] ?? null;
        return $query;
    }

    public function validate(array $query): void
    {
        $modelKey = $query['model'] ?? null;
        if (!$modelKey) {
            throw new InvalidArgumentException('model is required');
        }
        $modelCfg = $this->getModelConfig($modelKey);
        $fields = $modelCfg['fields'];

        // selects
        foreach ($query['selects'] as $f) {
            if (!isset($fields[$f])) {
                throw new InvalidArgumentException("Invalid select field: {$f}");
            }
        }
        // filters
        $opMap = $this->config['operators'];
        foreach ($query['filters'] as $i => $flt) {
            $field = $flt['field'] ?? null;
            $op = $flt['operator'] ?? null;
            if (!$field || !isset($fields[$field])) {
                throw new InvalidArgumentException("Invalid filter field at index {$i}");
            }
            $type = $fields[$field];
            if (!in_array($op, $opMap[$type] ?? [], true)) {
                throw new InvalidArgumentException("Invalid operator '{$op}' for field {$field}");
            }
        }
        // group_by
        foreach ($query['group_by'] as $f) {
            if (!isset($fields[$f])) {
                throw new InvalidArgumentException("Invalid group_by field: {$f}");
            }
        }
        // aggregates
        $allowedAgg = $this->config['aggregates'] ?? [];
        foreach ($query['aggregates'] as $i => $agg) {
            $fn = strtolower($agg['fn'] ?? '');
            $field = $agg['field'] ?? null;
            if (!in_array($fn, $allowedAgg, true)) {
                throw new InvalidArgumentException("Invalid aggregate function: {$fn}");
            }
            if ($fn !== 'count' && (!$field || !isset($fields[$field]))) {
                throw new InvalidArgumentException("Invalid aggregate field at index {$i}");
            }
        }
        // sorts
        foreach ($query['sorts'] as $i => $s) {
            $f = $s['field'] ?? null;
            $dir = strtolower($s['dir'] ?? 'asc');
            if (!$f || !isset($fields[$f])) {
                throw new InvalidArgumentException("Invalid sort field at index {$i}");
            }
            if (!in_array($dir, ['asc','desc'], true)) {
                throw new InvalidArgumentException("Invalid sort direction at index {$i}");
            }
        }
        // limit
        if ($query['limit'] < 1 || $query['limit'] > 200) {
            throw new InvalidArgumentException('limit must be between 1 and 200');
        }
    }

    public function build(array $query): array
    {
        static $memoryCache = [];
        $t0 = microtime(true);
        $query = $this->normalize($query);
        $this->validate($query);

        $cacheKey = hash('sha256', json_encode($query));
        if (isset($memoryCache[$cacheKey])) {
            // Return a copy to avoid external mutation
            return $memoryCache[$cacheKey];
        }

        $modelCfg = $this->getModelConfig($query['model']);
        $class = $modelCfg['class'];
        /** @var EloquentBuilder $builder */
        $builder = $class::query();

        $fields = $modelCfg['fields'];
        $selects = $query['selects'];
        $filters = $query['filters'];
        $groupBy = $query['group_by'];
        $aggregates = $query['aggregates'];
        $sorts = $query['sorts'];
        $limit = $query['limit'];
        $page = $query['page'];

        // Apply filters (type-safe)
        foreach ($filters as $flt) {
            $f = $flt['field'];
            $op = strtolower($flt['operator']);
            $val = $flt['value'] ?? null;
            $type = $fields[$f];

            $builder = $builder->where(function ($q) use ($f, $op, $val, $type) {
                switch ($op) {
                    case 'eq':   $q->where($f, '=', $val); break;
                    case 'neq':  $q->where($f, '!=', $val); break;
                    case 'like': $q->where($f, 'like', "%".$val."%"); break;
                    case 'starts_with': $q->where($f, 'like', $val."%"); break;
                    case 'ends_with':   $q->where($f, 'like', "%".$val); break;
                    case 'gt':   $q->where($f, '>', $val); break;
                    case 'gte':  $q->where($f, '>=', $val); break;
                    case 'lt':   $q->where($f, '<', $val); break;
                    case 'lte':  $q->where($f, '<=', $val); break;
                    case 'in':
                        $arr = is_array($val) ? $val : (is_null($val) ? [] : explode(',', (string)$val));
                        $q->whereIn($f, $arr);
                        break;
                    case 'between':
                        $arr = is_array($val) ? $val : explode(',', (string)$val);
                        $arr = array_values(array_filter($arr, fn($v) => $v !== '' && $v !== null));
                        if (count($arr) === 2) {
                            $q->whereBetween($f, [$arr[0], $arr[1]]);
                        }
                        break;
                }
            });
        }

        $columns = [];
        $queryBuilder = $builder;

        if (!empty($groupBy) || !empty($aggregates)) {
            // Grouping: select group_by fields and aggregates
            $selectParts = [];
            foreach ($groupBy as $gb) {
                $selectParts[] = $gb;
                $columns[] = $gb;
            }
            foreach ($aggregates as $agg) {
                $fn = strtolower($agg['fn']);
                $field = $agg['field'] ?? '*';
                if ($fn === 'count' && ($field === null || $field === '')) { $field = '*'; }
                $alias = ($agg['as'] ?? (sprintf('%s_%s', $fn, $field === '*' ? 'all' : $field)));
                $alias = preg_replace('/[^A-Za-z0-9_]/', '_', $alias);
                // Safe because fn and field are validated against whitelist; alias sanitized
                $selectParts[] = DB::raw(sprintf('%s(%s) as %s', strtoupper($fn), $field === '*' ? '*' : $field, $alias));
                $columns[] = $alias;
            }
            if (!empty($selectParts)) {
                $queryBuilder = $builder->select($selectParts)->groupBy($groupBy);
            }
        } else {
            // Plain select
            $columns = !empty($selects) ? $selects : ['id','created_at'];
            $queryBuilder = $builder->select($columns);
        }

        // Order by
        foreach ($sorts as $s) {
            $queryBuilder->orderBy($s['field'], strtolower($s['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc');
        }

        // Paginate
        /** @var LengthAwarePaginator $paginator */
        $paginator = $queryBuilder->paginate($limit, ['*'], 'page', $page);
        $rows = collect($paginator->items())->map(function ($row) {
            return is_array($row) ? $row : $row->toArray();
        })->all();

        // Summary (aggregates over all, without group)
        $summary = null;
        if (!empty($aggregates)) {
            $summary = [];
            foreach ($aggregates as $agg) {
                $fn = strtolower($agg['fn']);
                $field = $agg['field'] ?? '*';
                $alias = $agg['as'] ?? (sprintf('%s_%s', $fn, $field === '*' ? 'all' : $field));
                if ($fn === 'count' && ($field === null || $field === '')) { $field = '*'; }
                $summary[$alias] = $field === '*' ? $builder->{$fn}() : $builder->{$fn}($field);
            }
        }

        $execMs = (int) round((microtime(true) - $t0) * 1000);

        $result = [
            'columns' => $columns,
            'rows' => $rows,
            'summary' => $summary,
            'meta' => [
                'page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'exec_ms' => $execMs,
            ],
        ];
        $memoryCache[$cacheKey] = $result;
        return $result;
    }
}

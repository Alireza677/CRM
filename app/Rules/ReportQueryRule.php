<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ReportQueryRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // nullable
        }
        if (!is_array($value)) {
            $fail('query_json must be an object/array.');
            return;
        }
        $cfg = config('reports');
        $models = $cfg['models'] ?? [];
        $ops = $cfg['operators'] ?? [];
        $aggs = $cfg['aggregates'] ?? [];

        $modelKey = $value['model'] ?? null;
        if (!$modelKey || !isset($models[$modelKey])) {
            $fail('Invalid or missing model.');
            return;
        }
        $fields = $models[$modelKey]['fields'] ?? [];

        // selects
        foreach ((array)($value['selects'] ?? []) as $f) {
            if (!isset($fields[$f])) {
                $fail("Invalid select field: {$f}"); return;
            }
        }
        // filters
        foreach ((array)($value['filters'] ?? []) as $i => $flt) {
            $field = $flt['field'] ?? null;
            $op = $flt['operator'] ?? null;
            if (!$field || !isset($fields[$field])) { $fail("Invalid filter field at index {$i}"); return; }
            $type = $fields[$field];
            if (!in_array($op, $ops[$type] ?? [], true)) { $fail("Invalid operator '{$op}' for {$field}"); return; }
        }
        // group_by
        foreach ((array)($value['group_by'] ?? []) as $f) {
            if (!isset($fields[$f])) { $fail("Invalid group_by field: {$f}"); return; }
        }
        // aggregates
        foreach ((array)($value['aggregates'] ?? []) as $i => $agg) {
            $fn = strtolower($agg['fn'] ?? '');
            $field = $agg['field'] ?? null;
            if (!in_array($fn, $aggs, true)) { $fail("Invalid aggregate function: {$fn}"); return; }
            if ($fn !== 'count' && (!$field || !isset($fields[$field]))) { $fail("Invalid aggregate field at index {$i}"); return; }
        }
        // sorts
        foreach ((array)($value['sorts'] ?? []) as $i => $s) {
            $f = $s['field'] ?? null; $dir = strtolower($s['dir'] ?? 'asc');
            if (!$f || !isset($fields[$f])) { $fail("Invalid sort field at index {$i}"); return; }
            if (!in_array($dir, ['asc','desc'], true)) { $fail("Invalid sort direction at index {$i}"); return; }
        }
        // limit/page
        $limit = (int)($value['limit'] ?? 15);
        if ($limit < 1 || $limit > 200) { $fail('limit must be between 1 and 200'); return; }
        $page = (int)($value['page'] ?? 1);
        if ($page < 1) { $fail('page must be >= 1'); return; }
    }
}


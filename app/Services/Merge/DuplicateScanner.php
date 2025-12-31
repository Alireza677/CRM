<?php

namespace App\Services\Merge;

use App\Models\DuplicateGroup;
use App\Models\DuplicateGroupItem;
use App\Services\Merge\Contracts\MergeConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DuplicateScanner
{
    public function __construct(private readonly Normalizer $normalizer)
    {
    }

    public function scan(MergeConfig $config): int
    {
        $entityType = $config->entityType();
        $modelClass = $config->modelClass();
        $model = new $modelClass();
        $table = $model->getTable();

        $fields = array_values(array_filter($config->uniqueFields(), function (string $field) use ($table) {
            return Schema::hasColumn($table, $field);
        }));

        if (empty($fields)) {
            return 0;
        }

        return DB::transaction(function () use ($entityType, $modelClass, $fields, $table) {
            DuplicateGroup::where('entity_type', $entityType)->delete();

            $groups = [];

            $query = $modelClass::query()
                ->select(array_merge(['id'], $fields));

            if (Schema::hasColumn($table, 'merged_into_id')) {
                $query->whereNull($table . '.merged_into_id');
            }

            $query
                ->orderBy('id')
                ->chunkById(500, function ($rows) use (&$groups, $fields) {
                    foreach ($rows as $row) {
                        foreach ($fields as $field) {
                            $raw = $row->{$field} ?? null;
                            if ($raw === null || $raw === '') {
                                continue;
                            }

                            $normalized = $this->normalizeField($field, $raw);
                            if (!$normalized) {
                                continue;
                            }

                            $key = $field . ':' . $normalized;
                            $groups[$key]['match_key'] = $field;
                            $groups[$key]['match_value'] = $normalized;
                            $groups[$key]['entity_ids'][] = $row->id;
                        }
                    }
                });

            $createdGroups = 0;

            foreach ($groups as $group) {
                $entityIds = array_values(array_unique($group['entity_ids'] ?? []));
                if (count($entityIds) < 2) {
                    continue;
                }

                $duplicateGroup = DuplicateGroup::create([
                    'entity_type' => $entityType,
                    'match_key' => $group['match_key'],
                    'match_value' => $group['match_value'],
                ]);

                foreach ($entityIds as $entityId) {
                    DuplicateGroupItem::create([
                        'duplicate_group_id' => $duplicateGroup->id,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ]);
                }

                $createdGroups++;
            }

            return $createdGroups;
        });
    }

    private function normalizeField(string $field, string $value): ?string
    {
        if (str_contains($field, 'email')) {
            return $this->normalizer->normalizeEmail($value);
        }

        if (str_contains($field, 'name')) {
            return $this->normalizer->normalizeName($value);
        }

        return $this->normalizer->normalizePhone($value);
    }
}

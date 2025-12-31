<?php

namespace App\Services\Merge;

use App\Models\EntityMerge;
use App\Services\Merge\Contracts\MergeConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class MergeService
{
    public function merge(
        MergeConfig $config,
        int $winnerId,
        array $loserIds,
        array $fieldResolution = [],
        ?int $userId = null
    ): Model {
        $loserIds = array_values(array_unique(array_filter($loserIds, fn ($id) => (int) $id !== (int) $winnerId)));
        if (empty($loserIds)) {
            throw new InvalidArgumentException('Loser list cannot be empty.');
        }

        $modelClass = $config->modelClass();

        return DB::transaction(function () use ($config, $modelClass, $winnerId, $loserIds, $fieldResolution, $userId) {
            $winner = $modelClass::withoutGlobalScopes()->lockForUpdate()->findOrFail($winnerId);
            $losers = $modelClass::withoutGlobalScopes()
                ->whereIn('id', $loserIds)
                ->lockForUpdate()
                ->get();

            if ($losers->count() !== count($loserIds)) {
                throw new InvalidArgumentException('One or more losers were not found.');
            }

            foreach ($losers as $loser) {
                if (!empty($loser->merged_into_id)) {
                    throw new InvalidArgumentException('One or more losers are already merged.');
                }
            }

            $model = new $modelClass();
            $table = $model->getTable();
            $fields = array_values(array_filter(
                $config->mergeableFields(),
                fn (string $field) => Schema::hasColumn($table, $field)
            ));

            [$resolvedFields, $resolutionMeta] = $this->resolveFieldValues(
                $winner,
                $losers,
                $fields,
                $fieldResolution
            );

            $winner->fill($resolvedFields);
            $winner->save();

            $relationDefs = $config->relationDefinitions();

            foreach ($losers as $loser) {
                $relationsMoved = [
                    'direct' => [],
                    'pivot' => [],
                    'polymorphic' => [],
                ];

                foreach ($relationDefs['direct'] ?? [] as $direct) {
                    $table = $direct['table'];
                    $foreignKey = $direct['foreign_key'];

                    $count = DB::table($table)
                        ->where($foreignKey, $loser->id)
                        ->update([$foreignKey => $winner->id]);

                    $relationsMoved['direct'][$table] = $count;
                }

                foreach ($relationDefs['pivot'] ?? [] as $pivot) {
                    $table = $pivot['table'];
                    $foreignKey = $pivot['foreign_key'];
                    $relatedKey = $pivot['related_key'];

                    $loserRelated = DB::table($table)
                        ->where($foreignKey, $loser->id)
                        ->pluck($relatedKey)
                        ->all();

                    if (!empty($loserRelated)) {
                        $winnerRelated = DB::table($table)
                            ->where($foreignKey, $winner->id)
                            ->pluck($relatedKey)
                            ->all();

                        $missing = array_values(array_diff($loserRelated, $winnerRelated));
                        if (!empty($missing)) {
                            $now = now();
                            $rows = array_map(function ($relatedId) use ($foreignKey, $relatedKey, $winner, $now) {
                                return [
                                    $foreignKey => $winner->id,
                                    $relatedKey => $relatedId,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }, $missing);

                            DB::table($table)->insert($rows);
                        }
                    }

                    DB::table($table)->where($foreignKey, $loser->id)->delete();

                    $relationsMoved['pivot'][$table] = count($loserRelated);
                }

                foreach ($relationDefs['polymorphic'] ?? [] as $poly) {
                    $table = $poly['table'];
                    $typeCol = $poly['morph_type_column'];
                    $idCol = $poly['morph_id_column'];
                    $morphClass = $poly['morph_class'];

                    $count = DB::table($table)
                        ->where($typeCol, $morphClass)
                        ->where($idCol, $loser->id)
                        ->update([$idCol => $winner->id]);

                    $relationsMoved['polymorphic'][$table] = $count;
                }

                $loser->merged_into_id = $winner->id;
                $loser->merged_at = now();
                $loser->save();

                EntityMerge::create([
                    'entity_type' => $config->entityType(),
                    'winner_id' => $winner->id,
                    'loser_id' => $loser->id,
                    'field_resolution' => $resolutionMeta,
                    'relations_moved' => $relationsMoved,
                    'user_id' => $userId,
                ]);
            }

            return $winner;
        });
    }

    private function resolveFieldValues(Model $winner, $losers, array $fields, array $fieldResolution): array
    {
        $resolved = [];
        $resolutionMeta = [];

        foreach ($fields as $field) {
            $valueMap = [$winner->id => $winner->{$field} ?? null];
            foreach ($losers as $loser) {
                $valueMap[$loser->id] = $loser->{$field} ?? null;
            }

            $nonBlank = [];
            foreach ($valueMap as $contactId => $value) {
                if (!$this->isBlank($value)) {
                    $nonBlank[(string) $value] = $contactId;
                }
            }

            if (count($nonBlank) === 0) {
                $resolved[$field] = null;
                $resolutionMeta[$field] = [
                    'selected_id' => null,
                    'value' => null,
                ];
                continue;
            }

            if (count($nonBlank) === 1) {
                $value = array_key_first($nonBlank);
                $selectedId = $nonBlank[$value];
                $resolved[$field] = $valueMap[$selectedId];
                $resolutionMeta[$field] = [
                    'selected_id' => $selectedId,
                    'value' => $valueMap[$selectedId],
                ];
                continue;
            }

            $selectedId = Arr::get($fieldResolution, $field);
            if (!$selectedId) {
                throw new InvalidArgumentException("Field resolution missing for {$field}.");
            }
            if (!array_key_exists($selectedId, $valueMap)) {
                throw new InvalidArgumentException("Invalid field resolution for {$field}.");
            }

            $resolved[$field] = $valueMap[$selectedId];
            $resolutionMeta[$field] = [
                'selected_id' => $selectedId,
                'value' => $valueMap[$selectedId],
            ];
        }

        return [$resolved, $resolutionMeta];
    }

    private function isBlank($value): bool
    {
        if ($value === null) {
            return true;
        }

        return is_string($value) && trim($value) === '';
    }
}

<?php

namespace App\Services\Merge;

use App\Models\Contact;
use App\Models\DuplicateGroup;
use App\Services\Merge\Configs\ContactMergeConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContactDuplicateScanner
{
    private const MATCH_KEY_MOBILE = 'mobile';
    private const MATCH_KEY_PROVINCE = 'province';
    private const MATCH_KEY_ORGANIZATION = 'organization';

    public function __construct(private readonly Normalizer $normalizer)
    {
    }

    public function scan(array $matchKeys): int
    {
        $matchKeys = $this->sanitizeMatchKeys($matchKeys);
        if (empty($matchKeys)) {
            $matchKeys = [self::MATCH_KEY_MOBILE];
        }

        return DB::transaction(function () use ($matchKeys) {
            DuplicateGroup::where('entity_type', ContactMergeConfig::ENTITY_TYPE)->delete();

            $groups = $this->buildGroups($matchKeys);
            if (empty($groups)) {
                return 0;
            }

            $now = now();
            $groupRows = [];
            $matchKeyLabel = implode('+', $matchKeys);

            foreach ($groups as $group) {
                $groupRows[] = [
                    'entity_type' => ContactMergeConfig::ENTITY_TYPE,
                    'match_key' => $matchKeyLabel,
                    'match_value' => $group['match_value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($groupRows, 500) as $chunk) {
                DB::table('duplicate_groups')->insert($chunk);
            }

            $groupIdMap = DuplicateGroup::query()
                ->where('entity_type', ContactMergeConfig::ENTITY_TYPE)
                ->get(['id', 'match_value'])
                ->mapWithKeys(fn ($group) => [$group->match_value => $group->id])
                ->all();

            $createdGroups = 0;
            $itemRows = [];

            foreach ($groups as $key => $group) {
                $groupId = $groupIdMap[$key] ?? null;
                if (!$groupId) {
                    continue;
                }

                $createdGroups++;
                foreach ($group['entity_ids'] as $entityId) {
                    $itemRows[] = [
                        'duplicate_group_id' => $groupId,
                        'entity_type' => ContactMergeConfig::ENTITY_TYPE,
                        'entity_id' => $entityId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (count($itemRows) >= 1000) {
                    DB::table('duplicate_group_items')->insert($itemRows);
                    $itemRows = [];
                }
            }

            if (!empty($itemRows)) {
                DB::table('duplicate_group_items')->insert($itemRows);
            }

            return $createdGroups;
        });
    }

    private function buildGroups(array $matchKeys): array
    {
        $table = (new Contact())->getTable();
        $matchKeys = $this->filterKeysBySchema($matchKeys, $table);
        if (empty($matchKeys)) {
            return [];
        }

        $select = ['id'];
        if (in_array(self::MATCH_KEY_MOBILE, $matchKeys, true)) {
            $select[] = 'mobile';
        }
        if (in_array(self::MATCH_KEY_PROVINCE, $matchKeys, true)) {
            $select[] = 'state';
        }
        if (in_array(self::MATCH_KEY_ORGANIZATION, $matchKeys, true)) {
            $select[] = 'organization_id';
        }

        $groups = [];

        Contact::query()
            ->select(array_unique($select))
            ->when(Schema::hasColumn($table, 'merged_into_id'), function ($query) use ($table) {
                $query->whereNull($table . '.merged_into_id');
            })
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$groups, $matchKeys) {
                foreach ($rows as $row) {
                    $normalizedValues = [];

                    if (in_array(self::MATCH_KEY_MOBILE, $matchKeys, true)) {
                        $normalizedValues[self::MATCH_KEY_MOBILE] = $this->normalizeMobile($row->mobile ?? null);
                    }

                    if (in_array(self::MATCH_KEY_PROVINCE, $matchKeys, true)) {
                        $normalizedValues[self::MATCH_KEY_PROVINCE] = $this->normalizeProvince($row->state ?? null);
                    }

                    if (in_array(self::MATCH_KEY_ORGANIZATION, $matchKeys, true)) {
                        $normalizedValues[self::MATCH_KEY_ORGANIZATION] = $this->normalizeOrganization($row->organization_id ?? null);
                    }

                    $compositeValue = $this->buildCompositeValue($matchKeys, $normalizedValues);
                    if (!$compositeValue) {
                        continue;
                    }

                    $groups[$compositeValue]['match_value'] = $compositeValue;
                    $groups[$compositeValue]['entity_ids'][] = $row->id;
                }
            });

        $filtered = [];
        foreach ($groups as $key => $group) {
            $entityIds = array_values(array_unique($group['entity_ids'] ?? []));
            if (count($entityIds) < 2) {
                continue;
            }
            $group['entity_ids'] = $entityIds;
            $filtered[$key] = $group;
        }

        return $filtered;
    }

    private function buildCompositeValue(array $matchKeys, array $normalizedValues): ?string
    {
        $values = [];
        foreach ($matchKeys as $matchKey) {
            $value = $normalizedValues[$matchKey] ?? null;
            if ($value === null || $value === '') {
                return null;
            }
            $values[$matchKey] = $value;
        }

        $parts = [];
        foreach ($values as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        return implode(' | ', $parts);
    }
    private function normalizeMobile(?string $value): ?string
    {
        return $this->normalizer->normalizePhone($value);
    }

    private function normalizeProvince(?string $value): ?string
    {
        return $this->normalizer->normalizeName($value);
    }

    private function normalizeOrganization($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (string) ((int) $value);
        }

        return null;
    }

    private function sanitizeMatchKeys(array $matchKeys): array
    {
        $allowed = [
            self::MATCH_KEY_MOBILE,
            self::MATCH_KEY_PROVINCE,
            self::MATCH_KEY_ORGANIZATION,
        ];

        $keys = array_values(array_unique(array_intersect($matchKeys, $allowed)));

        return array_values($keys);
    }

    private function filterKeysBySchema(array $matchKeys, string $table): array
    {
        $available = [];
        foreach ($matchKeys as $key) {
            if ($key === self::MATCH_KEY_MOBILE && Schema::hasColumn($table, 'mobile')) {
                $available[] = $key;
            }
            if ($key === self::MATCH_KEY_PROVINCE && Schema::hasColumn($table, 'state')) {
                $available[] = $key;
            }
            if ($key === self::MATCH_KEY_ORGANIZATION && Schema::hasColumn($table, 'organization_id')) {
                $available[] = $key;
            }
        }

        return $available;
    }
}

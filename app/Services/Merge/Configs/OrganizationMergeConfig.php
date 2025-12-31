<?php

namespace App\Services\Merge\Configs;

use App\Models\Organization;
use App\Services\Merge\Contracts\MergeConfig;

class OrganizationMergeConfig implements MergeConfig
{
    public const ENTITY_TYPE = 'organization';

    public function entityType(): string
    {
        return self::ENTITY_TYPE;
    }

    public function modelClass(): string
    {
        return Organization::class;
    }

    public function mergeableFields(): array
    {
        return [
            'name',
            'email',
            'phone',
            'address',
            'website',
            'industry',
            'size',
            'notes',
            'state',
            'city',
            'assigned_to',
        ];
    }

    public function uniqueFields(): array
    {
        return ['name', 'email', 'phone'];
    }

    public function relationDefinitions(): array
    {
        return [
            'direct' => [
                ['table' => 'contacts', 'foreign_key' => 'organization_id'],
                ['table' => 'opportunities', 'foreign_key' => 'organization_id'],
                ['table' => 'proformas', 'foreign_key' => 'organization_id'],
                ['table' => 'quotations', 'foreign_key' => 'organization_id'],
            ],
            'pivot' => [],
            'polymorphic' => [
                [
                    'table' => 'notes',
                    'morph_type_column' => 'noteable_type',
                    'morph_id_column' => 'noteable_id',
                    'morph_class' => Organization::class,
                ],
                [
                    'table' => config('activitylog.table_name', 'activity_log'),
                    'morph_type_column' => 'subject_type',
                    'morph_id_column' => 'subject_id',
                    'morph_class' => Organization::class,
                ],
            ],
        ];
    }
}

<?php

namespace App\Services\Merge;

use App\Services\Merge\Configs\ContactMergeConfig;
use App\Services\Merge\Configs\OrganizationMergeConfig;
use App\Services\Merge\Contracts\MergeConfig;
use InvalidArgumentException;

class MergeConfigResolver
{
    public function resolve(string $entityType): MergeConfig
    {
        return match ($entityType) {
            ContactMergeConfig::ENTITY_TYPE => new ContactMergeConfig(),
            OrganizationMergeConfig::ENTITY_TYPE => new OrganizationMergeConfig(),
            default => throw new InvalidArgumentException("Unsupported entity type: {$entityType}"),
        };
    }
}

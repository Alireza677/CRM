<?php

namespace App\Crud;

class ResourceMap
{
    protected static array $map = [
        \App\Models\Opportunity::class => 'opportunities',
    ];

    public static function forModel(string $modelClass): ?string
    {
        return self::$map[$modelClass] ?? null;
    }
}

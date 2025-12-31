<?php

namespace App\Services\Merge\Contracts;

interface MergeConfig
{
    public function entityType(): string;

    /** @return class-string */
    public function modelClass(): string;

    /** @return array<int, string> */
    public function mergeableFields(): array;

    /** @return array<int, string> */
    public function uniqueFields(): array;

    /**
     * @return array{
     *   direct: array<int, array{table: string, foreign_key: string}>,
     *   pivot: array<int, array{table: string, foreign_key: string, related_key: string}>,
     *   polymorphic: array<int, array{table: string, morph_type_column: string, morph_id_column: string, morph_class: string}>
     * }
     */
    public function relationDefinitions(): array;
}

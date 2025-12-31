<?php

namespace App\Jobs;

use App\Services\Merge\DuplicateScanner;
use App\Services\Merge\MergeConfigResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ScanDuplicateGroupsJob
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(private readonly string $entityType)
    {
    }

    public function handle(DuplicateScanner $scanner, MergeConfigResolver $resolver): void
    {
        $config = $resolver->resolve($this->entityType);
        $scanner->scan($config);
    }
}

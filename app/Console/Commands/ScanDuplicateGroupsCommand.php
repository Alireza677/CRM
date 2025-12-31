<?php

namespace App\Console\Commands;

use App\Jobs\ScanDuplicateGroupsJob;
use App\Services\Merge\Configs\ContactMergeConfig;
use App\Services\Merge\Configs\OrganizationMergeConfig;
use Illuminate\Console\Command;

class ScanDuplicateGroupsCommand extends Command
{
    protected $signature = 'crm:scan-duplicates {entity=contact}';

    protected $description = 'Scan entities for duplicates and build duplicate groups.';

    public function handle(): int
    {
        $entity = (string) $this->argument('entity');

        $supported = [ContactMergeConfig::ENTITY_TYPE, OrganizationMergeConfig::ENTITY_TYPE];
        if (!in_array($entity, $supported, true)) {
            $this->error('Unsupported entity type.');
            return Command::FAILURE;
        }

        ScanDuplicateGroupsJob::dispatchSync($entity);
        $this->info('Duplicate scan completed.');

        return Command::SUCCESS;
    }
}

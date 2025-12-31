<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\SalesLead;
use App\Models\Contact;
use App\Models\Opportunity;

class BackfillConvertedOpportunityContacts extends Command
{
    protected $signature = 'crm:backfill-converted-opportunity-contacts
                            {--dry-run : فقط گزارش بده، چیزی ذخیره نکن}
                            {--include-deleted : اگر contacts.deleted_at پر است هم لحاظ کن}
                            {--force-overwrite : اگر contact.opportunity_id قبلاً مقدار دارد هم overwrite کن (خطرناک)}';

    protected $description = 'Backfill: set contacts.opportunity_id for old converted leads based on sales_leads.converted_opportunity_id.';

    public function handle(): int
    {
        $dryRun         = (bool) $this->option('dry-run');
        $includeDeleted = (bool) $this->option('include-deleted');
        $forceOverwrite = (bool) $this->option('force-overwrite');

        $this->info('Backfill started ' . ($dryRun ? '[DRY RUN]' : ''));

        $baseQuery = SalesLead::query()
            ->whereNotNull('converted_at')
            ->whereNotNull('converted_opportunity_id')
            ->where(function ($q) {
                $q->whereNotNull('contact_id')
                  ->orWhereHas('contacts');
            })
            ->with([
                'contacts:id', // lead_contacts pivot
            ])
            ->select(['id', 'contact_id', 'converted_opportunity_id']);

        $scanned = 0;
        $changedContacts = 0;
        $skippedAlreadyAssigned = 0;
        $skippedMissingOpportunity = 0;
        $skippedNoContacts = 0;

        $baseQuery->chunkById(200, function ($leads) use (
            $dryRun, $includeDeleted, $forceOverwrite,
            &$scanned, &$changedContacts, &$skippedAlreadyAssigned, &$skippedMissingOpportunity, &$skippedNoContacts
        ) {
            foreach ($leads as $lead) {
                $scanned++;

                $oppId = (int) $lead->converted_opportunity_id;

                // اگر فرصت واقعاً وجود ندارد (ممکن است حذف شده باشد)
                if (!Opportunity::query()->whereKey($oppId)->exists()) {
                    $skippedMissingOpportunity++;
                    continue;
                }

                // همه مخاطبین مرتبط Lead = primary + pivot
                $leadContactIds = collect()
                    ->when(!empty($lead->contact_id), fn ($c) => $c->push((int)$lead->contact_id))
                    ->merge($lead->contacts->pluck('id')->map(fn ($id) => (int)$id))
                    ->unique()
                    ->values();

                if ($leadContactIds->isEmpty()) {
                    $skippedNoContacts++;
                    continue;
                }

                // مخاطبین موجود در دیتابیس
                $contactsQuery = Contact::query()->whereIn('id', $leadContactIds);

                // اگر بخواهیم soft-deleted ها را نادیده بگیریم
                if (!$includeDeleted) {
                    $contactsQuery->whereNull('deleted_at');
                }

                $contacts = $contactsQuery->get(['id', 'opportunity_id']);

                if ($contacts->isEmpty()) {
                    $skippedNoContacts++;
                    continue;
                }

                // تعیین اینکه کدام contact باید آپدیت شود
                $toSet = [];
                foreach ($contacts as $c) {
                    $currentOpp = $c->opportunity_id;

                    // اگر همین الان روی همین فرصت است، کاری نکن
                    if ((int)$currentOpp === $oppId) {
                        continue;
                    }

                    // اگر قبلاً به فرصت دیگری وصل شده
                    if (!empty($currentOpp) && !$forceOverwrite) {
                        $skippedAlreadyAssigned++;
                        continue;
                    }

                    $toSet[] = (int)$c->id;
                }

                if (empty($toSet)) {
                    continue;
                }

                if ($dryRun) {
                    $this->line("Lead #{$lead->id} -> Opportunity #{$oppId}: would set opportunity_id for " . count($toSet) . " contacts");
                    $changedContacts += count($toSet);
                    continue;
                }

                DB::transaction(function () use ($toSet, $oppId, &$changedContacts) {
                    Contact::query()
                        ->whereIn('id', $toSet)
                        ->update(['opportunity_id' => $oppId]);

                    $changedContacts += count($toSet);
                });
            }
        });

        $this->newLine();
        $this->info("Done. scanned={$scanned}");
        $this->info("contacts_updated={$changedContacts}");
        $this->info("skipped_missing_opportunity={$skippedMissingOpportunity}");
        $this->info("skipped_no_contacts={$skippedNoContacts}");
        $this->info("skipped_already_assigned_to_other_opportunity={$skippedAlreadyAssigned}");

        if (!$dryRun && $skippedAlreadyAssigned > 0) {
            $this->warn("هشدار: {$skippedAlreadyAssigned} مخاطب قبلاً opportunity_id داشته و به‌صورت امن skip شد. اگر واقعاً می‌خواهی overwrite کنی، با --force-overwrite اجرا کن (ریسک دارد).");
        }

        return self::SUCCESS;
    }
}

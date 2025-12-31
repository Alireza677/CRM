<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Proforma;
use App\Models\SalesLead;
use App\Models\SmsList;
use App\Models\User;
use App\Services\Merge\Configs\ContactMergeConfig;
use App\Services\Merge\MergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_moves_relations_and_hides_loser(): void
    {
        $user = User::factory()->create();

        $winner = Contact::factory()->create([
            'first_name' => null,
            'email' => 'winner@example.com',
        ]);

        $loser = Contact::factory()->create([
            'first_name' => 'Ali',
            'email' => 'loser@example.com',
        ]);

        $opportunity = Opportunity::factory()->create([
            'contact_id' => $loser->id,
        ]);

        $proforma = Proforma::create([
            'subject' => 'Test Proforma',
            'contact_id' => $loser->id,
        ]);

        $lead = SalesLead::factory()->create([
            'contact_id' => $loser->id,
        ]);

        $lead->contacts()->attach($winner->id);
        $lead->contacts()->attach($loser->id);

        $smsList = SmsList::create([
            'name' => 'Test List',
            'created_by' => $user->id,
        ]);

        $smsList->contacts()->attach($winner->id);
        $smsList->contacts()->attach($loser->id);

        $note = Note::create([
            'body' => 'Test note',
            'user_id' => $user->id,
            'noteable_type' => Contact::class,
            'noteable_id' => $loser->id,
        ]);

        $service = app(MergeService::class);

        $service->merge(
            new ContactMergeConfig(),
            $winner->id,
            [$loser->id],
            ['email' => $loser->id],
            $user->id
        );

        $winner->refresh();

        $this->assertSame('Ali', $winner->first_name);
        $this->assertSame('loser@example.com', $winner->email);

        $this->assertSame($winner->id, Opportunity::find($opportunity->id)->contact_id);
        $this->assertSame($winner->id, Proforma::find($proforma->id)->contact_id);
        $this->assertSame($winner->id, SalesLead::find($lead->id)->contact_id);

        $this->assertDatabaseHas('lead_contacts', [
            'sales_lead_id' => $lead->id,
            'contact_id' => $winner->id,
        ]);
        $this->assertDatabaseMissing('lead_contacts', [
            'sales_lead_id' => $lead->id,
            'contact_id' => $loser->id,
        ]);

        $this->assertDatabaseHas('sms_list_contact', [
            'sms_list_id' => $smsList->id,
            'contact_id' => $winner->id,
        ]);
        $this->assertDatabaseMissing('sms_list_contact', [
            'sms_list_id' => $smsList->id,
            'contact_id' => $loser->id,
        ]);

        $this->assertSame($winner->id, Note::find($note->id)->noteable_id);

        $mergedLoser = Contact::withoutGlobalScopes()->find($loser->id);
        $this->assertSame($winner->id, $mergedLoser->merged_into_id);
        $this->assertNotNull($mergedLoser->merged_at);

        $this->assertNull(Contact::find($loser->id));

        $this->assertDatabaseHas('entity_merges', [
            'entity_type' => 'contact',
            'winner_id' => $winner->id,
            'loser_id' => $loser->id,
        ]);
    }
}

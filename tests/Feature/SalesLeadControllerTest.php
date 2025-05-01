<?php

namespace Tests\Feature;

use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SalesLeadControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_view_leads_index()
    {
        $response = $this->get(route('marketing.leads.index'));
        $response->assertStatus(200);
        $response->assertViewIs('marketing.leads.index');
    }

    public function test_can_create_lead()
    {
        $leadData = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'state' => $this->faker->state,
            'company' => $this->faker->company,
            'lead_source' => 'وب سایت',
            'lead_status' => 'تماس اولیه',
            'next_follow_up_date' => now()->addDays(7)->format('Y-m-d'),
            'notes' => $this->faker->paragraph,
        ];

        $response = $this->post(route('marketing.leads.store'), $leadData);
        $response->assertRedirect(route('marketing.leads.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sales_leads', [
            'first_name' => $leadData['first_name'],
            'last_name' => $leadData['last_name'],
            'company' => $leadData['company'],
        ]);
    }

    public function test_validation_works_for_required_fields()
    {
        $response = $this->post(route('marketing.leads.store'), []);
        $response->assertSessionHasErrors([
            'first_name',
            'last_name',
            'state',
            'company',
            'lead_source',
            'lead_status',
            'next_follow_up_date',
        ]);
    }

    public function test_validation_works_for_invalid_lead_source()
    {
        $leadData = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'state' => $this->faker->state,
            'company' => $this->faker->company,
            'lead_source' => 'Invalid Source',
            'lead_status' => 'تماس اولیه',
            'next_follow_up_date' => now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->post(route('marketing.leads.store'), $leadData);
        $response->assertSessionHasErrors('lead_source');
    }

    public function test_validation_works_for_invalid_date()
    {
        $leadData = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'state' => $this->faker->state,
            'company' => $this->faker->company,
            'lead_source' => 'وب سایت',
            'lead_status' => 'تماس اولیه',
            'next_follow_up_date' => now()->subDays(1)->format('Y-m-d'),
        ];

        $response = $this->post(route('marketing.leads.store'), $leadData);
        $response->assertSessionHasErrors('next_follow_up_date');
    }

    public function test_can_update_lead()
    {
        $lead = SalesLead::factory()->create(['created_by' => $this->user->id]);
        
        $updateData = [
            'first_name' => 'Updated First Name',
            'last_name' => 'Updated Last Name',
            'state' => 'Updated State',
            'company' => 'Updated Company',
            'lead_source' => 'نمایشگاه',
            'lead_status' => 'موکول به آینده',
            'next_follow_up_date' => now()->addDays(14)->format('Y-m-d'),
            'notes' => 'Updated notes',
        ];

        $response = $this->put(route('marketing.leads.update', $lead), $updateData);
        $response->assertRedirect(route('marketing.leads.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sales_leads', [
            'id' => $lead->id,
            'first_name' => $updateData['first_name'],
            'last_name' => $updateData['last_name'],
        ]);
    }

    public function test_can_delete_lead()
    {
        $lead = SalesLead::factory()->create(['created_by' => $this->user->id]);

        $response = $this->delete(route('marketing.leads.destroy', $lead));
        $response->assertRedirect(route('marketing.leads.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('sales_leads', ['id' => $lead->id]);
    }

    public function test_search_functionality_works()
    {
        $lead1 = SalesLead::factory()->create([
            'first_name' => 'John',
            'company' => 'Test Company',
            'created_by' => $this->user->id,
        ]);

        $lead2 = SalesLead::factory()->create([
            'first_name' => 'Jane',
            'company' => 'Another Company',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('marketing.leads.index', ['search' => 'John']));
        $response->assertViewHas('leads', function ($leads) use ($lead1) {
            return $leads->contains($lead1);
        });

        $response = $this->get(route('marketing.leads.index', ['search' => 'Test Company']));
        $response->assertViewHas('leads', function ($leads) use ($lead1) {
            return $leads->contains($lead1);
        });
    }

    public function test_filter_functionality_works()
    {
        $lead1 = SalesLead::factory()->create([
            'lead_source' => 'وب سایت',
            'lead_status' => 'تماس اولیه',
            'created_by' => $this->user->id,
        ]);

        $lead2 = SalesLead::factory()->create([
            'lead_source' => 'نمایشگاه',
            'lead_status' => 'موکول به آینده',
            'created_by' => $this->user->id,
        ]);

        $response = $this->get(route('marketing.leads.index', ['lead_source' => 'وب سایت']));
        $response->assertViewHas('leads', function ($leads) use ($lead1) {
            return $leads->contains($lead1);
        });

        $response = $this->get(route('marketing.leads.index', ['lead_status' => 'موکول به آینده']));
        $response->assertViewHas('leads', function ($leads) use ($lead2) {
            return $leads->contains($lead2);
        });
    }
} 
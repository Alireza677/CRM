<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_requires_auth(): void
    {
        $this->post(route('reports.preview'), [])->assertRedirect('/login');
    }

    public function test_preview_rejects_invalid_model(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson(route('reports.preview'), [
                'query_json' => [
                    'model' => 'InvalidModel',
                    'selects' => ['id'],
                    'limit' => 5,
                ],
            ])->assertStatus(422);
    }

    public function test_preview_rejects_invalid_field(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson(route('reports.preview'), [
                'query_json' => [
                    'model' => 'Opportunities',
                    'selects' => ['nonexistent'],
                    'limit' => 5,
                ],
            ])->assertStatus(422);
    }

    public function test_preview_success_with_pagination(): void
    {
        $user = User::factory()->create();
        Opportunity::factory()->count(25)->create(['name' => 'Sample', 'amount' => 1000]);

        $resp = $this->actingAs($user)
            ->postJson(route('reports.preview'), [
                'query_json' => [
                    'model' => 'Opportunities',
                    'selects' => ['id','name','amount','created_at'],
                    'filters' => [ ['field' => 'name', 'operator' => 'like', 'value' => 'Sam'] ],
                    'sorts' => [ ['field' => 'id', 'dir' => 'desc'] ],
                    'limit' => 10,
                    'page' => 2,
                ],
            ])->assertOk()->json();

        $this->assertEquals(10, $resp['meta']['per_page']);
        $this->assertEquals(2, $resp['meta']['page']);
        $this->assertNotEmpty($resp['rows']);
        $this->assertContains('amount', $resp['columns']);
    }

    public function test_preview_with_group_and_aggregate(): void
    {
        $user = User::factory()->create();
        Opportunity::factory()->count(5)->create(['name' => 'A', 'amount' => 100]);
        Opportunity::factory()->count(3)->create(['name' => 'B', 'amount' => 200]);

        $resp = $this->actingAs($user)
            ->postJson(route('reports.preview'), [
                'query_json' => [
                    'model' => 'Opportunities',
                    'group_by' => ['name'],
                    'aggregates' => [ ['fn' => 'sum', 'field' => 'amount', 'as' => 'sum_amount'] ],
                    'limit' => 50,
                ],
            ])->assertOk()->json();

        $this->assertContains('name', $resp['columns']);
        $this->assertContains('sum_amount', $resp['columns']);
        $this->assertNotEmpty($resp['rows']);
        $this->assertArrayHasKey('sum_amount', $resp['summary']);
    }

    public function test_run_view_renders_results(): void
    {
        $owner = User::factory()->create();
        Opportunity::factory()->count(3)->create(['name' => 'R', 'amount' => 10]);

        $report = Report::factory()->create([
            'created_by' => $owner->id,
            'visibility' => 'public',
            'query_json' => [
                'model' => 'Opportunities',
                'selects' => ['id','name','amount'],
                'limit' => 5,
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('reports.run', $report))
            ->assertOk()
            ->assertSee('اجرای گزارش')
            ->assertSee('amount');
    }
}


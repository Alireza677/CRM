<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure default users exist for factories
        User::factory()->create();
    }

    public function test_guest_cannot_access_reports(): void
    {
        $this->get(route('reports.index'))->assertRedirect('/login');
    }

    public function test_user_sees_accessible_reports_in_index(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $owned = Report::factory()->create(['created_by' => $user->id, 'visibility' => 'private']);
        $public = Report::factory()->create(['visibility' => 'public']);
        $shared = Report::factory()->create(['visibility' => 'shared', 'created_by' => $other->id]);
        $shared->sharedUsers()->sync([$user->id => ['can_edit' => false]]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee($owned->title)
            ->assertSee($public->title)
            ->assertSee($shared->title);
    }

    public function test_view_permissions(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $stranger = User::factory()->create();

        $private = Report::factory()->create(['created_by' => $owner->id, 'visibility' => 'private']);
        $public = Report::factory()->create(['visibility' => 'public']);
        $shared = Report::factory()->create(['visibility' => 'shared', 'created_by' => $owner->id]);
        $shared->sharedUsers()->sync([$member->id => ['can_edit' => false]]);

        // private: only owner
        $this->actingAs($owner)->get(route('reports.show', $private))->assertOk();
        $this->actingAs($stranger)->get(route('reports.show', $private))->assertForbidden();

        // public: everyone
        $this->actingAs($stranger)->get(route('reports.show', $public))->assertOk();

        // shared: members only
        $this->actingAs($member)->get(route('reports.show', $shared))->assertOk();
        $this->actingAs($stranger)->get(route('reports.show', $shared))->assertForbidden();
    }

    public function test_store_validation_and_create(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // invalid: missing title
        $this->post(route('reports.store'), [
            'visibility' => 'private',
        ])->assertSessionHasErrors(['title']);

        // valid create
        $resp = $this->post(route('reports.store'), [
            'title' => 'Test Report',
            'visibility' => 'public',
        ]);

        $resp->assertRedirect();
        $this->assertDatabaseHas('reports', [
            'title' => 'Test Report',
            'visibility' => 'public',
            'created_by' => $user->id,
        ]);
    }

    public function test_update_and_policy_enforced(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $report = Report::factory()->create(['created_by' => $owner->id, 'visibility' => 'private']);

        // other cannot update
        $this->actingAs($other)
            ->put(route('reports.update', $report), ['title' => 'X','visibility'=>'private'])
            ->assertForbidden();

        // owner can update
        $this->actingAs($owner)
            ->put(route('reports.update', $report), ['title' => 'Updated','visibility'=>'private'])
            ->assertRedirect();
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'title' => 'Updated']);
    }

    public function test_destroy_policy(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $report = Report::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($other)
            ->delete(route('reports.destroy', $report))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('reports.destroy', $report))
            ->assertRedirect();
        $this->assertSoftDeleted('reports', ['id' => $report->id]);
    }

    public function test_inactive_user_cannot_run(): void
    {
        // if users table has 'active' column, simulate inactive
        $user = User::factory()->create();
        if (\Schema::hasColumn('users','active')) {
            $user->active = 0; $user->save();
        }
        $report = Report::factory()->create(['created_by' => $user->id, 'visibility' => 'public', 'query_json' => ['model'=>'Opportunities','selects'=>['id']]]);
        $this->actingAs($user)->get(route('reports.run', $report))->assertForbidden();
    }

    public function test_share_updates_pivot_and_visibility(): void
    {
        $owner = User::factory()->create();
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $report = Report::factory()->create(['created_by' => $owner->id, 'visibility' => 'private']);

        $this->actingAs($owner)
            ->put(route('reports.share', $report), [
                'visibility' => 'shared',
                'shared_user_ids' => [$u1->id, $u2->id],
                'shared_can_edit_ids' => [$u2->id],
            ])->assertRedirect();

        $report->refresh();
        $this->assertEquals('shared', $report->visibility);
        $this->assertTrue($report->sharedUsers()->whereKey($u1->id)->exists());
        $this->assertDatabaseHas('report_user', ['report_id' => $report->id, 'user_id' => $u2->id, 'can_edit' => 1]);
    }
}

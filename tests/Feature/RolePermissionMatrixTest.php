<?php

namespace Tests\Feature;

use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(RolePermissionSeeder::class);
    }

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    protected function makeUser(): User
    {
        return User::factory()->create();
    }

    public function test_only_admin_can_access_matrix_routes()
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $role = Role::firstOrCreate(['name' => 'testrole']);

        $this->actingAs($admin)
            ->get(route('roles.matrix', ['role_id' => $role->id]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('roles.matrix', ['role_id' => $role->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('roles.matrix.store'), ['role_id' => $role->id, 'perm' => []])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('roles.matrix.store'), ['role_id' => $role->id, 'perm' => []])
            ->assertForbidden();
    }

    public function test_scoped_action_keeps_only_selected_scope()
    {
        $admin = $this->makeAdmin();
        $role = Role::firstOrCreate(['name' => 'testrole']);

        // Grant multiple view scopes then use matrix to select a single one
        $own = Permission::where('name', 'leads.view.own')->first();
        $team = Permission::where('name', 'leads.view.team')->first();
        $dept = Permission::where('name', 'leads.view.department')->first();
        $role->givePermissionTo([$own, $team]);

        // Get current version
        $res = $this->actingAs($admin)->get(route('roles.matrix', ['role_id' => $role->id]));
        $res->assertOk();
        $version = $this->extractVersion($res->getContent());

        $this->actingAs($admin)
            ->post(route('roles.matrix.store'), [
                'role_id' => $role->id,
                'version' => $version,
                'perm' => [
                    'leads.view' => 'department',
                ],
            ])->assertRedirect();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('leads.view.department'));
        $this->assertFalse($role->hasPermissionTo('leads.view.own'));
        $this->assertFalse($role->hasPermissionTo('leads.view.team'));
    }

    public function test_non_scoped_actions_checkbox_apply_and_remove()
    {
        $admin = $this->makeAdmin();
        $role = Role::firstOrCreate(['name' => 'testrole']);

        // Ensure permission exists
        $create = Permission::where('name', 'leads.create')->first();
        $this->assertNotNull($create);

        // Get version
        $res = $this->actingAs($admin)->get(route('roles.matrix', ['role_id' => $role->id]));
        $version = $this->extractVersion($res->getContent());

        // Grant via checkbox
        $this->actingAs($admin)
            ->post(route('roles.matrix.store'), [
                'role_id' => $role->id,
                'version' => $version,
                'perm' => [ 'leads.create' => 'on' ],
            ])->assertRedirect();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('leads.create'));

        // Remove by omitting field (no checkbox value)
        $res = $this->actingAs($admin)->get(route('roles.matrix', ['role_id' => $role->id]));
        $version = $this->extractVersion($res->getContent());
        $this->actingAs($admin)
            ->post(route('roles.matrix.store'), [
                'role_id' => $role->id,
                'version' => $version,
                'perm' => [ ],
            ])->assertRedirect();
        $role->refresh();
        $this->assertFalse($role->hasPermissionTo('leads.create'));
    }

    public function test_permissions_outside_matrix_remain_untouched()
    {
        $admin = $this->makeAdmin();
        $role = Role::firstOrCreate(['name' => 'testrole']);

        // Outside default actions set (e.g., reports.sales.own)
        $outside = Permission::firstOrCreate(['name' => 'reports.sales.own', 'guard_name' => 'web']);
        $role->givePermissionTo($outside);

        $res = $this->actingAs($admin)->get(route('roles.matrix', ['role_id' => $role->id]));
        $version = $this->extractVersion($res->getContent());

        // Perform unrelated change
        $this->actingAs($admin)
            ->post(route('roles.matrix.store'), [
                'role_id' => $role->id,
                'version' => $version,
                'perm' => [ 'leads.create' => 'on' ],
            ])->assertRedirect();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('reports.sales.own'));
    }

    public function test_cache_cleared_after_save()
    {
        $admin = $this->makeAdmin();
        $role = Role::firstOrCreate(['name' => 'testrole']);

        $res = $this->actingAs($admin)->get(route('roles.matrix', ['role_id' => $role->id]));
        $version = $this->extractVersion($res->getContent());

        $this->actingAs($admin)
            ->post(route('roles.matrix.store'), [
                'role_id' => $role->id,
                'version' => $version,
                'perm' => [ 'leads.create' => 'on' ],
            ])->assertRedirect();

        // If cache not cleared, hasPermissionTo may read old cache; expect true
        $this->assertTrue($role->fresh()->hasPermissionTo('leads.create'));
    }

    private function extractVersion(string $html): string
    {
        // naive extraction of hidden input name="version"
        if (preg_match('/name=\"version\"\s+value=\"([a-f0-9]{40})\"/i', $html, $m)) {
            return $m[1];
        }
        return '';
    }
}


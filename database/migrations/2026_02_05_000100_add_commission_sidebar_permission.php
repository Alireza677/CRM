<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $name = 'sidebar_settings_commissions.view';

        $permission = Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && ! $adminRole->hasPermissionTo($permission)) {
            $adminRole->givePermissionTo($permission);
        }
    }

    public function down(): void
    {
        $name = 'sidebar_settings_commissions.view';
        Permission::where('name', $name)->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $names = [
            'chat.view',
            'online_meetings.view',
        ];

        foreach ($names as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $roles = Role::all();
        foreach ($roles as $role) {
            $role->givePermissionTo($names);
        }
    }

    public function down(): void
    {
        $names = [
            'chat.view',
            'online_meetings.view',
        ];

        $roles = Role::all();
        foreach ($roles as $role) {
            foreach ($names as $name) {
                $role->revokePermissionTo($name);
            }
        }

        Permission::whereIn('name', $names)->delete();
    }
};

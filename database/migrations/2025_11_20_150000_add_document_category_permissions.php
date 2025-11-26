<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('auth.defaults.guard', 'web');
        $permissions = [
            'purchase_documents.view',
            'purchase_documents.download',
            'opportunity_documents.view',
            'opportunity_documents.download',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $roles = Role::whereHas('permissions', function ($query) {
            $query->where('name', 'like', 'documents.view%');
        })->get();

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $roles = $roles->push($admin)->unique('id');
        }

        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }
    }

    public function down(): void
    {
        $permissions = [
            'purchase_documents.view',
            'purchase_documents.download',
            'opportunity_documents.view',
            'opportunity_documents.download',
        ];

        Permission::whereIn('name', $permissions)->delete();
    }
};

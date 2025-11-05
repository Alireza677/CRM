<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesRestoreSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        // Derived from project usage and translations
        $roles = [
            'admin',
            'user',
            'finance',
            'factory_supervisor',
            'support',
            'sales_manager',
            'salesperson',
            'sales_agent',
            'admin_manager',
            'admin_staff',
        ];

        foreach ($roles as $name) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }
    }
}


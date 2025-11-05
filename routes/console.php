<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('roles:restore', function () {
    $guard = config('auth.defaults.guard', 'web');

    $roleNames = [
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

    if (! Schema::hasTable(config('permission.table_names.roles', 'roles'))) {
        $this->error('Roles table not found. Run: php artisan migrate');
        return 1;
    }

    $existedBefore = [];
    foreach ($roleNames as $name) {
        $existedBefore[$name] = Role::where('name', $name)->exists();
    }

    $permCountBefore = Schema::hasTable(config('permission.table_names.permissions', 'permissions'))
        ? Permission::count() : 0;

    // Run seeders idempotently
    Artisan::call('db:seed', ['--class' => \Database\Seeders\RolesRestoreSeeder::class, '--force' => true]);

    if (Schema::hasTable(config('permission.table_names.permissions', 'permissions'))) {
        Artisan::call('db:seed', ['--class' => \Database\Seeders\PermissionsRestoreSeeder::class, '--force' => true]);
    }

    $created = [];
    $existing = [];
    foreach ($roleNames as $name) {
        if (! $existedBefore[$name] && Role::where('name', $name)->exists()) {
            $created[] = $name;
        } else {
            $existing[] = $name;
        }
    }

    $permCountAfter = Schema::hasTable(config('permission.table_names.permissions', 'permissions'))
        ? Permission::count() : 0;

    // Assign top role to admin user
    $email = env('ADMIN_EMAIL', 'admin@example.com');
    $user = User::where('email', $email)->first() ?? User::find(1);
    $topRole = Role::where('name', 'مدیر کل')->first() ?? Role::where('name', 'admin')->first();
    if ($user && $topRole) {
        if (! $user->hasRole($topRole->name)) {
            $user->assignRole($topRole);
        }
    }

    // Reset permission cache
    try {
        Artisan::call('permission:cache-reset');
    } catch (\Throwable $e) {
        // ignore if command not available
    }

    $this->info('Roles/Permissions restore summary:');
    $this->line('  Roles created:   ' . count($created) . (count($created) ? ' [' . implode(', ', $created) . ']' : ''));
    $this->line('  Roles existing:  ' . count($existing) . (count($existing) ? ' [' . implode(', ', $existing) . ']' : ''));
    if ($permCountAfter >= $permCountBefore) {
        $this->line('  Permissions:     ' . $permCountAfter . ' (+' . max(0, $permCountAfter - $permCountBefore) . ')');
    }

    if ($user && $topRole) {
        $this->line('  Admin user:      ' . ($user->email ?? ('ID ' . $user->getKey())) . ' has role ' . $topRole->name);
    }

    $this->line('Done.');
})->purpose('Recreate roles/permissions and assign admin role safely');

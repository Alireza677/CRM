<?php

namespace Database\Seeders;

use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Database\Seeder;

class SalesLeadSeeder extends Seeder
{
    public function run()
    {
        // Create a test user if none exists
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        // Create 3 sample leads
        SalesLead::factory()
            ->count(3)
            ->create([
                'created_by' => $user->id,
                'assigned_to' => $user->id,
            ]);
    }
} 
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\Contact;
use App\Models\Organization;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users
        $users = User::factory(3)->create();

        // Create test contacts
        $contacts = Contact::factory(5)->create();

        // Create test organizations
        $organizations = Organization::factory(3)->create();

        // Create test leads
        Lead::factory(3)->create()->each(function ($lead) use ($users) {
            $lead->assigned_to = $users->random()->id;
            $lead->save();
        });

        // Create test opportunities
        Opportunity::factory(3)->create()->each(function ($opportunity) use ($users, $contacts, $organizations) {
            $opportunity->assigned_to = $users->random()->id;
            $opportunity->contact_id = $contacts->random()->id;
            $opportunity->organization_id = $organizations->random()->id;
            $opportunity->save();
        });

        // Reports
        $this->call(ReportSeeder::class);
    }
}

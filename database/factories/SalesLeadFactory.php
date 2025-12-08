<?php

namespace Database\Factories;

use App\Models\SalesLead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesLeadFactory extends Factory
{
    protected $model = SalesLead::class;

    public function definition()
    {
        $farsiFirstNames = ['Ù…Ø­Ù…Ø¯', 'Ø¹Ù„ÛŒ', 'Ø±Ø¶Ø§', 'Ø­Ø³ÛŒÙ†', 'ÙØ§Ø·Ù…Ù‡', 'Ø²Ù‡Ø±Ø§', 'Ù…Ø±ÛŒÙ…', 'Ø³Ø§Ø±Ø§'];
        $farsiLastNames = ['Ù…Ø­Ù…Ø¯ÛŒ', 'Ø­Ø³ÛŒÙ†ÛŒ', 'Ø±Ø¶Ø§ÛŒÛŒ', 'Ú©Ø±ÛŒÙ…ÛŒ', 'Ù…Ù‡Ø¯ÙˆÛŒ', 'Ù‚Ø§Ø³Ù…ÛŒ', 'Ø§Ú©Ø¨Ø±ÛŒ', 'Ù†ÙˆØ±ÛŒ'];
        $farsiCompanies = ['Ø´Ø±Ú©Øª ÙÙ†Ø§ÙˆØ±ÛŒ Ø§Ø·Ù„Ø§Ø¹Ø§Øª', 'Ú¯Ø±ÙˆÙ‡ ØµÙ†Ø¹ØªÛŒ', 'Ø´Ø±Ú©Øª Ø¨Ø§Ø²Ø±Ú¯Ø§Ù†ÛŒ', 'Ù…Ø¬ØªÙ…Ø¹ ØªØ¬Ø§Ø±ÛŒ', 'Ø´Ø±Ú©Øª Ø®Ø¯Ù…Ø§ØªÛŒ'];
        $farsiSources = ['ÙˆØ¨ Ø³Ø§ÛŒØª', 'Ù†Ù…Ø§ÛŒØ´Ú¯Ø§Ù‡', 'Ù…Ø¹Ø±ÙÛŒ', 'ØªØ¨Ù„ÛŒØºØ§Øª', 'Ø³Ø§ÛŒØ±'];
        $leadStatuses = [
            SalesLead::STATUS_NEW,
            SalesLead::STATUS_CONTACTED,
            SalesLead::STATUS_CONVERTED_TO_OPPORTUNITY,
            SalesLead::STATUS_DISCARDED,
        ];

        return [
            'first_name' => $this->faker->randomElement($farsiFirstNames),
            'last_name' => $this->faker->randomElement($farsiLastNames),
            'company' => $this->faker->randomElement($farsiCompanies),
            'email' => $this->faker->unique()->safeEmail,
            'phone' => '09' . $this->faker->numerify('########'),
            'mobile' => '09' . $this->faker->numerify('########'),
            'lead_source' => $this->faker->randomElement($farsiSources),
            'lead_status' => $this->faker->randomElement($leadStatuses),
            'notes' => $this->faker->realText(200),
            'lead_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'next_follow_up_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            'created_by' => User::factory(),
            'assigned_to' => User::factory(),
            'assigned_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'pool_status' => SalesLead::POOL_ASSIGNED,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
} 


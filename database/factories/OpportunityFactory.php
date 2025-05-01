<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OpportunityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake('fa_IR')->sentence(3),
            'type' => fake()->randomElement(['new', 'existing', 'upgrade']),
            'source' => fake()->randomElement(['website', 'social', 'referral', 'other']),
            'amount' => fake()->numberBetween(1000000, 100000000),
            'description' => fake('fa_IR')->paragraph(),
            'success_rate' => fake()->numberBetween(0, 100),
            'next_follow_up' => fake()->dateTimeBetween('now', '+1 month'),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
} 
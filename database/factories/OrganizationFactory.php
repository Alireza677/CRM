<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'website' => $this->faker->url,
            'industry' => $this->faker->word,
            'size' => $this->faker->numberBetween(1, 500),
            'notes' => $this->faker->sentence,
        ];
    }
}

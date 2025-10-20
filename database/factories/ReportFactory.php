<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Report> */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $visibilities = ['private','public','shared'];
        $visibility = $this->faker->randomElement($visibilities);

        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->boolean(70) ? $this->faker->paragraph() : null,
            'model' => $this->faker->boolean(40) ? $this->faker->randomElement(['leads','contacts','opportunities']) : null,
            'query_json' => $this->faker->boolean(40) ? ['filters' => ['q' => $this->faker->word]] : null,
            'visibility' => $visibility,
            'created_by' => User::inRandomOrder()->value('id') ?? User::factory(),
            'is_pinned' => $this->faker->boolean(10),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}


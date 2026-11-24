<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioProject>
 */
class PortfolioProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'title' => fake()->sentence(4),
            'image_path' => null,
            'description' => fake()->paragraph(),
            'technologies' => ['Laravel', 'MySQL'],
            'project_url' => fake()->url(),
            'github_url' => fake()->url(),
            'start_date' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'completion_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}

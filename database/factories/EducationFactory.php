<?php

namespace Database\Factories;

use App\Models\Education;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
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
            'institution_name' => fake()->company().' University',
            'qualification' => 'Bachelor of Science',
            'field_of_study' => fake()->word(),
            'start_year' => fake()->numberBetween(2000, 2020),
            'graduation_year' => fake()->numberBetween(2020, 2026),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}

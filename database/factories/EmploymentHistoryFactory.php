<?php

namespace Database\Factories;

use App\Models\EmploymentHistory;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmploymentHistory>
 */
class EmploymentHistoryFactory extends Factory
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
            'company_name' => fake()->company(),
            'company_logo_path' => null,
            'job_title' => fake()->jobTitle(),
            'employment_type' => 'Full-time',
            'start_date' => fake()->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'location' => fake()->city(),
            'description' => fake()->paragraph(),
            'is_current' => false,
        ];
    }
}

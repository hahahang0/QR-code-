<?php

namespace Database\Factories;

use App\Models\Certification;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certification>
 */
class CertificationFactory extends Factory
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
            'name' => fake()->sentence(3),
            'issuer' => fake()->company(),
            'issue_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'credential_url' => fake()->url(),
            'image_path' => null,
            'description' => fake()->optional()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}

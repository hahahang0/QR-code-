<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->optional()->date(),
            'gender' => fake()->optional()->randomElement(['Female', 'Male', 'Non-binary']),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'organization' => fake()->optional()->company(),
            'reference_number' => fake()->unique()->bothify('REF-####??'),
            'emergency_contact' => fake()->optional()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
            'status' => 'active',
            'created_by' => null,
        ];
    }
}

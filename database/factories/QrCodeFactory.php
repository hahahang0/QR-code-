<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrCode>
 */
class QrCodeFactory extends Factory
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
            'generated_by' => null,
            'schema_version' => 1,
            'payload' => json_encode(['version' => 1, 'type' => 'person', 'person' => ['full_name' => 'Sample Person']], JSON_THROW_ON_ERROR),
            'included_fields' => [],
            'error_correction' => 'Q',
            'signature_algorithm' => null,
            'signature' => null,
            'public_key_id' => null,
            'is_current' => true,
        ];
    }
}

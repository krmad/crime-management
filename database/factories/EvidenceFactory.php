<?php

namespace Database\Factories;

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Models\CrimeCase;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Evidence>
 */
class EvidenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'case_id' => CrimeCase::factory(),
            'evidence_number' => sprintf('EV-%d-%s', now()->year, Str::upper(Str::random(10))),
            'title' => fake()->randomElement([
                'Recovered CCTV Recording',
                'Sealed Document Envelope',
                'Fingerprint Lift Kit',
                'Photographic Scene Prints',
                'Recovered Mobile Device',
            ]),
            'evidence_type' => fake()->randomElement(EvidenceType::cases()),
            'description' => fake()->sentence(),
            'collected_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'collected_by' => User::factory(),
            'storage_location' => 'Evidence Room '.fake()->numberBetween(1, 12),
            'status' => fake()->randomElement(EvidenceStatus::cases()),
        ];
    }
}

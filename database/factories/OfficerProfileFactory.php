<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\OfficerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficerProfile>
 */
class OfficerProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'badge_number' => 'BADGE-'.fake()->unique()->numberBetween(1000, 99999),
            'rank' => fake()->randomElement(['Constable', 'Sergeant', 'Inspector']),
            'department' => fake()->randomElement(['Crime Prevention', 'Investigation', 'Patrol']),
            'police_station' => fake()->city().' Police Station',
            'phone' => fake()->phoneNumber(),
            'employment_status' => EmploymentStatus::Active,
        ];
    }
}

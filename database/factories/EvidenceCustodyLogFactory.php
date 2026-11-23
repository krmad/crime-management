<?php

namespace Database\Factories;

use App\Enums\CustodyEvent;
use App\Models\Evidence;
use App\Models\EvidenceCustodyLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvidenceCustodyLog>
 */
class EvidenceCustodyLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'evidence_id' => Evidence::factory(),
            'event_type' => CustodyEvent::Transfer,
            'transferred_by' => User::factory(),
            'received_by' => null,
            'from_location' => 'Evidence Room 1',
            'to_location' => 'Evidence Room 2',
            'occurred_at' => now(),
            'notes' => fake()->sentence(),
        ];
    }
}

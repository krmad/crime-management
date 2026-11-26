<?php

namespace Database\Factories;

use App\Enums\PunishmentType;
use App\Models\CaseJudgment;
use App\Models\Punishment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Punishment>
 */
class PunishmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_judgment_id' => CaseJudgment::factory(),
            'type' => PunishmentType::Fine,
            'fine_amount' => '1250.00',
            'imprisonment_term' => null,
            'details' => null,
        ];
    }

    public function forJudgment(CaseJudgment $judgment): static
    {
        return $this->state(fn () => [
            'case_judgment_id' => $judgment->id,
        ]);
    }

    public function imprisonment(): static
    {
        return $this->state(fn () => [
            'type' => PunishmentType::Imprisonment,
            'fine_amount' => null,
            'imprisonment_term' => '2 years',
            'details' => null,
        ]);
    }

    public function other(): static
    {
        return $this->state(fn () => [
            'type' => PunishmentType::Other,
            'fine_amount' => null,
            'imprisonment_term' => null,
            'details' => fake()->sentence(),
        ]);
    }
}

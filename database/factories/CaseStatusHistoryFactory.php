<?php

namespace Database\Factories;

use App\Enums\CaseStatus;
use App\Models\CaseStatusHistory;
use App\Models\CrimeCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseStatusHistory>
 */
class CaseStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'case_id' => CrimeCase::factory(),
            'previous_status' => CaseStatus::Open,
            'new_status' => CaseStatus::UnderInvestigation,
            'actor_id' => null,
            'reason' => null,
            'occurred_at' => now(),
        ];
    }

    public function forCase(CrimeCase $case): static
    {
        return $this->state(fn () => [
            'case_id' => $case->id,
        ]);
    }

    public function withActor(User $actor): static
    {
        return $this->state(fn () => [
            'actor_id' => $actor->id,
        ]);
    }
}

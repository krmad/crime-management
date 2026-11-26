<?php

namespace Database\Factories;

use App\Enums\CaseJudgmentState;
use App\Enums\JudgmentVerdict;
use App\Enums\UserRole;
use App\Models\CaseJudgment;
use App\Models\CrimeCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/**
 * @extends Factory<CaseJudgment>
 */
class CaseJudgmentFactory extends Factory
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
            'judge_id' => User::factory()->judge(),
            'judgment_state' => CaseJudgmentState::Draft,
            'verdict' => null,
            'judgment_notes' => null,
            'acquittal_notes' => null,
            'decided_at' => null,
            'completed_at' => null,
        ];
    }

    public function withCase(CrimeCase $case): static
    {
        return $this->state(fn () => [
            'case_id' => $case->id,
        ]);
    }

    public function withJudge(User $judge): static
    {
        if ($judge->role !== UserRole::Judge) {
            throw new InvalidArgumentException('Judgments must be assigned to a user with the Judge role.');
        }

        return $this->state(fn () => [
            'judge_id' => $judge->id,
        ]);
    }

    public function finalized(): static
    {
        return $this->state(fn () => [
            'judgment_state' => CaseJudgmentState::Final,
            'verdict' => JudgmentVerdict::Guilty,
            'decided_at' => now(),
            'completed_at' => now(),
        ]);
    }
}

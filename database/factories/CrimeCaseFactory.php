<?php

namespace Database\Factories;

use App\Enums\CaseStatus;
use App\Models\CrimeCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrimeCase>
 */
class CrimeCaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement([
                'Warehouse Burglary on Harbor Road',
                'Downtown Assault Investigation',
                'Vehicle Theft Near Transit Depot',
                'Fraud Report from Municipal Office',
                'Vandalism at Eastside Park',
            ]),
            'crime_type' => fake()->randomElement([
                'Burglary',
                'Assault',
                'Vehicle Theft',
                'Fraud',
                'Vandalism',
            ]),
            'location' => fake()->randomElement([
                'Downtown District',
                'North Harbor',
                'Westside Avenue',
                'Central Business District',
                'Eastside Park',
            ]),
            'description' => fake()->randomElement([
                'A fictional incident report was filed after a simulated break-in at a municipal storage facility.',
                'Officers recorded a fictional disturbance during a training exercise in the downtown district.',
                'This sample case describes a fictional vehicle theft used for internal system testing only.',
                'A fictional fraud report was logged against a mock municipal office account for demonstration purposes.',
            ]),
            'status' => CaseStatus::Open,
            'incident_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'investigation_notes' => null,
            'judgment_notes' => null,
            'closed_at' => null,
            'created_by' => User::factory(),
            'investigator_id' => null,
        ];
    }

    public function withCreator(User $creator): static
    {
        return $this->state(fn () => [
            'created_by' => $creator->id,
        ]);
    }

    public function assignedTo(User $investigator): static
    {
        return $this->state(fn () => [
            'investigator_id' => $investigator->id,
            'status' => CaseStatus::UnderInvestigation,
        ]);
    }

    public function judgmentRecorded(): static
    {
        return $this->state(fn () => [
            'status' => CaseStatus::JudgmentRecorded,
            'judgment_notes' => 'Fictional judgment summary recorded for internal testing.',
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => CaseStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}

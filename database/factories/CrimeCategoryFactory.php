<?php

namespace Database\Factories;

use App\Models\CrimeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrimeCategory>
 */
class CrimeCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'level' => 1,
            'name' => fake()->randomElement([
                'Book IV — Crimes Against the Public Interest or the Community',
                'Book V — Crimes Against Individuals and the Family',
                'Book VI — Crimes Against Property',
                'Book VIII — Petty Offences (Special Part)',
            ]),
            'code' => 'factory-'.fake()->unique()->uuid(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'source_reference' => null,
        ];
    }

    public function childOf(CrimeCategory $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->getKey(),
            'level' => $parent->level + 1,
        ]);
    }
}

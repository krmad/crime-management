<?php

namespace Database\Factories;

use App\Models\Victim;
use Illuminate\Database\Eloquent\Factories\Factory;

class VictimFactory extends Factory
{
    protected $model = Victim::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'date_of_birth' => null,
            'gender' => null,
            'address' => null,
            'phone' => null,
        ];
    }
}

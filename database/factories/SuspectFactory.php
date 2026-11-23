<?php

namespace Database\Factories;

use App\Models\Suspect;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuspectFactory extends Factory
{
    protected $model = Suspect::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'alias' => null,
            'date_of_birth' => null,
            'gender' => null,
            'address' => null,
            'phone' => null,
        ];
    }
}

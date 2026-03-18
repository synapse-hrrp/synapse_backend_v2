<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'nip' => 'NIP-' . now()->format('Y') . '-' . str_pad((string) $this->faker->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
        ];
    }
}
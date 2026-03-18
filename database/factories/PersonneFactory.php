<?php

namespace Database\Factories;

use App\Models\Personne;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonneFactory extends Factory
{
    protected $model = Personne::class;

    public function definition(): array
    {
        return [
            'nom'        => $this->faker->lastName(),
            'prenom'     => $this->faker->firstName(),
            'sexe'       => $this->faker->randomElement(['M', 'F']),
            'date_naissance' => $this->faker->date(),
        ];
    }
}
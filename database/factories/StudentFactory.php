<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'date_of_birth' => fake()->date(),
            'home_address' => fake()->firstName(),
            'father_name' => fake()->name(),
            'father_occupation' => fake()->name(),
            'mother_name' => fake()->name(),
            
        ];
    }
}

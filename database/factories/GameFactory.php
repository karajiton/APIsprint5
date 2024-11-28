<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = \App\Models\Game::class;

    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'dice_one' => rand(1, 6),
            'dice_two' => rand(1, 6),
            'win' => $this->faker->boolean,
        ];
    }
}


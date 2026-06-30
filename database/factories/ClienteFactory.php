<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name'        => $name,
            'portal_slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'is_active'   => true,
            'cancelled_at'=> null,
        ];
    }
}

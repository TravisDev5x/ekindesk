<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientFactory extends Factory
{
    protected $model = Client::class;

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

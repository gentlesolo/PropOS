<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Models\Agency;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgencyFactory extends Factory
{
    protected $model = Agency::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'email' => $this->faker->unique()->safeEmail(),
            'timezone' => 'UTC',
            'currency' => 'USD',
            'country_code' => 'US',
            'subscription_plan' => 'starter',
            'subscription_status' => 'active',
        ];
    }
}

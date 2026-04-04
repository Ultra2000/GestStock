<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name'       => fake()->company(),
            'email'      => fake()->unique()->safeEmail(),
            'phone'      => fake()->phoneNumber(),
            'address'    => fake()->streetAddress(),
            'zip_code'   => fake()->postcode(),
            'city'       => fake()->city(),
            'siret'      => fake()->numerify('##############'),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->company();
        return [
            'name'     => $name,
            'slug'     => Str::slug($name) . '-' . Str::random(4),
            'email'    => fake()->companyEmail(),
            'currency' => 'EUR',
            'siret'    => fake()->numerify('##############'),
            'tax_number' => 'FR' . fake()->numerify('###########'),
        ];
    }
}

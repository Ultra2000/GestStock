<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 5, 500);
        return [
            'company_id'       => Company::factory(),
            'name'             => fake()->words(3, true),
            'price'            => $price,
            'purchase_price'   => $price * 0.6,
            'vat_rate_sale'    => 20,
            'vat_category'     => 'S',
            'stock'            => fake()->numberBetween(10, 100),
            'barcode_type'     => 'CODE128',
        ];
    }
}

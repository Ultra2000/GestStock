<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        return [
            'company_id'       => $company->id,
            'customer_id'      => Customer::factory()->create(['company_id' => $company->id])->id,
            'invoice_number'   => 'FAC-' . date('Y') . '-' . fake()->unique()->numerify('######'),
            'type'             => 'invoice',
            'status'           => 'pending',
            'payment_method'   => 'transfer',
            'discount_percent' => 0,
            'tax_percent'      => 0,
            'total'            => 0,
            'total_ht'         => 0,
            'total_vat'        => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}

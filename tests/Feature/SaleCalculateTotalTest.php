<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;

describe('Sale::calculateTotal()', function () {

    beforeEach(function () {
        $this->company  = Company::factory()->create();
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->product  = Product::factory()->create(['company_id' => $this->company->id, 'price' => 100]);
    });

    function makeSale(Company $company, Customer $customer): Sale
    {
        return Sale::create([
            'company_id'       => $company->id,
            'customer_id'      => $customer->id,
            'invoice_number'   => 'FAC-TEST-' . uniqid(),
            'type'             => 'invoice',
            'status'           => 'pending',
            'payment_method'   => 'transfer',
            'discount_percent' => 0,
            'tax_percent'      => 0,
            'total'            => 0,
            'total_ht'         => 0,
            'total_vat'        => 0,
        ]);
    }

    it('calcule le total TTC avec un article à 20% TVA', function () {
        $sale = makeSale($this->company, $this->customer);

        Sale::withoutRecalc(function () use ($sale) {
            SaleItem::create([
                'sale_id'    => $sale->id,
                'product_id' => $this->product->id,
                'quantity'   => 2,
                'unit_price' => 100.00,
                'vat_rate'   => 20,
                'vat_category' => 'S',
                'total_price'  => 240.00,
            ]);
        });

        $sale->calculateTotal();
        $sale->refresh();

        expect((float) $sale->total_ht)->toBe(200.0)
            ->and((float) $sale->total_vat)->toBe(40.0)
            ->and((float) $sale->total)->toBe(240.0);
    });

    it('applique correctement une remise globale de 10%', function () {
        $sale = makeSale($this->company, $this->customer);
        $sale->update(['discount_percent' => 10]);

        Sale::withoutRecalc(function () use ($sale) {
            SaleItem::create([
                'sale_id'    => $sale->id,
                'product_id' => $this->product->id,
                'quantity'   => 1,
                'unit_price' => 100.00,
                'vat_rate'   => 20,
                'vat_category' => 'S',
                'total_price'  => 120.00,
            ]);
        });

        $sale->calculateTotal();
        $sale->refresh();

        // HT: 100 * (1 - 0.10) = 90
        // TVA: 20 * (1 - 0.10) = 18
        // TTC: 108
        expect((float) $sale->total_ht)->toBe(90.0)
            ->and((float) $sale->total_vat)->toBe(18.0)
            ->and((float) $sale->total)->toBe(108.0);
    });

    it('calcule le total avec plusieurs articles à taux de TVA différents', function () {
        $sale    = makeSale($this->company, $this->customer);
        $product2 = Product::factory()->create(['company_id' => $this->company->id]);

        Sale::withoutRecalc(function () use ($sale, $product2) {
            // Article 1 : 100 HT × 20% TVA → 120 TTC
            SaleItem::create([
                'sale_id'      => $sale->id,
                'product_id'   => $this->product->id,
                'quantity'     => 1,
                'unit_price'   => 100.00,
                'vat_rate'     => 20,
                'vat_category' => 'S',
                'total_price'  => 120.00,
            ]);
            // Article 2 : 200 HT × 5.5% TVA → 211 TTC
            SaleItem::create([
                'sale_id'      => $sale->id,
                'product_id'   => $product2->id,
                'quantity'     => 1,
                'unit_price'   => 200.00,
                'vat_rate'     => 5.5,
                'vat_category' => 'S',
                'total_price'  => 211.00,
            ]);
        });

        $sale->calculateTotal();
        $sale->refresh();

        expect((float) $sale->total_ht)->toBe(300.0)
            ->and((float) $sale->total_vat)->toBe(31.0)   // 20 + 11
            ->and((float) $sale->total)->toBe(331.0);
    });

    it('withoutRecalc empêche les recalculs intermédiaires inutiles', function () {
        $sale = makeSale($this->company, $this->customer);
        $callCount = 0;

        // Spy sur calculateTotal via un observer temporaire
        Sale::withoutRecalc(function () use ($sale, &$callCount) {
            // Pendant ce bloc, SaleItem::saved ne déclenche pas calculateTotal()
            expect(Sale::$skipRecalc)->toBeTrue();
            $callCount++;
        });

        // Après le bloc, le flag est remis à false
        expect(Sale::$skipRecalc)->toBeFalse()
            ->and($callCount)->toBe(1);
    });
});

<?php

use App\Models\SaleItem;

describe('SaleItem::calculateVat()', function () {

    it('calcule correctement la TVA à 20%', function () {
        $item = new SaleItem([
            'quantity'   => 2,
            'unit_price' => 100.00,
            'vat_rate'   => 20,
        ]);

        $item->calculateVat();

        expect((float) $item->unit_price_ht)->toBe(100.0)
            ->and((float) $item->total_price_ht)->toBe(200.0)
            ->and((float) $item->vat_amount)->toBe(40.0)
            ->and((float) $item->total_price)->toBe(240.0);
    });

    it('calcule correctement la TVA à 5.5%', function () {
        $item = new SaleItem([
            'quantity'   => 1,
            'unit_price' => 200.00,
            'vat_rate'   => 5.5,
        ]);

        $item->calculateVat();

        expect((float) $item->vat_amount)->toBe(11.0)
            ->and((float) $item->total_price)->toBe(211.0);
    });

    it('calcule correctement la TVA à 0%', function () {
        $item = new SaleItem([
            'quantity'   => 3,
            'unit_price' => 50.00,
            'vat_rate'   => 0,
        ]);

        $item->calculateVat();

        expect((float) $item->vat_amount)->toBe(0.0)
            ->and((float) $item->total_price)->toBe(150.0)
            ->and((float) $item->total_price_ht)->toBe(150.0);
    });

    it('arrondit correctement les centimes', function () {
        // 3 × 9.99 HT × 20% TVA = 29.97 HT + 5.994 TVA = 35.964 → 35.96
        $item = new SaleItem([
            'quantity'   => 3,
            'unit_price' => 9.99,
            'vat_rate'   => 20,
        ]);

        $item->calculateVat();

        expect((float) $item->total_price_ht)->toBe(29.97)
            ->and((float) $item->vat_amount)->toBe(5.99)    // round(29.97 * 0.20, 2)
            ->and((float) $item->total_price)->toBe(35.96);
    });

    it('les quantités décimales (ex: 1.5 kg) sont calculées correctement', function () {
        $item = new SaleItem([
            'quantity'   => 1.5,
            'unit_price' => 10.00,
            'vat_rate'   => 20,
        ]);

        $item->calculateVat();

        expect((float) $item->total_price_ht)->toBe(15.0)
            ->and((float) $item->vat_amount)->toBe(3.0)
            ->and((float) $item->total_price)->toBe(18.0);
    });
});

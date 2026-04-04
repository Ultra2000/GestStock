<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RecurringOrder;
use App\Models\RecurringOrderItem;
use Carbon\Carbon;

describe('RecurringOrder::generateSale()', function () {

    beforeEach(function () {
        $this->company  = Company::factory()->create();
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->product  = Product::factory()->create([
            'company_id' => $this->company->id,
            'price'      => 50.00,
            'stock'      => 100,
        ]);
    });

    function makeRecurringOrder(Company $company, Customer $customer, array $overrides = []): RecurringOrder
    {
        return RecurringOrder::create(array_merge([
            'company_id'      => $company->id,
            'customer_id'     => $customer->id,
            'user_id'         => \App\Models\User::factory()->create()->id,
            'name'            => 'Abonnement test',
            'frequency'       => 'monthly',
            'start_date'      => now()->subMonth(),
            'next_order_date' => now()->subDay(), // Dans le passé = exécutable
            'status'          => 'active',
            'total'           => 100.00,
            'orders_generated' => 0,
        ], $overrides));
    }

    it('génère une vente quand next_order_date est passée', function () {
        $order = makeRecurringOrder($this->company, $this->customer);
        RecurringOrderItem::create([
            'recurring_order_id' => $order->id,
            'product_id'         => $this->product->id,
            'quantity'           => 2,
            'unit_price'         => 50.00,
        ]);

        $sale = $order->generateSale();

        expect($sale)->not->toBeNull()
            ->and($sale->company_id)->toBe($this->company->id)
            ->and($sale->customer_id)->toBe($this->customer->id);
    });

    it('ne génère pas de vente si le statut n\'est pas active', function () {
        $order = makeRecurringOrder($this->company, $this->customer, ['status' => 'paused']);

        expect($order->generateSale())->toBeNull();
    });

    it('ne génère pas de vente si next_order_date est dans le futur', function () {
        $order = makeRecurringOrder($this->company, $this->customer, [
            'next_order_date' => now()->addDays(5),
        ]);

        expect($order->generateSale())->toBeNull();
    });

    it('incrémente orders_generated après génération', function () {
        $order = makeRecurringOrder($this->company, $this->customer);
        RecurringOrderItem::create([
            'recurring_order_id' => $order->id,
            'product_id'         => $this->product->id,
            'quantity'           => 1,
            'unit_price'         => 100.00,
        ]);

        $order->generateSale();
        $order->refresh();

        expect($order->orders_generated)->toBe(1);
    });

    it('avance next_order_date au mois suivant après génération (monthly)', function () {
        $order = makeRecurringOrder($this->company, $this->customer, [
            'next_order_date' => Carbon::parse('2026-01-15'),
        ]);
        RecurringOrderItem::create([
            'recurring_order_id' => $order->id,
            'product_id'         => $this->product->id,
            'quantity'           => 1,
            'unit_price'         => 100.00,
        ]);

        $order->generateSale();
        $order->refresh();

        expect($order->next_order_date->toDateString())->toBe('2026-02-15');
    });

    it('passe en status completed si next_order_date dépasse end_date', function () {
        $order = makeRecurringOrder($this->company, $this->customer, [
            'next_order_date' => Carbon::parse('2026-01-15'),
            'end_date'        => Carbon::parse('2026-01-20'),
        ]);
        RecurringOrderItem::create([
            'recurring_order_id' => $order->id,
            'product_id'         => $this->product->id,
            'quantity'           => 1,
            'unit_price'         => 100.00,
        ]);

        $order->generateSale();
        $order->refresh();

        // next_order_date sera 2026-02-15 > end_date 2026-01-20
        expect($order->status)->toBe('completed');
    });
});

<?php

use App\Models\RecurringOrder;
use Carbon\Carbon;

describe('RecurringOrder::calculateNextDate()', function () {

    function makeOrder(string $frequency, string $nextDate): RecurringOrder
    {
        $order = new RecurringOrder();
        $order->frequency = $frequency;
        $order->next_order_date = Carbon::parse($nextDate);
        return $order;
    }

    it('ne mutate pas next_order_date (bug Carbon)', function () {
        $order = makeOrder('monthly', '2026-01-15');
        $original = $order->next_order_date->copy();

        $order->calculateNextDate();

        expect($order->next_order_date->toDateString())->toBe($original->toDateString());
    });

    it('calcule +1 jour pour daily', function () {
        $order = makeOrder('daily', '2026-01-15');
        $next = $order->calculateNextDate();

        expect($next->toDateString())->toBe('2026-01-16');
    });

    it('calcule +1 semaine pour weekly', function () {
        $order = makeOrder('weekly', '2026-01-15');
        expect($order->calculateNextDate()->toDateString())->toBe('2026-01-22');
    });

    it('calcule +2 semaines pour biweekly', function () {
        $order = makeOrder('biweekly', '2026-01-15');
        expect($order->calculateNextDate()->toDateString())->toBe('2026-01-29');
    });

    it('calcule +1 mois pour monthly', function () {
        $order = makeOrder('monthly', '2026-01-15');
        expect($order->calculateNextDate()->toDateString())->toBe('2026-02-15');
    });

    it('calcule +3 mois pour quarterly', function () {
        $order = makeOrder('quarterly', '2026-01-15');
        expect($order->calculateNextDate()->toDateString())->toBe('2026-04-15');
    });

    it('calcule +1 an pour yearly', function () {
        $order = makeOrder('yearly', '2026-01-15');
        expect($order->calculateNextDate()->toDateString())->toBe('2027-01-15');
    });

    it('retourne par défaut +1 mois pour une fréquence inconnue', function () {
        $order = makeOrder('unknown', '2026-01-15');
        expect($order->calculateNextDate()->toDateString())->toBe('2026-02-15');
    });
});

<?php

use App\Models\Sale;

describe('Sale::withoutRecalc()', function () {

    beforeEach(function () {
        Sale::$skipRecalc = false;
    });

    afterEach(function () {
        Sale::$skipRecalc = false;
    });

    it('active le flag pendant le callback', function () {
        Sale::withoutRecalc(function () {
            expect(Sale::$skipRecalc)->toBeTrue();
        });
    });

    it('remet le flag à false après le callback', function () {
        Sale::withoutRecalc(fn () => null);

        expect(Sale::$skipRecalc)->toBeFalse();
    });

    it('remet le flag à false même si le callback lève une exception', function () {
        expect(fn () => Sale::withoutRecalc(function () {
            throw new \RuntimeException('erreur intentionnelle');
        }))->toThrow(\RuntimeException::class);

        expect(Sale::$skipRecalc)->toBeFalse();
    });

    it('ne peut pas rester bloqué à true si deux appels imbriqués échouent', function () {
        try {
            Sale::withoutRecalc(function () {
                Sale::withoutRecalc(function () {
                    throw new \RuntimeException('erreur interne');
                });
            });
        } catch (\RuntimeException) {
        }

        expect(Sale::$skipRecalc)->toBeFalse();
    });
});

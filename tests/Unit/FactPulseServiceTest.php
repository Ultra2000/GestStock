<?php

use App\Services\FactPulseService;

describe('FactPulseService', function () {

    it('isConfigured() retourne false quand les variables sont vides', function () {
        config([
            'services.factpulse.email'      => '',
            'services.factpulse.password'   => '',
            'services.factpulse.client_uid' => '',
        ]);

        $service = new FactPulseService();
        expect($service->isConfigured())->toBeFalse();
    });

    it('isConfigured() retourne true quand toutes les variables sont renseignées', function () {
        config([
            'services.factpulse.email'      => 'test@example.com',
            'services.factpulse.password'   => 'secret',
            'services.factpulse.client_uid' => 'uid-123',
        ]);

        $service = new FactPulseService();
        expect($service->isConfigured())->toBeTrue();
    });

    it('isConfigured() retourne false si seulement email est renseigné', function () {
        config([
            'services.factpulse.email'      => 'test@example.com',
            'services.factpulse.password'   => '',
            'services.factpulse.client_uid' => '',
        ]);

        $service = new FactPulseService();
        expect($service->isConfigured())->toBeFalse();
    });

    it('submitInvoice() lève une exception claire si non configuré', function () {
        config([
            'services.factpulse.email'      => '',
            'services.factpulse.password'   => '',
            'services.factpulse.client_uid' => '',
        ]);

        $service = new FactPulseService();
        $sale = new \App\Models\Sale();

        expect(fn () => $service->submitInvoice($sale))
            ->toThrow(\Exception::class, "L'intégration FactPulse n'est pas configurée");
    });

    it('submitInvoice() lève une exception descriptive si le client est null', function () {
        config([
            'services.factpulse.email'      => 'test@example.com',
            'services.factpulse.password'   => 'secret',
            'services.factpulse.client_uid' => 'uid-123',
        ]);

        $service = new FactPulseService();

        // Vente sans client (customer = null) — ne doit pas produire de NPE
        $sale = \Mockery::mock(\App\Models\Sale::class)->makePartial();
        $sale->shouldReceive('loadMissing')->andReturnSelf();
        $sale->customer = null;
        $sale->company  = (object) ['siret' => '12345678901234', 'name' => 'Test'];

        expect(fn () => $service->submitInvoice($sale))
            ->toThrow(\Exception::class, "n'a pas de client associé");
    });
});

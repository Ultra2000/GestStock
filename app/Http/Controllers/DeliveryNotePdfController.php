<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;

class DeliveryNotePdfController extends Controller
{
    public function download(DeliveryNote $deliveryNote)
    {
        return $this->buildPdf($deliveryNote)->download("BL-{$deliveryNote->reference}.pdf");
    }

    public function stream(DeliveryNote $deliveryNote)
    {
        return $this->buildPdf($deliveryNote)->stream("BL-{$deliveryNote->reference}.pdf");
    }

    private function buildPdf(DeliveryNote $deliveryNote): \Barryvdh\DomPDF\PDF
    {
        if ($deliveryNote->company) {
            Filament::setTenant($deliveryNote->company);
        }
        $this->authorize('view', $deliveryNote);

        return Pdf::loadView('pdf.delivery-note', [
            'deliveryNote' => $deliveryNote->load(['customer', 'items.product', 'sale']),
            'settings' => $deliveryNote->company,
        ])->setPaper('A4', 'portrait');
    }
}

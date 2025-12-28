<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;

class DeliveryNotePdfController extends Controller
{
    public function download(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->company) {
            Filament::setTenant($deliveryNote->company);
        }

        $this->authorize('view', $deliveryNote);

        $companySettings = $deliveryNote->company;

        $pdf = Pdf::loadView('pdf.delivery-note', [
            'deliveryNote' => $deliveryNote->load(['customer', 'items.product', 'sale']),
            'settings' => $companySettings,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("BL-{$deliveryNote->reference}.pdf");
    }

    public function stream(DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->company) {
            Filament::setTenant($deliveryNote->company);
        }

        $this->authorize('view', $deliveryNote);

        $companySettings = $deliveryNote->company;

        $pdf = Pdf::loadView('pdf.delivery-note', [
            'deliveryNote' => $deliveryNote->load(['customer', 'items.product', 'sale']),
            'settings' => $companySettings,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("BL-{$deliveryNote->reference}.pdf");
    }
}

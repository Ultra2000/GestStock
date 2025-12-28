<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;

class QuotePdfController extends Controller
{
    public function download(Quote $quote)
    {
        if ($quote->company) {
            Filament::setTenant($quote->company);
        }
        
        $this->authorize('view', $quote);

        $companySettings = $quote->company;

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote->load(['customer', 'items.product']),
            'settings' => $companySettings,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("Devis-{$quote->quote_number}.pdf");
    }

    public function stream(Quote $quote)
    {
        if ($quote->company) {
            Filament::setTenant($quote->company);
        }

        $this->authorize('view', $quote);

        $companySettings = $quote->company;

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quote->load(['customer', 'items.product']),
            'settings' => $companySettings,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream("Devis-{$quote->quote_number}.pdf");
    }
}

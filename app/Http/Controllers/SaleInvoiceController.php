<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\FacturXService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Filament\Facades\Filament;

class SaleInvoiceController extends Controller
{
    public function generate(Sale $sale, FacturXService $facturXService)
    {
        if ($sale->company) {
            Filament::setTenant($sale->company);
        }
        $this->authorize('view', $sale);

        $company = $sale->company;
        $verificationUrl = URL::signedRoute('sales.invoice.verify', ['sale' => $sale->id]);
        $verificationCode = substr(sha1($sale->id . '|' . $sale->invoice_number . '|' . ($sale->total ?? $sale->items->sum('total_price')) . '|' . $sale->created_at), 0, 12);
        
        $pdf = PDF::loadView('sales.invoice-pdf', [
            'sale' => $sale->load(['items.product', 'customer', 'warehouse']),
            'company' => $company,
            'verificationUrl' => $verificationUrl,
            'verificationCode' => $verificationCode,
        ])->setPaper('a4');

        // Generate Factur-X (PDF/A-3 + XML)
        $pdfContent = $pdf->output();
        $facturxContent = $facturXService->generate($sale, $pdfContent);

        return response($facturxContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="facture-vente-' . $sale->invoice_number . '.pdf"');
    }

    public function preview(Sale $sale, FacturXService $facturXService)
    {
        if ($sale->company) {
            Filament::setTenant($sale->company);
        }
        $this->authorize('view', $sale);

        $company = $sale->company;
        $sale->load(['items.product', 'customer', 'warehouse']);
        
        $verificationUrl = URL::signedRoute('sales.invoice.verify', ['sale' => $sale->id]);
        $verificationCode = substr(sha1($sale->id . '|' . $sale->invoice_number . '|' . ($sale->total ?? $sale->items->sum('total_price')) . '|' . $sale->created_at), 0, 12);
        
        // Generate XML for preview
        $facturxXml = null;
        try {
            $facturxXml = $facturXService->generateXml($sale);
        } catch (\Throwable $e) {
            $facturxXml = '<!-- Error generating XML: ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
        
        return view('sales.invoice', [
            'sale' => $sale,
            'company' => $company,
            'verificationUrl' => $verificationUrl,
            'verificationCode' => $verificationCode,
            'previewMode' => true,
            'facturxXml' => $facturxXml,
        ]);
    }
} 
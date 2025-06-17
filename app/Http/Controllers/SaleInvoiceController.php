<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleInvoiceController extends Controller
{
    public function generate(Sale $sale)
    {
        $company = CompanySetting::getCompany();
        $pdf = PDF::loadView('sales.invoice', compact('sale', 'company'));
        
        return $pdf->download('facture-vente-' . $sale->invoice_number . '.pdf');
    }
} 
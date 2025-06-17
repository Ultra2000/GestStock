<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;

class PurchaseInvoiceController extends Controller
{
    public function generate(Purchase $purchase)
    {
        $company = CompanySetting::getCompany();
        $pdf = PDF::loadView('purchases.invoice', compact('purchase', 'company'));
        
        return $pdf->download('facture-achat-' . $purchase->invoice_number . '.pdf');
    }
} 
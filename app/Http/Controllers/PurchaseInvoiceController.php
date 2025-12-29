<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Filament\Facades\Filament;

class PurchaseInvoiceController extends Controller
{
    public function generate(Purchase $purchase)
    {
        if ($purchase->company) {
            Filament::setTenant($purchase->company);
        }
        $this->authorize('view', $purchase);

        $company = $purchase->company;
        $purchase->load(['items.product', 'supplier']);
        
        $verificationUrl = URL::signedRoute('purchases.invoice.verify', ['purchase' => $purchase->id]);
        $verificationCode = substr(sha1($purchase->id . '|' . $purchase->invoice_number . '|' . ($purchase->total ?? $purchase->items->sum('total_price')) . '|' . $purchase->created_at), 0, 12);
        
        $pdf = PDF::loadView('purchases.invoice-pdf', [
            'purchase' => $purchase,
            'company' => $company,
            'verificationUrl' => $verificationUrl,
            'verificationCode' => $verificationCode,
        ])->setPaper('a4');

        return $pdf->download('facture-achat-' . $purchase->invoice_number . '.pdf');
    }

    public function preview(Purchase $purchase)
    {
        if ($purchase->company) {
            Filament::setTenant($purchase->company);
        }
        $this->authorize('view', $purchase);

        $company = $purchase->company;
        $verificationUrl = URL::signedRoute('purchases.invoice.verify', ['purchase' => $purchase->id]);
        $verificationCode = substr(sha1($purchase->id . '|' . $purchase->invoice_number . '|' . ($purchase->total ?? $purchase->items->sum('total_price')) . '|' . $purchase->created_at), 0, 12);
        return view('purchases.invoice', [
            'purchase' => $purchase->load(['items.product', 'supplier']),
            'company' => $company,
            'verificationUrl' => $verificationUrl,
            'verificationCode' => $verificationCode,
            'previewMode' => true,
        ]);
    }
} 
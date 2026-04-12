<?php

namespace App\Mail;

use App\Models\Purchase;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $type;
    public Purchase|Sale $model;
    public string $customMessage;

    public function __construct(string $type, Purchase|Sale $model, string $customMessage = '')
    {
        $this->type = $type; // 'purchase' | 'sale'
        $this->model = $model->load(['items.product', $type === 'purchase' ? 'supplier' : 'customer']);
        $this->customMessage = $customMessage;
    }

    public function build(): self
    {
        $view = $this->type === 'purchase' ? 'purchases.invoice' : 'sales.invoice';
        $company = $this->model->company;
        $filename = ($this->type === 'purchase' ? 'facture-achat-' : 'facture-vente-') . $this->model->invoice_number . '.pdf';

        $viewData = [
            $this->type === 'purchase' ? 'purchase' : 'sale' => $this->model,
            'company' => $company,
            'previewMode' => false,
        ];

        $pdf = Pdf::loadView($view, $viewData)->setPaper('A4', 'portrait');

        return $this->subject('Facture #' . $this->model->invoice_number)
            ->view($view)
            ->with($viewData)
            ->attachData($pdf->output(), $filename, [
                'mime' => 'application/pdf',
            ]);
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Stripe\Stripe;
use Stripe\Invoice;

class BillingHistory extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Historique des paiements';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?int    $navigationSort  = 99;
    protected static string  $view            = 'filament.pages.billing-history';

    public function getInvoices(): array
    {
        $company = Filament::getTenant();

        if (!$company || !$company->stripe_customer_id) {
            return [];
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $invoices = Invoice::all([
                'customer' => $company->stripe_customer_id,
                'limit'    => 24,
            ]);

            return collect($invoices->data)->map(fn ($inv) => [
                'id'          => $inv->id,
                'number'      => $inv->number ?? '—',
                'date'        => $inv->created ? date('d/m/Y', $inv->created) : '—',
                'period'      => $inv->period_start && $inv->period_end
                    ? date('d/m/Y', $inv->period_start) . ' → ' . date('d/m/Y', $inv->period_end)
                    : '—',
                'amount'      => number_format($inv->amount_paid / 100, 2, ',', ' ') . ' €',
                'status'      => $inv->status,
                'pdf_url'     => $inv->invoice_pdf,
                'hosted_url'  => $inv->hosted_invoice_url,
            ])->toArray();

        } catch (\Exception $e) {
            return [];
        }
    }
}

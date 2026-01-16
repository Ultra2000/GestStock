<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Sale;
use App\Models\Purchase;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = filament()->getTenant()->id;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $payment = $this->record;
        $payable = $payment->payable;

        // Mettre à jour le statut de paiement du document
        if ($payable instanceof Sale) {
            $totalPaid = $payable->payments()->sum('amount');
            
            $payable->update([
                'amount_paid' => $totalPaid,
                'payment_status' => $totalPaid >= $payable->total ? 'paid' : 'partial',
                'paid_at' => $totalPaid >= $payable->total ? now() : null,
            ]);

            Notification::make()
                ->title('Règlement enregistré')
                ->body("Paiement de {$payment->amount}€ enregistré pour {$payable->invoice_number}")
                ->success()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

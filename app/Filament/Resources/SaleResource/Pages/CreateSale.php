<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use App\Services\AccountingEntryService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return 'Nouvelle vente';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Laisser le modèle gérer la génération du numéro de facture
        return $data;
    }

    protected function afterCreate(): void
    {
        $sale = $this->record;
        $items = $this->data['items'] ?? [];

        // Désactiver le recalcul automatique pendant la création en lot
        // pour éviter de générer calculateTotal() N fois (1 par article)
        Sale::$skipRecalc = true;

        try {
            foreach ($items as $item) {
                $sale->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'vat_rate' => $item['vat_rate'] ?? 20,
                    'vat_category' => $item['vat_category'] ?? 'S',
                    'total_price' => $item['total_price'],
                ]);
            }
        } finally {
            Sale::$skipRecalc = false;
        }

        // Recalculer les totaux UNE SEULE FOIS avec tous les articles
        $sale->calculateTotal();

        // Générer les écritures comptables APRÈS que tous les articles soient enregistrés
        if ($sale->status === 'completed' && $sale->total > 0) {
            try {
                $accountingService = app(AccountingEntryService::class);

                if ($sale->type === 'credit_note' && $sale->parent_id) {
                    $originalSale = Sale::find($sale->parent_id);
                    if ($originalSale) {
                        $accountingService->reverseEntries($originalSale, $sale);
                    }
                } else {
                    $accountingService->createEntriesForSale($sale);
                }

                // Enregistrer le paiement POS si payé immédiatement
                if ($sale->payment_method && $sale->cash_session_id) {
                    $accountingService->registerPosPayment($sale);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error(
                    "Erreur génération écritures comptables {$sale->invoice_number}: " . $e->getMessage()
                );
            }
        }
    }
}

<?php

namespace App\Filament\Resources\PurchaseResource\Pages;

use App\Filament\Resources\PurchaseResource;
use App\Models\Product;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    public function getTitle(): string
    {
        return 'Nouvel achat';
    }

    protected function afterCreate(): void
    {
        $purchase = $this->record;
        
        // Recalculer les totaux
        $purchase->recalculateTotals();
        
        // Si le statut est "completed", mettre à jour le stock
        if ($purchase->status === 'completed') {
            foreach ($purchase->items as $item) {
                $product = $item->product;
                if ($product) {
                    // Ajouter au stock global
                    $product->increment('stock', $item->quantity);
                    
                    // Si multi-entrepôt, ajouter au stock de l'entrepôt
                    if ($purchase->warehouse_id) {
                        $product->warehouses()->syncWithoutDetaching([
                            $purchase->warehouse_id => [
                                'quantity' => \DB::raw("COALESCE(quantity, 0) + {$item->quantity}"),
                            ]
                        ]);
                        
                        // Créer un mouvement de stock
                        StockMovement::create([
                            'company_id' => $purchase->company_id,
                            'product_id' => $product->id,
                            'warehouse_id' => $purchase->warehouse_id,
                            'type' => 'in',
                            'quantity' => $item->quantity,
                            'reason' => 'purchase',
                            'reference' => $purchase->invoice_number,
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }
        }
    }
} 
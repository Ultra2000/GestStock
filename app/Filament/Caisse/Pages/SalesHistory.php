<?php

namespace App\Filament\Caisse\Pages;

use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class SalesHistory extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'filament.caisse.pages.sales-history';
    protected static ?string $navigationLabel = 'Historique';
    protected static ?string $title = 'Historique des ventes';
    protected static ?int $navigationSort = 3;

    /**
     * Récupère les ventes du jour
     */
    public function getTodaySales(): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return [];

        return Sale::where('company_id', $tenant->id)
            ->whereDate('created_at', today())
            ->with(['items.product', 'customer'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Annule une vente et remet le stock
     */
    public function cancelSale(int $saleId): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return ['success' => false, 'message' => 'Entreprise non trouvée'];
        }

        $sale = Sale::where('id', $saleId)
            ->where('company_id', $tenant->id)
            ->first();

        if (!$sale) {
            return ['success' => false, 'message' => 'Vente non trouvée'];
        }

        if ($sale->status === 'cancelled') {
            return ['success' => false, 'message' => 'Vente déjà annulée'];
        }

        // Remettre le stock
        foreach ($sale->items as $item) {
            $item->product->increment('stock', $item->quantity);
        }

        $sale->update(['status' => 'cancelled']);

        // Recalculer la session si elle existe
        if ($sale->cashSession) {
            $sale->cashSession->recalculate();
        }

        return ['success' => true, 'message' => 'Vente annulée'];
    }

    /**
     * Récupère les détails d'une vente
     */
    public function getSaleDetails(int $saleId): ?array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return null;

        $sale = Sale::where('id', $saleId)
            ->where('company_id', $tenant->id)
            ->with(['items.product', 'customer', 'cashSession'])
            ->first();

        return $sale?->toArray();
    }
}

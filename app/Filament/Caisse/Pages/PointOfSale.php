<?php

namespace App\Filament\Caisse\Pages;

use App\Models\CashSession;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PointOfSale extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string $view = 'filament.caisse.pages.point-of-sale';
    protected static ?string $navigationLabel = 'Caisse';
    protected static ?string $title = 'Point de Vente';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * Récupère les produits avec recherche
     */
    public function searchProducts(string $query = '', ?int $categoryId = null): array
    {
        $products = Product::query()
            ->when($query, fn($q) => $q->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            }))
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'code', 'price', 'stock', 'min_stock', 'barcode']);

        return $products->toArray();
    }

    /**
     * Récupère un produit par code-barres
     */
    public function getProductByBarcode(string $barcode): ?array
    {
        $product = Product::where('code', $barcode)
            ->orWhere('barcode', $barcode)
            ->first(['id', 'name', 'code', 'price', 'stock', 'min_stock', 'barcode']);

        return $product?->toArray();
    }

    /**
     * Récupère les clients
     */
    public function getCustomers(): array
    {
        return Customer::orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'phone', 'email'])
            ->toArray();
    }

    /**
     * Vérifie si une session de caisse est ouverte
     */
    public function hasOpenSession(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return false;
        
        return CashSession::where('company_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->exists();
    }

    /**
     * Récupère la session ouverte
     */
    public function getOpenSession(): ?array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return null;
        
        $session = CashSession::where('company_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
            
        return $session?->toArray();
    }

    /**
     * Enregistre une vente
     */
    public function recordSale(array $payload): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            return ['success' => false, 'message' => 'Entreprise non trouvée'];
        }

        // Vérifier qu'une session est ouverte
        $session = CashSession::getOpenSession($tenant->id, auth()->id());
        if (!$session) {
            return ['success' => false, 'message' => 'Veuillez ouvrir une session de caisse'];
        }

        try {
            return DB::transaction(function () use ($payload, $tenant, $session) {
                $sale = new Sale();
                $sale->company_id = $tenant->id;
                $sale->cash_session_id = $session->id;
                $sale->payment_method = $payload['payment_method'] ?? 'cash';
                $sale->payment_details = $payload['payment_details'] ?? null;
                
                // Client
                if (!empty($payload['customer_id'])) {
                    $sale->customer_id = $payload['customer_id'];
                } else {
                    $walkIn = Customer::firstOrCreate(
                        ['email' => 'walkin@pos.local', 'company_id' => $tenant->id],
                        [
                            'name' => 'Client comptoir',
                            'company_id' => $tenant->id,
                        ]
                    );
                    $sale->customer_id = $walkIn->id;
                }
                
                $sale->discount_percent = $payload['discount_percent'] ?? 0;
                $sale->tax_percent = $payload['tax_percent'] ?? 0;
                $sale->status = 'completed';
                $sale->save();

                $subtotal = 0;
                foreach ($payload['items'] as $line) {
                    $product = Product::lockForUpdate()->findOrFail($line['product_id']);
                    $qty = max(1, (int) $line['quantity']);
                    
                    if ($product->stock < $qty) {
                        throw new \RuntimeException("Stock insuffisant pour {$product->name}");
                    }
                    
                    $unit = $line['unit_price'] ?? $product->price;
                    $subtotal += ($qty * $unit);
                    $product->stock -= $qty;
                    $product->save();

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'total_price' => $qty * $unit,
                    ]);
                }
                
                $discount = $subtotal * (($sale->discount_percent ?? 0) / 100);
                $afterDiscount = $subtotal - $discount;
                $tax = $afterDiscount * (($sale->tax_percent ?? 0) / 100);
                $sale->total = round($afterDiscount + $tax, 2);
                $sale->save();

                // Mettre à jour la session de caisse
                $session->recalculate();

                return [
                    'success' => true,
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total' => $sale->total,
                ];
            });
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Récupère les dernières ventes de la session
     */
    public function getRecentSales(int $limit = 10): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return [];
        
        $session = CashSession::getOpenSession($tenant->id, auth()->id());
        if (!$session) return [];

        return Sale::where('cash_session_id', $session->id)
            ->with('items.product')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}

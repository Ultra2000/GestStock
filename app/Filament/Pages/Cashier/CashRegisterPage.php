<?php

namespace App\Filament\Pages\Cashier;

use Filament\Pages\Page;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\SaleItem;
use App\Models\CashSession;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;
use Livewire\Attributes\On;

class CashRegisterPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'filament.pages.cashier.cash-register';
    protected static ?string $navigationLabel = 'Caisse';
    protected static ?string $title = 'Point de vente';
    protected static ?string $navigationGroup = 'Point de Vente';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('pos')) {
            return false;
        }
        
        $user = auth()->user();
        if (!$user) return false;
        
        // Vérifier les permissions ou le rôle
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission('sales.create') || $user->hasPermission('sales.*');
        }
        
        return in_array($user->role ?? '', ['cashier', 'admin', 'manager']);
    }

    public function getCompanyId(): ?int
    {
        $tenant = Filament::getTenant();
        return $tenant?->id;
    }

    // Récupérer la session de caisse ouverte
    public function getOpenSession(): ?CashSession
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return null;

        return CashSession::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();
    }

    // Ouvrir une session de caisse
    public function openSession(float $openingAmount): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return ['success' => false, 'message' => 'Aucune entreprise sélectionnée'];
        }

        // Vérifier s'il y a déjà une session ouverte
        $existingSession = $this->getOpenSession();
        if ($existingSession) {
            return ['success' => false, 'message' => 'Une session est déjà ouverte'];
        }

        $session = CashSession::openSession(auth()->id(), $companyId, $openingAmount);
        
        return ['success' => true, 'session' => $session];
    }

    // Fermer la session de caisse
    public function closeSession(float $closingAmount, ?string $notes = null): array
    {
        $session = $this->getOpenSession();
        if (!$session) {
            return ['success' => false, 'message' => 'Aucune session ouverte'];
        }

        $session->closeSession($closingAmount, $notes);
        
        return [
            'success' => true,
            'session' => $session,
            'difference' => $session->difference,
        ];
    }

    // Vérifier si une session est ouverte
    public function hasOpenSession(): bool
    {
        return $this->getOpenSession() !== null;
    }

    // Récupérer les statistiques de la session
    public function getSessionStats(): array
    {
        $session = $this->getOpenSession();
        if (!$session) {
            return [];
        }

        $session->recalculate();

        return [
            'id' => $session->id,
            'opening_amount' => $session->opening_amount,
            'total_sales' => $session->total_sales,
            'total_cash' => $session->total_cash,
            'total_card' => $session->total_card,
            'total_mobile' => $session->total_mobile,
            'sales_count' => $session->sales_count,
            'expected_cash' => $session->opening_amount + $session->total_cash,
            'opened_at' => $session->opened_at->format('H:i'),
        ];
    }

    // Récupérer les ventes du jour
    public function getTodaySales(): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) return [];

        return Sale::with(['items.product', 'customer'])
            ->where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    // Annuler une vente
    public function cancelSale(int $saleId): array
    {
        $companyId = $this->getCompanyId();
        
        $sale = Sale::where('id', $saleId)
            ->where('company_id', $companyId)
            ->with('items')
            ->first();

        if (!$sale) {
            return ['success' => false, 'message' => 'Vente introuvable'];
        }

        if ($sale->status === 'cancelled') {
            return ['success' => false, 'message' => 'Vente déjà annulée'];
        }

        DB::transaction(function () use ($sale) {
            // Restaurer le stock
            foreach ($sale->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);
                }
            }

            $sale->update(['status' => 'cancelled']);
        });

        // Recalculer la session si elle existe
        if ($sale->cash_session_id) {
            $session = CashSession::find($sale->cash_session_id);
            $session?->recalculate();
        }

        return ['success' => true];
    }

    // Rechercher des produits
    public function searchProducts(string $query): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId || strlen($query) < 1) return [];

        return Product::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'code', 'price', 'stock', 'min_stock'])
            ->toArray();
    }

    // Récupérer un produit par code-barres
    public function getProductByBarcode(string $code): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return ['success' => false, 'message' => 'Aucune entreprise'];
        }

        $product = Product::where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        if (!$product) {
            return ['success' => false, 'message' => 'Produit introuvable'];
        }

        return [
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'price' => $product->price,
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
            ]
        ];
    }

    // Enregistrer une vente
    public function recordSale(array $payload): array
    {
        $companyId = $this->getCompanyId();
        if (!$companyId) {
            return ['success' => false, 'message' => 'Aucune entreprise sélectionnée'];
        }

        // Vérifier la session de caisse
        $session = $this->getOpenSession();
        if (!$session) {
            return ['success' => false, 'message' => 'Veuillez ouvrir une session de caisse'];
        }

        try {
            $saleId = DB::transaction(function () use ($payload, $companyId, $session) {
                $sale = new Sale();
                $sale->company_id = $companyId;
                $sale->cash_session_id = $session->id;
                $sale->payment_method = $payload['payment_method'] ?? 'cash';
                $sale->payment_details = $payload['payment_details'] ?? null;

                // Client comptoir par défaut
                if (!empty($payload['customer_id'])) {
                    $sale->customer_id = $payload['customer_id'];
                } else {
                    $walkIn = Customer::firstOrCreate(
                        ['email' => 'walkin@example.com', 'company_id' => $companyId],
                        [
                            'name' => 'Client comptoir',
                            'company_id' => $companyId,
                            'phone' => null,
                            'address' => null,
                            'city' => null,
                            'country' => null,
                            'notes' => 'Client généré automatiquement pour ventes comptoir',
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
                    $product = Product::where('company_id', $companyId)
                        ->lockForUpdate()
                        ->findOrFail($line['product_id']);
                    
                    $qty = (int) $line['quantity'];
                    if ($qty < 1) $qty = 1;
                    
                    if ($product->stock < $qty) {
                        throw new \RuntimeException('Stock insuffisant pour ' . $product->name);
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

                // Mettre à jour la session
                $session->recalculate();

                return $sale->id;
            });

            return ['success' => true, 'sale_id' => $saleId];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

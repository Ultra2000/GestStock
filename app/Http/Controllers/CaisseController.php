<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\CashSession;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;

class CaisseController extends Controller
{
    protected function getCompanyId(Request $request): ?int
    {
        $user = auth()->user();
        if (!$user) return null;

        // 1. Essayer le tenant Filament
        $tenant = Filament::getTenant();
        if ($tenant) {
            return $tenant->id;
        }

        // 2. Header ou session — mais vérifier que l'utilisateur appartient à l'entreprise
        $candidateId = null;

        if ($request->hasHeader('X-Company-Id')) {
            $candidateId = (int) $request->header('X-Company-Id');
        } elseif (session()->has('filament_tenant_id')) {
            $candidateId = (int) session('filament_tenant_id');
        }

        if ($candidateId) {
            // Vérifier que l'utilisateur a accès à cette entreprise
            if (method_exists($user, 'companies') && $user->companies()->where('companies.id', $candidateId)->exists()) {
                return $candidateId;
            }
            // Super admin peut accéder à tout
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return $candidateId;
            }
            return null; // Accès refusé — l'utilisateur n'appartient pas à cette entreprise
        }

        // 3. Fallback: première company de l'utilisateur
        if (method_exists($user, 'companies')) {
            return $user->companies()->first()?->id;
        }

        return $user->company_id ?? null;
    }

    protected function getOpenSession(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) return null;

        return CashSession::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
    }

    // Vérifier si une session est ouverte
    public function checkSession(Request $request)
    {
        $session = $this->getOpenSession($request);
        
        if (!$session) {
            return response()->json([
                'open' => false,
                'session' => null
            ]);
        }

        $session->recalculate();

        return response()->json([
            'open' => true,
            'session' => [
                'id' => $session->id,
                'opening_amount' => $session->opening_amount,
                'total_sales' => $session->total_sales,
                'sales_count' => $session->sales_count,
                'cash_sales' => $session->total_cash,
                'card_sales' => $session->total_card,
                'mobile_sales' => $session->total_mobile,
                'cash_in_drawer' => $session->opening_amount + $session->total_cash,
                'opened_at' => $session->opened_at->format('H:i'),
            ]
        ]);
    }

    // Ouvrir une session
    public function openSession(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune entreprise sélectionnée'
            ], 400);
        }

        // Vérifier s'il y a déjà une session ouverte
        $existingSession = $this->getOpenSession($request);
        if ($existingSession) {
            return response()->json([
                'success' => false,
                'message' => 'Une session est déjà ouverte'
            ], 400);
        }

        $openingAmount = floatval($request->input('opening_amount', 0));
        
        $session = CashSession::create([
            'company_id' => $companyId,
            'user_id' => auth()->id(),
            'opening_amount' => $openingAmount,
            'opened_at' => now(),
            'status' => 'open',
            'total_sales' => 0,
            'total_cash' => 0,
            'total_card' => 0,
            'total_mobile' => 0,
            'sales_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'opening_amount' => $session->opening_amount,
                'total_sales' => 0,
                'sales_count' => 0,
                'cash_sales' => 0,
                'card_sales' => 0,
                'mobile_sales' => 0,
                'cash_in_drawer' => $session->opening_amount,
                'opened_at' => $session->opened_at->format('H:i'),
            ]
        ]);
    }

    // Fermer une session
    public function closeSession(Request $request)
    {
        $session = $this->getOpenSession($request);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune session ouverte'
            ], 400);
        }

        // Recalculer les totaux avant fermeture
        $session->recalculate();
        
        $closingAmount = floatval($request->input('closing_amount', 0));
        $expectedCash = $session->opening_amount + $session->total_cash;
        
        $session->update([
            'closing_amount' => $closingAmount,
            'closed_at' => now(),
            'status' => 'closed',
            'difference' => $closingAmount - $expectedCash,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'session' => $session,
            'difference' => $closingAmount - $expectedCash,
        ]);
    }

    // Liste des produits
    public function products(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json([]);
        }

        $products = Product::where('company_id', $companyId)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'code', 'price', 'stock'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code,
                    'selling_price' => $p->price,
                    'quantity' => $p->stock,
                ];
            });

        return response()->json($products);
    }

    // Recherche de produits
    public function searchProducts(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        $query = $request->query('q', '');

        if (!$companyId || strlen($query) < 1) {
            return response()->json([]);
        }

        $products = Product::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('code', 'like', "%{$query}%");
            })
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'code', 'price', 'stock'])
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'code' => $p->code,
                    'selling_price' => $p->price,
                    'quantity' => $p->stock,
                ];
            });

        return response()->json($products);
    }

    // Produit par code-barres
    public function productByBarcode(Request $request, string $code)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json(['error' => 'No company'], 400);
        }

        $product = Product::where('company_id', $companyId)
            ->where('code', $code)
            ->first(['id', 'name', 'code', 'price', 'stock']);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'selling_price' => $product->price,
            'quantity' => $product->stock,
        ]);
    }

    // Enregistrer une vente
    public function recordSale(Request $request)
    {
        $companyId = $this->getCompanyId($request);
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune entreprise sélectionnée'
            ], 400);
        }

        $session = $this->getOpenSession($request);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez ouvrir une session de caisse'
            ], 400);
        }

        // Validation des entrées
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile,mixed',
        ]);

        $items = $request->input('items');
        $paymentMethod = $request->input('payment_method', 'cash');

        try {
            $sale = DB::transaction(function () use ($items, $paymentMethod, $companyId, $session) {
                // Client comptoir par défaut
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

                $sale = Sale::create([
                    'company_id' => $companyId,
                    'customer_id' => $walkIn->id,
                    'cash_session_id' => $session->id,
                    'payment_method' => $paymentMethod,
                    'status' => 'completed',
                    'total' => 0, // Sera recalculé après ajout des items
                    'discount_percent' => 0,
                    'tax_percent' => 0,
                ]);

                foreach ($items as $item) {
                    $product = Product::where('company_id', $companyId)
                        ->lockForUpdate()
                        ->findOrFail($item['product_id']);

                    $qty = (int) $item['quantity'];
                    $price = floatval($item['price']);

                    if ($product->stock < $qty) {
                        throw new \RuntimeException('Stock insuffisant pour ' . $product->name);
                    }

                    $product->decrement('stock', $qty);

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'total_price' => $qty * $price,
                    ]);
                }

                // Recalculer le total côté serveur (ne jamais faire confiance au client)
                $sale->calculateTotal();

                // Mettre à jour la session
                $session->recalculate();

                return $sale;
            });

            $session->refresh();

            return response()->json([
                'success' => true,
                'sale_id' => $sale->id,
                'session' => [
                    'id' => $session->id,
                    'opening_amount' => $session->opening_amount,
                    'total_sales' => $session->total_sales,
                    'sales_count' => $session->sales_count,
                    'cash_sales' => $session->total_cash,
                    'card_sales' => $session->total_card,
                    'mobile_sales' => $session->total_mobile,
                    'cash_in_drawer' => $session->opening_amount + $session->total_cash,
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

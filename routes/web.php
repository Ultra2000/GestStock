<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\CashReportController;
use App\Http\Controllers\QuotePdfController;
use App\Http\Controllers\DeliveryNotePdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Routes pour les invitations
Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invitation/{token}/accept', [InvitationController::class, 'accept'])->name('invitation.accept');

// Routes API pour la caisse (POS)
Route::middleware('auth')->prefix('api/pos')->group(function () {
    Route::get('/session/check', [CaisseController::class, 'checkSession']);
    Route::post('/session/open', [CaisseController::class, 'openSession']);
    Route::post('/session/close', [CaisseController::class, 'closeSession']);
    Route::get('/products', [CaisseController::class, 'products']);
    Route::get('/products/search', [CaisseController::class, 'searchProducts']);
    Route::get('/products/barcode/{code}', [CaisseController::class, 'productByBarcode']);
    Route::post('/sale', [CaisseController::class, 'recordSale']);
    
    // Rapports de caisse
    Route::get('/report', [CashReportController::class, 'getSessionReport']);
    Route::get('/report/history', [CashReportController::class, 'getSessionHistory']);
    Route::get('/report/{sessionId}/pdf', [CashReportController::class, 'exportPdf'])->name('pos.report.pdf');
    Route::get('/report/{sessionId}/excel', [CashReportController::class, 'exportExcel'])->name('pos.report.excel');
});

require __DIR__.'/auth.php';

// Route de test pour débugger l'accès admin
Route::get('/test-admin', function () {
    $user = auth()->user();
    if (!$user) {
        return 'Non authentifié';
    }
    
    return [
        'user' => $user->name,
        'email' => $user->email,
        'role' => $user->role,
        'email_verified' => $user->email_verified_at ? 'Oui' : 'Non',
        'guard' => auth()->getDefaultDriver(),
    ];
})->middleware('auth');

// Route pour générer la facture d'un achat (PDF)
use App\Http\Controllers\PurchaseInvoiceController;
use App\Models\Purchase;
use App\Http\Controllers\SaleInvoiceController;
use App\Models\Sale;
use Illuminate\Http\Request;
use App\Http\Controllers\ProductLabelController;

Route::get('/purchases/{purchase}/invoice', [PurchaseInvoiceController::class, 'generate'])
    ->middleware('auth')
    ->name('purchases.invoice');

Route::get('/sales/{sale}/invoice', [SaleInvoiceController::class, 'generate'])
    ->middleware('auth')
    ->name('sales.invoice');

// Prévisualisation (HTML) avant téléchargement PDF
Route::get('/purchases/{purchase}/invoice/preview', [PurchaseInvoiceController::class, 'preview'])
    ->middleware('auth')
    ->name('purchases.invoice.preview');

Route::get('/sales/{sale}/invoice/preview', [SaleInvoiceController::class, 'preview'])
    ->middleware('auth')
    ->name('sales.invoice.preview');

// Routes pour les devis (PDF)
Route::get('/quotes/{quote}/pdf', [QuotePdfController::class, 'download'])
    ->middleware('auth')
    ->name('quotes.pdf');

Route::get('/quotes/{quote}/pdf/preview', [QuotePdfController::class, 'stream'])
    ->middleware('auth')
    ->name('quotes.pdf.preview');

// Routes pour les bons de livraison (PDF)
Route::get('/delivery-notes/{deliveryNote}/pdf', [DeliveryNotePdfController::class, 'download'])
    ->middleware('auth')
    ->name('delivery-notes.pdf');

Route::get('/delivery-notes/{deliveryNote}/pdf/preview', [DeliveryNotePdfController::class, 'stream'])
    ->middleware('auth')
    ->name('delivery-notes.pdf.preview');

// API légère pour la caisse (panel caisse ou admin) - protégée auth
Route::middleware('auth')->group(function () {
    Route::get('/admin/api/products', function (\Illuminate\Http\Request $request) {
        $q = $request->query('q');
        if(!$q){ return []; }
        return \App\Models\Product::query()
            ->where(function($w) use ($q){
                $w->where('name','like',"%$q%")
                  ->orWhere('code','like',"%$q%" );
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['id','name','price','stock','min_stock']);
    });

    Route::post('/admin/api/cash-sale', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        if(!in_array($user->role, ['admin','cashier'])){
            return response()->json(['success'=>false,'message'=>'Non autorisé'], 403);
        }
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        try {
            $page = new \App\Filament\Pages\Cashier\CashRegisterPage();
            $saleId = $page->recordSale([
                'customer_id' => null,
                'items' => $data['items'],
                'discount_percent' => $data['discount_percent'] ?? 0,
                'tax_percent' => $data['tax_percent'] ?? 0,
            ]);
            return ['success'=>true,'sale_id'=>$saleId];
        } catch (\Throwable $e) {
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    });

    // Recherche directe par code barre / code produit (pour scan)
    Route::get('/admin/api/product-code/{code}', function (string $code) {
        $product = \App\Models\Product::where('code', $code)->first();
        if(!$product){
            return response()->json(['success'=>false,'message'=>'Produit introuvable'], 404);
        }
        return [
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'code' => $product->code,
            ]
        ];
    });

    // Impression étiquettes produits (ids=1,2,3 & q=1:3,2:5 pour quantités)
    Route::get('/admin/products/labels/print', [ProductLabelController::class, 'print'])
        ->name('products.labels.print');
});

// Routes publiques signées pour vérification d'authenticité d'une facture
Route::get('/verify/purchase/{purchase}', function (Request $request, Purchase $purchase) {
    if (! $request->hasValidSignature()) {
        abort(401, 'Lien de vérification invalide ou expiré');
    }
    $expectedCode = substr(sha1($purchase->id . '|' . $purchase->invoice_number . '|' . ($purchase->total ?? $purchase->items->sum('total_price')) . '|' . $purchase->created_at), 0, 12);
    return view('verify.invoice', [
        'type' => 'purchase',
        'model' => $purchase->load(['items.product', 'supplier']),
        'computedCode' => $expectedCode,
        'invoiceNumber' => $purchase->invoice_number,
        'amount' => $purchase->total ?? $purchase->items->sum('total_price'),
        'date' => $purchase->created_at,
    ]);
})->name('purchases.invoice.verify');

Route::get('/verify/sale/{sale}', function (Request $request, Sale $sale) {
    if (! $request->hasValidSignature()) {
        abort(401, 'Lien de vérification invalide ou expiré');
    }
    $expectedCode = substr(sha1($sale->id . '|' . $sale->invoice_number . '|' . ($sale->total ?? $sale->items->sum('total_price')) . '|' . $sale->created_at), 0, 12);
    return view('verify.invoice', [
        'type' => 'sale',
        'model' => $sale->load(['items.product', 'customer']),
        'computedCode' => $expectedCode,
        'invoiceNumber' => $sale->invoice_number,
        'amount' => $sale->total ?? $sale->items->sum('total_price'),
        'date' => $sale->created_at,
    ]);
})->name('sales.invoice.verify');

// Routes Multi-Entrepôts (impression)
Route::middleware('auth')->group(function () {
    Route::get('/stock-transfers/{transfer}/print', function (\App\Models\StockTransfer $transfer) {
        return view('prints.stock-transfer', ['transfer' => $transfer->load(['sourceWarehouse', 'destinationWarehouse', 'items.product', 'requestedBy', 'approvedBy'])]);
    })->name('stock-transfers.print');

    Route::get('/inventories/{inventory}/print', function (\App\Models\Inventory $inventory) {
        return view('prints.inventory', ['inventory' => $inventory->load(['warehouse', 'items.product', 'createdByUser', 'validatedByUser'])]);
    })->name('inventories.print');
});

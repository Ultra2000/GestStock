<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Company;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;

class StockReportController extends Controller
{
    /**
     * Générer le rapport d'état des stocks PDF
     */
    public function stockStatus(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->query('company_id') ?? Filament::getTenant()?->id;
        
        if (!$companyId) {
            abort(400, 'Company ID required');
        }

        $company = Company::findOrFail($companyId);
        
        // Filtres optionnels
        $warehouseId = $request->query('warehouse_id');
        $lowStockOnly = $request->boolean('low_stock_only');
        
        $query = Product::where('company_id', $companyId)
            ->with(['warehouses', 'supplier']);
        
        if ($warehouseId) {
            $query->whereHas('warehouses', function ($q) use ($warehouseId) {
                $q->where('warehouses.id', $warehouseId);
            });
        }
        
        if ($lowStockOnly) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }
        
        $products = $query->orderBy('name')->get();
        
        // Calculs statistiques
        $stats = [
            'total_products' => $products->count(),
            'total_value' => $products->sum(fn($p) => ($p->stock ?? 0) * ($p->purchase_price ?? 0)),
            'total_sell_value' => $products->sum(fn($p) => ($p->stock ?? 0) * ($p->price ?? 0)),
            'low_stock_count' => $products->filter(fn($p) => ($p->stock ?? 0) <= ($p->min_stock ?? 0))->count(),
            'out_of_stock_count' => $products->filter(fn($p) => ($p->stock ?? 0) <= 0)->count(),
        ];
        
        // Grouper par fournisseur
        $productsBySupplier = $products->groupBy(fn($p) => $p->supplier?->name ?? 'Sans fournisseur');
        
        // Grouper par entrepôt
        $productsByWarehouse = $products->groupBy(fn($p) => $p->warehouses->first()?->name ?? 'Entrepôt principal');
        
        $warehouses = Warehouse::where('company_id', $companyId)->get();
        
        $pdf = Pdf::loadView('reports.stock-status', [
            'company' => $company,
            'products' => $products,
            'productsByCategory' => $productsBySupplier, // On utilise le même nom pour la vue
            'productsByWarehouse' => $productsByWarehouse,
            'stats' => $stats,
            'warehouses' => $warehouses,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'low_stock_only' => $lowStockOnly,
            ],
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        $filename = 'etat-stocks-' . now()->format('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Prévisualiser le rapport d'état des stocks
     */
    public function stockStatusPreview(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->query('company_id') ?? Filament::getTenant()?->id;
        
        if (!$companyId) {
            abort(400, 'Company ID required');
        }

        $company = Company::findOrFail($companyId);
        
        $warehouseId = $request->query('warehouse_id');
        $lowStockOnly = $request->boolean('low_stock_only');
        
        $query = Product::where('company_id', $companyId)
            ->with(['warehouses', 'supplier']);
        
        if ($warehouseId) {
            $query->whereHas('warehouses', function ($q) use ($warehouseId) {
                $q->where('warehouses.id', $warehouseId);
            });
        }
        
        if ($lowStockOnly) {
            $query->whereColumn('stock', '<=', 'min_stock');
        }
        
        $products = $query->orderBy('name')->get();
        
        $stats = [
            'total_products' => $products->count(),
            'total_value' => $products->sum(fn($p) => ($p->stock ?? 0) * ($p->purchase_price ?? 0)),
            'total_sell_value' => $products->sum(fn($p) => ($p->stock ?? 0) * ($p->price ?? 0)),
            'low_stock_count' => $products->filter(fn($p) => ($p->stock ?? 0) <= ($p->min_stock ?? 0))->count(),
            'out_of_stock_count' => $products->filter(fn($p) => ($p->stock ?? 0) <= 0)->count(),
        ];
        
        $productsBySupplier = $products->groupBy(fn($p) => $p->supplier?->name ?? 'Sans fournisseur');
        $productsByWarehouse = $products->groupBy(fn($p) => $p->warehouses->first()?->name ?? 'Entrepôt principal');
        $warehouses = Warehouse::where('company_id', $companyId)->get();
        
        return view('reports.stock-status', [
            'company' => $company,
            'products' => $products,
            'productsByCategory' => $productsBySupplier,
            'productsByWarehouse' => $productsByWarehouse,
            'stats' => $stats,
            'warehouses' => $warehouses,
            'filters' => [
                'warehouse_id' => $warehouseId,
                'low_stock_only' => $lowStockOnly,
            ],
            'generatedAt' => now(),
            'previewMode' => true,
        ]);
    }

    /**
     * Rapport des mouvements de stock
     */
    public function stockMovements(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->query('company_id') ?? Filament::getTenant()?->id;
        
        if (!$companyId) {
            abort(400, 'Company ID required');
        }

        $company = Company::findOrFail($companyId);
        
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $warehouseId = $request->query('warehouse_id');
        
        $query = StockMovement::where('company_id', $companyId)
            ->with(['product', 'warehouse', 'user'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        
        $movements = $query->orderBy('created_at', 'desc')->get();
        
        // Statistiques
        $stats = [
            'total_in' => $movements->where('type', 'in')->sum('quantity'),
            'total_out' => $movements->where('type', 'out')->sum('quantity'),
            'total_adjustment' => $movements->where('type', 'adjustment')->sum('quantity'),
            'total_transfer' => $movements->where('type', 'transfer')->count(),
        ];
        
        // Grouper par jour
        $movementsByDay = $movements->groupBy(fn($m) => $m->created_at->format('Y-m-d'));
        
        $warehouses = Warehouse::where('company_id', $companyId)->get();
        
        $pdf = Pdf::loadView('reports.stock-movements', [
            'company' => $company,
            'movements' => $movements,
            'movementsByDay' => $movementsByDay,
            'stats' => $stats,
            'warehouses' => $warehouses,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'mouvements-stock-' . $startDate . '-' . $endDate . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export inventaire Excel/CSV
     */
    public function exportInventory(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->query('company_id') ?? Filament::getTenant()?->id;
        
        if (!$companyId) {
            abort(400, 'Company ID required');
        }

        $products = Product::where('company_id', $companyId)
            ->with(['warehouses', 'supplier'])
            ->orderBy('name')
            ->get();

        $filename = 'inventaire-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // En-têtes
            fputcsv($file, [
                'Référence',
                'Code-barres',
                'Désignation',
                'Fournisseur',
                'Entrepôt',
                'Stock actuel',
                'Stock minimum',
                'Prix achat HT',
                'Prix vente TTC',
                'Valeur stock (achat)',
                'Valeur stock (vente)',
                'Statut',
            ], ';');

            foreach ($products as $product) {
                $status = 'OK';
                if (($product->stock ?? 0) <= 0) {
                    $status = 'RUPTURE';
                } elseif (($product->stock ?? 0) <= ($product->min_stock ?? 0)) {
                    $status = 'BAS';
                }

                fputcsv($file, [
                    $product->code ?? '-',
                    $product->barcode ?? '-',
                    $product->name,
                    $product->supplier?->name ?? '-',
                    $product->warehouses->first()?->name ?? 'Principal',
                    $product->stock ?? 0,
                    $product->min_stock ?? 0,
                    number_format($product->purchase_price ?? 0, 2, ',', ''),
                    number_format($product->price ?? 0, 2, ',', ''),
                    number_format((($product->stock ?? 0) * ($product->purchase_price ?? 0)), 2, ',', ''),
                    number_format((($product->stock ?? 0) * ($product->price ?? 0)), 2, ',', ''),
                    $status,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

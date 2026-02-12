<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;

class CashReportController extends Controller
{
    /**
     * Résoudre le company_id de manière sécurisée
     */
    protected function resolveCompanyId(Request $request): ?int
    {
        $user = auth()->user();
        if (!$user) return null;

        // 1. Tenant Filament
        $tenant = Filament::getTenant();
        if ($tenant) return $tenant->id;

        // 2. Header ou query — vérifier l'appartenance
        $candidateId = $request->header('X-Company-Id') ?? $request->query('company_id');
        if ($candidateId) {
            $candidateId = (int) $candidateId;
            if (method_exists($user, 'companies') && $user->companies()->where('companies.id', $candidateId)->exists()) {
                return $candidateId;
            }
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return $candidateId;
            }
            return null;
        }

        // 3. Fallback
        if (method_exists($user, 'companies')) {
            return $user->companies()->first()?->id;
        }

        return $user->company_id ?? null;
    }

    /**
     * Récupérer les données du rapport de session
     */
    public function getSessionReport(Request $request)
    {
        $sessionId = $request->query('session_id');
        $companyId = $this->resolveCompanyId($request);

        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 400);
        }

        // Si pas de session_id, récupérer la session ouverte
        if (!$sessionId) {
            $session = CashSession::where('company_id', $companyId)
                ->where('user_id', auth()->id())
                ->whereNull('closed_at')
                ->first();
        } else {
            $session = CashSession::where('company_id', $companyId)
                ->where('id', $sessionId)
                ->first();
        }

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        // Recalculer les totaux
        $session->recalculate();

        // Récupérer les ventes détaillées
        $sales = Sale::with(['items.product', 'customer'])
            ->where('cash_session_id', $session->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        // Statistiques par mode de paiement
        $paymentStats = Sale::where('cash_session_id', $session->id)
            ->where('status', 'completed')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        // Calculer le total des ventes et les pourcentages
        $totalSalesAmount = floatval($session->total_sales);
        $totalSalesCount = intval($session->sales_count);

        $getPaymentStat = function ($method) use ($paymentStats, $totalSalesAmount) {
            $stat = $paymentStats->get($method);
            $count = $stat?->count ?? 0;
            $total = floatval($stat?->total ?? 0);
            $percentage = $totalSalesAmount > 0 ? ($total / $totalSalesAmount) * 100 : 0;
            return [
                'count' => $count,
                'total' => $total,
                'percentage' => round($percentage, 1),
            ];
        };

        // Produits les plus vendus
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.cash_session_id', $session->id)
            ->where('sales.status', 'completed')
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total_price) as total_amount')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $hourSql = $isSqlite ? "strftime('%H', created_at)" : "HOUR(created_at)";

        // Ventes par heure
        $salesByHour = Sale::where('cash_session_id', $session->id)
            ->where('status', 'completed')
            ->select(
                DB::raw("$hourSql as hour"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy(DB::raw($hourSql))
            ->orderBy('hour')
            ->get();

        return response()->json([
            'success' => true,
            'session' => [
                'id' => $session->id,
                'opened_at' => $session->opened_at->toISOString(),
                'closed_at' => $session->closed_at ? $session->closed_at->toISOString() : null,
                'opening_amount' => floatval($session->opening_amount),
                'closing_amount' => floatval($session->closing_amount),
                'expected_amount' => floatval($session->expected_amount),
                'difference' => floatval($session->difference ?? 0),
                'total_sales' => floatval($session->total_sales),
                'total_cash' => floatval($session->total_cash),
                'total_card' => floatval($session->total_card),
                'total_mobile' => floatval($session->total_mobile),
                'total_other' => floatval($session->total_other ?? 0),
                'sales_count' => $session->sales_count,
                'user' => $session->user->name ?? 'N/A',
                'status' => $session->closed_at ? 'closed' : 'open',
            ],
            'summary' => [
                'total_sales' => floatval($session->total_sales),
                'sales_count' => $session->sales_count,
                'average_sale' => $session->sales_count > 0 ? round($session->total_sales / $session->sales_count, 2) : 0,
                'cash_in_drawer' => floatval($session->opening_amount) + floatval($session->total_cash),
            ],
            'payment_stats' => [
                'cash' => $getPaymentStat('cash'),
                'card' => $getPaymentStat('card'),
                'mobile' => $getPaymentStat('mobile'),
                'mixed' => $getPaymentStat('mixed'),
            ],
            'top_products' => $topProducts->map(function ($product) {
                return [
                    'name' => $product->name,
                    'quantity' => intval($product->total_quantity),
                    'total' => floatval($product->total_amount),
                ];
            }),
            'sales_by_hour' => $salesByHour,
            'sales' => $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total' => floatval($sale->total),
                    'payment_method' => $sale->payment_method,
                    'items_count' => $sale->items->sum('quantity'),
                    'created_at' => $sale->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Exporter le rapport en PDF
     */
    public function exportPdf(Request $request, $sessionId)
    {
        $companyId = $this->resolveCompanyId($request);

        $session = CashSession::with('user')
            ->where('id', $sessionId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $session->recalculate();

        // Récupérer les ventes
        $sales = Sale::with(['items.product'])
            ->where('cash_session_id', $session->id)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        // Stats par paiement
        $paymentStats = Sale::where('cash_session_id', $session->id)
            ->where('status', 'completed')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total) as total'))
            ->groupBy('payment_method')
            ->get()
            ->keyBy('payment_method');

        // Top produits
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.cash_session_id', $session->id)
            ->where('sales.status', 'completed')
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total_price) as total_amount')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $company = \App\Models\Company::find($companyId);

        $pdf = Pdf::loadView('reports.cash-session', [
            'session' => $session,
            'sales' => $sales,
            'paymentStats' => $paymentStats,
            'topProducts' => $topProducts,
            'company' => $company,
        ]);

        $filename = 'rapport-caisse-' . $session->opened_at->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Exporter le rapport en Excel/CSV
     */
    public function exportExcel(Request $request, $sessionId)
    {
        $companyId = $this->resolveCompanyId($request);

        $session = CashSession::with('user')
            ->where('id', $sessionId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $session->recalculate();

        // Récupérer les ventes avec détails
        $sales = Sale::with(['items.product'])
            ->where('cash_session_id', $session->id)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        // Créer le CSV
        $filename = 'rapport-caisse-' . $session->opened_at->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($session, $sales) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // En-tête du rapport
            fputcsv($file, ['RAPPORT DE CAISSE'], ';');
            fputcsv($file, [''], ';');
            fputcsv($file, ['Caissier', $session->user->name ?? 'N/A'], ';');
            fputcsv($file, ['Ouverture', $session->opened_at->format('d/m/Y H:i')], ';');
            fputcsv($file, ['Fermeture', $session->closed_at ? $session->closed_at->format('d/m/Y H:i') : 'En cours'], ';');
            fputcsv($file, [''], ';');

            // Résumé financier
            fputcsv($file, ['RÉSUMÉ FINANCIER'], ';');
            fputcsv($file, ['Fond de caisse', number_format($session->opening_amount, 2, ',', ' ') . ' €'], ';');
            fputcsv($file, ['Total ventes', number_format($session->total_sales, 2, ',', ' ') . ' €'], ';');
            fputcsv($file, ['Ventes espèces', number_format($session->total_cash, 2, ',', ' ') . ' €'], ';');
            fputcsv($file, ['Ventes carte', number_format($session->total_card, 2, ',', ' ') . ' €'], ';');
            fputcsv($file, ['Ventes mobile', number_format($session->total_mobile, 2, ',', ' ') . ' €'], ';');
            fputcsv($file, ['Attendu en caisse', number_format($session->expected_amount, 2, ',', ' ') . ' €'], ';');
            if ($session->closing_amount !== null) {
                fputcsv($file, ['Montant compté', number_format($session->closing_amount, 2, ',', ' ') . ' €'], ';');
                fputcsv($file, ['Différence', number_format($session->difference, 2, ',', ' ') . ' €'], ';');
            }
            fputcsv($file, ['Nombre de tickets', $session->sales_count], ';');
            fputcsv($file, [''], ';');

            // Liste des ventes
            fputcsv($file, ['DÉTAIL DES VENTES'], ';');
            fputcsv($file, ['N° Ticket', 'Heure', 'Mode paiement', 'Articles', 'Total'], ';');

            foreach ($sales as $sale) {
                fputcsv($file, [
                    $sale->invoice_number,
                    $sale->created_at->format('H:i'),
                    ucfirst($sale->payment_method),
                    $sale->items->sum('quantity'),
                    number_format($sale->total, 2, ',', ' ') . ' €',
                ], ';');
            }

            fputcsv($file, [''], ';');

            // Détail par vente avec produits
            fputcsv($file, ['DÉTAIL PAR TICKET'], ';');
            foreach ($sales as $sale) {
                fputcsv($file, ['Ticket ' . $sale->invoice_number . ' - ' . $sale->created_at->format('H:i')], ';');
                fputcsv($file, ['Produit', 'Qté', 'Prix unit.', 'Total'], ';');
                foreach ($sale->items as $item) {
                    fputcsv($file, [
                        $item->product->name ?? 'Produit supprimé',
                        $item->quantity,
                        number_format($item->unit_price, 2, ',', ' ') . ' €',
                        number_format($item->total_price, 2, ',', ' ') . ' €',
                    ], ';');
                }
                fputcsv($file, ['', '', 'Total:', number_format($sale->total, 2, ',', ' ') . ' €'], ';');
                fputcsv($file, [''], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Historique des sessions de caisse
     */
    public function getSessionHistory(Request $request)
    {
        $companyId = $this->resolveCompanyId($request);

        if (!$companyId) {
            return response()->json(['error' => 'Company ID required'], 400);
        }

        $sessions = CashSession::with('user')
            ->where('company_id', $companyId)
            ->orderByDesc('opened_at')
            ->limit(30)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'user' => $session->user->name ?? 'N/A',
                    'opened_at' => $session->opened_at->format('d/m/Y H:i'),
                    'closed_at' => $session->closed_at ? $session->closed_at->format('d/m/Y H:i') : null,
                    'total_sales' => floatval($session->total_sales),
                    'sales_count' => $session->sales_count,
                    'difference' => floatval($session->difference ?? 0),
                    'status' => $session->closed_at ? 'closed' : 'open',
                ];
            });

        return response()->json($sessions);
    }
}

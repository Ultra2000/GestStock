<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\BankTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Carbon\Carbon;

class AccountingReportController extends Controller
{
    /**
     * Bilan comptable simplifié
     */
    public function financialReport(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->query('company_id') ?? Filament::getTenant()?->id;
        
        if (!$companyId) {
            abort(400, 'Company ID required');
        }

        $company = Company::findOrFail($companyId);
        
        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        
        // Ventes de la période
        $salesData = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                COUNT(*) as count,
                SUM(total) as total_ttc,
                SUM(COALESCE(total_ht, total)) as total_ht,
                SUM(COALESCE(total_vat, 0)) as total_tva
            ')
            ->first();
        
        // Achats de la période
        $purchasesData = Purchase::where('company_id', $companyId)
            ->whereIn('status', ['received', 'completed', 'paid'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                COUNT(*) as count,
                SUM(total) as total_ttc,
                SUM(COALESCE(total_ht, total)) as total_ht,
                SUM(COALESCE(total_vat, 0)) as total_tva
            ')
            ->first();
        
        // Ventes par mois
        $salesByMonth = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                strftime(\'%Y\', created_at) as year,
                strftime(\'%m\', created_at) as month,
                COUNT(*) as count,
                SUM(total) as total
            ')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        // Achats par mois
        $purchasesByMonth = Purchase::where('company_id', $companyId)
            ->whereIn('status', ['received', 'completed', 'paid'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                strftime(\'%Y\', created_at) as year,
                strftime(\'%m\', created_at) as month,
                COUNT(*) as count,
                SUM(total) as total
            ')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        // Ventes par mode de paiement
        $salesByPayment = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get();
        
        // Top 10 clients
        $topCustomers = Sale::where('sales.company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('sales.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('customer_id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->selectRaw('customers.name, COUNT(*) as orders_count, SUM(sales.total) as total_amount')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();
        
        // Top 10 fournisseurs
        $topSuppliers = Purchase::where('purchases.company_id', $companyId)
            ->whereIn('status', ['received', 'completed', 'paid'])
            ->whereBetween('purchases.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('supplier_id')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->selectRaw('suppliers.name, COUNT(*) as orders_count, SUM(purchases.total) as total_amount')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();
        
        // Valeur du stock
        $stockValue = Product::where('company_id', $companyId)
            ->selectRaw('
                SUM(stock * COALESCE(purchase_price, 0)) as value_achat,
                SUM(stock * COALESCE(price, 0)) as value_vente
            ')
            ->first();
        
        // Calcul résultat
        $revenue = floatval($salesData->total_ht ?? 0);
        $expenses = floatval($purchasesData->total_ht ?? 0);
        $grossProfit = $revenue - $expenses;
        $tvaCollected = floatval($salesData->total_tva ?? 0);
        $tvaDeductible = floatval($purchasesData->total_tva ?? 0);
        $tvaToPay = $tvaCollected - $tvaDeductible;
        
        $data = [
            'company' => $company,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sales' => [
                'count' => $salesData->count ?? 0,
                'total_ttc' => floatval($salesData->total_ttc ?? 0),
                'total_ht' => floatval($salesData->total_ht ?? 0),
                'total_tva' => floatval($salesData->total_tva ?? 0),
            ],
            'purchases' => [
                'count' => $purchasesData->count ?? 0,
                'total_ttc' => floatval($purchasesData->total_ttc ?? 0),
                'total_ht' => floatval($purchasesData->total_ht ?? 0),
                'total_tva' => floatval($purchasesData->total_tva ?? 0),
            ],
            'salesByMonth' => $salesByMonth,
            'purchasesByMonth' => $purchasesByMonth,
            'salesByPayment' => $salesByPayment,
            'topCustomers' => $topCustomers,
            'topSuppliers' => $topSuppliers,
            'stockValue' => [
                'achat' => floatval($stockValue->value_achat ?? 0),
                'vente' => floatval($stockValue->value_vente ?? 0),
            ],
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'gross_profit' => $grossProfit,
                'margin_percent' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0,
                'tva_collected' => $tvaCollected,
                'tva_deductible' => $tvaDeductible,
                'tva_to_pay' => $tvaToPay,
            ],
            'generatedAt' => now(),
        ];
        
        $pdf = Pdf::loadView('reports.financial-report', $data)->setPaper('a4');

        $filename = 'bilan-comptable-' . $startDate . '-' . $endDate . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Prévisualisation du bilan comptable
     */
    public function financialReportPreview(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->query('company_id') ?? Filament::getTenant()?->id;
        
        if (!$companyId) {
            abort(400, 'Company ID required');
        }

        $company = Company::findOrFail($companyId);
        
        $startDate = $request->query('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        
        // Même logique que financialReport...
        $salesData = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                COUNT(*) as count,
                SUM(total) as total_ttc,
                SUM(COALESCE(total_ht, total)) as total_ht,
                SUM(COALESCE(total_vat, 0)) as total_tva
            ')
            ->first();
        
        $purchasesData = Purchase::where('company_id', $companyId)
            ->whereIn('status', ['received', 'completed', 'paid'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                COUNT(*) as count,
                SUM(total) as total_ttc,
                SUM(COALESCE(total_ht, total)) as total_ht,
                SUM(COALESCE(total_vat, 0)) as total_tva
            ')
            ->first();
        
        $salesByMonth = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('strftime(\'%Y\', created_at) as year, strftime(\'%m\', created_at) as month, COUNT(*) as count, SUM(total) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();
        
        $purchasesByMonth = Purchase::where('company_id', $companyId)
            ->whereIn('status', ['received', 'completed', 'paid'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('strftime(\'%Y\', created_at) as year, strftime(\'%m\', created_at) as month, COUNT(*) as count, SUM(total) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')->orderBy('month')
            ->get();
        
        $salesByPayment = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get();
        
        $topCustomers = Sale::where('sales.company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('sales.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('customer_id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id')
            ->selectRaw('customers.name, COUNT(*) as orders_count, SUM(sales.total) as total_amount')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();
        
        $topSuppliers = Purchase::where('purchases.company_id', $companyId)
            ->whereIn('status', ['received', 'completed', 'paid'])
            ->whereBetween('purchases.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('supplier_id')
            ->join('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->selectRaw('suppliers.name, COUNT(*) as orders_count, SUM(purchases.total) as total_amount')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();
        
        $stockValue = Product::where('company_id', $companyId)
            ->selectRaw('SUM(stock * COALESCE(purchase_price, 0)) as value_achat, SUM(stock * COALESCE(price, 0)) as value_vente')
            ->first();
        
        $revenue = floatval($salesData->total_ht ?? 0);
        $expenses = floatval($purchasesData->total_ht ?? 0);
        $grossProfit = $revenue - $expenses;
        $tvaCollected = floatval($salesData->total_tva ?? 0);
        $tvaDeductible = floatval($purchasesData->total_tva ?? 0);
        $tvaToPay = $tvaCollected - $tvaDeductible;
        
        return view('reports.financial-report', [
            'company' => $company,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sales' => [
                'count' => $salesData->count ?? 0,
                'total_ttc' => floatval($salesData->total_ttc ?? 0),
                'total_ht' => floatval($salesData->total_ht ?? 0),
                'total_tva' => floatval($salesData->total_tva ?? 0),
            ],
            'purchases' => [
                'count' => $purchasesData->count ?? 0,
                'total_ttc' => floatval($purchasesData->total_ttc ?? 0),
                'total_ht' => floatval($purchasesData->total_ht ?? 0),
                'total_tva' => floatval($purchasesData->total_tva ?? 0),
            ],
            'salesByMonth' => $salesByMonth,
            'purchasesByMonth' => $purchasesByMonth,
            'salesByPayment' => $salesByPayment,
            'topCustomers' => $topCustomers,
            'topSuppliers' => $topSuppliers,
            'stockValue' => [
                'achat' => floatval($stockValue->value_achat ?? 0),
                'vente' => floatval($stockValue->value_vente ?? 0),
            ],
            'summary' => [
                'revenue' => $revenue,
                'expenses' => $expenses,
                'gross_profit' => $grossProfit,
                'margin_percent' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0,
                'tva_collected' => $tvaCollected,
                'tva_deductible' => $tvaDeductible,
                'tva_to_pay' => $tvaToPay,
            ],
            'generatedAt' => now(),
            'previewMode' => true,
        ]);
    }

    /**
     * Journal des ventes
     */
    public function salesJournal(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->query('company_id') ?? Filament::getTenant()?->id;
        
        if (!$companyId) {
            abort(400, 'Company ID required');
        }

        $company = Company::findOrFail($companyId);
        
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        
        $sales = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['customer', 'items.product'])
            ->orderBy('created_at')
            ->get();
        
        $totals = [
            'count' => $sales->count(),
            'total_ht' => $sales->sum(fn($s) => $s->total_ht ?? $s->total),
            'total_tva' => $sales->sum('total_vat'),
            'total_ttc' => $sales->sum('total'),
        ];
        
        $pdf = Pdf::loadView('reports.sales-journal', [
            'company' => $company,
            'sales' => $sales,
            'totals' => $totals,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'journal-ventes-' . $startDate . '-' . $endDate . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Journal des achats
     */
    public function purchasesJournal(Request $request, $companyId = null)
    {
        $companyId = $companyId ?? $request->query('company_id') ?? Filament::getTenant()?->id;
        
        if (!$companyId) {
            abort(400, 'Company ID required');
        }

        $company = Company::findOrFail($companyId);
        
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        
        $purchases = Purchase::where('company_id', $companyId)
            ->whereIn('status', ['received', 'completed', 'paid'])
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['supplier', 'items.product'])
            ->orderBy('created_at')
            ->get();
        
        $totals = [
            'count' => $purchases->count(),
            'total_ht' => $purchases->sum(fn($p) => $p->total_ht ?? $p->total),
            'total_tva' => $purchases->sum('total_vat'),
            'total_ttc' => $purchases->sum('total'),
        ];
        
        $pdf = Pdf::loadView('reports.purchases-journal', [
            'company' => $company,
            'purchases' => $purchases,
            'totals' => $totals,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = 'journal-achats-' . $startDate . '-' . $endDate . '.pdf';

        return $pdf->download($filename);
    }
}

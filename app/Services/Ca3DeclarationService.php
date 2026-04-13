<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Sale;
use App\Models\VatDeclaration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Ca3DeclarationService
{
    /**
     * Calcule les données CA3 pour une période donnée.
     * Retourne un tableau structuré avec toutes les cases.
     */
    public function calculate(int $companyId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        // --- TVA collectée (ventes validées) ---
        $sales = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->with('items')
            ->get();

        $collected = [];
        foreach ($sales as $sale) {
            foreach ($sale->getVatBreakdown() as $line) {
                $rate = (float) $line['rate'];
                if (!isset($collected[$rate])) {
                    $collected[$rate] = ['base' => 0, 'amount' => 0];
                }
                $collected[$rate]['base']   += $line['base'];
                $collected[$rate]['amount'] += $line['amount'];
            }
        }

        // --- TVA déductible (achats validés) ---
        $purchases = Purchase::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->with('items')
            ->get();

        $deductible = 0;
        foreach ($purchases as $purchase) {
            foreach ($purchase->getVatBreakdown() as $line) {
                $deductible += $line['amount'];
            }
        }

        // --- Construction des cases CA3 ---
        $base20    = round($collected[20.0]['base']   ?? 0, 2);
        $vat20     = round($collected[20.0]['amount'] ?? 0, 2);
        $base10    = round($collected[10.0]['base']   ?? 0, 2);
        $vat10     = round($collected[10.0]['amount'] ?? 0, 2);
        $base55    = round($collected[5.5]['base']    ?? 0, 2);
        $vat55     = round($collected[5.5]['amount']  ?? 0, 2);
        $base21    = round($collected[2.1]['base']    ?? 0, 2);
        $vat21     = round($collected[2.1]['amount']  ?? 0, 2);

        // Autres taux (0%, taux exotiques)
        $standardRates = [20.0, 10.0, 5.5, 2.1];
        $baseOther = 0;
        $vatOther  = 0;
        foreach ($collected as $rate => $data) {
            if (!in_array((float) $rate, $standardRates)) {
                $baseOther += $data['base'];
                $vatOther  += $data['amount'];
            }
        }
        $baseOther = round($baseOther, 2);
        $vatOther  = round($vatOther, 2);

        $totalCollected  = round($vat20 + $vat10 + $vat55 + $vat21 + $vatOther, 2);
        $totalDeductible = round($deductible, 2);
        $net             = round($totalCollected - $totalDeductible, 2);

        return [
            'base_20'               => $base20,
            'vat_20'                => $vat20,
            'base_10'               => $base10,
            'vat_10'                => $vat10,
            'base_55'               => $base55,
            'vat_55'                => $vat55,
            'base_21'               => $base21,
            'vat_21'                => $vat21,
            'base_other'            => $baseOther,
            'vat_other'             => $vatOther,
            'total_vat_collected'   => $totalCollected,
            'vat_deductible_goods'  => $totalDeductible,
            'vat_deductible_assets' => 0,
            'total_vat_deductible'  => $totalDeductible,
            'vat_due'               => $net > 0 ? $net : 0,
            'vat_credit'            => $net < 0 ? abs($net) : 0,
        ];
    }

    /**
     * Sauvegarde une déclaration CA3 en base de données.
     */
    public function save(
        int $companyId,
        string $startDate,
        string $endDate,
        string $periodLabel,
        array $data,
        ?string $notes = null
    ): VatDeclaration {
        return VatDeclaration::create([
            'company_id'   => $companyId,
            'created_by'   => Auth::id(),
            'period_start' => $startDate,
            'period_end'   => $endDate,
            'period_label' => $periodLabel,
            'notes'        => $notes,
            ...$data,
        ]);
    }

    /**
     * Génère le libellé de période pour l'affichage.
     */
    public function periodLabel(string $startDate, string $endDate, string $period): string
    {
        $start = Carbon::parse($startDate);
        return match ($period) {
            'month'   => ucfirst($start->translatedFormat('F Y')),
            'quarter' => 'T' . $start->quarter . ' ' . $start->year,
            'year'    => 'Année ' . $start->year,
            default   => $start->format('d/m/Y') . ' – ' . Carbon::parse($endDate)->format('d/m/Y'),
        };
    }
}

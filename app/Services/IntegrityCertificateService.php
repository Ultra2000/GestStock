<?php

namespace App\Services;

use App\Filament\Pages\JournalAudit;
use App\Models\AccountingEntry;
use App\Models\AccountingSetting;
use App\Models\Company;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

/**
 * Service de génération du Certificat d'Intégrité Comptable
 * 
 * Ce certificat atteste qu'à une date donnée, le système comptable
 * a été vérifié et jugé conforme par l'auto-audit.
 */
class IntegrityCertificateService
{
    protected int $companyId;
    protected ?Company $company;
    protected array $auditData;
    protected array $healthScore;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->company = Company::find($companyId);
    }

    /**
     * Génère le certificat PDF
     */
    public function generate(): \Barryvdh\DomPDF\PDF
    {
        // Récupérer les données d'audit fraîches (pas depuis le cache)
        $auditPage = new JournalAudit();
        
        // Forcer le rafraîchissement du cache pour avoir des données à jour
        JournalAudit::clearAuditCache($this->companyId);
        
        $this->auditData = $this->getAuditDataDirect();
        $this->healthScore = $this->calculateHealthScore();

        // Préparer les données du certificat
        $certificateData = $this->prepareCertificateData();

        // Générer le PDF
        $pdf = Pdf::loadView('pdf.integrity-certificate', $certificateData);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }

    /**
     * Récupère les données d'audit directement (sans cache)
     */
    protected function getAuditDataDirect(): array
    {
        return [
            'sales_integrity' => $this->auditSalesIntegrity(),
            'purchases_integrity' => $this->auditPurchasesIntegrity(),
            'sequence_audit' => $this->auditSequences(),
            'vat_coherence' => $this->auditVatCoherence(),
            'timestamp' => now(),
        ];
    }

    /**
     * Calcule le score de santé
     */
    protected function calculateHealthScore(): array
    {
        $score = 0;
        $details = [];

        if ($this->auditData['sales_integrity']['is_valid']) {
            $score += 30;
            $details['sales'] = ['score' => 30, 'max' => 30, 'valid' => true];
        } else {
            $details['sales'] = ['score' => 0, 'max' => 30, 'valid' => false];
        }

        if ($this->auditData['purchases_integrity']['is_valid']) {
            $score += 20;
            $details['purchases'] = ['score' => 20, 'max' => 20, 'valid' => true];
        } else {
            $details['purchases'] = ['score' => 0, 'max' => 20, 'valid' => false];
        }

        if ($this->auditData['sequence_audit']['is_valid']) {
            $score += 25;
            $details['sequences'] = ['score' => 25, 'max' => 25, 'valid' => true];
        } else {
            $details['sequences'] = ['score' => 0, 'max' => 25, 'valid' => false];
        }

        if ($this->auditData['vat_coherence']['is_valid']) {
            $score += 25;
            $details['vat'] = ['score' => 25, 'max' => 25, 'valid' => true];
        } else {
            $details['vat'] = ['score' => 0, 'max' => 25, 'valid' => false];
        }

        return [
            'score' => $score,
            'max' => 100,
            'is_perfect' => $score === 100,
            'details' => $details,
        ];
    }

    /**
     * Prépare toutes les données pour le certificat
     */
    protected function prepareCertificateData(): array
    {
        $settings = AccountingSetting::where('company_id', $this->companyId)->first();
        
        // Statistiques clés
        $stats = $this->getKeyStatistics();
        
        // Générer le hash d'intégrité
        $dataToHash = [
            'company_id' => $this->companyId,
            'timestamp' => now()->toISOString(),
            'score' => $this->healthScore['score'],
            'total_entries' => $stats['total_entries'],
            'last_fec_sequence' => $stats['last_fec_sequence'],
            'total_sales_ht' => $this->auditData['sales_integrity']['metier_ht'],
            'total_vat' => $this->auditData['vat_coherence']['theoretical_vat'],
        ];
        
        $integrityHash = hash('sha256', json_encode($dataToHash));
        
        // Numéro unique du certificat
        $certificateNumber = 'CERT-' . $this->companyId . '-' . now()->format('YmdHis');

        return [
            'certificate_number' => $certificateNumber,
            'generated_at' => now(),
            'company' => $this->company,
            'settings' => $settings,
            'health_score' => $this->healthScore,
            'audit_data' => $this->auditData,
            'stats' => $stats,
            'integrity_hash' => $integrityHash,
            'hash_algorithm' => 'SHA-256',
            'period' => [
                'start' => now()->startOfYear()->format('d/m/Y'),
                'end' => now()->format('d/m/Y'),
            ],
        ];
    }

    /**
     * Récupère les statistiques clés
     */
    protected function getKeyStatistics(): array
    {
        $entries = AccountingEntry::where('company_id', $this->companyId);
        
        return [
            'total_entries' => $entries->count(),
            'last_fec_sequence' => $entries->max('fec_sequence') ?? 0,
            'first_entry_date' => $entries->min('entry_date'),
            'last_entry_date' => $entries->max('entry_date'),
            'total_debit' => $entries->sum('debit'),
            'total_credit' => $entries->sum('credit'),
            'journals_used' => $entries->distinct('journal_code')->count('journal_code'),
            'accounts_used' => $entries->distinct('account_number')->count('account_number'),
        ];
    }

    /**
     * Audit intégrité des ventes
     */
    protected function auditSalesIntegrity(): array
    {
        $salesData = Sale::where('company_id', $this->companyId)
            ->whereNotNull('invoice_number')
            ->selectRaw('SUM(total) as total_ttc, SUM(total_vat) as total_vat, COUNT(*) as count')
            ->first();

        $salesTotalTTC = $salesData->total_ttc ?? 0;
        $salesTotalVAT = $salesData->total_vat ?? 0;
        $salesTotalHT = $salesTotalTTC - $salesTotalVAT;

        $accountingCA = AccountingEntry::where('company_id', $this->companyId)
            ->where('account_number', 'like', '7%')
            ->where('source_type', Sale::class)
            ->sum('credit');

        $difference = round($salesTotalHT - $accountingCA, 2);

        return [
            'metier_ht' => $salesTotalHT,
            'comptable_ht' => $accountingCA,
            'difference' => $difference,
            'is_valid' => abs($difference) < 0.01,
            'count' => $salesData->count ?? 0,
        ];
    }

    /**
     * Audit intégrité des achats
     */
    protected function auditPurchasesIntegrity(): array
    {
        $purchasesData = DB::table('purchases')
            ->where('company_id', $this->companyId)
            ->whereNotNull('reference')
            ->selectRaw('SUM(total) as total_ttc, SUM(total_vat) as total_vat, COUNT(*) as count')
            ->first();

        $purchasesTTC = $purchasesData->total_ttc ?? 0;
        $purchasesVAT = $purchasesData->total_vat ?? 0;
        $purchasesHT = $purchasesTTC - $purchasesVAT;

        $accountingCharges = AccountingEntry::where('company_id', $this->companyId)
            ->where('account_number', 'like', '6%')
            ->where('source_type', 'App\\Models\\Purchase')
            ->sum('debit');

        $difference = round($purchasesHT - $accountingCharges, 2);

        return [
            'metier_ht' => $purchasesHT,
            'comptable_ht' => $accountingCharges,
            'difference' => $difference,
            'is_valid' => abs($difference) < 0.01,
            'count' => $purchasesData->count ?? 0,
        ];
    }

    /**
     * Audit des séquences
     */
    protected function auditSequences(): array
    {
        // Vérifier séquence FEC
        $sequences = AccountingEntry::where('company_id', $this->companyId)
            ->orderBy('fec_sequence')
            ->pluck('fec_sequence')
            ->toArray();

        $fecGaps = [];
        if (!empty($sequences)) {
            $expected = 1;
            foreach ($sequences as $seq) {
                if ($seq != $expected) {
                    for ($i = $expected; $i < $seq; $i++) {
                        $fecGaps[] = $i;
                    }
                }
                $expected = $seq + 1;
            }
        }

        // Vérifier séquence factures
        $invoices = Sale::where('company_id', $this->companyId)
            ->whereNotNull('invoice_number')
            ->orderBy('invoice_number')
            ->pluck('invoice_number')
            ->toArray();

        $invoiceGaps = [];
        $invoicesByYear = [];
        foreach ($invoices as $inv) {
            if (preg_match('/FAC-(\d{4})-(\d+)/', $inv, $matches)) {
                $year = $matches[1];
                $num = (int) $matches[2];
                $invoicesByYear[$year][] = $num;
            }
        }

        foreach ($invoicesByYear as $year => $numbers) {
            sort($numbers);
            $expected = 1;
            foreach ($numbers as $num) {
                if ($num != $expected) {
                    for ($i = $expected; $i < $num; $i++) {
                        $invoiceGaps[] = "FAC-{$year}-" . str_pad($i, 5, '0', STR_PAD_LEFT);
                    }
                }
                $expected = $num + 1;
            }
        }

        $isValid = empty($fecGaps) && empty($invoiceGaps);

        return [
            'fec_gaps_count' => count($fecGaps),
            'invoice_gaps_count' => count($invoiceGaps),
            'is_valid' => $isValid,
            'total_entries' => count($sequences),
            'total_invoices' => count($invoices),
        ];
    }

    /**
     * Audit cohérence TVA
     */
    protected function auditVatCoherence(): array
    {
        $settings = AccountingSetting::where('company_id', $this->companyId)->first();
        $isEncaissements = ($settings->vat_regime ?? 'debits') === 'encaissements';

        $theoreticalVat = Sale::where('company_id', $this->companyId)
            ->whereNotNull('invoice_number')
            ->sum('total_vat');

        $accountedVat = AccountingEntry::where('company_id', $this->companyId)
            ->where('account_number', 'like', '4457%')
            ->where('account_number', 'not like', '44574%')
            ->sum('credit');

        $pendingVat = 0;
        if ($isEncaissements) {
            $pendingVat = AccountingEntry::where('company_id', $this->companyId)
                ->where('account_number', 'like', '44574%')
                ->selectRaw('SUM(credit) - SUM(debit) as solde')
                ->value('solde') ?? 0;
        }

        $totalAccounted = $accountedVat + $pendingVat;
        $difference = round($theoreticalVat - $totalAccounted, 2);

        return [
            'regime' => $isEncaissements ? 'encaissements' : 'debits',
            'theoretical_vat' => $theoreticalVat,
            'accounted_vat' => $accountedVat,
            'pending_vat' => $pendingVat,
            'difference' => $difference,
            'is_valid' => abs($difference) < 0.01,
        ];
    }
}

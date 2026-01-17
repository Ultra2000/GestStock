<?php

namespace App\Filament\Pages;

use App\Models\AccountingEntry;
use App\Models\AccountingSetting;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\IntegrityCertificateService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Filament\Facades\Filament;

/**
 * Journal d'Audit - Tableau de Concordance
 * 
 * Vérifie que la comptabilité est fidèle à la réalité métier :
 * - Intégrité Ventes/Achats (Métier vs Comptable)
 * - Continuité des séquences (FEC, factures)
 * - Cohérence de la TVA (théorique vs comptabilisée)
 */
class JournalAudit extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.journal-audit';

    protected static ?string $navigationLabel = 'Journal d\'Audit';

    protected static ?string $title = 'Journal d\'Audit - Santé du Système';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 9;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? true;
    }

    /**
     * Durée du cache en secondes (5 minutes)
     */
    protected const CACHE_TTL = 300;

    /**
     * Récupère toutes les données d'audit avec cache
     */
    public function getAuditData(): array
    {
        $companyId = filament()->getTenant()?->id;
        $cacheKey = "audit_data_{$companyId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            return [
                'sales_integrity' => $this->auditSalesIntegrity($companyId),
                'purchases_integrity' => $this->auditPurchasesIntegrity($companyId),
                'sequence_audit' => $this->auditSequences($companyId),
                'vat_coherence' => $this->auditVatCoherence($companyId),
                'anomalies' => $this->detectAnomalies($companyId),
                'lettering_stats' => $this->getLetteringStats($companyId),
                'last_audit' => now()->format('d/m/Y H:i:s'),
            ];
        });
    }

    /**
     * PILIER 1A : Audit d'intégrité des Ventes
     * Compare : ∑Sales(TTC) - ∑VAT = ∑Écritures Classe 7
     */
    protected function auditSalesIntegrity(int $companyId): array
    {
        // Source A (Métier) : Total des ventes validées
        $salesData = Sale::where('company_id', $companyId)
            ->whereNotNull('invoice_number') // Factures validées uniquement
            ->selectRaw('SUM(total) as total_ttc, SUM(total_vat) as total_vat, COUNT(*) as count')
            ->first();

        $salesTotalTTC = $salesData->total_ttc ?? 0;
        $salesTotalVAT = $salesData->total_vat ?? 0;
        $salesTotalHT = $salesTotalTTC - $salesTotalVAT;
        $salesCount = $salesData->count ?? 0;

        // Source B (Comptable) : Total des écritures classe 7 (Produits)
        $accountingCA = AccountingEntry::where('company_id', $companyId)
            ->where('account_number', 'like', '7%')
            ->where('source_type', Sale::class)
            ->sum('credit');

        // Calcul de l'écart
        $difference = round($salesTotalHT - $accountingCA, 2);
        $isValid = abs($difference) < 0.01;

        return [
            'metier_ttc' => $salesTotalTTC,
            'metier_vat' => $salesTotalVAT,
            'metier_ht' => $salesTotalHT,
            'comptable_ht' => $accountingCA,
            'difference' => $difference,
            'is_valid' => $isValid,
            'count' => $salesCount,
            'status' => $isValid ? 'success' : 'danger',
            'message' => $isValid 
                ? "✅ Concordance parfaite ({$salesCount} factures)" 
                : "⚠️ Écart de " . number_format(abs($difference), 2, ',', ' ') . " €",
        ];
    }

    /**
     * PILIER 1B : Audit d'intégrité des Achats
     */
    protected function auditPurchasesIntegrity(int $companyId): array
    {
        // Source A (Métier)
        $purchasesData = Purchase::where('company_id', $companyId)
            ->whereNotNull('reference')
            ->selectRaw('SUM(total) as total_ttc, SUM(total_vat) as total_vat, COUNT(*) as count')
            ->first();

        $purchasesTTC = $purchasesData->total_ttc ?? 0;
        $purchasesVAT = $purchasesData->total_vat ?? 0;
        $purchasesHT = $purchasesTTC - $purchasesVAT;
        $purchasesCount = $purchasesData->count ?? 0;

        // Source B (Comptable) : Total des écritures classe 6 (Charges)
        $accountingCharges = AccountingEntry::where('company_id', $companyId)
            ->where('account_number', 'like', '6%')
            ->where('source_type', Purchase::class)
            ->sum('debit');

        $difference = round($purchasesHT - $accountingCharges, 2);
        $isValid = abs($difference) < 0.01;

        return [
            'metier_ttc' => $purchasesTTC,
            'metier_vat' => $purchasesVAT,
            'metier_ht' => $purchasesHT,
            'comptable_ht' => $accountingCharges,
            'difference' => $difference,
            'is_valid' => $isValid,
            'count' => $purchasesCount,
            'status' => $isValid ? 'success' : 'danger',
            'message' => $isValid 
                ? "✅ Concordance parfaite ({$purchasesCount} achats)" 
                : "⚠️ Écart de " . number_format(abs($difference), 2, ',', ' ') . " €",
        ];
    }

    /**
     * PILIER 2 : Audit de Séquence (Gap Detection)
     * Vérifie la continuité des séquences FEC et numéros de factures
     */
    protected function auditSequences(int $companyId): array
    {
        $results = [
            'fec_sequence' => $this->checkFecSequence($companyId),
            'invoice_sequence' => $this->checkInvoiceSequence($companyId),
        ];

        $allValid = $results['fec_sequence']['is_valid'] && $results['invoice_sequence']['is_valid'];

        return [
            'details' => $results,
            'is_valid' => $allValid,
            'status' => $allValid ? 'success' : 'danger',
            'message' => $allValid 
                ? "✅ Séquences continues" 
                : "⚠️ Ruptures détectées",
        ];
    }

    /**
     * Vérifie la continuité de la séquence FEC
     */
    protected function checkFecSequence(int $companyId): array
    {
        $sequences = AccountingEntry::where('company_id', $companyId)
            ->orderBy('fec_sequence')
            ->pluck('fec_sequence')
            ->toArray();

        if (empty($sequences)) {
            return [
                'is_valid' => true,
                'gaps' => [],
                'total' => 0,
                'message' => 'Aucune écriture',
            ];
        }

        $gaps = [];
        $expected = 1;
        
        foreach ($sequences as $seq) {
            if ($seq != $expected) {
                // Trou détecté
                for ($i = $expected; $i < $seq; $i++) {
                    $gaps[] = $i;
                }
            }
            $expected = $seq + 1;
        }

        $isValid = empty($gaps);
        $lastSeq = end($sequences);

        return [
            'is_valid' => $isValid,
            'gaps' => array_slice($gaps, 0, 10), // Max 10 gaps affichés
            'gaps_count' => count($gaps),
            'total' => count($sequences),
            'last_sequence' => $lastSeq,
            'message' => $isValid 
                ? "Séquence 1 à {$lastSeq} continue" 
                : count($gaps) . " trou(s) détecté(s)",
        ];
    }

    /**
     * Vérifie la continuité des numéros de factures
     */
    protected function checkInvoiceSequence(int $companyId): array
    {
        // Extraire les numéros de factures (format: FAC-2026-00001)
        $invoices = Sale::where('company_id', $companyId)
            ->whereNotNull('invoice_number')
            ->orderBy('invoice_number')
            ->pluck('invoice_number')
            ->toArray();

        if (empty($invoices)) {
            return [
                'is_valid' => true,
                'gaps' => [],
                'total' => 0,
                'message' => 'Aucune facture',
            ];
        }

        // Grouper par année et vérifier la continuité
        $invoicesByYear = [];
        foreach ($invoices as $inv) {
            if (preg_match('/FAC-(\d{4})-(\d+)/', $inv, $matches)) {
                $year = $matches[1];
                $num = (int) $matches[2];
                $invoicesByYear[$year][] = $num;
            }
        }

        $gaps = [];
        foreach ($invoicesByYear as $year => $numbers) {
            sort($numbers);
            $expected = 1;
            foreach ($numbers as $num) {
                if ($num != $expected) {
                    for ($i = $expected; $i < $num; $i++) {
                        $gaps[] = "FAC-{$year}-" . str_pad($i, 5, '0', STR_PAD_LEFT);
                    }
                }
                $expected = $num + 1;
            }
        }

        $isValid = empty($gaps);

        return [
            'is_valid' => $isValid,
            'gaps' => array_slice($gaps, 0, 10),
            'gaps_count' => count($gaps),
            'total' => count($invoices),
            'message' => $isValid 
                ? count($invoices) . " factures en séquence continue" 
                : count($gaps) . " numéro(s) manquant(s)",
        ];
    }

    /**
     * PILIER 3 : Cohérence de la TVA
     * Compare TVA théorique vs TVA comptabilisée
     */
    protected function auditVatCoherence(int $companyId): array
    {
        $settings = AccountingSetting::where('company_id', $companyId)->first();
        $isEncaissements = ($settings->vat_regime ?? 'debits') === 'encaissements';

        // TVA collectée théorique (depuis les ventes)
        $theoreticalVatCollected = Sale::where('company_id', $companyId)
            ->whereNotNull('invoice_number')
            ->sum('total_vat');

        // TVA collectée comptabilisée (comptes 4457xx)
        $accountedVatCollected = AccountingEntry::where('company_id', $companyId)
            ->where('account_number', 'like', '4457%')
            ->where('account_number', 'not like', '44574%') // Exclure TVA en attente
            ->sum('credit');

        // TVA en attente (régime encaissements)
        $pendingVat = 0;
        $expectedPendingVat = 0;
        
        if ($isEncaissements) {
            // TVA en attente comptabilisée (44574x)
            $pendingVat = AccountingEntry::where('company_id', $companyId)
                ->where('account_number', 'like', '44574%')
                ->selectRaw('SUM(credit) - SUM(debit) as solde')
                ->value('solde') ?? 0;

            // TVA théorique en attente = TVA des factures non totalement payées
            $expectedPendingVat = Sale::where('company_id', $companyId)
                ->whereNotNull('invoice_number')
                ->where(function ($q) {
                    $q->whereNull('payment_status')
                      ->orWhere('payment_status', '!=', 'paid');
                })
                ->selectRaw('SUM(total_vat) - COALESCE(SUM(amount_paid * total_vat / total), 0) as pending_vat')
                ->value('pending_vat') ?? 0;
        }

        // En régime débits : toute la TVA doit être collectée
        // En régime encaissements : TVA collectée + TVA en attente = TVA théorique
        $totalAccountedVat = $accountedVatCollected + $pendingVat;
        $difference = round($theoreticalVatCollected - $totalAccountedVat, 2);
        $isValid = abs($difference) < 0.01;

        // Vérification TVA en attente (régime encaissements)
        $pendingDifference = round($expectedPendingVat - $pendingVat, 2);
        $isPendingValid = !$isEncaissements || abs($pendingDifference) < 0.01;

        return [
            'regime' => $isEncaissements ? 'encaissements' : 'debits',
            'regime_label' => $isEncaissements ? 'TVA sur Encaissements' : 'TVA sur Débits',
            'theoretical_vat' => $theoreticalVatCollected,
            'accounted_vat' => $accountedVatCollected,
            'pending_vat' => $pendingVat,
            'expected_pending_vat' => $expectedPendingVat,
            'total_accounted' => $totalAccountedVat,
            'difference' => $difference,
            'pending_difference' => $pendingDifference,
            'is_valid' => $isValid && $isPendingValid,
            'status' => ($isValid && $isPendingValid) ? 'success' : 'danger',
            'message' => ($isValid && $isPendingValid)
                ? "✅ TVA cohérente ({$settings->vat_regime})"
                : "⚠️ Écart TVA de " . number_format(abs($difference), 2, ',', ' ') . " €",
        ];
    }

    /**
     * Détecte les anomalies spécifiques
     */
    protected function detectAnomalies(int $companyId): array
    {
        $anomalies = [];

        // 1. Ventes sans écritures comptables
        $salesWithoutEntries = Sale::where('company_id', $companyId)
            ->whereNotNull('invoice_number')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('accounting_entries')
                    ->whereColumn('accounting_entries.source_id', 'sales.id')
                    ->where('accounting_entries.source_type', Sale::class);
            })
            ->limit(5)
            ->get(['id', 'invoice_number', 'total', 'created_at']);

        foreach ($salesWithoutEntries as $sale) {
            $anomalies[] = [
                'type' => 'danger',
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => "Vente sans écriture",
                'description' => "Facture {$sale->invoice_number} ({$sale->total} €) n'a pas d'écriture comptable",
                'date' => $sale->created_at->format('d/m/Y'),
                'action' => "Régénérer les écritures",
            ];
        }

        // 2. Achats sans écritures comptables
        $purchasesWithoutEntries = Purchase::where('company_id', $companyId)
            ->whereNotNull('reference')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('accounting_entries')
                    ->whereColumn('accounting_entries.source_id', 'purchases.id')
                    ->where('accounting_entries.source_type', Purchase::class);
            })
            ->limit(5)
            ->get(['id', 'reference', 'total', 'created_at']);

        foreach ($purchasesWithoutEntries as $purchase) {
            $anomalies[] = [
                'type' => 'danger',
                'icon' => 'heroicon-o-exclamation-triangle',
                'title' => "Achat sans écriture",
                'description' => "Achat {$purchase->reference} ({$purchase->total} €) n'a pas d'écriture comptable",
                'date' => $purchase->created_at->format('d/m/Y'),
                'action' => "Régénérer les écritures",
            ];
        }

        // 3. Écritures déséquilibrées par pièce
        $unbalancedPieces = AccountingEntry::where('company_id', $companyId)
            ->select('piece_number')
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('piece_number')
            ->havingRaw('ABS(SUM(debit) - SUM(credit)) > 0.01')
            ->limit(5)
            ->get();

        foreach ($unbalancedPieces as $piece) {
            $diff = abs($piece->total_debit - $piece->total_credit);
            $anomalies[] = [
                'type' => 'warning',
                'icon' => 'heroicon-o-scale',
                'title' => "Pièce déséquilibrée",
                'description' => "Pièce {$piece->piece_number} : écart de " . number_format($diff, 2, ',', ' ') . " €",
                'date' => '-',
                'action' => "Vérifier l'écriture",
            ];
        }

        // 4. Paiements non lettrés (avertissement)
        $unletteredPayments = Payment::where('company_id', $companyId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('accounting_entries')
                    ->whereColumn('accounting_entries.source_id', 'payments.id')
                    ->where('accounting_entries.source_type', Payment::class)
                    ->whereNotNull('accounting_entries.lettering');
            })
            ->count();

        if ($unletteredPayments > 0) {
            $anomalies[] = [
                'type' => 'info',
                'icon' => 'heroicon-o-link',
                'title' => "Paiements non lettrés",
                'description' => "{$unletteredPayments} paiement(s) en attente de lettrage",
                'date' => '-',
                'action' => "Lettrer les paiements",
            ];
        }

        return $anomalies;
    }

    /**
     * Statistiques de lettrage
     */
    protected function getLetteringStats(int $companyId): array
    {
        $totalEntries = AccountingEntry::where('company_id', $companyId)
            ->whereIn(DB::raw('SUBSTR(account_number, 1, 3)'), ['411', '401']) // Clients et Fournisseurs
            ->count();

        $letteredEntries = AccountingEntry::where('company_id', $companyId)
            ->whereIn(DB::raw('SUBSTR(account_number, 1, 3)'), ['411', '401'])
            ->whereNotNull('lettering')
            ->count();

        $percentage = $totalEntries > 0 ? round(($letteredEntries / $totalEntries) * 100, 1) : 100;

        return [
            'total' => $totalEntries,
            'lettered' => $letteredEntries,
            'unlettered' => $totalEntries - $letteredEntries,
            'percentage' => $percentage,
            'status' => $percentage >= 90 ? 'success' : ($percentage >= 70 ? 'warning' : 'danger'),
        ];
    }

    /**
     * Score global de santé du système (0-100)
     */
    public function getHealthScore(): array
    {
        $audit = $this->getAuditData();
        
        $score = 0;
        $maxScore = 100;
        $details = [];

        // Intégrité Ventes (30 points)
        if ($audit['sales_integrity']['is_valid']) {
            $score += 30;
            $details['sales'] = ['score' => 30, 'max' => 30, 'label' => 'Ventes'];
        } else {
            $details['sales'] = ['score' => 0, 'max' => 30, 'label' => 'Ventes'];
        }

        // Intégrité Achats (20 points)
        if ($audit['purchases_integrity']['is_valid']) {
            $score += 20;
            $details['purchases'] = ['score' => 20, 'max' => 20, 'label' => 'Achats'];
        } else {
            $details['purchases'] = ['score' => 0, 'max' => 20, 'label' => 'Achats'];
        }

        // Séquences (25 points)
        if ($audit['sequence_audit']['is_valid']) {
            $score += 25;
            $details['sequences'] = ['score' => 25, 'max' => 25, 'label' => 'Séquences'];
        } else {
            $details['sequences'] = ['score' => 0, 'max' => 25, 'label' => 'Séquences'];
        }

        // TVA (25 points)
        if ($audit['vat_coherence']['is_valid']) {
            $score += 25;
            $details['vat'] = ['score' => 25, 'max' => 25, 'label' => 'TVA'];
        } else {
            $details['vat'] = ['score' => 0, 'max' => 25, 'label' => 'TVA'];
        }

        // Déterminer le statut
        $status = 'success';
        $label = 'Excellent';
        
        if ($score < 100) {
            $status = 'warning';
            $label = 'Attention requise';
        }
        if ($score < 75) {
            $status = 'danger';
            $label = 'Anomalies critiques';
        }

        return [
            'score' => $score,
            'max' => $maxScore,
            'percentage' => round(($score / $maxScore) * 100),
            'status' => $status,
            'label' => $label,
            'details' => $details,
        ];
    }

    /**
     * Rafraîchit le cache d'audit
     */
    public function refreshAudit(): void
    {
        $companyId = filament()->getTenant()?->id;
        Cache::forget("audit_data_{$companyId}");
        
        // Notifier le rafraîchissement
        $this->dispatch('audit-refreshed');
    }

    /**
     * Génère et télécharge le Certificat d'Intégrité PDF
     */
    public function downloadCertificate(): StreamedResponse
    {
        $companyId = filament()->getTenant()?->id;
        
        $service = new IntegrityCertificateService($companyId);
        $pdf = $service->generate();
        
        $filename = 'certificat-integrite-' . now()->format('Y-m-d-His') . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Invalide le cache d'audit (appelé depuis les modèles)
     */
    public static function clearAuditCache(int $companyId): void
    {
        Cache::forget("audit_data_{$companyId}");
    }
}

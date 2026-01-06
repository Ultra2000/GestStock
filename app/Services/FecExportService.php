<?php

namespace App\Services;

use App\Models\AccountingSetting;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FecExportService
{
    protected AccountingSetting $settings;
    protected int $companyId;
    protected \App\Models\Company $company;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->company = \App\Models\Company::findOrFail($companyId);
        $this->settings = AccountingSetting::getForCompany($companyId);
    }

    /**
     * Générer le fichier FEC pour une période donnée
     * 
     * Format FEC : 18 colonnes séparées par des pipes (|)
     * 1. JournalCode - Code du journal
     * 2. JournalLib - Libellé du journal
     * 3. EcritureNum - Numéro d'écriture
     * 4. EcritureDate - Date d'écriture (yyyyMMdd)
     * 5. CompteNum - Numéro de compte
     * 6. CompteLib - Libellé du compte
     * 7. CompAuxNum - Numéro de compte auxiliaire (client/fournisseur)
     * 8. CompAuxLib - Libellé du compte auxiliaire
     * 9. PieceRef - Référence de la pièce
     * 10. PieceDate - Date de la pièce (yyyyMMdd)
     * 11. EcritureLib - Libellé de l'écriture
     * 12. Debit - Montant au débit
     * 13. Credit - Montant au crédit
     * 14. EcritureLet - Lettrage (optionnel)
     * 15. DateLet - Date de lettrage (optionnel)
     * 16. ValidDate - Date de validation (yyyyMMdd)
     * 17. Montantdevise - Montant en devise (optionnel)
     * 18. Idevise - Code devise (optionnel)
     */
    public function generate(string $startDate, string $endDate): string
    {
        $entries = collect();

        // Récupérer les ventes de la période
        $sales = Sale::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->with(['customer', 'items'])
            ->orderBy('created_at')
            ->get();

        foreach ($sales as $sale) {
            $entries = $entries->merge($this->generateSaleEntries($sale));
        }

        // Récupérer les achats de la période
        $purchases = Purchase::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed'])
            ->with(['supplier', 'items'])
            ->orderBy('created_at')
            ->get();

        foreach ($purchases as $purchase) {
            $entries = $entries->merge($this->generatePurchaseEntries($purchase));
        }

        // Trier par date puis par numéro d'écriture
        $entries = $entries->sortBy([
            ['EcritureDate', 'asc'],
            ['EcritureNum', 'asc'],
        ]);

        return $this->formatAsFec($entries);
    }

    /**
     * Générer les écritures comptables pour une vente
     */
    protected function generateSaleEntries(Sale $sale): Collection
    {
        $entries = collect();
        $date = $sale->created_at->format('Ymd');
        $ecritureNum = $sale->invoice_number;
        $pieceRef = $sale->invoice_number;
        
        $customerAccount = $this->settings->account_customers;
        $customerAuxNum = $sale->customer ? str_pad($sale->customer->id, 6, '0', STR_PAD_LEFT) : '';
        $customerAuxLib = $sale->customer?->name ?? 'Client inconnu';

        // 1. Débit client (montant TTC)
        $entries->push([
            'JournalCode' => $this->settings->journal_sales,
            'JournalLib' => 'Ventes',
            'EcritureNum' => $ecritureNum,
            'EcritureDate' => $date,
            'CompteNum' => $customerAccount,
            'CompteLib' => 'Clients',
            'CompAuxNum' => $customerAuxNum,
            'CompAuxLib' => $customerAuxLib,
            'PieceRef' => $pieceRef,
            'PieceDate' => $date,
            'EcritureLib' => "Vente {$sale->invoice_number}",
            'Debit' => number_format($sale->total, 2, '.', ''),
            'Credit' => '0.00',
            'EcritureLet' => '',
            'DateLet' => '',
            'ValidDate' => $date,
            'Montantdevise' => '',
            'Idevise' => '',
        ]);

        // 2. Crédit ventes (montant HT)
        $entries->push([
            'JournalCode' => $this->settings->journal_sales,
            'JournalLib' => 'Ventes',
            'EcritureNum' => $ecritureNum,
            'EcritureDate' => $date,
            'CompteNum' => $this->settings->account_sales,
            'CompteLib' => 'Ventes de marchandises',
            'CompAuxNum' => '',
            'CompAuxLib' => '',
            'PieceRef' => $pieceRef,
            'PieceDate' => $date,
            'EcritureLib' => "Vente {$sale->invoice_number}",
            'Debit' => '0.00',
            'Credit' => number_format($sale->total_ht, 2, '.', ''),
            'EcritureLet' => '',
            'DateLet' => '',
            'ValidDate' => $date,
            'Montantdevise' => '',
            'Idevise' => '',
        ]);

        // 3. Crédit TVA collectée (montant TVA)
        if ($sale->total_vat > 0) {
            $entries->push([
                'JournalCode' => $this->settings->journal_sales,
                'JournalLib' => 'Ventes',
                'EcritureNum' => $ecritureNum,
                'EcritureDate' => $date,
                'CompteNum' => $this->settings->account_vat_collected,
                'CompteLib' => 'TVA collectée',
                'CompAuxNum' => '',
                'CompAuxLib' => '',
                'PieceRef' => $pieceRef,
                'PieceDate' => $date,
                'EcritureLib' => "TVA vente {$sale->invoice_number}",
                'Debit' => '0.00',
                'Credit' => number_format($sale->total_vat, 2, '.', ''),
                'EcritureLet' => '',
                'DateLet' => '',
                'ValidDate' => $date,
                'Montantdevise' => '',
                'Idevise' => '',
            ]);
        }

        // 4. Si paiement immédiat, crédit du compte de trésorerie
        if ($sale->payment_method && in_array($sale->status, ['paid', 'completed'])) {
            $treasuryAccount = $this->getTreasuryAccount($sale->payment_method);
            $treasuryLib = $this->getTreasuryLabel($sale->payment_method);
            
            $entries->push([
                'JournalCode' => $treasuryAccount === $this->settings->account_cash 
                    ? $this->settings->journal_cash 
                    : $this->settings->journal_bank,
                'JournalLib' => $treasuryAccount === $this->settings->account_cash ? 'Caisse' : 'Banque',
                'EcritureNum' => $ecritureNum . '-PAY',
                'EcritureDate' => $date,
                'CompteNum' => $treasuryAccount,
                'CompteLib' => $treasuryLib,
                'CompAuxNum' => '',
                'CompAuxLib' => '',
                'PieceRef' => $pieceRef,
                'PieceDate' => $date,
                'EcritureLib' => "Règlement vente {$sale->invoice_number}",
                'Debit' => number_format($sale->total, 2, '.', ''),
                'Credit' => '0.00',
                'EcritureLet' => '',
                'DateLet' => '',
                'ValidDate' => $date,
                'Montantdevise' => '',
                'Idevise' => '',
            ]);

            // Contrepartie débit client (lettrage)
            $entries->push([
                'JournalCode' => $treasuryAccount === $this->settings->account_cash 
                    ? $this->settings->journal_cash 
                    : $this->settings->journal_bank,
                'JournalLib' => $treasuryAccount === $this->settings->account_cash ? 'Caisse' : 'Banque',
                'EcritureNum' => $ecritureNum . '-PAY',
                'EcritureDate' => $date,
                'CompteNum' => $customerAccount,
                'CompteLib' => 'Clients',
                'CompAuxNum' => $customerAuxNum,
                'CompAuxLib' => $customerAuxLib,
                'PieceRef' => $pieceRef,
                'PieceDate' => $date,
                'EcritureLib' => "Règlement vente {$sale->invoice_number}",
                'Debit' => '0.00',
                'Credit' => number_format($sale->total, 2, '.', ''),
                'EcritureLet' => '',
                'DateLet' => '',
                'ValidDate' => $date,
                'Montantdevise' => '',
                'Idevise' => '',
            ]);
        }

        return $entries;
    }

    /**
     * Générer les écritures comptables pour un achat
     */
    protected function generatePurchaseEntries(Purchase $purchase): Collection
    {
        $entries = collect();
        $date = $purchase->created_at->format('Ymd');
        $ecritureNum = $purchase->invoice_number;
        $pieceRef = $purchase->invoice_number;
        
        $supplierAccount = $this->settings->account_suppliers;
        $supplierAuxNum = $purchase->supplier ? str_pad($purchase->supplier->id, 6, '0', STR_PAD_LEFT) : '';
        $supplierAuxLib = $purchase->supplier?->name ?? 'Fournisseur inconnu';

        // 1. Débit achats (montant HT)
        $entries->push([
            'JournalCode' => $this->settings->journal_purchases,
            'JournalLib' => 'Achats',
            'EcritureNum' => $ecritureNum,
            'EcritureDate' => $date,
            'CompteNum' => $this->settings->account_purchases,
            'CompteLib' => 'Achats de marchandises',
            'CompAuxNum' => '',
            'CompAuxLib' => '',
            'PieceRef' => $pieceRef,
            'PieceDate' => $date,
            'EcritureLib' => "Achat {$purchase->invoice_number}",
            'Debit' => number_format($purchase->total_ht, 2, '.', ''),
            'Credit' => '0.00',
            'EcritureLet' => '',
            'DateLet' => '',
            'ValidDate' => $date,
            'Montantdevise' => '',
            'Idevise' => '',
        ]);

        // 2. Débit TVA déductible (montant TVA)
        if ($purchase->total_vat > 0) {
            $entries->push([
                'JournalCode' => $this->settings->journal_purchases,
                'JournalLib' => 'Achats',
                'EcritureNum' => $ecritureNum,
                'EcritureDate' => $date,
                'CompteNum' => $this->settings->account_vat_deductible,
                'CompteLib' => 'TVA déductible',
                'CompAuxNum' => '',
                'CompAuxLib' => '',
                'PieceRef' => $pieceRef,
                'PieceDate' => $date,
                'EcritureLib' => "TVA achat {$purchase->invoice_number}",
                'Debit' => number_format($purchase->total_vat, 2, '.', ''),
                'Credit' => '0.00',
                'EcritureLet' => '',
                'DateLet' => '',
                'ValidDate' => $date,
                'Montantdevise' => '',
                'Idevise' => '',
            ]);
        }

        // 3. Crédit fournisseur (montant TTC)
        $entries->push([
            'JournalCode' => $this->settings->journal_purchases,
            'JournalLib' => 'Achats',
            'EcritureNum' => $ecritureNum,
            'EcritureDate' => $date,
            'CompteNum' => $supplierAccount,
            'CompteLib' => 'Fournisseurs',
            'CompAuxNum' => $supplierAuxNum,
            'CompAuxLib' => $supplierAuxLib,
            'PieceRef' => $pieceRef,
            'PieceDate' => $date,
            'EcritureLib' => "Achat {$purchase->invoice_number}",
            'Debit' => '0.00',
            'Credit' => number_format($purchase->total, 2, '.', ''),
            'EcritureLet' => '',
            'DateLet' => '',
            'ValidDate' => $date,
            'Montantdevise' => '',
            'Idevise' => '',
        ]);

        // 4. Si paiement immédiat, débit du compte de trésorerie
        if ($purchase->payment_method && $purchase->status === 'completed') {
            $treasuryAccount = $this->getTreasuryAccount($purchase->payment_method);
            $treasuryLib = $this->getTreasuryLabel($purchase->payment_method);
            
            $entries->push([
                'JournalCode' => $treasuryAccount === $this->settings->account_cash 
                    ? $this->settings->journal_cash 
                    : $this->settings->journal_bank,
                'JournalLib' => $treasuryAccount === $this->settings->account_cash ? 'Caisse' : 'Banque',
                'EcritureNum' => $ecritureNum . '-PAY',
                'EcritureDate' => $date,
                'CompteNum' => $supplierAccount,
                'CompteLib' => 'Fournisseurs',
                'CompAuxNum' => $supplierAuxNum,
                'CompAuxLib' => $supplierAuxLib,
                'PieceRef' => $pieceRef,
                'PieceDate' => $date,
                'EcritureLib' => "Règlement achat {$purchase->invoice_number}",
                'Debit' => number_format($purchase->total, 2, '.', ''),
                'Credit' => '0.00',
                'EcritureLet' => '',
                'DateLet' => '',
                'ValidDate' => $date,
                'Montantdevise' => '',
                'Idevise' => '',
            ]);

            // Contrepartie crédit fournisseur (lettrage)
            $entries->push([
                'JournalCode' => $treasuryAccount === $this->settings->account_cash 
                    ? $this->settings->journal_cash 
                    : $this->settings->journal_bank,
                'JournalLib' => $treasuryAccount === $this->settings->account_cash ? 'Caisse' : 'Banque',
                'EcritureNum' => $ecritureNum . '-PAY',
                'EcritureDate' => $date,
                'CompteNum' => $treasuryAccount,
                'CompteLib' => $treasuryLib,
                'CompAuxNum' => '',
                'CompAuxLib' => '',
                'PieceRef' => $pieceRef,
                'PieceDate' => $date,
                'EcritureLib' => "Règlement achat {$purchase->invoice_number}",
                'Debit' => '0.00',
                'Credit' => number_format($purchase->total, 2, '.', ''),
                'EcritureLet' => '',
                'DateLet' => '',
                'ValidDate' => $date,
                'Montantdevise' => '',
                'Idevise' => '',
            ]);
        }

        return $entries;
    }

    /**
     * Déterminer le compte de trésorerie selon le mode de paiement
     */
    protected function getTreasuryAccount(string $paymentMethod): string
    {
        return match($paymentMethod) {
            'cash' => $this->settings->account_cash,
            'card', 'transfer', 'check', 'sepa_debit', 'paypal' => $this->settings->account_bank,
            default => $this->settings->account_bank,
        };
    }

    /**
     * Obtenir le libellé du compte de trésorerie
     */
    protected function getTreasuryLabel(string $paymentMethod): string
    {
        return match($paymentMethod) {
            'cash' => 'Caisse',
            'card' => 'Banque - Carte bancaire',
            'transfer' => 'Banque - Virement',
            'check' => 'Banque - Chèque',
            'sepa_debit' => 'Banque - Prélèvement SEPA',
            'paypal' => 'Banque - PayPal',
            default => 'Banque',
        };
    }

    /**
     * Formater les écritures au format FEC
     */
    protected function formatAsFec(Collection $entries): string
    {
        // En-tête du fichier FEC
        $header = implode('|', [
            'JournalCode',
            'JournalLib',
            'EcritureNum',
            'EcritureDate',
            'CompteNum',
            'CompteLib',
            'CompAuxNum',
            'CompAuxLib',
            'PieceRef',
            'PieceDate',
            'EcritureLib',
            'Debit',
            'Credit',
            'EcritureLet',
            'DateLet',
            'ValidDate',
            'Montantdevise',
            'Idevise',
        ]);

        $lines = [$header];

        foreach ($entries as $entry) {
            $lines[] = implode('|', [
                $entry['JournalCode'],
                $entry['JournalLib'],
                $entry['EcritureNum'],
                $entry['EcritureDate'],
                $entry['CompteNum'],
                $entry['CompteLib'],
                $entry['CompAuxNum'],
                $entry['CompAuxLib'],
                $entry['PieceRef'],
                $entry['PieceDate'],
                $entry['EcritureLib'],
                $entry['Debit'],
                $entry['Credit'],
                $entry['EcritureLet'],
                $entry['DateLet'],
                $entry['ValidDate'],
                $entry['Montantdevise'],
                $entry['Idevise'],
            ]);
        }

        return implode("\n", $lines);
    }

    /**
     * Générer le nom de fichier FEC selon la norme
     * Format: SirenFECYYYYMMDD (où YYYYMMDD est la date de clôture)
     */
    public function getFileName(string $endDate): string
    {
        // Extraire le SIREN (9 premiers chiffres du SIRET)
        $siren = $this->getSiren();
        $date = date('Ymd', strtotime($endDate));
        return "{$siren}FEC{$date}.txt";
    }

    /**
     * Récupérer le SIREN depuis le SIRET de l'entreprise
     */
    protected function getSiren(): string
    {
        if ($this->company->siret && strlen($this->company->siret) >= 9) {
            // SIREN = 9 premiers chiffres du SIRET (14 chiffres)
            return substr(preg_replace('/[^0-9]/', '', $this->company->siret), 0, 9);
        }
        return $this->settings->fec_siren ?? '000000000';
    }

    /**
     * Récupérer la raison sociale de l'entreprise
     */
    protected function getCompanyName(): string
    {
        return $this->settings->fec_company_name ?? $this->company->name ?? 'Société';
    }
}

<?php

namespace App\Services;

use App\Models\AccountingCategory;
use App\Models\AccountingEntry;
use App\Models\AccountingSetting;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingEntryService
{
    /**
     * Génère les écritures comptables pour une vente validée
     * 
     * Schéma d'écriture pour une vente :
     * - DÉBIT 411xxx (Client) : Montant TTC
     * - CRÉDIT 707xxx (Ventes) : Montant HT (par taux TVA)
     * - CRÉDIT 445xxx (TVA collectée) : Montant TVA (par taux)
     */
    public function createEntriesForSale(Sale $sale): array
    {
        if ($sale->status !== 'completed') {
            throw new \Exception("Impossible de générer les écritures : la vente n'est pas validée.");
        }

        // Vérifier si les écritures existent déjà
        $existingEntries = AccountingEntry::where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->exists();

        if ($existingEntries) {
            Log::warning("Écritures déjà existantes pour la vente {$sale->invoice_number}");
            return [];
        }

        $settings = AccountingSetting::getForCompany($sale->company_id);
        $entries = [];

        DB::beginTransaction();

        try {
            $sale->load(['customer', 'items.product.accountingCategory']);
            
            // 1. Écriture DÉBIT Client (TTC)
            $customerAuxiliary = $this->getCustomerAuxiliary($sale->customer);
            
            $entries[] = AccountingEntry::create([
                'company_id' => $sale->company_id,
                'source_type' => Sale::class,
                'source_id' => $sale->id,
                'entry_date' => $sale->created_at->toDateString(),
                'piece_number' => $sale->invoice_number,
                'journal_code' => $settings->journal_sales ?? 'VTE',
                'account_number' => $settings->account_customers ?? '411000',
                'account_auxiliary' => $customerAuxiliary,
                'label' => $this->buildLabel($sale),
                'debit' => $sale->total,
                'credit' => 0,
                'created_by' => auth()->id(),
            ]);

            // 2. Regrouper les lignes par compte de vente et taux TVA
            $salesByAccount = $this->groupSaleItemsByAccount($sale->items, $settings);

            // 3. Écritures CRÉDIT Ventes (HT) par compte
            foreach ($salesByAccount as $accountKey => $data) {
                $entries[] = AccountingEntry::create([
                    'company_id' => $sale->company_id,
                    'source_type' => Sale::class,
                    'source_id' => $sale->id,
                    'entry_date' => $sale->created_at->toDateString(),
                    'piece_number' => $sale->invoice_number,
                    'journal_code' => $settings->journal_sales ?? 'VTE',
                    'account_number' => $data['account_sales'],
                    'label' => $this->buildLabel($sale) . " - HT",
                    'debit' => 0,
                    'credit' => $data['total_ht'],
                    'vat_rate' => $data['vat_rate'],
                    'vat_base' => $data['total_ht'],
                    'created_by' => auth()->id(),
                ]);
            }

            // 4. Écritures CRÉDIT TVA collectée (par taux)
            // SAUF si régime "encaissements" : la TVA sera comptabilisée au paiement
            $vatRegime = $settings->vat_regime ?? 'debits';
            
            if (!AccountingSetting::isVatFranchise($sale->company_id)) {
                $vatByRate = $this->groupVatByRate($sale->items, $settings);
                
                foreach ($vatByRate as $rate => $data) {
                    if ($data['total_vat'] > 0) {
                        if ($vatRegime === 'encaissements') {
                            // Régime encaissements : TVA en attente (44574x)
                            $entries[] = AccountingEntry::create([
                                'company_id' => $sale->company_id,
                                'source_type' => Sale::class,
                                'source_id' => $sale->id,
                                'entry_date' => $sale->created_at->toDateString(),
                                'piece_number' => $sale->invoice_number,
                                'journal_code' => $settings->journal_sales ?? 'VTE',
                                'account_number' => $this->getVatPendingAccount((float) $rate),
                                'label' => "TVA en attente {$rate}% - {$sale->invoice_number}",
                                'debit' => 0,
                                'credit' => $data['total_vat'],
                                'vat_rate' => $rate,
                                'vat_base' => $data['total_ht'],
                                'created_by' => auth()->id(),
                            ]);
                        } else {
                            // Régime débits : TVA collectée immédiatement (4457x)
                            $entries[] = AccountingEntry::create([
                                'company_id' => $sale->company_id,
                                'source_type' => Sale::class,
                                'source_id' => $sale->id,
                                'entry_date' => $sale->created_at->toDateString(),
                                'piece_number' => $sale->invoice_number,
                                'journal_code' => $settings->journal_sales ?? 'VTE',
                                'account_number' => $data['account_vat'],
                                'label' => "TVA collectée {$rate}% - {$sale->invoice_number}",
                                'debit' => 0,
                                'credit' => $data['total_vat'],
                                'vat_rate' => $rate,
                                'vat_base' => $data['total_ht'],
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }
            }

            // Vérifier l'équilibre
            $totalDebit = collect($entries)->sum('debit');
            $totalCredit = collect($entries)->sum('credit');
            
            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new \Exception(
                    "Déséquilibre comptable détecté : Débit={$totalDebit}, Crédit={$totalCredit}"
                );
            }

            DB::commit();
            
            Log::info("Écritures comptables créées pour vente {$sale->invoice_number}", [
                'entries_count' => count($entries),
                'total' => $sale->total,
            ]);

            return $entries;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur création écritures vente {$sale->invoice_number}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Génère les écritures comptables pour un achat validé
     */
    public function createEntriesForPurchase(Purchase $purchase): array
    {
        if ($purchase->status !== 'completed') {
            throw new \Exception("Impossible de générer les écritures : l'achat n'est pas terminé.");
        }

        $existingEntries = AccountingEntry::where('source_type', Purchase::class)
            ->where('source_id', $purchase->id)
            ->exists();

        if ($existingEntries) {
            return [];
        }

        $settings = AccountingSetting::getForCompany($purchase->company_id);
        $entries = [];

        DB::beginTransaction();

        try {
            $purchase->load(['supplier', 'items.product.accountingCategory']);
            
            // 1. Écriture CRÉDIT Fournisseur (TTC)
            $supplierAuxiliary = $this->getSupplierAuxiliary($purchase->supplier);
            
            $entries[] = AccountingEntry::create([
                'company_id' => $purchase->company_id,
                'source_type' => Purchase::class,
                'source_id' => $purchase->id,
                'entry_date' => $purchase->created_at->toDateString(),
                'piece_number' => $purchase->reference ?? 'ACH-' . $purchase->id,
                'journal_code' => $settings->journal_purchases ?? 'ACH',
                'account_number' => $settings->account_suppliers ?? '401000',
                'account_auxiliary' => $supplierAuxiliary,
                'label' => "Achat {$purchase->supplier?->name}",
                'debit' => 0,
                'credit' => $purchase->total,
                'created_by' => auth()->id(),
            ]);

            // 2. Écritures DÉBIT Achats (HT) par compte
            $purchasesByAccount = $this->groupPurchaseItemsByAccount($purchase->items, $settings);
            
            foreach ($purchasesByAccount as $data) {
                $entries[] = AccountingEntry::create([
                    'company_id' => $purchase->company_id,
                    'source_type' => Purchase::class,
                    'source_id' => $purchase->id,
                    'entry_date' => $purchase->created_at->toDateString(),
                    'piece_number' => $purchase->reference ?? 'ACH-' . $purchase->id,
                    'journal_code' => $settings->journal_purchases ?? 'ACH',
                    'account_number' => $data['account_purchases'],
                    'label' => "Achat {$purchase->supplier?->name} - HT",
                    'debit' => $data['total_ht'],
                    'credit' => 0,
                    'vat_rate' => $data['vat_rate'],
                    'vat_base' => $data['total_ht'],
                    'created_by' => auth()->id(),
                ]);
            }

            // 3. Écritures DÉBIT TVA déductible (par taux)
            if (!AccountingSetting::isVatFranchise($purchase->company_id)) {
                $vatByRate = $this->groupVatByRateForPurchase($purchase->items, $settings);
                
                foreach ($vatByRate as $rate => $data) {
                    if ($data['total_vat'] > 0) {
                        $entries[] = AccountingEntry::create([
                            'company_id' => $purchase->company_id,
                            'source_type' => Purchase::class,
                            'source_id' => $purchase->id,
                            'entry_date' => $purchase->created_at->toDateString(),
                            'piece_number' => $purchase->reference ?? 'ACH-' . $purchase->id,
                            'journal_code' => $settings->journal_purchases ?? 'ACH',
                            'account_number' => $data['account_vat'],
                            'label' => "TVA déductible {$rate}%",
                            'debit' => $data['total_vat'],
                            'credit' => 0,
                            'vat_rate' => $rate,
                            'vat_base' => $data['total_ht'],
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            DB::commit();
            return $entries;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur création écritures achat: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Contre-passer des écritures (pour avoir/annulation)
     */
    public function reverseEntries(Sale $originalSale, Sale $creditNote): array
    {
        $originalEntries = AccountingEntry::where('source_type', Sale::class)
            ->where('source_id', $originalSale->id)
            ->get();

        if ($originalEntries->isEmpty()) {
            throw new \Exception("Aucune écriture à contre-passer pour cette vente.");
        }

        $reversedEntries = [];

        DB::beginTransaction();

        try {
            foreach ($originalEntries as $entry) {
                $reversedEntries[] = AccountingEntry::create([
                    'company_id' => $entry->company_id,
                    'source_type' => Sale::class,
                    'source_id' => $creditNote->id,
                    'entry_date' => $creditNote->created_at->toDateString(),
                    'piece_number' => $creditNote->invoice_number,
                    'journal_code' => $entry->journal_code,
                    'account_number' => $entry->account_number,
                    'account_auxiliary' => $entry->account_auxiliary,
                    'label' => "Avoir {$creditNote->invoice_number} (annule {$entry->piece_number})",
                    'debit' => $entry->credit, // Inverse
                    'credit' => $entry->debit, // Inverse
                    'vat_rate' => $entry->vat_rate,
                    'vat_base' => $entry->vat_base,
                    'reversal_of_id' => $entry->id,
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();
            return $reversedEntries;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Logique Waterfall : Récupère le compte de vente pour un produit
     * Priorité : Produit > Catégorie > Défaut Global
     */
    public function getSalesAccountForProduct(?Product $product, AccountingSetting $settings): string
    {
        // 1. Compte spécifique du produit
        if ($product && $product->account_sales) {
            return $product->account_sales;
        }

        // 2. Compte de la catégorie comptable du produit
        if ($product && $product->accountingCategory) {
            $categoryAccount = $product->accountingCategory->getSalesAccount();
            if ($categoryAccount) {
                return $categoryAccount;
            }
        }

        // 3. Compte par défaut global
        return $settings->account_sales ?? '707000';
    }

    /**
     * Logique Waterfall : Récupère le compte d'achat pour un produit
     */
    public function getPurchasesAccountForProduct(?Product $product, AccountingSetting $settings): string
    {
        if ($product && $product->account_purchases) {
            return $product->account_purchases;
        }

        if ($product && $product->accountingCategory) {
            $categoryAccount = $product->accountingCategory->getPurchasesAccount();
            if ($categoryAccount) {
                return $categoryAccount;
            }
        }

        return $settings->account_purchases ?? '607000';
    }

    /**
     * Logique Waterfall : Récupère le compte TVA collectée
     */
    public function getVatCollectedAccount(?Product $product, AccountingSetting $settings, float $vatRate): string
    {
        if ($product && $product->account_vat_collected) {
            return $product->account_vat_collected;
        }

        if ($product && $product->accountingCategory) {
            $categoryAccount = $product->accountingCategory->getVatAccount();
            if ($categoryAccount) {
                return $categoryAccount;
            }
        }

        // Comptes TVA standards par taux
        $vatAccounts = [
            20.00 => '445710',
            10.00 => '445712',
            5.50 => '445711',
            2.10 => '445713',
        ];

        return $vatAccounts[$vatRate] ?? $settings->account_vat_collected ?? '445710';
    }

    /**
     * Logique Waterfall : Récupère le compte TVA déductible
     */
    public function getVatDeductibleAccount(?Product $product, AccountingSetting $settings, float $vatRate): string
    {
        if ($product && $product->account_vat_deductible) {
            return $product->account_vat_deductible;
        }

        if ($product && $product->accountingCategory) {
            $categoryAccount = $product->accountingCategory->getVatAccount();
            if ($categoryAccount) {
                // Transformer 4457xx en 4456xx pour TVA déductible
                return str_replace('4457', '4456', $categoryAccount);
            }
        }

        $vatAccounts = [
            20.00 => '445660',
            10.00 => '445662',
            5.50 => '445661',
            2.10 => '445663',
        ];

        return $vatAccounts[$vatRate] ?? $settings->account_vat_deductible ?? '445660';
    }

    /**
     * Génère le code auxiliaire client
     */
    protected function getCustomerAuxiliary(?Customer $customer): ?string
    {
        if (!$customer) {
            return null;
        }

        return 'CLI-' . str_pad($customer->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Génère le code auxiliaire fournisseur
     */
    protected function getSupplierAuxiliary(?Supplier $supplier): ?string
    {
        if (!$supplier) {
            return null;
        }

        return 'FRN-' . str_pad($supplier->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Construit le libellé de l'écriture
     */
    protected function buildLabel(Sale $sale): string
    {
        $label = "Vente {$sale->invoice_number}";
        
        if ($sale->customer) {
            $label .= " - {$sale->customer->name}";
        }

        return substr($label, 0, 255);
    }

    /**
     * Regroupe les lignes de vente par compte et taux TVA
     */
    protected function groupSaleItemsByAccount($items, AccountingSetting $settings): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $product = $item->product;
            $accountSales = $this->getSalesAccountForProduct($product, $settings);
            $vatRate = $item->vat_rate ?? $product?->vat_rate_sale ?? 20.00;
            
            $key = $accountSales . '_' . $vatRate;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'account_sales' => $accountSales,
                    'vat_rate' => $vatRate,
                    'total_ht' => 0,
                ];
            }

            $grouped[$key]['total_ht'] += $item->total_ht ?? ($item->quantity * $item->unit_price);
        }

        return $grouped;
    }

    /**
     * Regroupe la TVA par taux
     */
    protected function groupVatByRate($items, AccountingSetting $settings): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $product = $item->product;
            $vatRate = $item->vat_rate ?? $product?->vat_rate_sale ?? 20.00;
            $accountVat = $this->getVatCollectedAccount($product, $settings, $vatRate);
            
            $key = (string) $vatRate;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'account_vat' => $accountVat,
                    'total_ht' => 0,
                    'total_vat' => 0,
                ];
            }

            $itemHt = $item->total_ht ?? ($item->quantity * $item->unit_price);
            $itemVat = $item->vat_amount ?? ($itemHt * $vatRate / 100);
            
            $grouped[$key]['total_ht'] += $itemHt;
            $grouped[$key]['total_vat'] += $itemVat;
        }

        return $grouped;
    }

    /**
     * Regroupe les lignes d'achat par compte
     */
    protected function groupPurchaseItemsByAccount($items, AccountingSetting $settings): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $product = $item->product;
            $accountPurchases = $this->getPurchasesAccountForProduct($product, $settings);
            $vatRate = $item->vat_rate ?? $product?->vat_rate_purchase ?? 20.00;
            
            $key = $accountPurchases . '_' . $vatRate;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'account_purchases' => $accountPurchases,
                    'vat_rate' => $vatRate,
                    'total_ht' => 0,
                ];
            }

            $grouped[$key]['total_ht'] += $item->total_ht ?? ($item->quantity * $item->unit_price);
        }

        return $grouped;
    }

    /**
     * Regroupe la TVA par taux pour les achats
     */
    protected function groupVatByRateForPurchase($items, AccountingSetting $settings): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $product = $item->product;
            $vatRate = $item->vat_rate ?? $product?->vat_rate_purchase ?? 20.00;
            $accountVat = $this->getVatDeductibleAccount($product, $settings, $vatRate);
            
            $key = (string) $vatRate;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'account_vat' => $accountVat,
                    'total_ht' => 0,
                    'total_vat' => 0,
                ];
            }

            $itemHt = $item->total_ht ?? ($item->quantity * $item->unit_price);
            $itemVat = $item->vat_amount ?? ($itemHt * $vatRate / 100);
            
            $grouped[$key]['total_ht'] += $itemHt;
            $grouped[$key]['total_vat'] += $itemVat;
        }

        return $grouped;
    }

    /**
     * Valide un numéro de compte PCG
     */
    public static function validateAccountNumber(string $account): bool
    {
        // Doit être numérique, 6+ chiffres, ne pas commencer par 0
        return preg_match('/^[1-9][0-9]{5,}$/', $account) === 1;
    }

    /**
     * Valide la classe du compte (1=Capitaux, 2=Immo, etc.)
     */
    public static function validateAccountClass(string $account, array $allowedClasses): bool
    {
        if (!self::validateAccountNumber($account)) {
            return false;
        }

        $class = (int) substr($account, 0, 1);
        return in_array($class, $allowedClasses);
    }

    /**
     * Génère les écritures comptables pour un paiement (règlement)
     * 
     * Schéma pour paiement client :
     * - DÉBIT 512/530 (Banque/Caisse) : Montant payé
     * - CRÉDIT 411xxx (Client) : Montant payé
     * 
     * Schéma pour paiement fournisseur :
     * - DÉBIT 401xxx (Fournisseur) : Montant payé
     * - CRÉDIT 512/530 (Banque/Caisse) : Montant payé
     * 
     * + TVA si régime "encaissements" (prestataires de services)
     */
    public function createEntriesForPayment(Payment $payment): array
    {
        $settings = AccountingSetting::getForCompany($payment->company_id);
        $entries = [];
        $payable = $payment->payable;

        if (!$payable) {
            throw new \Exception("Document payé introuvable.");
        }

        DB::beginTransaction();

        try {
            $accountTreasury = $payment->getAccountForMethod();
            $journalCode = $payment->getJournalCode();
            $pieceNumber = $this->generatePaymentPieceNumber($payment, $settings);

            if ($payment->isCustomerPayment()) {
                // Paiement client (vente)
                $entries = $this->createCustomerPaymentEntries(
                    $payment, $payable, $settings, $accountTreasury, $journalCode, $pieceNumber
                );
            } else {
                // Paiement fournisseur (achat)
                $entries = $this->createSupplierPaymentEntries(
                    $payment, $payable, $settings, $accountTreasury, $journalCode, $pieceNumber
                );
            }

            // Attribuer le numéro FEC global
            foreach ($entries as $entry) {
                $entry->fec_sequence = $this->getNextFecSequence($payment->company_id);
                $entry->entry_type = 'payment';
                $entry->save();
            }

            // Lettrage automatique avec l'écriture de vente/achat
            $this->autoLetterPayment($payment, $entries);

            DB::commit();

            Log::info("Écritures de paiement créées", [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
            ]);

            return $entries;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erreur création écritures paiement: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Écritures pour un paiement client
     */
    protected function createCustomerPaymentEntries(
        Payment $payment,
        Sale $sale,
        AccountingSetting $settings,
        string $accountTreasury,
        string $journalCode,
        string $pieceNumber
    ): array {
        $entries = [];
        $customerAuxiliary = $this->getCustomerAuxiliary($sale->customer);
        $label = "Règlement {$sale->invoice_number}";

        if ($sale->customer) {
            $label .= " - {$sale->customer->name}";
        }

        // 1. DÉBIT Trésorerie (Banque/Caisse)
        $entries[] = AccountingEntry::create([
            'company_id' => $payment->company_id,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'entry_date' => $payment->payment_date->toDateString(),
            'piece_number' => $pieceNumber,
            'journal_code' => $journalCode,
            'account_number' => $accountTreasury,
            'label' => $label,
            'debit' => $payment->amount,
            'credit' => 0,
            'created_by' => auth()->id(),
        ]);

        // 2. CRÉDIT Client
        $entries[] = AccountingEntry::create([
            'company_id' => $payment->company_id,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'entry_date' => $payment->payment_date->toDateString(),
            'piece_number' => $pieceNumber,
            'journal_code' => $journalCode,
            'account_number' => $settings->account_customers ?? '411000',
            'account_auxiliary' => $customerAuxiliary,
            'label' => $label,
            'debit' => 0,
            'credit' => $payment->amount,
            'created_by' => auth()->id(),
        ]);

        // 3. TVA si régime "encaissements" et pas encore comptabilisée
        if ($this->isVatOnReceipts($settings) && !$this->isVatFranchise($payment->company_id)) {
            $vatEntries = $this->createVatEntriesOnPayment($payment, $sale, $settings, $journalCode, $pieceNumber);
            $entries = array_merge($entries, $vatEntries);
        }

        return $entries;
    }

    /**
     * Écritures pour un paiement fournisseur
     */
    protected function createSupplierPaymentEntries(
        Payment $payment,
        Purchase $purchase,
        AccountingSetting $settings,
        string $accountTreasury,
        string $journalCode,
        string $pieceNumber
    ): array {
        $entries = [];
        $supplierAuxiliary = $this->getSupplierAuxiliary($purchase->supplier);
        $label = "Règlement achat {$purchase->reference}";

        if ($purchase->supplier) {
            $label .= " - {$purchase->supplier->name}";
        }

        // 1. DÉBIT Fournisseur (on solde la dette)
        $entries[] = AccountingEntry::create([
            'company_id' => $payment->company_id,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'entry_date' => $payment->payment_date->toDateString(),
            'piece_number' => $pieceNumber,
            'journal_code' => $journalCode,
            'account_number' => $settings->account_suppliers ?? '401000',
            'account_auxiliary' => $supplierAuxiliary,
            'label' => $label,
            'debit' => $payment->amount,
            'credit' => 0,
            'created_by' => auth()->id(),
        ]);

        // 2. CRÉDIT Trésorerie (Banque/Caisse)
        $entries[] = AccountingEntry::create([
            'company_id' => $payment->company_id,
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'entry_date' => $payment->payment_date->toDateString(),
            'piece_number' => $pieceNumber,
            'journal_code' => $journalCode,
            'account_number' => $accountTreasury,
            'label' => $label,
            'debit' => 0,
            'credit' => $payment->amount,
            'created_by' => auth()->id(),
        ]);

        return $entries;
    }

    /**
     * Génère les écritures de TVA au moment du paiement (régime encaissements)
     */
    protected function createVatEntriesOnPayment(
        Payment $payment,
        Sale $sale,
        AccountingSetting $settings,
        string $journalCode,
        string $pieceNumber
    ): array {
        $entries = [];

        // Calculer la part de TVA correspondant au paiement
        // Ratio = montant payé / total TTC
        $ratio = $payment->amount / $sale->total;

        $sale->load('items.product');
        $vatByRate = $this->groupVatByRate($sale->items, $settings);

        foreach ($vatByRate as $rate => $data) {
            $vatAmount = round($data['total_vat'] * $ratio, 2);
            
            if ($vatAmount > 0) {
                // DÉBIT TVA en attente (44574x)
                $entries[] = AccountingEntry::create([
                    'company_id' => $payment->company_id,
                    'source_type' => Payment::class,
                    'source_id' => $payment->id,
                    'entry_date' => $payment->payment_date->toDateString(),
                    'piece_number' => $pieceNumber,
                    'journal_code' => $journalCode,
                    'account_number' => $this->getVatPendingAccount($rate),
                    'label' => "TVA encaissée {$rate}% - {$sale->invoice_number}",
                    'debit' => $vatAmount,
                    'credit' => 0,
                    'vat_rate' => $rate,
                    'created_by' => auth()->id(),
                ]);

                // CRÉDIT TVA collectée (4457x)
                $entries[] = AccountingEntry::create([
                    'company_id' => $payment->company_id,
                    'source_type' => Payment::class,
                    'source_id' => $payment->id,
                    'entry_date' => $payment->payment_date->toDateString(),
                    'piece_number' => $pieceNumber,
                    'journal_code' => $journalCode,
                    'account_number' => $data['account_vat'],
                    'label' => "TVA collectée {$rate}% - {$sale->invoice_number}",
                    'debit' => 0,
                    'credit' => $vatAmount,
                    'vat_rate' => $rate,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        return $entries;
    }

    /**
     * Vérifie si l'entreprise est en régime TVA sur les encaissements
     */
    public function isVatOnReceipts(AccountingSetting $settings): bool
    {
        return ($settings->vat_regime ?? 'debits') === 'encaissements';
    }

    /**
     * Vérifie si l'entreprise est en franchise de TVA
     */
    public function isVatFranchise(int $companyId): bool
    {
        return AccountingSetting::isVatFranchise($companyId);
    }

    /**
     * Compte TVA en attente (régime encaissements)
     */
    protected function getVatPendingAccount(float $vatRate): string
    {
        $accounts = [
            20.00 => '445740',
            10.00 => '445742',
            5.50 => '445741',
            2.10 => '445743',
        ];

        return $accounts[$vatRate] ?? '445740';
    }

    /**
     * Génère le numéro de pièce pour un paiement
     */
    protected function generatePaymentPieceNumber(Payment $payment, AccountingSetting $settings): string
    {
        $prefix = $payment->payment_method === 'cash' ? 'CAI' : 'BQ';
        $year = $payment->payment_date->format('Y');

        $lastNumber = AccountingEntry::where('company_id', $payment->company_id)
            ->where('piece_number', 'like', "{$prefix}-{$year}-%")
            ->selectRaw("MAX(CAST(SUBSTRING(piece_number, " . (strlen($prefix) + 7) . ") AS UNSIGNED)) as max_num")
            ->value('max_num') ?? 0;

        return "{$prefix}-{$year}-" . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Obtient le prochain numéro FEC global (sans trou)
     */
    public function getNextFecSequence(int $companyId): int
    {
        return AccountingEntry::where('company_id', $companyId)
            ->max('fec_sequence') + 1 ?? 1;
    }

    /**
     * Lettrage automatique entre paiement et document source
     */
    protected function autoLetterPayment(Payment $payment, array $paymentEntries): void
    {
        $payable = $payment->payable;
        
        if (!$payable) {
            return;
        }

        // Trouver l'écriture client/fournisseur du document original
        $sourceType = get_class($payable);
        $accountNumber = $payment->isCustomerPayment() ? '411' : '401';

        $originalEntry = AccountingEntry::where('source_type', $sourceType)
            ->where('source_id', $payable->id)
            ->where('account_number', 'like', "{$accountNumber}%")
            ->whereNull('lettering')
            ->first();

        if (!$originalEntry) {
            return;
        }

        // Trouver l'écriture client/fournisseur du paiement
        $paymentEntry = collect($paymentEntries)->first(function ($entry) use ($accountNumber) {
            return str_starts_with($entry->account_number, $accountNumber);
        });

        if (!$paymentEntry) {
            return;
        }

        // Générer un code de lettrage unique
        $letteringCode = $this->generateLetteringCode($payment->company_id);
        $letteringDate = $payment->payment_date;

        // Appliquer le lettrage
        $originalEntry->lettering = $letteringCode;
        $originalEntry->lettering_date = $letteringDate;
        $originalEntry->save();

        $paymentEntry->lettering = $letteringCode;
        $paymentEntry->lettering_date = $letteringDate;
        $paymentEntry->save();
    }

    /**
     * Génère un code de lettrage unique (AA, AB, AC... BA, BB...)
     */
    protected function generateLetteringCode(int $companyId): string
    {
        $lastCode = AccountingEntry::where('company_id', $companyId)
            ->whereNotNull('lettering')
            ->orderByDesc('lettering')
            ->value('lettering');

        if (!$lastCode) {
            return 'AA';
        }

        // Incrémenter le code (AA -> AB -> ... -> AZ -> BA -> ...)
        $lastCode = strtoupper($lastCode);
        $len = strlen($lastCode);
        
        for ($i = $len - 1; $i >= 0; $i--) {
            if ($lastCode[$i] !== 'Z') {
                $lastCode[$i] = chr(ord($lastCode[$i]) + 1);
                return $lastCode;
            }
            $lastCode[$i] = 'A';
        }

        return 'A' . $lastCode;
    }

    /**
     * Enregistre un paiement POS (cash immédiat)
     */
    public function registerPosPayment(Sale $sale): ?Payment
    {
        // Si la vente n'est pas complétée ou pas de méthode de paiement, ignorer
        if ($sale->status !== 'completed' || !$sale->payment_method) {
            return null;
        }

        $settings = AccountingSetting::getForCompany($sale->company_id);

        // Déterminer le compte selon le mode de paiement
        $accountNumber = match ($sale->payment_method) {
            'cash', 'especes' => $settings->account_cash ?? '530000',
            'check', 'cheque' => '511200',
            default => $settings->account_bank ?? '512000',
        };

        // Créer le paiement
        $payment = Payment::create([
            'company_id' => $sale->company_id,
            'payable_type' => Sale::class,
            'payable_id' => $sale->id,
            'amount' => $sale->total,
            'payment_method' => $sale->payment_method,
            'payment_date' => $sale->created_at->toDateString(),
            'account_number' => $accountNumber,
            'cash_session_id' => $sale->cash_session_id,
            'created_by' => auth()->id(),
        ]);

        // Mettre à jour le statut de paiement de la vente
        $sale->update([
            'payment_status' => 'paid',
            'amount_paid' => $sale->total,
            'paid_at' => now(),
        ]);

        return $payment;
    }
}

<?php // Shared invoice data calculations
    $currency = $company->currency ?? 'EUR';
    $status = $sale->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $discountPercent = $sale->discount_percent ?? 0;

    // Calculs TVA
    $totalHt = $sale->total_ht ?? $sale->items->sum('total_price_ht');
    $totalVat = $sale->total_vat ?? $sale->items->sum('vat_amount');
    $grandTotal = $sale->total ?? ($totalHt + $totalVat);

    // Ventilation TVA par taux (pour multi-taux)
    $vatBreakdown = $sale->getVatBreakdown();
    $hasMultipleVatRates = count($vatBreakdown) > 1;
    $totalAvantRemise = $sale->items->sum('total_price');
    $discountAmount = $totalAvantRemise * ($discountPercent / 100);

    // Montant en lettres
    $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
    $euros = floor($grandTotal);
    $centimes = round(($grandTotal - $euros) * 100);

    $currencyUnits = [
        'EUR' => ['euro', 'euros', 'centime', 'centimes'],
        'FCFA' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
        'XOF' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
        'XAF' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
        'USD' => ['dollar', 'dollars', 'cent', 'cents'],
        'GBP' => ['livre sterling', 'livres sterling', 'penny', 'pence'],
        'CHF' => ['franc suisse', 'francs suisses', 'centime', 'centimes'],
        'CAD' => ['dollar canadien', 'dollars canadiens', 'cent', 'cents'],
    ];
    $u = $currencyUnits[$currency] ?? ['unité', 'unités', 'centime', 'centimes'];

    $euroWord = $euros == 1 ? $u[0] : $u[1];
    $centimeWord = $centimes == 1 ? $u[2] : $u[3];

    $amountInWords = ucfirst($fmt->format($euros)) . ' ' . $euroWord;
    if ($centimes > 0) {
        $amountInWords .= ' et ' . $fmt->format($centimes) . ' ' . $centimeWord;
    }

    // Libellés
    $statusLabels = [
        'completed' => 'Payée',
        'pending' => 'En attente',
        'cancelled' => 'Annulée',
    ];
    $statusLabel = $statusLabels[$status] ?? ucfirst($status);
    $invoiceTypeLabel = $sale->type === 'credit_note' ? 'Avoir N°' : 'Facture N°';
    $docTitle = ($sale->type === 'credit_note' ? 'Avoir' : 'Facture') . ' ' . $sale->invoice_number;

    // === MENTIONS LÉGALES (Réforme Facture Électronique 2026) ===
    $accountingSettings = \App\Models\AccountingSetting::where('company_id', $company->id)->first();

    // 1. Émetteur - Identifiants légaux
    $formeJuridique = $company->forme_juridique ?? null;
    $capitalSocial = $company->capital_social ?? null;
    $codeNaf = $company->code_naf ?? null;
    $rcsNumber = $company->rcs_number ?? null;
    $rmNumber = $company->rm_number ?? null;

    // Ligne de forme juridique + capital (ex: "SAS au capital de 10 000 €")
    $legalFormLine = null;
    if ($formeJuridique) {
        $legalFormLine = $formeJuridique;
        if ($capitalSocial) {
            $legalFormLine .= ' au capital de ' . $capitalSocial;
        }
    }

    // 2. Régime TVA
    $isVatFranchise = $accountingSettings->is_vat_franchise ?? false;
    $isVatOnDebits = ($accountingSettings->vat_regime ?? 'debits') === 'debits' && !$isVatFranchise;

    // 3. Nature de l'opération & livraison
    $natureOp = $sale->nature_operation ?? null;
    $deliveryAddr = $sale->delivery_address ?? null;
    $deliveryDate = $sale->delivery_date ?? null;
    $natureOpLabels = ['goods' => 'Vente de biens', 'services' => 'Prestation de services', 'mixed' => 'Mixte'];

    // 4. Échéance & Conditions de paiement
    $dueDate = $sale->due_date ?? $sale->created_at->copy()->addDays(30);
    $paymentTerms = $accountingSettings->payment_terms ?? null;

    // 5. Pénalités & Recouvrement (B2B obligatoire)
    $penaltyRate = $accountingSettings->penalty_rate ?? null;
    $recoveryFee = $accountingSettings->recovery_fee ?? 40.00;

    // 6. Client B2B identifiants
    $customerTaxNumber = optional($sale->customer)->tax_number ?? null;
    $customerSiret = optional($sale->customer)->siret ?? null;
    $customerSiren = optional($sale->customer)->registration_number ?? null;

    // QR Code
    $qrBase64 = null;
    if (!empty($verificationUrl)) {
        try {
            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(65)->generate($verificationUrl);
            $qrBase64 = base64_encode($qrSvg);
        } catch (\Throwable $e) {
            $qrBase64 = null;
        }
    }

    // Logo
    $logoSrc = null;
    if ($company->logo_path) {
        $logoSrc = public_path('storage/' . $company->logo_path);
    }
?>

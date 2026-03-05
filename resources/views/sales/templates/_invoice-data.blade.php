{{-- Shared invoice data calculations - included by all PDF templates --}}
@php
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

    // Mentions légales
    $accountingSettings = \App\Models\AccountingSetting::where('company_id', $company->id)->first();
    $isVatOnDebits = ($accountingSettings->vat_regime ?? 'debits') === 'debits' && !($accountingSettings->is_vat_franchise ?? false);
    $natureOp = $sale->nature_operation ?? null;
    $deliveryAddr = $sale->delivery_address ?? null;
    $natureOpLabels = ['goods' => 'Vente de biens', 'services' => 'Prestation de services', 'mixed' => 'Mixte'];

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
@endphp

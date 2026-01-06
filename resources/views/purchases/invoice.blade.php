<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture d'achat {{ $purchase->invoice_number }}</title>
    @if(!empty($previewMode))
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @endif
    <style>
        :root {
            --primary: #7c3aed;
            --primary-light: #8b5cf6;
            --primary-dark: #6d28d9;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            color: var(--gray-800);
            background: #fff;
            line-height: 1.5;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        
        /* Header avec gradient violet */
        .invoice-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            box-shadow: 0 10px 40px rgba(124, 58, 237, 0.25);
        }
        
        .company-info h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        
        .company-info .subtitle {
            font-size: 13px;
            opacity: 0.8;
            font-weight: 400;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 8px;
        }
        
        .company-details {
            margin-top: 16px;
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .invoice-meta {
            text-align: right;
            min-width: 200px;
        }
        
        .invoice-number {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -1px;
        }
        
        .invoice-number span {
            font-size: 14px;
            opacity: 0.7;
            font-weight: 400;
            display: block;
            margin-bottom: 4px;
        }
        
        .invoice-date {
            margin-top: 12px;
            font-size: 13px;
            opacity: 0.8;
        }
        
        .logo-container {
            margin-bottom: 16px;
        }
        
        .logo-container img {
            max-height: 60px;
            max-width: 150px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        
        /* Preview Banner */
        .preview-banner {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            border: 1px solid var(--primary);
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--primary-dark);
        }
        
        .preview-banner a {
            color: var(--primary-dark);
            font-weight: 600;
            text-decoration: none;
            padding: 6px 16px;
            background: white;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .preview-banner a:hover {
            background: var(--primary-dark);
            color: white;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-completed {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
        }
        .status-completed::before { background: #10b981; }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }
        .status-pending::before { background: #f59e0b; }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }
        .status-cancelled::before { background: #ef4444; }
        
        /* Cards Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .info-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
        }
        
        .info-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.1);
        }
        
        .info-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .info-card-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .info-card-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
        }
        
        .info-card-content h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 6px;
        }
        
        .info-card-content p {
            font-size: 12px;
            color: var(--gray-500);
            line-height: 1.6;
        }
        
        /* Table moderne */
        .items-section {
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 2px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .items-table thead {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        }
        
        .items-table thead th {
            padding: 14px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
        }
        
        .items-table thead th:last-child {
            text-align: right;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.2s;
        }
        
        .items-table tbody tr:hover {
            background: var(--gray-50);
        }
        
        .items-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .items-table tbody td {
            padding: 14px 16px;
            font-size: 13px;
        }
        
        .items-table tbody td:last-child {
            text-align: right;
            font-weight: 600;
        }
        
        .product-name {
            font-weight: 500;
            color: var(--gray-800);
        }
        
        .text-right { text-align: right; }
        .text-muted { color: var(--gray-500); }
        
        /* Totals Card */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 32px;
        }
        
        .totals-card {
            width: 320px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 13px;
        }
        
        .totals-row:last-child {
            border-bottom: none;
        }
        
        .totals-row .label {
            color: var(--gray-500);
        }
        
        .totals-row .value {
            font-weight: 500;
        }
        
        .totals-row.discount .value {
            color: var(--success);
        }
        
        .totals-row.grand-total {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 16px 20px;
        }
        
        .totals-row.grand-total .label {
            color: rgba(255,255,255,0.8);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
        }
        
        .totals-row.grand-total .value {
            font-size: 20px;
            font-weight: 700;
        }
        
        .amount-words {
            padding: 12px 20px;
            background: white;
            font-size: 11px;
            font-style: italic;
            color: var(--gray-500);
            border-top: 1px dashed var(--gray-300);
        }
        
        /* QR Verification */
        .verification-section {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .qr-code {
            flex-shrink: 0;
            padding: 8px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .qr-code img {
            display: block;
            width: 100px;
            height: 100px;
        }
        
        .verification-info h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .verification-info p {
            font-size: 11px;
            color: var(--gray-500);
            line-height: 1.6;
        }
        
        .verification-code {
            display: inline-block;
            font-family: 'SF Mono', Monaco, monospace;
            background: var(--primary);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 6px;
        }
        
        /* Notes */
        .notes-section {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 32px;
        }
        
        .notes-section h4 {
            font-size: 12px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .notes-section p {
            font-size: 12px;
            color: #78350f;
            line-height: 1.6;
        }
        
        /* Footer */
        .invoice-footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-500);
            font-size: 11px;
            line-height: 1.6;
        }
        
        /* Actions */
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 32px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }
        
        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }
        
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .invoice-container { padding: 20px; max-width: none; }
            .invoice-header { box-shadow: none; }
            .info-card:hover { border-color: var(--gray-200); box-shadow: none; }
        }
    </style>
</head>
<body>
@php
    $currency = 'EUR';
    $status = $purchase->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $discountPercent = $purchase->discount_percent ?? 0;
    
    // Utiliser les valeurs TVA calculées par ligne et stockées dans Purchase
    $totalHt = $purchase->total_ht ?? $purchase->items->sum('total_price_ht');
    $totalVat = $purchase->total_vat ?? $purchase->items->sum('vat_amount');
    $grandTotal = $purchase->total ?? ($totalHt + $totalVat);
    
    // Calculer le taux TVA effectif (moyenne pondérée)
    $effectiveVatRate = $totalHt > 0 ? round(($totalVat / $totalHt) * 100, 1) : 0;
    
    // Calculer la remise si présente
    $totalAvantRemise = $purchase->items->sum('total_price');
    $discountAmount = $totalAvantRemise * ($discountPercent / 100);
    
    function amountToWordsFrPurchase($number, $currency = 'EUR') {
        $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        $euros = floor($number);
        $centimes = round(($number - $euros) * 100);
        
        // Noms des unités et sous-unités par devise
        $units = [
            'EUR' => ['euro', 'euros', 'centime', 'centimes'],
            'FCFA' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
            'XOF' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
            'USD' => ['dollar', 'dollars', 'cent', 'cents'],
            'GBP' => ['livre sterling', 'livres sterling', 'penny', 'pence'],
        ];
        $u = $units[$currency] ?? ['unité', 'unités', 'centime', 'centimes'];
        
        $euroWord = $euros == 1 ? $u[0] : $u[1];
        $centimeWord = $centimes == 1 ? $u[2] : $u[3];
        
        $text = ucfirst($fmt->format($euros)) . ' ' . $euroWord;
        if ($centimes > 0) {
            $text .= ' et ' . $fmt->format($centimes) . ' ' . $centimeWord;
        }
        return $text;
    }
    
    // Nom de devise en lettres (pour affichage simple)
    $currencyNames = [
        'EUR' => 'euros',
        'FCFA' => 'francs CFA',
        'XOF' => 'francs CFA',
        'USD' => 'dollars',
        'GBP' => 'livres sterling',
    ];
    $currencyInWords = $currencyNames[$currency] ?? $currency;
    
    $statusLabels = [
        'completed' => 'Terminé',
        'pending' => 'En attente',
        'cancelled' => 'Annulé'
    ];
@endphp

<div class="invoice-container">
    @if(!empty($previewMode))
        <div class="preview-banner no-print">
            <span>📋 Mode prévisualisation — Document non finalisé</span>
            <a href="{{ route('purchases.invoice', $purchase) }}">Télécharger PDF</a>
        </div>
    @endif

    <header class="invoice-header">
        <div class="company-info">
            @if($company->logo_path)
                <div class="logo-container">
                    <img src="{{ public_path($company->logo_path) }}" alt="{{ $company->name }}">
                </div>
            @endif
            <h1>{{ $company->name ?: 'Votre Entreprise' }}</h1>
            <span class="subtitle">📦 Bon d'achat</span>
            <div class="company-details">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->phone)Tél: {{ $company->phone }}@endif
                @if($company->email) • {{ $company->email }}@endif
                @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
            </div>
        </div>
        <div class="invoice-meta">
            <div class="invoice-number">
                <span>Document N°</span>
                {{ $purchase->invoice_number }}
            </div>
            <div class="invoice-date">
                {{ $purchase->created_at->format('d M Y') }}
            </div>
            <div style="margin-top: 16px;">
                <span class="status-badge {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? $status }}
                </span>
            </div>
        </div>
    </header>

    <div class="info-grid">
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">🏭</div>
                <span class="info-card-title">Fournisseur</span>
            </div>
            <div class="info-card-content">
                <h3>{{ $purchase->supplier->name ?? 'Fournisseur non défini' }}</h3>
                <p>
                    @if(optional($purchase->supplier)->address){{ $purchase->supplier->address }}<br>@endif
                    @if(optional($purchase->supplier)->phone)📞 {{ $purchase->supplier->phone }}<br>@endif
                    @if(optional($purchase->supplier)->email)✉️ {{ $purchase->supplier->email }}@endif
                </p>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">📋</div>
                <span class="info-card-title">Détails</span>
            </div>
            <div class="info-card-content">
                <h3>Informations de paiement</h3>
                <p>
                    Mode: {{ ucfirst($purchase->payment_method ?? 'Non spécifié') }}<br>
                    Référence: {{ $purchase->reference ?? $purchase->invoice_number }}
                </p>
            </div>
        </div>
    </div>

    @if($purchase->notes)
        <div class="notes-section">
            <h4>📝 Notes internes</h4>
            <p>{{ $purchase->notes }}</p>
        </div>
    @endif

    <div class="items-section">
        <h2 class="section-title">Articles commandés</h2>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%">Désignation</th>
                    <th style="width: 12%">Qté</th>
                    <th style="width: 18%" class="text-right">P.U. HT</th>
                    <th style="width: 10%" class="text-right">TVA</th>
                    <th style="width: 20%" class="text-right">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase->items as $item)
                    <tr>
                        <td><span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span></td>
                        <td>{{ $item->quantity }}</td>
                        <td class="text-right text-muted">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                        <td class="text-right">{{ number_format($item->vat_rate ?? 0, 0) }}%</td>
                        <td class="text-right">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: var(--gray-400);">
                            Aucun article dans ce bon d'achat
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals-section">
        <div class="totals-card">
            <div class="totals-row">
                <span class="label">Total HT</span>
                <span class="value">{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</span>
            </div>
            @if($discountPercent > 0)
                <div class="totals-row discount">
                    <span class="label">Remise ({{ number_format($discountPercent, 1, ',', ' ') }}%)</span>
                    <span class="value">- {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</span>
                </div>
            @endif
            <div class="totals-row">
                <span class="label">TVA ({{ number_format($effectiveVatRate, 1, ',', ' ') }}%)</span>
                <span class="value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</span>
            </div>
            <div class="totals-row grand-total">
                <span class="label">Total TTC</span>
                <span class="value">{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</span>
            </div>
            <div class="amount-words">
                {{ amountToWordsFrPurchase($grandTotal, $currency) }}
            </div>
        </div>
    </div>

    @if(!empty($verificationUrl) && !empty($verificationCode))
        <div class="verification-section">
            <div class="qr-code">
                @php
                    try {
                        $qr = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(100)->margin(0)->generate($verificationUrl));
                    } catch (\Throwable $e) { $qr = null; }
                @endphp
                @if($qr)
                    <img src="data:image/png;base64,{{ $qr }}" alt="QR Code">
                @else
                    <div style="width:100px;height:100px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:10px;">QR indisponible</div>
                @endif
            </div>
            <div class="verification-info">
                <h4>🔒 Vérification d'authenticité</h4>
                <p>
                    Scannez le QR code ou visitez l'URL ci-dessous pour vérifier l'authenticité de ce document.<br>
                    <span style="word-break:break-all;font-size:10px;">{{ $verificationUrl }}</span>
                </p>
                <span class="verification-code">{{ $verificationCode }}</span>
            </div>
        </div>
    @endif

    <footer class="invoice-footer">
        @if($company->footer_text)
            {{ $company->footer_text }}
        @else
            Merci pour votre confiance • Document généré automatiquement<br>
            {{ $company->name }} — {{ $company->phone ?? '' }} — {{ $company->email ?? '' }}
        @endif
    </footer>

    <div class="actions no-print">
        @if(empty($previewMode))
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimer
            </button>
        @else
            <a href="{{ route('purchases.invoice', $purchase) }}" class="btn btn-primary">
                📄 Télécharger PDF
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                🖨️ Imprimer
            </button>
        @endif
    </div>
</div>
</body>
</html>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture' }} {{ $sale->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        @page {
            margin: 25mm 22mm 30mm 22mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.5;
            letter-spacing: 0.01em;
        }
        
        /* ===== HEADER ===== */
        .header {
            background-color: #1e293b;
            color: #ffffff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table td {
            vertical-align: top;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .company-subtitle {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        
        .company-details {
            font-size: 9px;
            color: #cbd5e1;
            line-height: 1.5;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-label {
            font-size: 10px;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        
        .invoice-number {
            font-size: 22px;
            font-weight: bold;
            color: #ffffff;
        }
        
        .invoice-date {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 8px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        
        .status-completed {
            background-color: #065f46;
            color: #10b981;
        }
        
        .status-pending {
            background-color: #78350f;
            color: #f59e0b;
        }
        
        .status-cancelled {
            background-color: #7f1d1d;
            color: #ef4444;
        }
        
        .logo {
            max-height: 45px;
            max-width: 100px;
            margin-bottom: 8px;
            background: #ffffff;
            padding: 4px;
            border-radius: 4px;
        }
        
        /* ===== INFO CARDS ===== */
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }
        
        .info-table td {
            width: 50%;
            vertical-align: top;
        }
        
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
        }
        
        .info-card-header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        
        .info-card-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #3b82f6;
            letter-spacing: 0.8px;
        }
        
        .info-card-name {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .info-card-text {
            font-size: 9px;
            color: #64748b;
            line-height: 1.5;
        }
        
        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 10px;
            padding-left: 8px;
            border-left: 3px solid #3b82f6;
        }
        
        /* ===== ITEMS TABLE ===== */
        .items-section {
            margin-bottom: 20px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        
        .items-table thead tr {
            background-color: #1e293b;
        }
        
        .items-table thead th {
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px 8px;
            text-align: left;
            letter-spacing: 0.3px;
        }
        
        .items-table thead th.text-right {
            text-align: right;
        }
        
        .items-table thead th.text-center {
            text-align: center;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .items-table tbody td {
            padding: 10px 8px;
            font-size: 10px;
            vertical-align: middle;
        }
        
        .items-table tbody td.text-right {
            text-align: right;
        }
        
        .items-table tbody td.text-center {
            text-align: center;
        }
        
        .product-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .text-muted {
            color: #64748b;
        }
        
        /* ===== TOTALS ===== */
        .totals-section {
            margin-bottom: 20px;
        }
        
        .totals-wrapper {
            width: 100%;
        }
        
        .totals-wrapper td.spacer {
            width: 55%;
        }
        
        .totals-wrapper td.totals {
            width: 45%;
        }
        
        .totals-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .totals-row {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .totals-row:last-child {
            border-bottom: none;
        }
        
        .totals-row-table {
            width: 100%;
        }
        
        .totals-label {
            color: #64748b;
            font-size: 10px;
        }
        
        .totals-value {
            text-align: right;
            font-weight: 600;
            font-size: 10px;
            color: #1e293b;
        }
        
        .totals-value.discount {
            color: #10b981;
        }
        
        .grand-total {
            background-color: #1e293b;
            color: #ffffff;
            padding: 12px;
        }
        
        .grand-total .totals-label {
            color: #94a3b8;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }
        
        .grand-total .totals-value {
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
        }
        
        .amount-words {
            padding: 8px 12px;
            background-color: #ffffff;
            font-size: 8px;
            font-style: italic;
            color: #64748b;
            border-top: 1px dashed #cbd5e1;
        }
        
        /* ===== NOTES ===== */
        .notes-box {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 9px;
        }
        
        .notes-title {
            font-weight: bold;
            color: #92400e;
        }
        
        /* ===== QR VERIFICATION ===== */
        .verification-section {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
        }
        
        .verification-table {
            width: 100%;
        }
        
        .qr-cell {
            width: 80px;
            vertical-align: top;
        }
        
        .qr-box {
            background-color: #ffffff;
            padding: 5px;
            border-radius: 4px;
            display: inline-block;
        }
        
        .qr-box img {
            width: 65px;
            height: 65px;
        }
        
        .verification-info {
            padding-left: 12px;
            vertical-align: middle;
        }
        
        .verification-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .verification-text {
            font-size: 8px;
            color: #64748b;
            line-height: 1.4;
        }
        
        .verification-code {
            display: inline-block;
            font-family: monospace;
            background-color: #1e293b;
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            margin-top: 5px;
        }
        
        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 8px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
@php
    $currency = $company->currency ?? 'EUR';
    $status = $sale->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $discountPercent = $sale->discount_percent ?? 0;
    
    // Calculs TVA
    $totalHt = $sale->total_ht ?? $sale->items->sum('total_price_ht');
    $totalVat = $sale->total_vat ?? $sale->items->sum('vat_amount');
    $grandTotal = $sale->total ?? ($totalHt + $totalVat);
    $effectiveVatRate = $totalHt > 0 ? round(($totalVat / $totalHt) * 100, 1) : 0;
    $totalAvantRemise = $sale->items->sum('total_price');
    $discountAmount = $totalAvantRemise * ($discountPercent / 100);
    
    // Fonction montant en lettres
    function amountToWordsFrSalePdf($number, $currency = 'EUR') {
        $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        $euros = floor($number);
        $centimes = round(($number - $euros) * 100);
        
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
    
    $statusLabels = [
        'completed' => 'Payée',
        'pending' => 'En attente',
        'cancelled' => 'Annulée'
    ];
    
    $invoiceTypeLabel = $sale->type === 'credit_note' ? 'Avoir N°' : 'Facture N°';
@endphp

<!-- HEADER -->
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if($company->logo_path)
                    <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="logo">
                @endif
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                <div class="company-subtitle">{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture de vente' }}</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)Tél: {{ $company->phone }}@endif
                    @if($company->email) • {{ $company->email }}@endif
                    @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
                    @if($company->siret)<br>SIRET: {{ $company->siret }}@endif
                </div>
            </td>
            <td class="invoice-title">
                <div class="invoice-label">{{ $invoiceTypeLabel }}</div>
                <div class="invoice-number">{{ $sale->invoice_number }}</div>
                <div class="invoice-date">{{ $sale->created_at->format('d M Y') }}</div>
                <span class="status-badge {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
            </td>
        </tr>
    </table>
</div>

<!-- INFO CARDS -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-header">
                        <span class="info-card-title">Client</span>
                    </div>
                    <div class="info-card-name">{{ $sale->customer->name ?? 'Client non défini' }}</div>
                    <div class="info-card-text">
                        @if(optional($sale->customer)->siret)<strong>SIRET:</strong> {{ $sale->customer->siret }}<br>@endif
                        @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                        @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                        @if(optional($sale->customer)->phone)Tél: {{ $sale->customer->phone }}<br>@endif
                        @if(optional($sale->customer)->email){{ $sale->customer->email }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-header">
                        <span class="info-card-title">Détails</span>
                    </div>
                    <div class="info-card-name">Informations de paiement</div>
                    <div class="info-card-text">
                        Mode: {{ ucfirst($sale->payment_method ?? 'Non spécifié') }}<br>
                        Référence: {{ $sale->reference ?? $sale->invoice_number }}<br>
                        @if($sale->warehouse)Entrepôt: {{ $sale->warehouse->name }}@endif
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ITEMS TABLE -->
<div class="items-section">
    <div class="section-title">Articles facturés</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Désignation</th>
                <th style="width: 10%;" class="text-center">Qté</th>
                <th style="width: 18%;" class="text-right">P.U. HT</th>
                <th style="width: 12%;" class="text-center">TVA</th>
                <th style="width: 20%;" class="text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sale->items as $item)
                <tr>
                    <td><span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span></td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right text-muted">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                    <td class="text-center">{{ number_format($item->vat_rate ?? 0, 0) }}%</td>
                    <td class="text-right">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">
                        Aucun article dans cette facture
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- TOTALS -->
<div class="totals-section">
    <table class="totals-wrapper">
        <tr>
            <td class="spacer"></td>
            <td class="totals">
                <div class="totals-card">
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Total HT</td>
                                <td class="totals-value">{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @if($discountAmount > 0)
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">Remise ({{ number_format($discountPercent, 1) }}%)</td>
                                <td class="totals-value discount">- {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TVA ({{ number_format($effectiveVatRate, 1) }}%)</td>
                                <td class="totals-value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="totals-row grand-total">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TOTAL TTC</td>
                                <td class="totals-value">{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="amount-words">
                        {{ amountToWordsFrSalePdf($grandTotal, $currency) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- NOTES -->
@if($sale->notes)
<div class="notes-box">
    <span class="notes-title">📝 Note:</span> {{ $sale->notes }}
</div>
@endif

<!-- QR VERIFICATION -->
@if(!empty($verificationUrl) && !empty($verificationCode))
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td class="qr-cell">
                <div class="qr-box">
                    @php
                        try {
                            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(65)->generate($verificationUrl);
                            $qrBase64 = base64_encode($qrSvg);
                        } catch (\Throwable $e) {
                            $qrBase64 = null;
                        }
                    @endphp
                    @if($qrBase64)
                        <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" alt="QR Code">
                    @else
                        <div style="width:65px;height:65px;background:#f1f5f9;"></div>
                    @endif
                </div>
            </td>
            <td class="verification-info">
                <div class="verification-title">🔒 Vérification d'authenticité</div>
                <div class="verification-text">
                    Scannez le QR code ou visitez l'URL ci-dessous pour vérifier l'authenticité de ce document.<br>
                    <span style="font-size:7px;word-break:break-all;">{{ $verificationUrl }}</span>
                </div>
                <span class="verification-code">{{ $verificationCode }}</span>
            </td>
        </tr>
    </table>
</div>
@endif

<!-- FOOTER -->
<div class="footer">
    @if($company->footer_text)
        {{ $company->footer_text }}
    @else
        Merci pour votre confiance • Document généré automatiquement<br>
        {{ $company->name }} — {{ $company->phone ?? '' }} — {{ $company->email ?? '' }}
    @endif
</div>

</body>
</html>

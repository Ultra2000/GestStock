<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture' }} {{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 40px;
        }
        
        /* Header */
        .invoice-header {
            background-color: #1e293b;
            color: white;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            width: 100%;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table td {
            vertical-align: top;
        }
        
        .company-info h1 {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 4px 0;
            color: white;
        }
        
        .company-info .subtitle {
            font-size: 12px;
            opacity: 0.8;
            margin: 0;
        }
        
        .company-details {
            margin-top: 16px;
            font-size: 11px;
            opacity: 0.9;
            line-height: 1.6;
            color: #cbd5e1;
        }
        
        .invoice-meta {
            text-align: right;
        }
        
        .invoice-number {
            font-size: 28px;
            font-weight: bold;
            color: white;
            margin-bottom: 5px;
        }
        
        .invoice-number span {
            font-size: 12px;
            opacity: 0.7;
            font-weight: normal;
            display: block;
            margin-bottom: 2px;
        }
        
        .invoice-date {
            font-size: 12px;
            opacity: 0.8;
            color: #cbd5e1;
            margin-bottom: 15px;
        }
        
        .logo-container img {
            max-height: 50px;
            margin-bottom: 15px;
            background-color: white;
            padding: 5px;
            border-radius: 4px;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        /* Info Grid */
        .info-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 20px 0;
            margin: 0 -20px 32px -20px;
        }
        
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            width: 48%;
            vertical-align: top;
        }
        
        .info-card-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
        }
        
        .info-card-content h3 {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #1e293b;
        }
        
        .info-card-content p {
            font-size: 11px;
            color: #64748b;
            margin: 0;
            line-height: 1.5;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .items-table thead th {
            background-color: #1e293b;
            color: white;
            padding: 12px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .items-table thead th:last-child {
            text-align: right;
        }
        
        .items-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 11px;
            color: #334155;
        }
        
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .items-table td:last-child {
            text-align: right;
            font-weight: bold;
            color: #1e293b;
        }
        
        .product-name {
            font-weight: bold;
            color: #1e293b;
            display: block;
            margin-bottom: 2px;
        }
        
        .product-desc {
            font-size: 10px;
            color: #64748b;
        }
        
        /* Totals */
        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 32px;
        }
        
        .totals-table td {
            padding: 10px 15px;
            font-size: 11px;
        }
        
        .totals-table .label {
            color: #64748b;
            text-align: left;
        }
        
        .totals-table .value {
            text-align: right;
            font-weight: bold;
            color: #1e293b;
        }
        
        .totals-table .grand-total td {
            background-color: #1e293b;
            color: white;
            padding: 15px;
        }
        
        .totals-table .grand-total .label {
            color: rgba(255,255,255,0.8);
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .totals-table .grand-total .value {
            font-size: 16px;
            color: white;
        }
        
        .amount-words {
            text-align: right;
            font-size: 10px;
            font-style: italic;
            color: #64748b;
            margin-top: -20px;
            margin-bottom: 32px;
        }
        
        /* Verification */
        .verification-section {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 32px;
        }
        
        .verification-table {
            width: 100%;
        }
        
        .qr-code-img {
            width: 80px;
            height: 80px;
            background: white;
            padding: 5px;
            border-radius: 4px;
        }
        
        .verification-content h4 {
            margin: 0 0 5px 0;
            font-size: 12px;
            color: #1e293b;
        }
        
        .verification-content p {
            margin: 0;
            font-size: 10px;
            color: #64748b;
        }
        
        .verification-code {
            display: inline-block;
            background-color: #1e293b;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 10px;
            margin-top: 5px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 10px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
@php
    $currency = $company->currency ?? 'FCFA';
    $status = $sale->status;
    $computedTotal = $sale->items->sum('total_price');
    $discountPercent = $sale->discount_percent ?? 0;
    $taxPercent = $sale->tax_percent ?? 0;
    $discountAmount = $computedTotal * ($discountPercent / 100);
    $afterDiscount = $computedTotal - $discountAmount;
    $taxAmount = $afterDiscount * ($taxPercent / 100);
    $grandTotal = $sale->total ?? ($afterDiscount + $taxAmount);
    
    function amountToWordsFr($number) {
        $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        return ucfirst($fmt->format((int) $number));
    }
    
    $statusLabels = [
        'completed' => 'Payée',
        'pending' => 'En attente',
        'cancelled' => 'Annulée'
    ];
@endphp

<!-- Header -->
<div class="invoice-header">
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-info">
                    @if($company->logo_path)
                        <div class="logo-container">
                            <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="{{ $company->name }}">
                        </div>
                    @endif
                    <h1>{{ $company->name ?: 'Votre Entreprise' }}</h1>
                    <p class="subtitle">{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture de vente' }}</p>
                    <div class="company-details">
                        @if($company->address){{ $company->address }}<br>@endif
                        @if($company->phone)Tél: {{ $company->phone }}@endif
                        @if($company->email) • {{ $company->email }}@endif
                        @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
                    </div>
                </div>
            </td>
            <td class="invoice-meta">
                <div class="invoice-number">
                    <span>{{ $sale->type === 'credit_note' ? 'Avoir N°' : 'Facture N°' }}</span>
                    {{ $sale->invoice_number }}
                </div>
                <div class="invoice-date">
                    {{ $sale->created_at->format('d M Y') }}
                </div>
                <span class="status-badge">
                    {{ $statusLabels[$status] ?? $status }}
                </span>
            </td>
        </tr>
    </table>
</div>

<!-- Info Grid -->
<table class="info-grid">
    <tr>
        <td class="info-card">
            <div class="info-card-header">👤 Client</div>
            <div class="info-card-content">
                <h3>{{ $sale->customer->name ?? 'Client non défini' }}</h3>
                <p>
                    @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                    @if(optional($sale->customer)->phone)📞 {{ $sale->customer->phone }}<br>@endif
                    @if(optional($sale->customer)->email)✉️ {{ $sale->customer->email }}@endif
                </p>
            </div>
        </td>
        <td class="info-card">
            <div class="info-card-header">📋 Détails</div>
            <div class="info-card-content">
                <h3>Informations de paiement</h3>
                <p>
                    Mode: {{ ucfirst($sale->payment_method ?? 'Non spécifié') }}<br>
                    @if($sale->warehouse)Entrepôt: {{ $sale->warehouse->name }}@endif
                </p>
            </div>
        </td>
    </tr>
</table>

<!-- Items Table -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 40%;">Désignation</th>
            <th style="width: 15%; text-align: center;">Prix Unit.</th>
            <th style="width: 15%; text-align: center;">Qté</th>
            <th style="width: 30%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sale->items as $item)
        <tr>
            <td>
                <span class="product-name">{{ $item->product->name ?? 'Article' }}</span>
                @if($item->product && $item->product->sku)
                    <span class="product-desc">Réf: {{ $item->product->sku }}</span>
                @endif
            </td>
            <td style="text-align: center;">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
            <td style="text-align: center;">{{ $item->quantity }}</td>
            <td>{{ number_format($item->total_price, 0, ',', ' ') }} {{ $currency }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Totals -->
<table class="totals-table">
    <tr>
        <td class="label">Sous-total</td>
        <td class="value">{{ number_format($computedTotal, 0, ',', ' ') }} {{ $currency }}</td>
    </tr>
    @if($discountAmount > 0)
    <tr>
        <td class="label">Remise ({{ $discountPercent }}%)</td>
        <td class="value" style="color: #10b981;">-{{ number_format($discountAmount, 0, ',', ' ') }} {{ $currency }}</td>
    </tr>
    @endif
    @if($taxAmount > 0)
    <tr>
        <td class="label">TVA ({{ $taxPercent }}%)</td>
        <td class="value">{{ number_format($taxAmount, 0, ',', ' ') }} {{ $currency }}</td>
    </tr>
    @endif
    <tr class="grand-total">
        <td class="label">Total TTC</td>
        <td class="value">{{ number_format($grandTotal, 0, ',', ' ') }} {{ $currency }}</td>
    </tr>
</table>

<div class="amount-words">
    Arrêté la présente facture à la somme de : <strong>{{ amountToWordsFr($grandTotal) }} {{ $currency }}</strong>
</div>

@if($sale->notes)
<div style="margin-top: 20px; padding: 10px; background-color: #f1f5f9; border-radius: 6px; font-size: 11px;">
    <strong>Note:</strong> {{ $sale->notes }}
</div>
@endif

<!-- Verification -->
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td style="width: 100px; vertical-align: middle;">
                <div class="qr-code-img">
                    <img src="data:image/svg+xml;base64, {{ base64_encode(QrCode::format('svg')->size(80)->generate($verificationUrl)) }}" alt="QR Code">
                </div>
            </td>
            <td style="vertical-align: middle; padding-left: 15px;">
                <div class="verification-content">
                    <h4>Sécurité & Authenticité</h4>
                    <p>Ce document est sécurisé par une signature numérique unique.</p>
                    <div class="verification-code">Code: {{ $verificationCode }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Footer -->
<div class="footer">
    {{ $company->name }} - {{ $company->address ?? '' }}<br>
    @if($company->registration_number) SIRET: {{ $company->registration_number }} @endif
    @if($company->tax_number) - TVA: {{ $company->tax_number }} @endif
    <br>
    Généré le {{ now()->format('d/m/Y à H:i') }}
</div>

</body>
</html>

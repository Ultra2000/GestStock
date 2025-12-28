<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Facture d'achat {{ $purchase->invoice_number }}</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        
        /* Header */
        .header {
            background-color: #7c3aed;
            color: white;
            padding: 20px 25px;
            margin: -20mm -15mm 20px -15mm;
            width: calc(100% + 30mm);
        }
        
        .header-content {
            width: 100%;
        }
        
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header td {
            vertical-align: top;
        }
        
        .company-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 3px;
            color: white;
        }
        
        .company-subtitle {
            display: inline-block;
            font-size: 10px;
            color: white;
            background-color: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .company-details {
            font-size: 10px;
            color: #ddd6fe;
            line-height: 1.5;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-label {
            font-size: 10px;
            color: #c4b5fd;
            margin-bottom: 2px;
        }
        
        .invoice-number {
            font-size: 24px;
            font-weight: bold;
            color: white;
            margin-bottom: 8px;
        }
        
        .invoice-date {
            font-size: 11px;
            color: #ddd6fe;
            margin-bottom: 10px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-completed {
            background-color: #10b981;
            color: white;
        }
        
        .status-pending {
            background-color: #f59e0b;
            color: white;
        }
        
        .status-cancelled {
            background-color: #ef4444;
            color: white;
        }
        
        /* Info Cards */
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        
        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
        }
        
        .info-grid td:first-child {
            padding-left: 0;
        }
        
        .info-grid td:last-child {
            padding-right: 0;
        }
        
        .info-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }
        
        .info-card-header {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-card-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .info-card-content {
            font-size: 10px;
            color: #64748b;
            line-height: 1.5;
        }
        
        /* Notes Section */
        .notes-section {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        
        .notes-title {
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }
        
        .notes-content {
            font-size: 10px;
            color: #78350f;
            line-height: 1.5;
        }
        
        /* Section Title */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 12px;
            padding-left: 10px;
            border-left: 3px solid #7c3aed;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table thead th {
            background-color: #7c3aed;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .items-table thead th:last-child {
            text-align: right;
        }
        
        .items-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        
        .items-table tbody td:last-child {
            text-align: right;
            font-weight: bold;
        }
        
        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .product-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-muted {
            color: #64748b;
        }
        
        /* Totals */
        .totals-container {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .totals-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 12px;
            font-size: 11px;
        }
        
        .totals-table .label {
            color: #64748b;
            text-align: left;
        }
        
        .totals-table .value {
            text-align: right;
            font-weight: 500;
        }
        
        .totals-table .discount .value {
            color: #10b981;
        }
        
        .totals-table .grand-total td {
            background-color: #7c3aed;
            color: white;
            font-weight: bold;
            padding: 12px;
        }
        
        .totals-table .grand-total .label {
            color: #ddd6fe;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .totals-table .grand-total .value {
            font-size: 16px;
            color: white;
        }
        
        .amount-words {
            font-size: 9px;
            font-style: italic;
            color: #64748b;
            text-align: right;
            padding: 8px 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-top: 1px dashed #cbd5e1;
        }
        
        /* QR Verification */
        .verification-section {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .verification-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .verification-table td {
            vertical-align: middle;
        }
        
        .qr-cell {
            width: 100px;
            padding-right: 15px;
        }
        
        .qr-code {
            background: white;
            padding: 5px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }
        
        .qr-code img {
            width: 90px;
            height: 90px;
        }
        
        .verification-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }
        
        .verification-text {
            font-size: 9px;
            color: #64748b;
            line-height: 1.5;
        }
        
        .verification-code {
            display: inline-block;
            background-color: #7c3aed;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 10px;
            margin-top: 5px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #64748b;
            line-height: 1.5;
        }
        
        /* Logo in header */
        .logo-img {
            max-height: 50px;
            max-width: 120px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
@php
    $currency = 'FCFA';
    $status = $purchase->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $computedTotal = $purchase->items->sum('total_price');
    $discountPercent = $purchase->discount_percent ?? 0;
    $taxPercent = $purchase->tax_percent ?? 0;
    $discountAmount = $computedTotal * ($discountPercent / 100);
    $afterDiscount = $computedTotal - $discountAmount;
    $taxAmount = $afterDiscount * ($taxPercent / 100);
    $grandTotal = $purchase->total ?? ($afterDiscount + $taxAmount);
    
    function amountToWordsFrPurchasePdf($number) {
        $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        return ucfirst($fmt->format((int) $number));
    }
    
    $statusLabels = [
        'completed' => 'TERMINÉ',
        'pending' => 'EN ATTENTE',
        'cancelled' => 'ANNULÉ'
    ];
@endphp

<!-- Header -->
<div class="header">
    <table class="header-content">
        <tr>
            <td style="width: 60%;">
                @if($company->logo_path)
                    <img src="{{ public_path($company->logo_path) }}" class="logo-img" alt="{{ $company->name }}">
                @endif
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                <span class="company-subtitle">📦 Bon d'achat</span>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)Tél: {{ $company->phone }}@endif
                    @if($company->email) • {{ $company->email }}@endif
                    @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
                </div>
            </td>
            <td class="invoice-title">
                <div class="invoice-label">Document N°</div>
                <div class="invoice-number">{{ $purchase->invoice_number }}</div>
                <div class="invoice-date">{{ $purchase->created_at->format('d M Y') }}</div>
                <span class="status-badge {{ $statusClass }}">{{ $statusLabels[$status] ?? strtoupper($status) }}</span>
            </td>
        </tr>
    </table>
</div>

<!-- Info Cards -->
<div class="info-section">
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-header">🏭 Fournisseur</div>
                    <div class="info-card-title">{{ $purchase->supplier->name ?? 'Fournisseur non défini' }}</div>
                    <div class="info-card-content">
                        @if(optional($purchase->supplier)->address){{ $purchase->supplier->address }}<br>@endif
                        @if(optional($purchase->supplier)->phone)Tél: {{ $purchase->supplier->phone }}<br>@endif
                        @if(optional($purchase->supplier)->email)Email: {{ $purchase->supplier->email }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-header">📋 Détails</div>
                    <div class="info-card-title">Informations de paiement</div>
                    <div class="info-card-content">
                        Mode: {{ ucfirst($purchase->payment_method ?? 'Non spécifié') }}<br>
                        Référence: {{ $purchase->reference ?? $purchase->invoice_number }}
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- Notes -->
@if($purchase->notes)
    <div class="notes-section">
        <div class="notes-title">📝 Notes internes</div>
        <div class="notes-content">{{ $purchase->notes }}</div>
    </div>
@endif

<!-- Items -->
<div class="section-title">Articles commandés</div>
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 45%;">Désignation</th>
            <th style="width: 15%;">Quantité</th>
            <th style="width: 20%;" class="text-right">Prix unitaire</th>
            <th style="width: 20%;" class="text-right">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($purchase->items as $item)
            <tr>
                <td><span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span></td>
                <td>{{ $item->quantity }}</td>
                <td class="text-right text-muted">{{ number_format($item->unit_price, 0, ',', ' ') }} {{ $currency }}</td>
                <td class="text-right">{{ number_format($item->total_price, 0, ',', ' ') }} {{ $currency }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align: center; padding: 30px; color: #94a3b8;">
                    Aucun article dans ce bon d'achat
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Totals -->
<div class="totals-container">
    <table class="totals-table">
        <tr>
            <td class="label">Sous-total</td>
            <td class="value">{{ number_format($computedTotal, 0, ',', ' ') }} {{ $currency }}</td>
        </tr>
        @if($discountPercent > 0)
            <tr class="discount">
                <td class="label">Remise ({{ number_format($discountPercent, 1, ',', ' ') }}%)</td>
                <td class="value">- {{ number_format($discountAmount, 0, ',', ' ') }} {{ $currency }}</td>
            </tr>
        @endif
        @if($taxPercent > 0)
            <tr>
                <td class="label">TVA ({{ number_format($taxPercent, 1, ',', ' ') }}%)</td>
                <td class="value">{{ number_format($taxAmount, 0, ',', ' ') }} {{ $currency }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td class="label">Total TTC</td>
            <td class="value">{{ number_format($grandTotal, 0, ',', ' ') }} {{ $currency }}</td>
        </tr>
    </table>
    <div class="amount-words">
        {{ amountToWordsFrPurchasePdf($grandTotal) }} francs CFA
    </div>
</div>

<!-- QR Verification -->
@if(!empty($verificationUrl) && !empty($verificationCode))
    <div class="verification-section">
        <table class="verification-table">
            <tr>
                <td class="qr-cell">
                    <div class="qr-code">
                        @php
                            try {
                                $qr = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(90)->margin(0)->generate($verificationUrl));
                            } catch (\Throwable $e) { $qr = null; }
                        @endphp
                        @if($qr)
                            <img src="data:image/png;base64,{{ $qr }}" alt="QR Code">
                        @endif
                    </div>
                </td>
                <td>
                    <div class="verification-title">🔒 Vérification d'authenticité</div>
                    <div class="verification-text">
                        Scannez le QR code ou visitez l'URL ci-dessous pour vérifier l'authenticité de ce document.<br>
                        <span style="word-break: break-all; font-size: 8px;">{{ $verificationUrl }}</span>
                    </div>
                    <span class="verification-code">{{ $verificationCode }}</span>
                </td>
            </tr>
        </table>
    </div>
@endif

<!-- Footer -->
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

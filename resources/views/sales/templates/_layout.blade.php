<?php require base_path('resources/views/sales/templates/_invoice_data.php'); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $docTitle }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.4;
            padding: 15mm 20mm;
            margin: 0;
        }

        /* ===== HEADER ===== */
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .logo {
            max-height: 45px;
            max-width: 120px;
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-subtitle {
            font-size: 9px;
            color: #666;
            margin-bottom: 6px;
        }

        .company-details {
            font-size: 8px;
            color: #555;
            line-height: 1.5;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 2px;
        }

        .invoice-number {
            font-size: 18px;
            font-weight: bold;
        }

        .invoice-date {
            font-size: 9px;
            color: #666;
            margin-top: 6px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #333;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .status-completed {
            border-color: #333;
        }

        .status-pending {
            border-color: #999;
            color: #999;
        }

        .status-cancelled {
            border-color: #999;
            color: #999;
            text-decoration: line-through;
        }

        /* ===== INFO SECTION ===== */
        .info-section {
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }

        .info-table td:last-child {
            padding: 0 0 0 8px;
        }

        .info-card {
            border: 1px solid #ccc;
            padding: 8px 10px;
        }

        .info-card-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #eee;
        }

        .info-card-name {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .info-card-text {
            font-size: 8px;
            color: #555;
            line-height: 1.5;
        }

        /* ===== ITEMS TABLE ===== */
        .items-section {
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ccc;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table thead th {
            padding: 6px 8px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid #333;
            color: #333;
        }

        .items-table tbody td {
            padding: 5px 8px;
            font-size: 9px;
            border-bottom: 1px solid #eee;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #ccc;
        }

        .product-name {
            font-weight: 500;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #777; }

        /* ===== TOTALS ===== */
        .totals-section {
            margin-bottom: 15px;
        }

        .totals-wrapper {
            width: 100%;
            border-collapse: collapse;
        }

        .spacer { width: 55%; }

        .totals {
            width: 45%;
            vertical-align: top;
        }

        .totals-card {
            border: 1px solid #ccc;
        }

        .totals-row {
            padding: 4px 10px;
            border-bottom: 1px solid #eee;
        }

        .totals-row-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-label {
            font-size: 8px;
            color: #555;
        }

        .totals-value {
            text-align: right;
            font-size: 9px;
            font-weight: 500;
        }

        .totals-value.discount {
            color: #555;
        }

        .grand-total {
            border-top: 2px solid #333;
            padding: 6px 10px;
        }

        .grand-total .totals-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #333;
        }

        .grand-total .totals-value {
            font-size: 12px;
            font-weight: bold;
        }

        .amount-words {
            padding: 4px 10px;
            font-size: 7px;
            font-style: italic;
            color: #777;
            border-top: 1px dashed #ccc;
        }

        /* ===== LEGAL ===== */
        .legal-section {
            border: 1px solid #ccc;
            padding: 6px 10px;
            margin-bottom: 10px;
            font-size: 7px;
            color: #555;
        }

        .legal-section strong {
            color: #333;
        }

        .legal-row {
            margin-bottom: 2px;
        }

        /* ===== NOTES ===== */
        .notes-box {
            border: 1px solid #ccc;
            padding: 6px 10px;
            margin-bottom: 12px;
            font-size: 8px;
        }

        .notes-title {
            font-weight: bold;
        }

        /* ===== QR VERIFICATION ===== */
        .verification-section {
            border: 1px solid #ccc;
            padding: 8px;
            margin-bottom: 10px;
        }

        .verification-table {
            width: 100%;
        }

        .qr-cell {
            width: 70px;
            vertical-align: top;
        }

        .qr-box {
            display: inline-block;
        }

        .qr-box img {
            width: 60px;
            height: 60px;
        }

        .verification-info {
            padding-left: 10px;
            vertical-align: middle;
        }

        .verification-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .verification-text {
            font-size: 7px;
            color: #555;
            line-height: 1.4;
        }

        .verification-code {
            display: inline-block;
            font-family: monospace;
            border: 1px solid #333;
            padding: 2px 6px;
            font-size: 8px;
            margin-top: 3px;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            color: #777;
            font-size: 7px;
            line-height: 1.5;
        }

        .footer-sub {
            font-size: 7px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
    </style>
</head>
<body>

{{-- ==================== HEADER ==================== --}}
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="{{ $company->name }}" class="logo">
                @endif
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                <div class="company-subtitle">{{ $sale->type === 'credit_note' ? 'AVOIR' : 'FACTURE' }}</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->phone)Tel: {{ $company->phone }}@endif
                    @if($company->email) &middot; {{ $company->email }}@endif
                    @if($company->tax_number)<br>TVA: {{ $company->tax_number }}@endif
                    @if($company->siret)<br>SIRET: {{ $company->siret }}@endif
                </div>
            </td>
            <td class="invoice-title">
                <div class="invoice-label">{{ $invoiceTypeLabel }}</div>
                <div class="invoice-number">{{ $sale->invoice_number }}</div>
                <div class="invoice-date">{{ $sale->created_at->format('d/m/Y') }}</div>
                <span class="status-badge status-{{ $status ?: 'pending' }}">{{ $statusLabel }}</span>
            </td>
        </tr>
    </table>
</div>

{{-- ==================== CLIENT + PAIEMENT ==================== --}}
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Client</div>
                    <div class="info-card-name">{{ $sale->customer->name ?? 'Client non defini' }}</div>
                    <div class="info-card-text">
                        @if(optional($sale->customer)->registration_number)<strong>SIREN:</strong> {{ $sale->customer->registration_number }}<br>@endif
                        @if(optional($sale->customer)->siret)<strong>SIRET:</strong> {{ $sale->customer->siret }}<br>@endif
                        @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                        @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                        @if(optional($sale->customer)->phone)Tel: {{ $sale->customer->phone }}<br>@endif
                        @if(optional($sale->customer)->email){{ $sale->customer->email }}<br>@endif
                        @if($customerTaxNumber)<strong>TVA Intra:</strong> {{ $customerTaxNumber }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Paiement</div>
                    <div class="info-card-name">{{ ucfirst($sale->payment_method ?? 'Non specifie') }}</div>
                    <div class="info-card-text">
                        <strong>Echeance:</strong> {{ $dueDate instanceof \Carbon\Carbon ? $dueDate->format('d/m/Y') : \Carbon\Carbon::parse($dueDate)->format('d/m/Y') }}<br>
                        <strong>Ref:</strong> {{ $sale->reference ?? $sale->invoice_number }}
                        @if($sale->warehouse)<br><strong>Entrepot:</strong> {{ $sale->warehouse->name }}@endif
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ==================== ARTICLES ==================== --}}
<div class="items-section">
    <div class="section-title">Articles</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Designation</th>
                <th style="width: 10%;" class="text-center">Qte</th>
                <th style="width: 18%;" class="text-right">P.U. HT</th>
                <th style="width: 12%;" class="text-center">TVA</th>
                <th style="width: 20%;" class="text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sale->items as $item)
                <tr>
                    <td><span class="product-name">{{ $item->product->name ?? 'Produit supprime' }}</span></td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', ' '), '0'), ',') }}</td>
                    <td class="text-right text-muted">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->vat_rate ?? 0, 2, ',', ' '), '0'), ',') }}%</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 15px; color: #999;">Aucun article</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ==================== TOTAUX ==================== --}}
<div class="totals-section">
    <table class="totals-wrapper">
        <tr>
            <td class="spacer"></td>
            <td class="totals">
                <div class="totals-card">
                    {{-- Sous-total HT --}}
                    <div class="totals-row">
                        <table class="totals-row-table"><tr>
                            <td class="totals-label">Total HT</td>
                            <td class="totals-value">{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr></table>
                    </div>

                    {{-- Remise --}}
                    @if($discountAmount > 0)
                    <div class="totals-row">
                        <table class="totals-row-table"><tr>
                            <td class="totals-label">Remise ({{ number_format($discountPercent, 1) }}%)</td>
                            <td class="totals-value discount">- {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr></table>
                    </div>
                    @endif

                    {{-- TVA --}}
                    @if($hasMultipleVatRates)
                        @foreach($vatBreakdown as $vat)
                        <div class="totals-row">
                            <table class="totals-row-table"><tr>
                                <td class="totals-label">TVA {{ rtrim(rtrim(number_format($vat['rate'], 2, ',', ' '), '0'), ',') }}% (base {{ number_format($vat['base'], 2, ',', ' ') }})</td>
                                <td class="totals-value">{{ number_format($vat['amount'], 2, ',', ' ') }} {{ $currency }}</td>
                            </tr></table>
                        </div>
                        @endforeach
                    @else
                    <div class="totals-row">
                        <table class="totals-row-table"><tr>
                            <td class="totals-label">TVA ({{ rtrim(rtrim(number_format($vatBreakdown[0]['rate'] ?? 20, 2, ',', ' '), '0'), ',') }}%)</td>
                            <td class="totals-value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr></table>
                    </div>
                    @endif

                    {{-- Grand total --}}
                    <div class="grand-total">
                        <table class="totals-row-table"><tr>
                            <td class="totals-label">TOTAL TTC</td>
                            <td class="totals-value">{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr></table>
                    </div>

                    {{-- En lettres --}}
                    <div class="amount-words">{{ $amountInWords }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ==================== MENTIONS LEGALES ==================== --}}
<div class="legal-section">
    @include('sales.templates._legal-mentions')
</div>

{{-- ==================== NOTES ==================== --}}
@if($sale->notes)
<div class="notes-box">
    <span class="notes-title">Note :</span> {{ $sale->notes }}
</div>
@endif

{{-- ==================== QR CODE ==================== --}}
@if(!empty($verificationUrl) && !empty($verificationCode))
<div class="verification-section">
    <table class="verification-table">
        <tr>
            <td class="qr-cell">
                @if($qrBase64)
                    <div class="qr-box">
                        <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" alt="QR Code">
                    </div>
                @endif
            </td>
            <td class="verification-info">
                <div class="verification-title">Verification d'authenticite</div>
                <div class="verification-text">
                    Scannez le QR code ou visitez l'URL ci-dessous pour verifier l'authenticite de ce document.<br>
                    <span style="word-break: break-all;">{{ $verificationUrl }}</span>
                </div>
                <span class="verification-code">{{ $verificationCode }}</span>
            </td>
        </tr>
    </table>
</div>
@endif

{{-- ==================== FOOTER ==================== --}}
<div class="footer">
    @if($company->footer_text)
        {{ $company->footer_text }}
    @else
        Merci pour votre confiance &middot; {{ $company->name }} &middot; {{ $company->phone ?? '' }} &middot; {{ $company->email ?? '' }}
    @endif
    <div class="footer-sub">TVA Intracommunautaire : {{ $company->tax_number ?? 'N/A' }}</div>
</div>

</body>
</html>

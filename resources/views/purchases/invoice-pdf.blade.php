<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bon d'achat {{ $purchase->invoice_number }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.5;
            padding: 20mm 18mm 25mm 18mm;
        }

        /* ===== BANDE TYPE DOCUMENT ===== */
        .doc-type-band {
            background-color: #7c3aed;
            padding: 8px 20px;
            margin-bottom: 0;
            border-radius: 6px 6px 0 0;
        }

        .doc-type-band-inner {
            width: 100%;
        }

        .doc-type-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #ddd6fe;
        }

        .doc-type-name {
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1px;
        }

        .doc-type-right {
            text-align: right;
        }

        .doc-type-number {
            font-size: 22px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1px;
        }

        .doc-type-date {
            font-size: 9px;
            color: #c4b5fd;
            margin-top: 2px;
        }

        /* ===== HEADER ===== */
        .header {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-top: none;
            padding: 16px 20px;
            margin-bottom: 18px;
            border-radius: 0 0 6px 6px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .company-details {
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.6;
            margin-top: 4px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }

        .status-completed { background-color: #dcfce7; color: #166534; }
        .status-pending   { background-color: #fef9c3; color: #854d0e; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }

        .logo {
            max-height: 50px;
            max-width: 120px;
            margin-bottom: 6px;
        }

        /* ===== DIVIDER ===== */
        .divider {
            border: none;
            border-top: 2px solid #7c3aed;
            margin: 0 0 18px 0;
            opacity: 0.25;
        }

        /* ===== INFO CARDS ===== */
        .info-section { margin-bottom: 18px; }

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
            border-radius: 6px;
            padding: 12px;
        }

        .info-card-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            color: #7c3aed;
            letter-spacing: 1px;
            padding-bottom: 6px;
            border-bottom: 1.5px solid #ede9fe;
            margin-bottom: 8px;
        }

        .info-card-name {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .info-card-text {
            font-size: 9px;
            color: #64748b;
            line-height: 1.6;
        }

        /* ===== SECTION TITLE ===== */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1.5px solid #ede9fe;
        }

        /* ===== ITEMS TABLE ===== */
        .items-section { margin-bottom: 18px; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table thead tr { background-color: #7c3aed; }

        .items-table thead th {
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 9px 8px;
            text-align: left;
            letter-spacing: 0.3px;
        }

        .items-table thead th.text-right  { text-align: right; }
        .items-table thead th.text-center { text-align: center; }

        .items-table tbody tr { border-bottom: 1px solid #f1f5f9; }
        .items-table tbody tr:nth-child(even) { background-color: #fafafa; }

        .items-table tbody td {
            padding: 9px 8px;
            font-size: 9.5px;
            vertical-align: middle;
        }

        .items-table tbody td.text-right  { text-align: right; }
        .items-table tbody td.text-center { text-align: center; }

        .product-name { font-weight: 600; color: #1e293b; }
        .text-muted   { color: #64748b; }

        .discount-line {
            font-size: 8px;
            color: #10b981;
            margin-top: 2px;
        }

        /* ===== TOTALS ===== */
        .totals-section { margin-bottom: 18px; }

        .totals-wrapper { width: 100%; }
        .totals-wrapper td.spacer  { width: 52%; }
        .totals-wrapper td.totals  { width: 48%; }

        .totals-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
        }

        .totals-row {
            padding: 7px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .totals-row:last-child { border-bottom: none; }

        .totals-row-table { width: 100%; }

        .totals-label { color: #64748b; font-size: 9.5px; }
        .totals-value { text-align: right; font-weight: 600; font-size: 9.5px; color: #1e293b; }
        .totals-value.discount { color: #10b981; }

        .grand-total {
            background-color: #7c3aed;
            color: #ffffff;
            padding: 11px 12px;
        }

        .grand-total .totals-label {
            color: #ddd6fe;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }

        .grand-total .totals-value {
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
        }

        .amount-words {
            padding: 7px 12px;
            background-color: #f8fafc;
            font-size: 8px;
            font-style: italic;
            color: #64748b;
            border-top: 1px dashed #cbd5e1;
        }

        /* ===== NOTES ===== */
        .notes-box {
            background-color: #fffbeb;
            border-left: 3px solid #f59e0b;
            border-radius: 4px;
            padding: 9px 12px;
            margin-bottom: 14px;
            font-size: 9px;
            color: #78350f;
        }

        /* ===== QR VERIFICATION ===== */
        .verification-section {
            background-color: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        .verification-table { width: 100%; }

        .qr-cell { width: 75px; vertical-align: middle; }

        .qr-box {
            background-color: #ffffff;
            padding: 4px;
            border-radius: 4px;
            display: inline-block;
        }

        .qr-box img { width: 60px; height: 60px; }

        .verification-info { padding-left: 12px; vertical-align: middle; }

        .verification-title {
            font-size: 9.5px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .verification-text {
            font-size: 8px;
            color: #64748b;
            line-height: 1.4;
        }

        .verification-code {
            display: inline-block;
            font-family: monospace;
            background-color: #7c3aed;
            color: #ffffff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            margin-top: 4px;
            letter-spacing: 1px;
        }

        /* ===== FOOTER ===== */
        .footer {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 8px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
@php
    $currency        = $company->currency ?? 'EUR';
    $status          = $purchase->status;
    $statusClass     = 'status-' . ($status ?: 'pending');
    $discountPercent = $purchase->discount_percent ?? 0;
    $totalHt         = $purchase->total_ht ?? $purchase->items->sum('total_price_ht');
    $totalVat        = $purchase->total_vat ?? $purchase->items->sum('vat_amount');
    $grandTotal      = $purchase->total ?? ($totalHt + $totalVat);
    $effectiveVatRate = $totalHt > 0 ? round(($totalVat / $totalHt) * 100, 1) : 0;
    $discountAmount  = $grandTotal > 0 ? ($purchase->items->sum('total_price') * ($discountPercent / 100)) : 0;

    $statusLabels = ['completed' => 'Terminé', 'pending' => 'En attente', 'cancelled' => 'Annulé'];

    function amountToWordsFrPurchasePdf($number, $currency = 'EUR') {
        $fmt     = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        $euros   = floor($number);
        $centimes = round(($number - $euros) * 100);
        $units   = [
            'EUR'  => ['euro', 'euros', 'centime', 'centimes'],
            'FCFA' => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
            'XOF'  => ['franc CFA', 'francs CFA', 'centime', 'centimes'],
            'USD'  => ['dollar', 'dollars', 'cent', 'cents'],
            'GBP'  => ['livre sterling', 'livres sterling', 'penny', 'pence'],
        ];
        $u = $units[$currency] ?? ['unité', 'unités', 'centime', 'centimes'];
        $text = ucfirst($fmt->format($euros)) . ' ' . ($euros == 1 ? $u[0] : $u[1]);
        if ($centimes > 0) {
            $text .= ' et ' . $fmt->format($centimes) . ' ' . ($centimes == 1 ? $u[2] : $u[3]);
        }
        return $text;
    }
@endphp

<!-- BANDE TYPE DE DOCUMENT -->
<div class="doc-type-band">
    <table class="doc-type-band-inner" style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="vertical-align:middle;">
                <div class="doc-type-label">Document commercial</div>
                <div class="doc-type-name">Bon d'achat</div>
            </td>
            <td class="doc-type-right" style="vertical-align:middle;">
                <div style="font-size:9px;color:#c4b5fd;margin-bottom:2px;">Numéro</div>
                <div class="doc-type-number">{{ $purchase->invoice_number }}</div>
                <div class="doc-type-date">Émis le {{ $purchase->created_at->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

<!-- HEADER ENTREPRISE -->
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width:60%;vertical-align:middle;">
                @if($company->logo_path)
                    <img src="{{ public_path('storage/' . $company->logo_path) }}" alt="{{ $company->name }}" class="logo">
                @endif
                <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
                <div class="company-details">
                    @if($company->address){{ $company->address }}<br>@endif
                    @if($company->zip_code || $company->city){{ $company->zip_code }} {{ $company->city }}<br>@endif
                    @if($company->phone)Tél : {{ $company->phone }}@endif
                    @if($company->email) &nbsp;·&nbsp; {{ $company->email }}@endif
                    @if($company->tax_number)<br>N° TVA : {{ $company->tax_number }}@endif
                    @if($company->siret)<br>SIRET : {{ $company->siret }}@endif
                </div>
            </td>
            <td style="text-align:right;vertical-align:middle;">
                <span class="status-badge {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? ucfirst($status) }}
                </span>
                @if($purchase->warehouse)
                    <div style="margin-top:8px;font-size:9px;color:#64748b;">
                        Livraison : <strong>{{ $purchase->warehouse->name }}</strong>
                    </div>
                @endif
                @if($purchase->payment_method)
                    <div style="margin-top:4px;font-size:9px;color:#64748b;">
                        Paiement : <strong>{{ ucfirst(str_replace('_', ' ', $purchase->payment_method)) }}</strong>
                    </div>
                @endif
            </td>
        </tr>
    </table>
</div>

<!-- FOURNISSEUR -->
<div class="info-section">
    <table class="info-table">
        <tr>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Fournisseur</div>
                    <div class="info-card-name">{{ $purchase->supplier->name ?? 'Fournisseur non défini' }}</div>
                    <div class="info-card-text">
                        @if(optional($purchase->supplier)->siret)SIRET : {{ $purchase->supplier->siret }}<br>@endif
                        @if(optional($purchase->supplier)->address){{ $purchase->supplier->address }}<br>@endif
                        @if(optional($purchase->supplier)->zip_code || optional($purchase->supplier)->city){{ optional($purchase->supplier)->zip_code }} {{ optional($purchase->supplier)->city }}<br>@endif
                        @if(optional($purchase->supplier)->phone)Tél : {{ $purchase->supplier->phone }}<br>@endif
                        @if(optional($purchase->supplier)->email){{ $purchase->supplier->email }}@endif
                    </div>
                </div>
            </td>
            <td>
                <div class="info-card">
                    <div class="info-card-title">Récapitulatif</div>
                    <div class="info-card-text" style="margin-top:4px;">
                        <table style="width:100%;border-collapse:collapse;">
                            <tr>
                                <td style="color:#64748b;padding-bottom:4px;">Date commande</td>
                                <td style="text-align:right;font-weight:600;padding-bottom:4px;">{{ $purchase->created_at->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <td style="color:#64748b;padding-bottom:4px;">Référence</td>
                                <td style="text-align:right;font-weight:600;padding-bottom:4px;">{{ $purchase->invoice_number }}</td>
                            </tr>
                            @if($discountPercent > 0)
                            <tr>
                                <td style="color:#64748b;padding-bottom:4px;">Remise globale</td>
                                <td style="text-align:right;font-weight:600;color:#10b981;padding-bottom:4px;">{{ number_format($discountPercent, 1) }}%</td>
                            </tr>
                            @endif
                            <tr>
                                <td style="color:#64748b;">Nombre d'articles</td>
                                <td style="text-align:right;font-weight:600;">{{ $purchase->items->count() }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- ARTICLES -->
<div class="items-section">
    <div class="section-title">Articles commandés</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:38%;">Désignation</th>
                <th style="width:8%;" class="text-center">Qté</th>
                <th style="width:15%;" class="text-right">P.U. HT</th>
                <th style="width:10%;" class="text-center">Remise</th>
                <th style="width:10%;" class="text-center">TVA</th>
                <th style="width:19%;" class="text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchase->items as $item)
                <tr>
                    <td>
                        <span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span>
                        @if(!empty($item->product->code))
                            <br><span class="text-muted" style="font-size:8px;">Réf. {{ $item->product->code }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', ''), '0'), ',') }}</td>
                    <td class="text-right text-muted">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                    <td class="text-center">
                        @if(($item->discount_percent ?? 0) > 0)
                            <span style="color:#10b981;font-weight:600;">{{ number_format($item->discount_percent, 1) }}%</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->vat_rate ?? 0, 2, ',', ''), '0'), ',') }}%</td>
                    <td class="text-right"><strong>{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:20px;color:#94a3b8;">
                        Aucun article dans ce bon d'achat
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- TOTAUX -->
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
                                <td class="totals-label">Remise globale ({{ number_format($discountPercent, 1) }}%)</td>
                                <td class="totals-value discount">− {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif
                    <div class="totals-row">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TVA ({{ rtrim(rtrim(number_format($effectiveVatRate, 2, ',', ''), '0'), ',') }}%)</td>
                                <td class="totals-value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="grand-total">
                        <table class="totals-row-table">
                            <tr>
                                <td class="totals-label">TOTAL TTC</td>
                                <td class="totals-value">{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="amount-words">
                        {{ amountToWordsFrPurchasePdf($grandTotal, $currency) }}
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- NOTES -->
@if($purchase->notes)
<div class="notes-box">
    <strong>Note :</strong> {{ $purchase->notes }}
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
                            $qrSvg    = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(60)->generate($verificationUrl);
                            $qrBase64 = base64_encode($qrSvg);
                        } catch (\Throwable $e) {
                            $qrBase64 = null;
                        }
                    @endphp
                    @if($qrBase64)
                        <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" alt="QR Code">
                    @else
                        <div style="width:60px;height:60px;background:#f1f5f9;border-radius:4px;"></div>
                    @endif
                </div>
            </td>
            <td class="verification-info">
                <div class="verification-title">Vérification d'authenticité</div>
                <div class="verification-text">
                    Scannez le QR code pour vérifier ce document.<br>
                    <span style="font-size:7px;word-break:break-all;color:#94a3b8;">{{ $verificationUrl }}</span>
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
        {{ $company->name }}
        @if($company->phone) &nbsp;·&nbsp; {{ $company->phone }}@endif
        @if($company->email) &nbsp;·&nbsp; {{ $company->email }}@endif
        <br>Document généré automatiquement &nbsp;·&nbsp; {{ now()->format('d/m/Y à H:i') }}
    @endif
</div>

</body>
</html>

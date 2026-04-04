<?php require base_path('resources/views/sales/templates/_invoice_data.php'); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $docTitle }}</title>
    <style>
        @page { size: A4; margin: 25mm 22mm 25mm 22mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #334155;
            line-height: 1.6;
        }

        /* ===== HEADER ===== */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 40px; max-width: 90px; margin-bottom: 8px; }
        .company-name { font-size: 18px; font-weight: bold; color: #0f172a; margin-bottom: 2px; }
        .company-details { font-size: 9px; color: #94a3b8; line-height: 1.5; }
        .invoice-label-box { text-align: right; }
        .invoice-word { font-size: 32px; font-weight: 300; color: #cbd5e1; text-transform: uppercase; letter-spacing: 6px; }
        .invoice-num { font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 4px; }
        .invoice-date { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        /* ===== STATUS ===== */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-top: 6px; }
        .badge-completed { background-color: #dcfce7; color: #166534; }
        .badge-pending { background-color: #fef9c3; color: #854d0e; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }

        /* ===== INFO ===== */
        .info-section { width: 100%; border-collapse: collapse; margin-bottom: 30px; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding: 0; }
        .info-section td { padding: 16px 0; vertical-align: top; width: 50%; }
        .info-section td:first-child { padding-right: 20px; }
        .info-section td:last-child { text-align: right; }
        .info-label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-bottom: 6px; }
        .info-name { font-size: 12px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .info-text { font-size: 9px; color: #64748b; }
        .info-highlight { font-weight: bold; color: #4f46e5; }

        /* ===== TABLE ===== */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table thead tr { border-bottom: 1px solid #e2e8f0; }
        .items-table thead th {
            font-size: 8px; font-weight: bold; text-transform: uppercase;
            color: #94a3b8; padding: 0 8px 10px 8px; text-align: left; letter-spacing: 0.5px;
        }
        .items-table thead th.text-right { text-align: right; }
        .items-table thead th.text-center { text-align: center; }
        .items-table tbody tr { border-bottom: 1px solid #f1f5f9; }
        .items-table tbody td { padding: 14px 8px; font-size: 10px; vertical-align: middle; }
        .items-table tbody td.text-right { text-align: right; }
        .items-table tbody td.text-center { text-align: center; }
        .product-name { font-weight: 600; color: #0f172a; }

        /* ===== TOTALS ===== */
        .totals-outer { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .totals-outer td.spacer { width: 55%; }
        .totals-outer td.content { width: 45%; }
        .totals-row { width: 100%; border-collapse: collapse; }
        .totals-row td { padding: 6px 0; font-size: 10px; }
        .totals-row td:first-child { color: #64748b; }
        .totals-row td:last-child { text-align: right; font-weight: 600; color: #0f172a; }
        .totals-row td.discount { color: #10b981; }
        .total-grand { border-top: 2px solid #0f172a; }
        .total-grand td { padding-top: 10px; font-size: 16px; font-weight: bold; color: #0f172a; }
        .amount-words { font-size: 8px; font-style: italic; color: #94a3b8; margin-top: 6px; text-align: right; }

        /* ===== LEGAL ===== */
        .legal-box { font-size: 8px; color: #94a3b8; margin-bottom: 15px; padding: 8px 0; border-top: 1px solid #f1f5f9; }
        .legal-box strong { color: #64748b; }
        .legal-row { margin-bottom: 3px; }

        /* ===== NOTES ===== */
        .notes-box { background-color: #fefce8; border: 1px solid #fde68a; border-radius: 4px; padding: 10px; margin-bottom: 15px; font-size: 9px; }
        .notes-title { font-weight: bold; color: #92400e; }

        /* ===== QR ===== */
        .qr-section { margin-bottom: 15px; }
        .qr-table { width: 100%; border-collapse: collapse; }
        .qr-cell { width: 75px; vertical-align: top; }
        .qr-cell img { width: 60px; height: 60px; }
        .qr-info { padding-left: 12px; vertical-align: middle; }
        .qr-title { font-size: 9px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .qr-text { font-size: 7px; color: #94a3b8; }
        .qr-code { display: inline-block; font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 8px; color: #334155; margin-top: 4px; }

        /* ===== FOOTER ===== */
        .footer { text-align: center; padding-top: 10px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 8px; }
    </style>
</head>
<body>

<!-- HEADER -->
<table class="header-table">
    <tr>
        <td style="width: 55%;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="{{ $company->name }}" class="logo">
            @else
                <div style="width: 40px; height: 40px; background: #4f46e5; border-radius: 4px; margin-bottom: 8px;"></div>
            @endif
            <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
            <div class="company-details">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->phone)Tél: {{ $company->phone }}@endif
                @if($company->email) · {{ $company->email }}@endif
                @if($company->tax_number)<br>TVA: {{ $company->tax_number }}@endif
                @if($company->siret)<br>SIRET: {{ $company->siret }}@endif
            </div>
        </td>
        <td class="invoice-label-box">
            <div class="invoice-word">{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture' }}</div>
            <div class="invoice-num"># {{ $sale->invoice_number }}</div>
            <div class="invoice-date">{{ $sale->created_at->format('d M Y') }}</div>
            <span class="badge badge-{{ $status ?: 'pending' }}">{{ $statusLabel }}</span>
        </td>
    </tr>
</table>

<!-- INFO -->
<table class="info-section">
    <tr>
        <td>
            <div class="info-label">Facturé à</div>
            <div class="info-name">{{ $sale->customer->name ?? 'Client non défini' }}</div>
            <div class="info-text">
                @if(optional($sale->customer)->registration_number)SIREN: {{ $sale->customer->registration_number }}<br>@endif
                @if(optional($sale->customer)->siret)SIRET: {{ $sale->customer->siret }}<br>@endif
                @if($customerTaxNumber)TVA Intra: {{ $customerTaxNumber }}<br>@endif
                @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                @if(optional($sale->customer)->phone)Tél: {{ $sale->customer->phone }}<br>@endif
                @if(optional($sale->customer)->email){{ $sale->customer->email }}@endif
            </div>
        </td>
        <td>
            <div class="info-label">Paiement attendu</div>
            <div class="info-text">
                <span class="info-highlight">{{ ucfirst($sale->payment_method ?? 'Virement bancaire') }}</span><br>
                Échéance : {{ $dueDate instanceof \Carbon\Carbon ? $dueDate->format('d/m/Y') : \Carbon\Carbon::parse($dueDate)->format('d/m/Y') }}<br>
                Réf: {{ $sale->reference ?? $sale->invoice_number }}
                @if($sale->warehouse)<br>Entrepôt: {{ $sale->warehouse->name }}@endif
            </div>
        </td>
    </tr>
</table>

<!-- ITEMS -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 42%;">Désignation</th>
            <th style="width: 10%;" class="text-center">Qté</th>
            <th style="width: 18%;" class="text-right">Prix Unit. HT</th>
            <th style="width: 10%;" class="text-center">TVA</th>
            <th style="width: 20%;" class="text-right">Total HT</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sale->items as $item)
            <tr>
                <td><span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span></td>
                <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', ' '), '0'), ',') }}</td>
                <td class="text-right" style="color: #64748b;">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format($item->vat_rate ?? 0, 2, ',', ' '), '0'), ',') }}%</td>
                <td class="text-right" style="font-weight: bold;">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">Aucun article</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- TOTALS -->
<table class="totals-outer">
    <tr>
        <td class="spacer"></td>
        <td class="content">
            <table class="totals-row">
                <tr>
                    <td>Total HT</td>
                    <td>{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            </table>
            @if($discountAmount > 0)
            <table class="totals-row">
                <tr>
                    <td>Remise ({{ number_format($discountPercent, 1) }}%)</td>
                    <td class="discount">- {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            </table>
            @endif
            @if($hasMultipleVatRates)
                @foreach($vatBreakdown as $vat)
                <table class="totals-row">
                    <tr>
                        <td>TVA {{ rtrim(rtrim(number_format($vat['rate'], 2, ',', ' '), '0'), ',') }}% (base {{ number_format($vat['base'], 2, ',', ' ') }})</td>
                        <td>{{ number_format($vat['amount'], 2, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                </table>
                @endforeach
            @else
            <table class="totals-row">
                <tr>
                    <td>TVA ({{ rtrim(rtrim(number_format($vatBreakdown[0]['rate'] ?? 20, 2, ',', ' '), '0'), ',') }}%)</td>
                    <td>{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            </table>
            @endif
            <table class="totals-row total-grand">
                <tr>
                    <td>TOTAL TTC</td>
                    <td>{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            </table>
            <div class="amount-words">{{ $amountInWords }}</div>
        </td>
    </tr>
</table>

<!-- MENTIONS LÉGALES -->
<div class="legal-box">
    @include('sales.templates._legal-mentions')
</div>

<!-- NOTES -->
@if($sale->notes)
<div class="notes-box">
    <span class="notes-title">Note :</span> {{ $sale->notes }}
</div>
@endif

<!-- QR VERIFICATION -->
@if(!empty($verificationUrl) && !empty($verificationCode))
<div class="qr-section">
    <table class="qr-table">
        <tr>
            <td class="qr-cell">
                @if($qrBase64)
                    <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" alt="QR">
                @endif
            </td>
            <td class="qr-info">
                <div class="qr-title">Vérification d'authenticité</div>
                <div class="qr-text">
                    Scannez le QR code ou visitez :<br>
                    <span style="word-break: break-all;">{{ $verificationUrl }}</span>
                </div>
                <span class="qr-code">{{ $verificationCode }}</span>
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
        {{ $company->name }} · {{ $company->phone ?? '' }} · {{ $company->email ?? '' }}
    @endif
</div>

</body>
</html>

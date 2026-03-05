@include('sales.templates._invoice-data')
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

        /* ===== DECORATIVE CIRCLES (simulated with positioned divs) ===== */
        .deco-top { position: absolute; top: -60px; right: -60px; width: 200px; height: 200px; background: #f5f3ff; border-radius: 100px; }
        .deco-bottom { position: absolute; bottom: -50px; left: -50px; width: 160px; height: 160px; background: #eef2ff; border-radius: 80px; }

        /* ===== HEADER ===== */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 35px; }
        .header-table td { vertical-align: bottom; }
        .company-name { font-size: 22px; font-weight: bold; color: #312e81; }
        .company-details { font-size: 8px; color: #a5b4fc; margin-top: 4px; }
        .invoice-badge {
            display: inline-block; background-color: #4f46e5; color: #ffffff;
            padding: 8px 20px; border-radius: 20px; font-size: 12px; font-weight: bold;
        }

        /* ===== STATUS ===== */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-left: 8px; }
        .badge-completed { background-color: #dcfce7; color: #166534; }
        .badge-pending { background-color: #fef9c3; color: #854d0e; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }

        /* ===== INFO BLOCKS ===== */
        .info-section { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .info-section td { width: 50%; vertical-align: top; padding: 0 10px 0 0; }
        .info-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px; }
        .info-label-fuchsia { color: #c026d3; }
        .info-label-indigo { color: #818cf8; }
        .info-name { font-size: 16px; font-weight: bold; color: #1e293b; margin-bottom: 4px; }
        .info-text { font-size: 9px; color: #64748b; }

        /* ===== ITEMS ===== */
        .item-card {
            background-color: #faf5ff; border: 1px solid #f3e8ff; border-radius: 8px;
            padding: 12px 14px; margin-bottom: 8px;
        }
        .item-table { width: 100%; border-collapse: collapse; }
        .item-table td { vertical-align: middle; }
        .item-name { font-weight: bold; color: #1e293b; font-size: 11px; }
        .item-detail { font-size: 8px; color: #a78bfa; margin-top: 2px; }
        .item-total { text-align: right; font-weight: bold; font-size: 14px; color: #4f46e5; }

        /* ===== TOTALS ===== */
        .totals-outer { width: 100%; border-collapse: collapse; margin-bottom: 20px; margin-top: 20px; }
        .totals-outer td.spacer { width: 50%; }
        .totals-outer td.content { width: 50%; }
        .totals-box {
            background-color: #4f46e5; color: #ffffff; border-radius: 12px;
            padding: 16px 20px; overflow: hidden;
        }
        .totals-row { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .totals-row td { padding: 3px 0; font-size: 10px; }
        .totals-row td:first-child { color: rgba(255,255,255,0.7); }
        .totals-row td:last-child { text-align: right; color: rgba(255,255,255,0.8); font-weight: 600; }
        .totals-row td.discount { color: #86efac; }
        .total-grand { border-top: 1px solid rgba(255,255,255,0.3); margin-top: 8px; padding-top: 8px; }
        .total-grand td { font-size: 20px; font-weight: bold; color: #ffffff; padding-top: 8px; }
        .amount-words { font-size: 8px; font-style: italic; color: rgba(255,255,255,0.5); margin-top: 8px; text-align: right; }

        /* ===== LEGAL ===== */
        .legal-box { font-size: 8px; color: #a78bfa; margin-bottom: 15px; padding: 8px 0; border-top: 1px solid #ede9fe; }
        .legal-box strong { color: #7c3aed; }

        /* ===== NOTES ===== */
        .notes-box { background-color: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 10px; margin-bottom: 15px; font-size: 9px; }
        .notes-title { font-weight: bold; color: #7c3aed; }

        /* ===== QR ===== */
        .qr-section { background-color: #f5f3ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 12px; margin-bottom: 15px; }
        .qr-table { width: 100%; border-collapse: collapse; }
        .qr-cell { width: 75px; vertical-align: top; }
        .qr-cell img { width: 60px; height: 60px; }
        .qr-info { padding-left: 12px; vertical-align: middle; }
        .qr-title { font-size: 9px; font-weight: bold; color: #4f46e5; margin-bottom: 3px; }
        .qr-text { font-size: 7px; color: #a78bfa; }
        .qr-code { display: inline-block; font-family: monospace; background: #4f46e5; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 8px; margin-top: 4px; }

        /* ===== FOOTER ===== */
        .footer { text-align: center; padding-top: 10px; border-top: 1px solid #ede9fe; color: #a78bfa; font-size: 8px; }
    </style>
</head>
<body>

<!-- HEADER -->
<table class="header-table">
    <tr>
        <td style="width: 60%;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="{{ $company->name }}" style="max-height: 40px; margin-bottom: 8px;">
            @endif
            <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
            <div class="company-details">
                @if($company->address){{ $company->address }}@endif
                @if($company->phone) · {{ $company->phone }}@endif
                @if($company->email) · {{ $company->email }}@endif
            </div>
        </td>
        <td style="text-align: right;">
            <span class="invoice-badge">
                {{ $sale->type === 'credit_note' ? 'AVOIR' : 'FACTURE' }} #{{ last(explode('-', $sale->invoice_number)) }}
            </span>
            <span class="badge badge-{{ $status ?: 'pending' }}">{{ $statusLabel }}</span>
            <div style="font-size: 9px; color: #a78bfa; margin-top: 6px;">{{ $sale->created_at->format('d M Y') }}</div>
        </td>
    </tr>
</table>

<!-- INFO -->
<table class="info-section">
    <tr>
        <td>
            <div class="info-label info-label-fuchsia">Client</div>
            <div class="info-name">{{ $sale->customer->name ?? 'Client non défini' }}</div>
            <div class="info-text">
                @if(optional($sale->customer)->registration_number)SIREN: {{ $sale->customer->registration_number }}<br>@endif
                @if(optional($sale->customer)->siret)SIRET: {{ $sale->customer->siret }}<br>@endif
                @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                @if(optional($sale->customer)->phone)Tél: {{ $sale->customer->phone }}@endif
            </div>
        </td>
        <td>
            <div class="info-label info-label-indigo">Échéance</div>
            <div class="info-name">{{ $sale->created_at->addDays(30)->format('d/m/Y') }}</div>
            <div class="info-text">
                Émis le {{ $sale->created_at->format('d/m/Y') }}<br>
                Mode: {{ ucfirst($sale->payment_method ?? 'Non spécifié') }}<br>
                Réf: {{ $sale->reference ?? $sale->invoice_number }}
                @if($sale->warehouse)<br>Entrepôt: {{ $sale->warehouse->name }}@endif
            </div>
        </td>
    </tr>
</table>

<!-- ITEMS (Card Style) -->
@forelse($sale->items as $item)
<div class="item-card">
    <table class="item-table">
        <tr>
            <td style="width: 70%;">
                <div class="item-name">{{ $item->product->name ?? 'Produit supprimé' }}</div>
                <div class="item-detail">
                    {{ rtrim(rtrim(number_format($item->quantity, 2, ',', ' '), '0'), ',') }}
                    × {{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }} HT
                    · TVA {{ rtrim(rtrim(number_format($item->vat_rate ?? 0, 2, ',', ' '), '0'), ',') }}%
                </div>
            </td>
            <td class="item-total">
                {{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}
            </td>
        </tr>
    </table>
</div>
@empty
<div class="item-card" style="text-align: center; color: #a78bfa;">Aucun article dans cette facture</div>
@endforelse

<!-- TOTALS -->
<table class="totals-outer">
    <tr>
        <td class="spacer"></td>
        <td class="content">
            <div class="totals-box">
                <table class="totals-row">
                    <tr><td>Total HT</td><td>{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</td></tr>
                </table>
                @if($discountAmount > 0)
                <table class="totals-row">
                    <tr><td>Remise ({{ number_format($discountPercent, 1) }}%)</td><td class="discount">- {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</td></tr>
                </table>
                @endif
                @if($hasMultipleVatRates)
                    @foreach($vatBreakdown as $vat)
                    <table class="totals-row">
                        <tr>
                            <td>TVA {{ rtrim(rtrim(number_format($vat['rate'], 2, ',', ' '), '0'), ',') }}%</td>
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
                    <tr><td>Total</td><td>{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</td></tr>
                </table>
                <div class="amount-words">{{ $amountInWords }}</div>
            </div>
        </td>
    </tr>
</table>

<!-- MENTIONS LÉGALES -->
<div class="legal-box">
    @if($isVatOnDebits)<strong>TVA acquittée sur les débits</strong><br>@endif
    @if($natureOp)Nature de l'opération : {{ $natureOpLabels[$natureOp] ?? $natureOp }}<br>@endif
    @if($deliveryAddr)Adresse de livraison : {{ $deliveryAddr }}@endif
    @if($company->tax_number)<br>TVA Intracommunautaire : {{ $company->tax_number }}@endif
    @if($company->siret)<br>SIRET : {{ $company->siret }}@endif
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
        {{ $company->name }} · Merci pour votre confiance
    @endif
</div>

</body>
</html>

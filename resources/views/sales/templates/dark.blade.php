@include('sales.templates._invoice-data')
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $docTitle }}</title>
    <style>
        @page { size: A4; margin: 20mm 18mm 20mm 18mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            font-size: 10px;
            color: #cbd5e1;
            line-height: 1.6;
            background-color: #0f172a;
        }

        .page-border {
            border: 4px solid rgba(99, 102, 241, 0.25);
            padding: 24px;
        }

        /* ===== HEADER ===== */
        .header-table { width: 100%; border-collapse: collapse; border-bottom: 1px solid #1e293b; padding-bottom: 24px; margin-bottom: 24px; }
        .header-table td { vertical-align: top; }
        .brand { color: #818cf8; font-weight: bold; font-size: 20px; margin-bottom: 8px; font-style: italic; }
        .company-details { font-size: 9px; color: #475569; line-height: 1.5; max-width: 200px; }
        .logo { max-height: 40px; max-width: 80px; margin-bottom: 8px; border: 1px solid #1e293b; padding: 3px; }

        .invoice-title-right { text-align: right; }
        .invoice-word { font-size: 32px; font-weight: bold; color: #ffffff; letter-spacing: 4px; }
        .invoice-num { color: #6366f1; font-weight: bold; font-size: 11px; margin-top: 4px; }
        .invoice-date { font-size: 9px; color: #475569; margin-top: 4px; }

        /* ===== STATUS ===== */
        .badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-top: 8px; }
        .badge-completed { background-color: #064e3b; color: #34d399; }
        .badge-pending { background-color: #78350f; color: #fbbf24; }
        .badge-cancelled { background-color: #7f1d1d; color: #f87171; }

        /* ===== INFO ===== */
        .info-section { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .info-section td { width: 50%; vertical-align: top; }
        .info-comment { color: #6366f1; font-weight: bold; font-size: 8px; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px; }
        .info-name { color: #ffffff; font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .info-text { font-size: 9px; color: #64748b; }
        .info-right { text-align: right; }

        /* ===== TABLE ===== */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; border: 1px solid #1e293b; border-radius: 4px; }
        .items-table thead tr { background-color: rgba(30, 41, 59, 0.5); }
        .items-table thead th {
            color: #818cf8; font-size: 8px; font-weight: bold; text-transform: uppercase;
            padding: 10px 12px; text-align: left; letter-spacing: 0.5px;
        }
        .items-table thead th.text-right { text-align: right; }
        .items-table thead th.text-center { text-align: center; }
        .items-table thead th.text-white { color: #ffffff; }
        .items-table tbody tr { border-bottom: 1px solid #1e293b; }
        .items-table tbody td { padding: 10px 12px; font-size: 10px; vertical-align: middle; }
        .items-table tbody td.text-right { text-align: right; }
        .items-table tbody td.text-center { text-align: center; }
        .items-table tbody td.text-white { color: #ffffff; font-weight: bold; }

        /* ===== TOTALS ===== */
        .totals-section { text-align: right; margin-bottom: 20px; }
        .totals-line { font-size: 10px; margin-bottom: 4px; }
        .totals-line span { color: #ffffff; }
        .grand-total-box {
            display: inline-block; background-color: #6366f1; color: #0f172a;
            padding: 10px 20px; font-size: 18px; font-weight: bold; margin-top: 10px;
        }
        .amount-words { font-size: 8px; color: #475569; font-style: italic; margin-top: 6px; }

        /* ===== LEGAL ===== */
        .legal-box { font-size: 8px; color: #475569; margin-bottom: 15px; padding: 8px 0; border-top: 1px solid #1e293b; }
        .legal-box strong { color: #94a3b8; }

        /* ===== NOTES ===== */
        .notes-box { background-color: #1e293b; border: 1px solid #334155; border-radius: 4px; padding: 10px; margin-bottom: 15px; font-size: 9px; color: #94a3b8; }
        .notes-title { font-weight: bold; color: #818cf8; }

        /* ===== QR ===== */
        .qr-section { background-color: #1e293b; border: 1px solid #334155; border-radius: 4px; padding: 12px; margin-bottom: 15px; }
        .qr-table { width: 100%; border-collapse: collapse; }
        .qr-cell { width: 75px; vertical-align: top; }
        .qr-cell img { width: 60px; height: 60px; background: #ffffff; padding: 4px; }
        .qr-info { padding-left: 12px; vertical-align: middle; }
        .qr-title { font-size: 9px; font-weight: bold; color: #818cf8; margin-bottom: 3px; }
        .qr-text { font-size: 7px; color: #475569; }
        .qr-code { display: inline-block; font-family: monospace; background: #6366f1; color: #0f172a; padding: 2px 8px; border-radius: 2px; font-size: 8px; margin-top: 4px; }

        /* ===== FOOTER ===== */
        .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #1e293b; color: #334155; font-size: 7px; }
    </style>
</head>
<body>
<div class="page-border">

<!-- HEADER -->
<table class="header-table">
    <tr>
        <td style="width: 50%;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="{{ $company->name }}" class="logo">
            @else
                <div class="brand">&lt;{{ strtoupper(Str::limit($company->name ?? 'FREQ', 8, '')) }}/&gt;</div>
            @endif
            <div class="company-details">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->phone){{ $company->phone }}@endif
                @if($company->email)<br>{{ $company->email }}@endif
            </div>
        </td>
        <td class="invoice-title-right">
            <div class="invoice-word">INVOICE</div>
            <div class="invoice-num">{{ $sale->invoice_number }}</div>
            <div class="invoice-date">{{ $sale->created_at->format('Y-m-d') }}</div>
            <span class="badge badge-{{ $status ?: 'pending' }}">{{ $statusLabel }}</span>
        </td>
    </tr>
</table>

<!-- INFO -->
<table class="info-section">
    <tr>
        <td>
            <div class="info-comment">// DESTINATAIRE</div>
            <div class="info-name">{{ $sale->customer->name ?? 'Client non défini' }}</div>
            <div class="info-text">
                @if(optional($sale->customer)->registration_number)SIREN: {{ $sale->customer->registration_number }}<br>@endif
                @if(optional($sale->customer)->siret)SIRET: {{ $sale->customer->siret }}<br>@endif
                @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                @if(optional($sale->customer)->phone){{ $sale->customer->phone }}@endif
            </div>
        </td>
        <td class="info-right">
            <div class="info-comment">// TERMES</div>
            <div style="color: #ffffff; font-size: 12px; font-weight: bold;">NET 30 DAYS</div>
            <div class="info-text" style="margin-top: 4px;">
                DUE: {{ $sale->created_at->addDays(30)->format('Y-m-d') }}<br>
                MODE: {{ strtoupper($sale->payment_method ?? 'VIREMENT') }}<br>
                REF: {{ $sale->reference ?? $sale->invoice_number }}
                @if($sale->warehouse)<br>DEPOT: {{ $sale->warehouse->name }}@endif
            </div>
        </td>
    </tr>
</table>

<!-- ITEMS -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width: 42%;">ITEM_DESCRIPTION</th>
            <th style="width: 10%;" class="text-center">QTY</th>
            <th style="width: 18%;" class="text-right">UNIT_PRICE</th>
            <th style="width: 10%;" class="text-center">VAT_%</th>
            <th style="width: 20%;" class="text-right text-white">TOTAL_HT</th>
        </tr>
    </thead>
    <tbody>
        @forelse($sale->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Produit supprimé' }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', ' '), '0'), ',') }}</td>
                <td class="text-right">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, '.', ',') }}</td>
                <td class="text-center">{{ rtrim(rtrim(number_format($item->vat_rate ?? 0, 2, '.', ''), '0'), '.') }}%</td>
                <td class="text-right text-white">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, '.', ',') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px; color: #334155;">// NO ITEMS</td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- TOTALS -->
<div class="totals-section">
    <div class="totals-line">SUB_TOTAL_HT: <span>{{ number_format($totalHt, 2, '.', ',') }}</span></div>
    @if($discountAmount > 0)
    <div class="totals-line">DISCOUNT_{{ number_format($discountPercent, 1) }}%: <span style="color: #34d399;">-{{ number_format($discountAmount, 2, '.', ',') }}</span></div>
    @endif
    @if($hasMultipleVatRates)
        @foreach($vatBreakdown as $vat)
        <div class="totals-line">TAX_V_{{ number_format($vat['rate'], 2, '.', '') }}: <span>{{ number_format($vat['amount'], 2, '.', ',') }}</span></div>
        @endforeach
    @else
    <div class="totals-line">TAX_V_{{ number_format($vatBreakdown[0]['rate'] ?? 20, 2, '.', '') }}: <span>{{ number_format($totalVat, 2, '.', ',') }}</span></div>
    @endif
    <div class="grand-total-box">GRAND_TOTAL: {{ number_format($grandTotal, 2, '.', ',') }} {{ $currency }}</div>
    <div class="amount-words">{{ $amountInWords }}</div>
</div>

<!-- MENTIONS LÉGALES -->
<div class="legal-box">
    @if($isVatOnDebits)<strong>TVA acquittée sur les débits</strong><br>@endif
    @if($natureOp)Nature opération : {{ $natureOpLabels[$natureOp] ?? $natureOp }}<br>@endif
    @if($deliveryAddr)Livraison : {{ $deliveryAddr }}<br>@endif
    @if($company->tax_number)VAT_ID: {{ $company->tax_number }}<br>@endif
    @if($company->siret)SIRET: {{ $company->siret }}@endif
</div>

<!-- NOTES -->
@if($sale->notes)
<div class="notes-box">
    <span class="notes-title">/* NOTE */</span> {{ $sale->notes }}
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
                <div class="qr-title">// VERIFICATION</div>
                <div class="qr-text">
                    scan_qr || visit:<br>
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
    System generated invoice. No signature required.<br>
    @if($company->footer_text){{ $company->footer_text }}@else{{ $company->name }} — {{ $company->email ?? '' }}@endif
</div>

</div>
</body>
</html>

<?php require base_path('resources/views/sales/templates/_invoice_data.php'); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $docTitle }}</title>
    <style>
        /* ===== RESET & BASE ===== */
        @page { size: A4; margin: 22mm 20mm 25mm 20mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-size: 10px; line-height: 1.5; }
        table { border-collapse: collapse; }
        .w-full { width: 100%; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-small { font-size: 8px; }
        .text-xs { font-size: 7px; }
        .mb-4 { margin-bottom: 4px; }
        .mb-8 { margin-bottom: 8px; }
        .mb-12 { margin-bottom: 12px; }
        .mb-16 { margin-bottom: 16px; }
        .mb-20 { margin-bottom: 20px; }
        .mb-24 { margin-bottom: 24px; }

        /* ===== THEME VARIABLES (overridden per template) ===== */
        @yield('styles')
    </style>
</head>
<body>
@yield('body-open')

{{-- ==================== HEADER ==================== --}}
<table class="w-full header mb-20">
    <tr>
        <td style="width: 58%; vertical-align: top;">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="{{ $company->name }}" class="logo">
            @endif
            <div class="company-name">{{ $company->name ?: 'Votre Entreprise' }}</div>
            <div class="company-subtitle">{{ $sale->type === 'credit_note' ? 'Avoir' : 'Facture de vente' }}</div>
            <div class="company-details">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->phone){{ $company->phone }}@endif
                @if($company->email) &middot; {{ $company->email }}@endif
                @if($company->tax_number)<br>TVA: {{ $company->tax_number }}@endif
                @if($company->siret)<br>SIRET: {{ $company->siret }}@endif
            </div>
        </td>
        <td style="vertical-align: top;" class="text-right">
            <div class="invoice-type-label">{{ $invoiceTypeLabel }}</div>
            <div class="invoice-number">{{ $sale->invoice_number }}</div>
            <div class="invoice-date">{{ $sale->created_at->format('d M Y') }}</div>
            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
        </td>
    </tr>
</table>

{{-- ==================== CLIENT + DETAILS ==================== --}}
<table class="w-full info-grid mb-20">
    <tr>
        <td class="info-card-cell" style="width: 48%; vertical-align: top;">
            <div class="info-card">
                <div class="info-card-label">Client</div>
                <div class="info-card-name">{{ $sale->customer->name ?? 'Client non defini' }}</div>
                <div class="info-card-text">
                    @if(optional($sale->customer)->registration_number)SIREN: {{ $sale->customer->registration_number }}<br>@endif
                    @if(optional($sale->customer)->siret)SIRET: {{ $sale->customer->siret }}<br>@endif
                    @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                    @if(optional($sale->customer)->zip_code || optional($sale->customer)->city){{ optional($sale->customer)->zip_code }} {{ optional($sale->customer)->city }}<br>@endif
                    @if(optional($sale->customer)->phone)Tel: {{ $sale->customer->phone }}<br>@endif
                    @if(optional($sale->customer)->email){{ $sale->customer->email }}<br>@endif
                    @if($customerTaxNumber)<strong>TVA Intra:</strong> {{ $customerTaxNumber }}@endif
                </div>
            </div>
        </td>
        <td style="width: 4%;"></td>
        <td class="info-card-cell" style="width: 48%; vertical-align: top;">
            <div class="info-card">
                <div class="info-card-label">Paiement</div>
                <div class="info-card-name">{{ ucfirst($sale->payment_method ?? 'Non specifie') }}</div>
                <div class="info-card-text">
                    Echeance: {{ $dueDate instanceof \Carbon\Carbon ? $dueDate->format('d/m/Y') : \Carbon\Carbon::parse($dueDate)->format('d/m/Y') }}<br>
                    Ref: {{ $sale->reference ?? $sale->invoice_number }}
                    @if($sale->warehouse)<br>Entrepot: {{ $sale->warehouse->name }}@endif
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- ==================== ARTICLES ==================== --}}
<div class="mb-20">
    <table class="w-full items-table">
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
                    <td class="product-name">{{ $item->product->name ?? 'Produit supprime' }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2, ',', ' '), '0'), ',') }}</td>
                    <td class="text-right item-muted">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} {{ $currency }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($item->vat_rate ?? 0, 2, ',', ' '), '0'), ',') }}%</td>
                    <td class="text-right text-bold">{{ number_format($item->total_price_ht ?? ($item->quantity * $item->unit_price), 2, ',', ' ') }} {{ $currency }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px;">Aucun article</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ==================== TOTAUX ==================== --}}
<div class="mb-20">
    <table class="w-full">
        <tr>
            <td style="width: 55%;"></td>
            <td style="width: 45%;">
                <div class="totals-card">
                    {{-- Sous-total HT --}}
                    <div class="totals-row">
                        <table class="w-full"><tr>
                            <td class="totals-label">Total HT</td>
                            <td class="totals-value">{{ number_format($totalHt, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr></table>
                    </div>

                    {{-- Remise --}}
                    @if($discountAmount > 0)
                    <div class="totals-row">
                        <table class="w-full"><tr>
                            <td class="totals-label">Remise ({{ number_format($discountPercent, 1) }}%)</td>
                            <td class="totals-value totals-discount">- {{ number_format($discountAmount, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr></table>
                    </div>
                    @endif

                    {{-- TVA --}}
                    @if($hasMultipleVatRates)
                        @foreach($vatBreakdown as $vat)
                        <div class="totals-row">
                            <table class="w-full"><tr>
                                <td class="totals-label">TVA {{ rtrim(rtrim(number_format($vat['rate'], 2, ',', ' '), '0'), ',') }}% (base {{ number_format($vat['base'], 2, ',', ' ') }})</td>
                                <td class="totals-value">{{ number_format($vat['amount'], 2, ',', ' ') }} {{ $currency }}</td>
                            </tr></table>
                        </div>
                        @endforeach
                    @else
                    <div class="totals-row">
                        <table class="w-full"><tr>
                            <td class="totals-label">TVA ({{ rtrim(rtrim(number_format($vatBreakdown[0]['rate'] ?? 20, 2, ',', ' '), '0'), ',') }}%)</td>
                            <td class="totals-value">{{ number_format($totalVat, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr></table>
                    </div>
                    @endif

                    {{-- Grand total --}}
                    <div class="totals-grand">
                        <table class="w-full"><tr>
                            <td class="totals-grand-label">TOTAL TTC</td>
                            <td class="totals-grand-value">{{ number_format($grandTotal, 2, ',', ' ') }} {{ $currency }}</td>
                        </tr></table>
                    </div>

                    {{-- En lettres --}}
                    <div class="totals-words">{{ $amountInWords }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ==================== MENTIONS LEGALES ==================== --}}
<div class="legal-section mb-12">
    @include('sales.templates._legal-mentions')
</div>

{{-- ==================== NOTES ==================== --}}
@if($sale->notes)
<div class="notes-box mb-12">
    <strong class="notes-title">Note :</strong> {{ $sale->notes }}
</div>
@endif

{{-- ==================== QR CODE ==================== --}}
@if(!empty($verificationUrl) && !empty($verificationCode))
<div class="qr-section mb-12">
    <table class="w-full">
        <tr>
            <td style="width: 75px; vertical-align: top;">
                @if($qrBase64)
                    <div class="qr-box">
                        <img src="data:image/svg+xml;base64,{{ $qrBase64 }}" alt="QR" style="width: 60px; height: 60px;">
                    </div>
                @endif
            </td>
            <td style="padding-left: 12px; vertical-align: middle;">
                <div class="qr-title">Verification d'authenticite</div>
                <div class="qr-text">
                    Scannez le QR code ou visitez :<br>
                    <span style="font-size: 7px; word-break: break-all;">{{ $verificationUrl }}</span>
                </div>
                <span class="qr-code">{{ $verificationCode }}</span>
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

@yield('body-close')
</body>
</html>

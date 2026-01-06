<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Devis {{ $quote->reference }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1e293b;
            letter-spacing: 0.01em;
        }
        .container {
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9px;
            color: #666;
        }
        .quote-title {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .quote-info {
            font-size: 10px;
        }
        .quote-info strong {
            display: inline-block;
            width: 80px;
        }
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .address-box {
            display: table-cell;
            width: 48%;
            padding: 15px;
            background: #f8fafc;
            border-radius: 5px;
        }
        .address-box.right {
            margin-left: 4%;
        }
        .address-title {
            font-size: 11px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-accepted { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .status-expired { background: #fef3c7; color: #d97706; }
        
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background: #2563eb;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
        }
        table.items th:first-child {
            border-radius: 5px 0 0 0;
        }
        table.items th:last-child {
            border-radius: 0 5px 0 0;
            text-align: right;
        }
        table.items td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items tr:nth-child(even) {
            background: #f8fafc;
        }
        table.items .text-right {
            text-align: right;
        }
        table.items .text-center {
            text-align: center;
        }
        .product-name {
            font-weight: bold;
        }
        .product-desc {
            font-size: 9px;
            color: #666;
        }
        
        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px;
        }
        .totals .label {
            text-align: left;
            color: #666;
        }
        .totals .value {
            text-align: right;
            font-weight: bold;
        }
        .totals .total-row {
            background: #2563eb;
            color: white;
        }
        .totals .total-row td {
            padding: 12px 8px;
            font-size: 14px;
        }
        
        .conditions {
            background: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .conditions-title {
            font-weight: bold;
            margin-bottom: 8px;
            color: #2563eb;
        }
        .conditions-text {
            font-size: 9px;
            color: #666;
        }
        
        .notes {
            margin-bottom: 20px;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notes-text {
            font-size: 9px;
            color: #666;
            white-space: pre-line;
        }
        
        .validity {
            text-align: center;
            padding: 15px;
            background: #fef3c7;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .validity strong {
            color: #d97706;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 40px;
        }
        .signature-box {
            display: table-cell;
            width: 45%;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 5px;
            text-align: center;
        }
        .signature-box.right {
            margin-left: 10%;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 50px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if($settings && $settings->logo_path)
                    <img src="{{ storage_path('app/public/' . $settings->logo_path) }}" alt="Logo" style="max-height: 60px; margin-bottom: 10px;">
                @endif
                <div class="company-name">{{ $settings->name ?? 'Votre Entreprise' }}</div>
                <div class="company-info">
                    @if($settings)
                        {{ $settings->address }}<br>
                        @if($settings->phone) Tél: {{ $settings->phone }}<br> @endif
                        @if($settings->email) {{ $settings->email }}<br> @endif
                        @if($settings->registration_number) SIRET: {{ $settings->registration_number }}<br> @endif
                        @if($settings->tax_number) N° TVA: {{ $settings->tax_number }} @endif
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div class="quote-title">DEVIS</div>
                <div class="quote-info">
                    <table style="margin-left: auto; border-collapse: collapse;">
                        <tr>
                            <td style="text-align: left; padding-right: 10px;"><strong>N° :</strong></td>
                            <td style="text-align: right;">{{ $quote->quote_number }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-right: 10px;"><strong>Date :</strong></td>
                            <td style="text-align: right;">{{ $quote->quote_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td style="text-align: left; padding-right: 10px;"><strong>Validité :</strong></td>
                            <td style="text-align: right;">{{ $quote->valid_until ? $quote->valid_until->format('d/m/Y') : '-' }}</td>
                        </tr>
                    </table>
                </div>
                <span class="status-badge status-{{ $quote->status }}">
                    @switch($quote->status)
                        @case('draft') Brouillon @break
                        @case('sent') Envoyé @break
                        @case('accepted') Accepté @break
                        @case('rejected') Refusé @break
                        @case('expired') Expiré @break
                        @case('converted') Converti @break
                    @endswitch
                </span>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <div class="address-box">
                <div class="address-title">Client</div>
                <strong>{{ $quote->customer->name }}</strong><br>
                @if($quote->customer->address)
                    {{ $quote->customer->address }}<br>
                @endif
                @if($quote->customer->postal_code || $quote->customer->city)
                    {{ $quote->customer->postal_code }} {{ $quote->customer->city }}<br>
                @endif
                @if($quote->customer->phone)
                    Tél: {{ $quote->customer->phone }}<br>
                @endif
                @if($quote->customer->email)
                    {{ $quote->customer->email }}
                @endif
            </div>
            @if($quote->shipping_address)
            <div class="address-box right">
                <div class="address-title">Adresse de livraison</div>
                {!! nl2br(e($quote->shipping_address)) !!}
            </div>
            @endif
        </div>

        <!-- Items Table -->
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 40%;">Désignation</th>
                    <th class="text-center" style="width: 12%;">Quantité</th>
                    <th class="text-right" style="width: 16%;">Prix unit. HT</th>
                    <th class="text-center" style="width: 12%;">Remise</th>
                    <th class="text-right" style="width: 20%;">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote->items as $item)
                <tr>
                    <td>
                        <div class="product-name">{{ $item->product->name ?? $item->description }}</div>
                        @if($item->description && $item->product)
                            <div class="product-desc">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                    <td class="text-center">
                        @if($item->discount_percent > 0)
                            {{ number_format($item->discount_percent, 0) }}%
                        @elseif($item->discount_amount > 0)
                            {{ number_format($item->discount_amount, 2, ',', ' ') }} €
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->total, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Sous-total HT</td>
                    <td class="value">{{ number_format($quote->subtotal, 2, ',', ' ') }} €</td>
                </tr>
                @if($quote->discount_amount > 0)
                <tr>
                    <td class="label">Remise</td>
                    <td class="value">-{{ number_format($quote->discount_amount, 2, ',', ' ') }} €</td>
                </tr>
                @endif
                <tr>
                    <td class="label">TVA ({{ $quote->tax_rate ?? 20 }}%)</td>
                    <td class="value">{{ number_format($quote->tax_amount, 2, ',', ' ') }} €</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL TTC</td>
                    <td class="value">{{ number_format($quote->total_amount, 2, ',', ' ') }} €</td>
                </tr>
            </table>
        </div>

        <!-- Validity Notice -->
        @if($quote->valid_until)
        <div class="validity">
            <strong>Ce devis est valable jusqu'au {{ $quote->valid_until->format('d/m/Y') }}</strong>
        </div>
        @endif

        <!-- Conditions -->
        @if($quote->terms_and_conditions || ($settings && $settings->footer_text))
        <div class="conditions">
            <div class="conditions-title">Conditions</div>
            <div class="conditions-text">
                {{ $quote->terms_and_conditions ?? $settings->footer_text }}
            </div>
        </div>
        @endif

        <!-- Notes -->
        @if($quote->notes)
        <div class="notes">
            <div class="notes-title">Notes</div>
            <div class="notes-text">{{ $quote->notes }}</div>
        </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">Bon pour accord</div>
                <div class="signature-line">Date et signature du client</div>
            </div>
            <div class="signature-box right">
                <div class="signature-title">L'entreprise</div>
                <div class="signature-line">{{ $settings->name ?? '' }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            {{ $settings->name ?? '' }} - 
            @if($settings && $settings->registration_number) SIRET: {{ $settings->registration_number }} - @endif
            @if($settings && $settings->tax_number) TVA: {{ $settings->tax_number }} - @endif
            <br>
            Document généré le {{ now()->format('d/m/Y à H:i') }}
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bon de Livraison {{ $deliveryNote->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
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
            color: #059669;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9px;
            color: #666;
        }
        .document-title {
            font-size: 24px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 10px;
        }
        .document-info {
            font-size: 10px;
        }
        .document-info strong {
            display: inline-block;
            width: 100px;
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
            background: #f0fdf4;
            border-radius: 5px;
            vertical-align: top;
        }
        .address-box.shipping {
            background: #ecfeff;
        }
        .address-title {
            font-size: 11px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .address-box.shipping .address-title {
            color: #0891b2;
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
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-preparing { background: #dbeafe; color: #1d4ed8; }
        .status-ready { background: #e0e7ff; color: #4f46e5; }
        .status-shipped { background: #cffafe; color: #0891b2; }
        .status-delivered { background: #d1fae5; color: #059669; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        
        .shipping-info {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .shipping-info-title {
            font-weight: bold;
            color: #0369a1;
            margin-bottom: 10px;
        }
        .shipping-info table {
            width: 100%;
        }
        .shipping-info td {
            padding: 3px 0;
        }
        .shipping-info td:first-child {
            color: #666;
            width: 120px;
        }
        
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background: #059669;
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
            text-align: center;
        }
        table.items td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.items tr:nth-child(even) {
            background: #f0fdf4;
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
        .product-ref {
            font-size: 9px;
            color: #666;
        }
        .product-barcode {
            font-size: 8px;
            color: #999;
            font-family: monospace;
        }
        
        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #333;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .summary-box {
            display: table-cell;
            width: 30%;
            padding: 15px;
            background: #f8fafc;
            border-radius: 5px;
            text-align: center;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #059669;
        }
        .summary-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        
        .notes {
            background: #fffbeb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .notes-title {
            font-weight: bold;
            color: #d97706;
            margin-bottom: 5px;
        }
        .notes-text {
            font-size: 9px;
            color: #666;
            white-space: pre-line;
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
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .signature-content {
            height: 80px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 10px;
            padding-top: 5px;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
        .signature-fields {
            margin-top: 15px;
            font-size: 9px;
        }
        .signature-fields td {
            padding: 5px 0;
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
        
        .reserved-box {
            border: 2px dashed #e5e7eb;
            padding: 15px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .reserved-title {
            font-weight: bold;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-size: 9px;
        }
        .reserved-content {
            display: table;
            width: 100%;
        }
        .reserved-field {
            display: table-cell;
            width: 33%;
            padding: 10px;
        }
        .reserved-label {
            font-size: 8px;
            color: #999;
            margin-bottom: 5px;
        }
        .reserved-input {
            border-bottom: 1px solid #ccc;
            height: 25px;
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
                        @if($settings->email) {{ $settings->email }} @endif
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div class="document-title">BON DE LIVRAISON</div>
                <div class="document-info">
                    <strong>N° BL :</strong> {{ $deliveryNote->reference }}<br>
                    <strong>Date :</strong> {{ $deliveryNote->delivery_date->format('d/m/Y') }}<br>
                    @if($deliveryNote->sale)
                    <strong>Commande :</strong> {{ $deliveryNote->sale->reference }}<br>
                    @endif
                </div>
                <span class="status-badge status-{{ $deliveryNote->status }}">
                    @switch($deliveryNote->status)
                        @case('pending') En attente @break
                        @case('preparing') En préparation @break
                        @case('ready') Prêt @break
                        @case('shipped') Expédié @break
                        @case('delivered') Livré @break
                        @case('cancelled') Annulé @break
                    @endswitch
                </span>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <div class="address-box">
                <div class="address-title">Client</div>
                <strong>{{ $deliveryNote->customer->name }}</strong><br>
                @if($deliveryNote->customer->address)
                    {{ $deliveryNote->customer->address }}<br>
                @endif
                @if($deliveryNote->customer->postal_code || $deliveryNote->customer->city)
                    {{ $deliveryNote->customer->postal_code }} {{ $deliveryNote->customer->city }}<br>
                @endif
                @if($deliveryNote->customer->phone)
                    Tél: {{ $deliveryNote->customer->phone }}
                @endif
            </div>
            <div class="address-box shipping" style="margin-left: 4%;">
                <div class="address-title">Adresse de livraison</div>
                @if($deliveryNote->shipping_address)
                    {!! nl2br(e($deliveryNote->shipping_address)) !!}
                @else
                    <strong>{{ $deliveryNote->customer->name }}</strong><br>
                    {{ $deliveryNote->customer->address }}<br>
                    {{ $deliveryNote->customer->postal_code }} {{ $deliveryNote->customer->city }}
                @endif
            </div>
        </div>

        <!-- Shipping Info -->
        @if($deliveryNote->carrier || $deliveryNote->tracking_number)
        <div class="shipping-info">
            <div class="shipping-info-title">Informations de transport</div>
            <table>
                @if($deliveryNote->carrier)
                <tr>
                    <td>Transporteur :</td>
                    <td><strong>{{ $deliveryNote->carrier }}</strong></td>
                </tr>
                @endif
                @if($deliveryNote->tracking_number)
                <tr>
                    <td>N° de suivi :</td>
                    <td><strong>{{ $deliveryNote->tracking_number }}</strong></td>
                </tr>
                @endif
                @if($deliveryNote->shipped_at)
                <tr>
                    <td>Date d'expédition :</td>
                    <td>{{ $deliveryNote->shipped_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endif
                @if($deliveryNote->delivered_at)
                <tr>
                    <td>Date de livraison :</td>
                    <td>{{ $deliveryNote->delivered_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endif
                @if($deliveryNote->weight)
                <tr>
                    <td>Poids total :</td>
                    <td>{{ number_format($deliveryNote->weight, 2, ',', ' ') }} kg</td>
                </tr>
                @endif
                @if($deliveryNote->packages_count)
                <tr>
                    <td>Nombre de colis :</td>
                    <td>{{ $deliveryNote->packages_count }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif

        <!-- Items Table -->
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 8%;">Vérifié</th>
                    <th style="width: 15%;">Référence</th>
                    <th style="width: 42%;">Désignation</th>
                    <th class="text-center" style="width: 15%;">Qté commandée</th>
                    <th class="text-center" style="width: 15%;">Qté livrée</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryNote->items as $item)
                <tr>
                    <td class="text-center">
                        <span class="checkbox"></span>
                    </td>
                    <td>
                        <div class="product-ref">{{ $item->product->sku ?? '-' }}</div>
                        @if($item->product && $item->product->barcode)
                            <div class="product-barcode">{{ $item->product->barcode }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="product-name">{{ $item->product->name ?? $item->description }}</div>
                        @if($item->description && $item->product)
                            <div class="product-ref">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity_ordered, 0, ',', ' ') }}</td>
                    <td class="text-center"><strong>{{ number_format($item->quantity_delivered, 0, ',', ' ') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-box">
                <div class="summary-value">{{ $deliveryNote->items->count() }}</div>
                <div class="summary-label">Références</div>
            </div>
            <div class="summary-box" style="margin: 0 15px;">
                <div class="summary-value">{{ number_format($deliveryNote->items->sum('quantity_delivered'), 0, ',', ' ') }}</div>
                <div class="summary-label">Articles livrés</div>
            </div>
            <div class="summary-box">
                <div class="summary-value">{{ $deliveryNote->packages_count ?? '-' }}</div>
                <div class="summary-label">Colis</div>
            </div>
        </div>

        <!-- Notes -->
        @if($deliveryNote->notes)
        <div class="notes">
            <div class="notes-title">Instructions / Remarques</div>
            <div class="notes-text">{{ $deliveryNote->notes }}</div>
        </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">Expéditeur</div>
                <div class="signature-content"></div>
                <table class="signature-fields" style="width: 100%;">
                    <tr>
                        <td>Préparé par :</td>
                        <td style="border-bottom: 1px solid #ccc; width: 60%;"></td>
                    </tr>
                    <tr>
                        <td>Date :</td>
                        <td style="border-bottom: 1px solid #ccc;"></td>
                    </tr>
                </table>
                <div class="signature-line">Cachet et signature</div>
            </div>
            <div class="signature-box" style="margin-left: 10%;">
                <div class="signature-title">Réception - Client</div>
                <div class="signature-content"></div>
                <table class="signature-fields" style="width: 100%;">
                    <tr>
                        <td>Reçu par :</td>
                        <td style="border-bottom: 1px solid #ccc; width: 60%;"></td>
                    </tr>
                    <tr>
                        <td>Date :</td>
                        <td style="border-bottom: 1px solid #ccc;"></td>
                    </tr>
                </table>
                <div class="signature-line">Signature (Bon pour réception conforme)</div>
            </div>
        </div>

        <!-- Reserved Box -->
        <div class="reserved-box">
            <div class="reserved-title">Réservé au service logistique</div>
            <div class="reserved-content">
                <div class="reserved-field">
                    <div class="reserved-label">Observations :</div>
                    <div class="reserved-input"></div>
                </div>
                <div class="reserved-field">
                    <div class="reserved-label">Réserves éventuelles :</div>
                    <div class="reserved-input"></div>
                </div>
                <div class="reserved-field">
                    <div class="reserved-label">État de la marchandise :</div>
                    <div class="reserved-input"></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            {{ $settings->name ?? '' }} - 
            @if($settings && $settings->registration_number) SIRET: {{ $settings->registration_number }} @endif
            <br>
            Document généré le {{ now()->format('d/m/Y à H:i') }} - Veuillez conserver ce bon de livraison
        </div>
    </div>
</body>
</html>

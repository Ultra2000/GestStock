<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de Transfert - {{ $transfer->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-info h1 {
            font-size: 24px;
            color: #2563eb;
        }
        .document-info {
            text-align: right;
        }
        .document-info h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background: #e5e7eb; color: #4b5563; }
        .status-pending, .status-partial { background: #fef3c7; color: #92400e; }
        .status-approved, .status-in_transit { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .warehouses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .warehouse-box {
            width: 45%;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        .warehouse-box h3 {
            color: #2563eb;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .arrow {
            display: flex;
            align-items: center;
            font-size: 30px;
            color: #2563eb;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 5px;
        }
        .detail-item label {
            font-size: 10px;
            color: #6b7280;
            display: block;
        }
        .detail-item strong {
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #2563eb;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 20px;
        }
        .totals table {
            border: none;
        }
        .totals td {
            border: none;
            padding: 5px;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature-box {
            width: 30%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
        }

        .notes {
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 5px;
        }
        .notes h4 {
            margin-bottom: 10px;
        }

        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🖨️ Imprimer
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Fermer
        </button>
    </div>

    <div class="header">
        <div class="company-info">
            <h1>{{ filament()->getTenant()?->name ?? 'GestStock' }}</h1>
            <p>Bon de Transfert Inter-Entrepôts</p>
        </div>
        <div class="document-info">
            <h2>{{ $transfer->reference }}</h2>
            <span class="status-badge status-{{ $transfer->status }}">{{ $transfer->status_label }}</span>
            <p style="margin-top: 10px;">Date: {{ $transfer->transfer_date->format('d/m/Y') }}</p>
        </div>
    </div>

    <div class="warehouses">
        <div class="warehouse-box">
            <h3>📦 ENTREPÔT SOURCE</h3>
            <strong>{{ $transfer->sourceWarehouse->name }}</strong><br>
            <small>Code: {{ $transfer->sourceWarehouse->code }}</small><br>
            @if($transfer->sourceWarehouse->address)
                <small>{{ $transfer->sourceWarehouse->full_address }}</small>
            @endif
        </div>
        <div class="arrow">→</div>
        <div class="warehouse-box">
            <h3>🏪 ENTREPÔT DESTINATION</h3>
            <strong>{{ $transfer->destinationWarehouse->name }}</strong><br>
            <small>Code: {{ $transfer->destinationWarehouse->code }}</small><br>
            @if($transfer->destinationWarehouse->address)
                <small>{{ $transfer->destinationWarehouse->full_address }}</small>
            @endif
        </div>
    </div>

    <div class="details-grid">
        <div class="detail-item">
            <label>Date prévue</label>
            <strong>{{ $transfer->expected_date?->format('d/m/Y') ?? '-' }}</strong>
        </div>
        <div class="detail-item">
            <label>Date expédition</label>
            <strong>{{ $transfer->shipped_date?->format('d/m/Y') ?? '-' }}</strong>
        </div>
        <div class="detail-item">
            <label>Transporteur</label>
            <strong>{{ $transfer->carrier ?? '-' }}</strong>
        </div>
        <div class="detail-item">
            <label>N° de suivi</label>
            <strong>{{ $transfer->tracking_number ?? '-' }}</strong>
        </div>
        <div class="detail-item">
            <label>Demandé par</label>
            <strong>{{ $transfer->requestedBy?->name ?? '-' }}</strong>
        </div>
        <div class="detail-item">
            <label>Approuvé par</label>
            <strong>{{ $transfer->approvedBy?->name ?? '-' }}</strong>
        </div>
        <div class="detail-item">
            <label>Expédié par</label>
            <strong>{{ $transfer->shippedBy?->name ?? '-' }}</strong>
        </div>
        <div class="detail-item">
            <label>Réceptionné par</label>
            <strong>{{ $transfer->receivedBy?->name ?? '-' }}</strong>
        </div>
    </div>

    <h3 style="margin-bottom: 10px;">Détail des produits</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Code</th>
                <th>Désignation</th>
                <th class="text-center" style="width: 80px;">Demandé</th>
                <th class="text-center" style="width: 80px;">Expédié</th>
                <th class="text-center" style="width: 80px;">Reçu</th>
                <th class="text-right" style="width: 100px;">Coût unit.</th>
                <th class="text-right" style="width: 100px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $item)
                <tr>
                    <td>{{ $item->product->code }}</td>
                    <td>
                        {{ $item->product->name }}
                        @if($item->batch_number)
                            <br><small>Lot: {{ $item->batch_number }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity_requested, 2, ',', ' ') }}</td>
                    <td class="text-center">{{ number_format($item->quantity_shipped, 2, ',', ' ') }}</td>
                    <td class="text-center">{{ number_format($item->quantity_received, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->unit_cost ?? $item->product->purchase_price ?? 0, 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item->total_value, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Nombre d'articles:</td>
                <td class="text-right">{{ $transfer->total_items }}</td>
            </tr>
            <tr>
                <td>Quantité totale:</td>
                <td class="text-right">{{ number_format($transfer->total_quantity, 2, ',', ' ') }}</td>
            </tr>
            <tr class="total-row">
                <td>Valeur totale:</td>
                <td class="text-right">{{ number_format($transfer->total_value, 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>
    </div>

    @if($transfer->notes)
        <div class="notes">
            <h4>Notes</h4>
            <p>{{ $transfer->notes }}</p>
        </div>
    @endif

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">Expéditeur</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Transporteur</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Réceptionnaire</div>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #6b7280;">
        Document généré le {{ now()->format('d/m/Y à H:i') }} | {{ $transfer->reference }}
    </div>
</body>
</html>

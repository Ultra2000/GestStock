<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaire - {{ $inventory->reference }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            letter-spacing: 0.01em;
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
            font-size: 22px;
            color: #059669;
        }
        .document-info {
            text-align: right;
        }
        .document-info h2 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background: #e5e7eb; color: #4b5563; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-pending_validation { background: #fef3c7; color: #92400e; }
        .status-validated { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 5px;
            border: 1px solid #86efac;
        }
        .info-item label {
            font-size: 9px;
            color: #6b7280;
            display: block;
            text-transform: uppercase;
        }
        .info-item strong {
            font-size: 12px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-box {
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        .stat-box.primary { background: #dbeafe; border: 1px solid #93c5fd; }
        .stat-box.success { background: #dcfce7; border: 1px solid #86efac; }
        .stat-box.warning { background: #fef3c7; border: 1px solid #fcd34d; }
        .stat-box.danger { background: #fee2e2; border: 1px solid #fca5a5; }
        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
        }
        .stat-box .label {
            font-size: 10px;
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #059669;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background: #f9fafb;
        }
        tr.discrepancy-surplus {
            background: #dbeafe;
        }
        tr.discrepancy-shortage {
            background: #fee2e2;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .summary {
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 5px;
        }
        .summary table {
            width: 50%;
            margin-left: auto;
        }
        .summary td {
            border: none;
            padding: 5px;
        }
        .summary .total-row {
            font-weight: bold;
            border-top: 2px solid #333;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
        }
        .signature-box {
            width: 40%;
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

        @media print {
            body { padding: 0; font-size: 10px; }
            .no-print { display: none; }
            .stat-box .value { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #059669; color: white; border: none; border-radius: 5px; cursor: pointer;">
            🖨️ Imprimer
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6b7280; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Fermer
        </button>
    </div>

    <div class="header">
        <div class="company-info">
            <h1>{{ filament()->getTenant()?->name ?? 'GestStock' }}</h1>
            <p>Fiche d'Inventaire</p>
        </div>
        <div class="document-info">
            <h2>{{ $inventory->reference }}</h2>
            <span class="status-badge status-{{ $inventory->status }}">{{ $inventory->status_label }}</span>
            <p style="margin-top: 10px;">{{ $inventory->name }}</p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <label>Entrepôt</label>
            <strong>{{ $inventory->warehouse->name }}</strong>
        </div>
        <div class="info-item">
            <label>Type</label>
            <strong>{{ $inventory->type_label }}</strong>
        </div>
        <div class="info-item">
            <label>Date inventaire</label>
            <strong>{{ $inventory->inventory_date->format('d/m/Y') }}</strong>
        </div>
        <div class="info-item">
            <label>Créé par</label>
            <strong>{{ $inventory->createdByUser?->name ?? '-' }}</strong>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-box primary">
            <div class="value">{{ $inventory->total_items }}</div>
            <div class="label">Articles</div>
        </div>
        <div class="stat-box success">
            <div class="value">{{ $inventory->items_counted }}</div>
            <div class="label">Comptés</div>
        </div>
        <div class="stat-box warning">
            <div class="value">{{ $inventory->progress_percent }}%</div>
            <div class="label">Progression</div>
        </div>
        <div class="stat-box {{ $inventory->discrepancies_count > 0 ? 'danger' : 'success' }}">
            <div class="value">{{ $inventory->discrepancies_count }}</div>
            <div class="label">Écarts</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 70px;">Code</th>
                <th>Produit</th>
                <th class="text-center" style="width: 60px;">Emplacement</th>
                <th class="text-right" style="width: 70px;">Attendu</th>
                <th class="text-right" style="width: 70px;">Compté</th>
                <th class="text-right" style="width: 70px;">Écart</th>
                <th class="text-center" style="width: 60px;">Statut</th>
                <th class="text-right" style="width: 90px;">Valeur écart</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inventory->items as $item)
                <tr class="{{ $item->is_counted && $item->quantity_difference != 0 ? ($item->quantity_difference > 0 ? 'discrepancy-surplus' : 'discrepancy-shortage') : '' }}">
                    <td>{{ $item->product->code }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td class="text-center">{{ $item->location?->code ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->quantity_expected, 2, ',', ' ') }}</td>
                    <td class="text-right">
                        @if($item->is_counted)
                            {{ number_format($item->quantity_counted, 2, ',', ' ') }}
                        @else
                            <span style="color: #9ca3af;">-</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item->is_counted)
                            <span style="color: {{ $item->quantity_difference > 0 ? '#2563eb' : ($item->quantity_difference < 0 ? '#dc2626' : '#059669') }};">
                                {{ $item->quantity_difference > 0 ? '+' : '' }}{{ number_format($item->quantity_difference, 2, ',', ' ') }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @if(!$item->is_counted)
                            --
                        @elseif($item->quantity_difference == 0)
                            OK
                        @elseif($item->quantity_difference > 0)
                            +
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item->is_counted && $item->value_difference != 0)
                            <span style="color: {{ $item->value_difference > 0 ? '#059669' : '#dc2626' }};">
                                {{ number_format($item->value_difference, 0, ',', ' ') }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table>
            <tr>
                <td>Valeur attendue:</td>
                <td class="text-right">{{ number_format($inventory->total_value_expected, 2, ',', ' ') }} €</td>
            </tr>
            <tr>
                <td>Valeur comptée:</td>
                <td class="text-right">{{ number_format($inventory->total_value_counted, 2, ',', ' ') }} €</td>
            </tr>
            <tr class="total-row">
                <td>Différence:</td>
                <td class="text-right" style="color: {{ $inventory->value_difference >= 0 ? '#059669' : '#dc2626' }};">
                    {{ $inventory->value_difference >= 0 ? '+' : '' }}{{ number_format($inventory->value_difference, 2, ',', ' ') }} €
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 20px; font-size: 10px;">
        <strong>Légende:</strong> 
        -- En attente | OK Conforme | + Excédent | - Manquant
    </div>

    @if($inventory->notes)
        <div class="notes">
            <strong>Notes:</strong>
            <p>{{ $inventory->notes }}</p>
        </div>
    @endif

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">Responsable inventaire</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Validation direction</div>
        </div>
    </div>

    @if($inventory->status === 'validated')
        <div style="margin-top: 20px; padding: 10px; background: #dcfce7; border-radius: 5px; text-align: center;">
            ✅ Inventaire validé le {{ $inventory->validated_at?->format('d/m/Y') }} par {{ $inventory->validatedByUser?->name ?? 'N/A' }}
        </div>
    @endif

    <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #6b7280;">
        Document généré le {{ now()->format('d/m/Y à H:i') }} | {{ $inventory->reference }}
    </div>
</body>
</html>

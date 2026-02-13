<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Journal des Achats - {{ $startDate }} au {{ $endDate }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        @page {
            margin: 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #1e293b;
            line-height: 1.3;
            letter-spacing: 0.01em;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #ef4444;
        }
        .header h1 {
            font-size: 18px;
            color: #ef4444;
            margin-bottom: 3px;
        }
        .header .company {
            font-size: 12px;
            color: #666;
        }
        .header .period {
            font-size: 10px;
            color: #888;
            margin-top: 3px;
        }
        .summary {
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
        }
        .summary-grid {
            width: 100%;
        }
        .summary-grid td {
            padding: 5px 10px;
            text-align: center;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
        }
        .summary-label {
            font-size: 8px;
            color: #991b1b;
        }
        table.journal {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        table.journal th {
            background: #ef4444;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-weight: 600;
        }
        table.journal td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.journal tr:nth-child(even) {
            background: #f9fafb;
        }
        table.journal .right {
            text-align: right;
        }
        table.journal .center {
            text-align: center;
        }
        table.journal .total-row {
            background: #fee2e2;
            font-weight: bold;
        }
        table.journal .total-row td {
            border-top: 2px solid #ef4444;
            padding: 8px 4px;
        }
        .status-received { color: #10b981; }
        .status-pending { color: #f59e0b; }
        .status-paid { color: #3b82f6; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            padding: 10px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📕 JOURNAL DES ACHATS</h1>
        <div class="company">{{ $company->name ?? 'Entreprise' }}</div>
        <div class="period">Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
    </div>

    <div class="summary">
        <table class="summary-grid">
            <tr>
                <td width="25%">
                    <div class="summary-value">{{ number_format($totals['count']) }}</div>
                    <div class="summary-label">Commandes</div>
                </td>
                <td width="25%">
                    <div class="summary-value">{{ number_format($totals['total_ht'], 2, ',', ' ') }} €</div>
                    <div class="summary-label">Total HT</div>
                </td>
                <td width="25%">
                    <div class="summary-value">{{ number_format($totals['total_tva'], 2, ',', ' ') }} €</div>
                    <div class="summary-label">TVA déductible</div>
                </td>
                <td width="25%">
                    <div class="summary-value">{{ number_format($totals['total_ttc'], 2, ',', ' ') }} €</div>
                    <div class="summary-label">Total TTC</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="journal">
        <thead>
            <tr>
                <th width="8%">Date</th>
                <th width="12%">N° Commande</th>
                <th width="20%">Fournisseur</th>
                <th width="10%">Statut</th>
                <th width="12%" class="right">HT</th>
                <th width="10%" class="right">TVA</th>
                <th width="12%" class="right">TTC</th>
                <th width="16%">Articles</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchases as $purchase)
                @php
                    $ht = $purchase->total_amount - ($purchase->tax_amount ?? 0);
                    $statusClass = match($purchase->status) {
                        'received', 'completed' => 'status-received',
                        'paid' => 'status-paid',
                        default => 'status-pending',
                    };
                    $statusLabel = match($purchase->status) {
                        'received' => 'Reçu',
                        'completed' => 'Terminé',
                        'paid' => 'Payé',
                        'pending' => 'En attente',
                        'ordered' => 'Commandé',
                        default => ucfirst($purchase->status ?? '-'),
                    };
                @endphp
                <tr>
                    <td>{{ $purchase->created_at->format('d/m/Y') }}</td>
                    <td><strong>{{ $purchase->reference ?? $purchase->id }}</strong></td>
                    <td>{{ Str::limit($purchase->supplier?->name ?? 'Fournisseur inconnu', 25) }}</td>
                    <td class="{{ $statusClass }}">{{ $statusLabel }}</td>
                    <td class="right">{{ number_format($ht, 2, ',', ' ') }} €</td>
                    <td class="right">{{ number_format($purchase->tax_amount ?? 0, 2, ',', ' ') }} €</td>
                    <td class="right"><strong>{{ number_format($purchase->total_amount, 2, ',', ' ') }} €</strong></td>
                    <td>{{ $purchase->items->count() }} art. ({{ $purchase->items->sum('quantity') }} u.)</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4"><strong>TOTAUX ({{ $totals['count'] }} commandes)</strong></td>
                <td class="right"><strong>{{ number_format($totals['total_ht'], 2, ',', ' ') }} €</strong></td>
                <td class="right"><strong>{{ number_format($totals['total_tva'], 2, ',', ' ') }} €</strong></td>
                <td class="right"><strong>{{ number_format($totals['total_ttc'], 2, ',', ' ') }} €</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        {{ $company->name ?? 'FRECORP ERP' }} - Journal des achats du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }} - Généré le {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>

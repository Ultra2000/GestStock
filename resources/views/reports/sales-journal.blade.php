<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Journal des Ventes - {{ $startDate }} au {{ $endDate }}</title>
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
            border-bottom: 3px solid #10b981;
        }
        .header h1 {
            font-size: 18px;
            color: #10b981;
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
            background: #d1fae5;
            border: 1px solid #10b981;
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
            color: #047857;
        }
        .summary-label {
            font-size: 8px;
            color: #065f46;
        }
        table.journal {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        table.journal th {
            background: #10b981;
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
            background: #d1fae5;
            font-weight: bold;
        }
        table.journal .total-row td {
            border-top: 2px solid #10b981;
            padding: 8px 4px;
        }
        .payment-cash { color: #10b981; }
        .payment-card { color: #3b82f6; }
        .payment-mobile { color: #8b5cf6; }
        .payment-other { color: #6b7280; }
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
        <h1>📗 JOURNAL DES VENTES</h1>
        <div class="company">{{ $company->name ?? 'Entreprise' }}</div>
        <div class="period">Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
    </div>

    <div class="summary">
        <table class="summary-grid">
            <tr>
                <td width="25%">
                    <div class="summary-value">{{ number_format($totals['count']) }}</div>
                    <div class="summary-label">Factures</div>
                </td>
                <td width="25%">
                    <div class="summary-value">{{ number_format($totals['total_ht'], 2, ',', ' ') }} €</div>
                    <div class="summary-label">Total HT</div>
                </td>
                <td width="25%">
                    <div class="summary-value">{{ number_format($totals['total_tva'], 2, ',', ' ') }} €</div>
                    <div class="summary-label">TVA collectée</div>
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
                <th width="12%">N° Facture</th>
                <th width="20%">Client</th>
                <th width="10%">Paiement</th>
                <th width="12%" class="right">HT</th>
                <th width="10%" class="right">TVA</th>
                <th width="12%" class="right">TTC</th>
                <th width="16%">Articles</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
                @php
                    $ht = $sale->total - ($sale->tax_amount ?? 0);
                    $paymentClass = match($sale->payment_method) {
                        'cash' => 'payment-cash',
                        'card' => 'payment-card',
                        'mobile' => 'payment-mobile',
                        default => 'payment-other',
                    };
                    $paymentLabel = match($sale->payment_method) {
                        'cash' => 'Espèces',
                        'card' => 'Carte',
                        'mobile' => 'Mobile',
                        'transfer' => 'Virement',
                        'check' => 'Chèque',
                        'mixed' => 'Mixte',
                        default => ucfirst($sale->payment_method ?? '-'),
                    };
                @endphp
                <tr>
                    <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                    <td><strong>{{ $sale->invoice_number }}</strong></td>
                    <td>{{ Str::limit($sale->customer?->name ?? 'Client comptoir', 25) }}</td>
                    <td class="{{ $paymentClass }}">{{ $paymentLabel }}</td>
                    <td class="right">{{ number_format($ht, 2, ',', ' ') }} €</td>
                    <td class="right">{{ number_format($sale->tax_amount ?? 0, 2, ',', ' ') }} €</td>
                    <td class="right"><strong>{{ number_format($sale->total, 2, ',', ' ') }} €</strong></td>
                    <td>{{ $sale->items->count() }} art. ({{ $sale->items->sum('quantity') }} u.)</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4"><strong>TOTAUX ({{ $totals['count'] }} factures)</strong></td>
                <td class="right"><strong>{{ number_format($totals['total_ht'], 2, ',', ' ') }} €</strong></td>
                <td class="right"><strong>{{ number_format($totals['total_tva'], 2, ',', ' ') }} €</strong></td>
                <td class="right"><strong>{{ number_format($totals['total_ttc'], 2, ',', ' ') }} €</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        {{ $company->name ?? 'FRECORP ERP' }} - Journal des ventes du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }} - Généré le {{ $generatedAt->format('d/m/Y H:i') }}
    </div>
</body>
</html>

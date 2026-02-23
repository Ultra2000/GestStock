<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bilan Comptable - {{ $startDate }} au {{ $endDate }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.4;
            letter-spacing: 0.01em;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #7c3aed;
        }
        .header h1 {
            font-size: 22px;
            color: #7c3aed;
            margin-bottom: 5px;
        }
        .header .company {
            font-size: 14px;
            color: #666;
            margin-bottom: 3px;
        }
        .header .period {
            font-size: 12px;
            color: #888;
            background: #f3f4f6;
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 5px;
        }
        .summary-box {
            background: #7c3aed;
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .summary-box h2 {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .summary-grid {
            width: 100%;
        }
        .summary-grid td {
            padding: 5px 10px;
        }
        .summary-value {
            font-size: 22px;
            font-weight: bold;
        }
        .summary-label {
            font-size: 10px;
            opacity: 0.8;
        }
        .profit-positive {
            color: #4ade80;
        }
        .profit-negative {
            color: #f87171;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #7c3aed;
            padding: 8px 12px;
            background: #f3e8ff;
            border-radius: 6px;
            margin-bottom: 12px;
        }
        .data-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .data-grid th, .data-grid td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-grid th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            font-size: 9px;
            text-transform: uppercase;
        }
        .data-grid .right {
            text-align: right;
        }
        .data-grid .center {
            text-align: center;
        }
        .data-grid .total-row {
            background: #f3f4f6;
            font-weight: bold;
        }
        .data-grid .total-row td {
            border-top: 2px solid #7c3aed;
        }
        .two-col {
            width: 100%;
        }
        .two-col td {
            width: 48%;
            vertical-align: top;
            padding: 0 5px;
        }
        .card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .card-title {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .card-value {
            font-size: 18px;
            font-weight: bold;
            color: #7c3aed;
        }
        .card-sub {
            font-size: 9px;
            color: #6b7280;
        }
        .highlight-green {
            color: #10b981;
        }
        .highlight-red {
            color: #ef4444;
        }
        .highlight-blue {
            color: #3b82f6;
        }
        .tva-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        .tva-box h3 {
            color: #92400e;
            font-size: 11px;
            margin-bottom: 10px;
        }
        .tva-grid td {
            padding: 5px 10px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }
        .month-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        .month-table th, .month-table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .month-table th {
            background: #7c3aed;
            color: white;
        }
        .month-table .positive {
            color: #10b981;
        }
        .month-table .negative {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BILAN COMPTABLE</h1>
        <div class="company">{{ $company->name ?? 'Entreprise' }}</div>
        <div class="period">Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
    </div>

    {{-- Résumé principal --}}
    <div class="summary-box">
        <h2>Résultat de la période</h2>
        <table class="summary-grid">
            <tr>
                <td width="25%">
                    <div class="summary-value highlight-green">{{ number_format($summary['revenue'], 2, ',', ' ') }} €</div>
                    <div class="summary-label">Chiffre d'affaires HT</div>
                </td>
                <td width="25%">
                    <div class="summary-value highlight-red">{{ number_format($summary['expenses'], 2, ',', ' ') }} €</div>
                    <div class="summary-label">Achats HT</div>
                </td>
                <td width="25%">
                    <div class="summary-value {{ $summary['gross_profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                        {{ number_format($summary['gross_profit'], 2, ',', ' ') }} €
                    </div>
                    <div class="summary-label">Marge brute</div>
                </td>
                <td width="25%">
                    <div class="summary-value">{{ $summary['margin_percent'] }}%</div>
                    <div class="summary-label">Taux de marge</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Détails Ventes et Achats --}}
    <table class="two-col">
        <tr>
            <td>
                <div class="section-title">Ventes</div>
                <div class="card">
                    <table class="data-grid">
                        <tr>
                            <td>Nombre de ventes</td>
                            <td class="right"><strong>{{ number_format($sales['count']) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total HT</td>
                            <td class="right highlight-green"><strong>{{ number_format($sales['total_ht'], 2, ',', ' ') }} €</strong></td>
                        </tr>
                        <tr>
                            <td>TVA collectée</td>
                            <td class="right">{{ number_format($sales['total_tva'], 2, ',', ' ') }} €</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total TTC</td>
                            <td class="right"><strong>{{ number_format($sales['total_ttc'], 2, ',', ' ') }} €</strong></td>
                        </tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="section-title">Achats</div>
                <div class="card">
                    <table class="data-grid">
                        <tr>
                            <td>Nombre d'achats</td>
                            <td class="right"><strong>{{ number_format($purchases['count']) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Total HT</td>
                            <td class="right highlight-red"><strong>{{ number_format($purchases['total_ht'], 2, ',', ' ') }} €</strong></td>
                        </tr>
                        <tr>
                            <td>TVA déductible</td>
                            <td class="right">{{ number_format($purchases['total_tva'], 2, ',', ' ') }} €</td>
                        </tr>
                        <tr class="total-row">
                            <td>Total TTC</td>
                            <td class="right"><strong>{{ number_format($purchases['total_ttc'], 2, ',', ' ') }} €</strong></td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- TVA --}}
    <div class="tva-box">
        <h3>Synthèse TVA</h3>
        <table class="tva-grid">
            <tr>
                <td width="33%">
                    <div class="card-sub">TVA collectée (ventes)</div>
                    <div style="font-size: 14px; font-weight: bold; color: #10b981;">{{ number_format($summary['tva_collected'], 2, ',', ' ') }} €</div>
                </td>
                <td width="33%">
                    <div class="card-sub">TVA déductible (achats)</div>
                    <div style="font-size: 14px; font-weight: bold; color: #ef4444;">{{ number_format($summary['tva_deductible'], 2, ',', ' ') }} €</div>
                </td>
                <td width="34%">
                    <div class="card-sub">TVA à reverser</div>
                    <div style="font-size: 14px; font-weight: bold; color: {{ $summary['tva_to_pay'] >= 0 ? '#7c3aed' : '#10b981' }};">
                        {{ number_format($summary['tva_to_pay'], 2, ',', ' ') }} €
                        @if($summary['tva_to_pay'] < 0)
                            (crédit)
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Évolution mensuelle --}}
    @if($salesByMonth->count() > 0 || $purchasesByMonth->count() > 0)
    <div class="section" style="margin-top: 20px;">
        <div class="section-title">Évolution mensuelle</div>
        <table class="month-table">
            <thead>
                <tr>
                    <th>Mois</th>
                    <th>Ventes (nb)</th>
                    <th>Ventes (€)</th>
                    <th>Achats (nb)</th>
                    <th>Achats (€)</th>
                    <th>Résultat</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $months = collect();
                    foreach($salesByMonth as $s) {
                        $key = $s->year . '-' . str_pad($s->month, 2, '0', STR_PAD_LEFT);
                        $months[$key] = ['year' => $s->year, 'month' => $s->month, 'sales_count' => $s->count, 'sales_total' => $s->total, 'purchases_count' => 0, 'purchases_total' => 0];
                    }
                    foreach($purchasesByMonth as $p) {
                        $key = $p->year . '-' . str_pad($p->month, 2, '0', STR_PAD_LEFT);
                        if (!isset($months[$key])) {
                            $months[$key] = ['year' => $p->year, 'month' => $p->month, 'sales_count' => 0, 'sales_total' => 0, 'purchases_count' => 0, 'purchases_total' => 0];
                        }
                        $months[$key]['purchases_count'] = $p->count;
                        $months[$key]['purchases_total'] = $p->total;
                    }
                    $months = $months->sortKeys();
                    $monthNames = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
                @endphp
                @foreach($months as $m)
                    @php
                        $result = ($m['sales_total'] ?? 0) - ($m['purchases_total'] ?? 0);
                    @endphp
                    <tr>
                        <td><strong>{{ $monthNames[(int) $m['month']] ?? $m['month'] }} {{ $m['year'] }}</strong></td>
                        <td>{{ $m['sales_count'] }}</td>
                        <td class="positive">{{ number_format($m['sales_total'], 2, ',', ' ') }} €</td>
                        <td>{{ $m['purchases_count'] }}</td>
                        <td class="negative">{{ number_format($m['purchases_total'], 2, ',', ' ') }} €</td>
                        <td class="{{ $result >= 0 ? 'positive' : 'negative' }}"><strong>{{ number_format($result, 2, ',', ' ') }} €</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Top clients et fournisseurs --}}
    <table class="two-col" style="margin-top: 20px;">
        <tr>
            <td>
                @if($topCustomers->count() > 0)
                <div class="section-title">Top 10 Clients</div>
                <table class="data-grid">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th class="center">Commandes</th>
                            <th class="right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topCustomers as $customer)
                        <tr>
                            <td>{{ Str::limit($customer->name, 25) }}</td>
                            <td class="center">{{ $customer->orders_count }}</td>
                            <td class="right highlight-green">{{ number_format($customer->total_amount, 2, ',', ' ') }} €</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </td>
            <td>
                @if($topSuppliers->count() > 0)
                <div class="section-title">Top 10 Fournisseurs</div>
                <table class="data-grid">
                    <thead>
                        <tr>
                            <th>Fournisseur</th>
                            <th class="center">Commandes</th>
                            <th class="right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topSuppliers as $supplier)
                        <tr>
                            <td>{{ Str::limit($supplier->name, 25) }}</td>
                            <td class="center">{{ $supplier->orders_count }}</td>
                            <td class="right highlight-red">{{ number_format($supplier->total_amount, 2, ',', ' ') }} €</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </td>
        </tr>
    </table>

    {{-- Valeur du stock --}}
    <div class="section" style="margin-top: 20px;">
        <div class="section-title">Valorisation du Stock</div>
        <table class="two-col">
            <tr>
                <td>
                    <div class="card">
                        <div class="card-title">Valeur au prix d'achat</div>
                        <div class="card-value highlight-blue">{{ number_format($stockValue['achat'], 2, ',', ' ') }} €</div>
                        <div class="card-sub">Coût d'acquisition du stock actuel</div>
                    </div>
                </td>
                <td>
                    <div class="card">
                        <div class="card-title">Valeur au prix de vente</div>
                        <div class="card-value highlight-green">{{ number_format($stockValue['vente'], 2, ',', ' ') }} €</div>
                        <div class="card-sub">Potentiel de chiffre d'affaires</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Ventes par mode de paiement --}}
    @if($salesByPayment->count() > 0)
    <div class="section" style="margin-top: 15px;">
        <div class="section-title">Répartition par mode de paiement</div>
        <table class="data-grid">
            <thead>
                <tr>
                    <th>Mode de paiement</th>
                    <th class="center">Nombre</th>
                    <th class="right">Montant</th>
                    <th class="right">%</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalPayments = $salesByPayment->sum('total');
                    $paymentLabels = [
                        'cash' => 'Espèces',
                        'card' => 'Carte bancaire',
                        'mobile' => 'Paiement mobile',
                        'transfer' => 'Virement',
                        'check' => 'Chèque',
                        'mixed' => 'Mixte',
                    ];
                @endphp
                @foreach($salesByPayment as $payment)
                <tr>
                    <td>{{ $paymentLabels[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</td>
                    <td class="center">{{ $payment->count }}</td>
                    <td class="right">{{ number_format($payment->total, 2, ',', ' ') }} €</td>
                    <td class="right">{{ $totalPayments > 0 ? number_format(($payment->total / $totalPayments) * 100, 1) : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        {{ $company->name ?? 'FRECORP ERP' }} - Bilan comptable généré le {{ $generatedAt->format('d/m/Y à H:i') }}<br>
        Ce document est un récapitulatif à usage interne et ne constitue pas un document comptable officiel.
    </div>
</body>
</html>

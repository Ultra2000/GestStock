<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Caisse - {{ $session->opened_at->format('d/m/Y') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 22px;
            color: #7c3aed;
            margin-bottom: 5px;
        }
        .header .company {
            font-size: 14px;
            color: #666;
        }
        .header .date {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #7c3aed;
            padding: 8px 12px;
            background: #f3e8ff;
            border-radius: 5px;
            margin-bottom: 12px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label, .info-value {
            display: table-cell;
            padding: 6px 10px;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            width: 40%;
            color: #666;
            font-weight: 500;
        }
        .info-value {
            text-align: right;
            font-weight: bold;
        }
        .highlight {
            background: #f9fafb;
        }
        .payment-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .payment-grid th, .payment-grid td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .payment-grid th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .payment-grid .amount {
            text-align: right;
            font-weight: bold;
        }
        .payment-grid .count {
            text-align: center;
            color: #666;
        }
        table.sales {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.sales th, table.sales td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        table.sales th {
            background: #f9fafb;
            font-weight: 600;
        }
        table.sales .right {
            text-align: right;
        }
        table.sales .center {
            text-align: center;
        }
        .summary-box {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .summary-box h3 {
            font-size: 13px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .summary-box .total {
            font-size: 28px;
            font-weight: bold;
        }
        .summary-box .sub {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 5px;
        }
        .two-col {
            width: 100%;
        }
        .two-col td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        .two-col td:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
        .status-open {
            color: #059669;
            background: #d1fae5;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
        }
        .status-closed {
            color: #dc2626;
            background: #fee2e2;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
        }
        .difference-positive {
            color: #059669;
        }
        .difference-negative {
            color: #dc2626;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        .top-products {
            margin-top: 10px;
        }
        .top-products li {
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
            list-style: none;
        }
        .top-products .name {
            display: inline-block;
            width: 60%;
        }
        .top-products .qty {
            display: inline-block;
            width: 15%;
            text-align: center;
        }
        .top-products .amount {
            display: inline-block;
            width: 25%;
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>RAPPORT DE CAISSE</h1>
            @if($company)
                <div class="company">{{ $company->name }}</div>
            @endif
            <div class="date">{{ $session->opened_at->format('d/m/Y') }} - 
                @if($session->closed_at)
                    <span class="status-closed">Session clôturée</span>
                @else
                    <span class="status-open">Session en cours</span>
                @endif
            </div>
        </div>

        <div class="summary-box">
            <h3>TOTAL DES VENTES</h3>
            <div class="total">{{ number_format($session->total_sales, 2, ',', ' ') }} €</div>
            <div class="sub">{{ $session->sales_count }} ticket(s) - Caissier: {{ $session->user->name ?? 'N/A' }}</div>
        </div>

        <table class="two-col">
            <tr>
                <td>
                    <div class="section">
                        <div class="section-title">📋 Informations Session</div>
                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">Ouverture</div>
                                <div class="info-value">{{ $session->opened_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Fermeture</div>
                                <div class="info-value">{{ $session->closed_at ? $session->closed_at->format('d/m/Y H:i') : 'En cours' }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Durée</div>
                                <div class="info-value">
                                    @if($session->closed_at)
                                        {{ $session->opened_at->diffForHumans($session->closed_at, true) }}
                                    @else
                                        {{ $session->opened_at->diffForHumans(now(), true) }}
                                    @endif
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Nombre de tickets</div>
                                <div class="info-value">{{ $session->sales_count }}</div>
                            </div>
                            @if($session->sales_count > 0)
                            <div class="info-row">
                                <div class="info-label">Panier moyen</div>
                                <div class="info-value">{{ number_format($session->total_sales / $session->sales_count, 2, ',', ' ') }} €</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div class="section">
                        <div class="section-title">💰 Situation de Caisse</div>
                        <div class="info-grid">
                            <div class="info-row highlight">
                                <div class="info-label">Fond de caisse</div>
                                <div class="info-value">{{ number_format($session->opening_amount, 2, ',', ' ') }} €</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">+ Ventes espèces</div>
                                <div class="info-value">{{ number_format($session->total_cash, 2, ',', ' ') }} €</div>
                            </div>
                            <div class="info-row highlight">
                                <div class="info-label">= Attendu en caisse</div>
                                <div class="info-value">{{ number_format($session->expected_amount, 2, ',', ' ') }} €</div>
                            </div>
                            @if($session->closing_amount !== null)
                            <div class="info-row">
                                <div class="info-label">Montant compté</div>
                                <div class="info-value">{{ number_format($session->closing_amount, 2, ',', ' ') }} €</div>
                            </div>
                            <div class="info-row highlight">
                                <div class="info-label">Différence</div>
                                <div class="info-value {{ $session->difference >= 0 ? 'difference-positive' : 'difference-negative' }}">
                                    {{ $session->difference >= 0 ? '+' : '' }}{{ number_format($session->difference, 2, ',', ' ') }} €
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section">
            <div class="section-title">💳 Répartition par Mode de Paiement</div>
            <table class="payment-grid">
                <thead>
                    <tr>
                        <th>Mode de paiement</th>
                        <th class="count">Nb tickets</th>
                        <th class="amount">Montant</th>
                        <th class="amount">%</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>💵 Espèces</td>
                        <td class="count">{{ $paymentStats->get('cash')->count ?? 0 }}</td>
                        <td class="amount">{{ number_format($paymentStats->get('cash')->total ?? 0, 2, ',', ' ') }} €</td>
                        <td class="amount">{{ $session->total_sales > 0 ? number_format((($paymentStats->get('cash')->total ?? 0) / $session->total_sales) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td>💳 Carte bancaire</td>
                        <td class="count">{{ $paymentStats->get('card')->count ?? 0 }}</td>
                        <td class="amount">{{ number_format($paymentStats->get('card')->total ?? 0, 2, ',', ' ') }} €</td>
                        <td class="amount">{{ $session->total_sales > 0 ? number_format((($paymentStats->get('card')->total ?? 0) / $session->total_sales) * 100, 1) : 0 }}%</td>
                    </tr>
                    <tr>
                        <td>📱 Paiement mobile</td>
                        <td class="count">{{ $paymentStats->get('mobile')->count ?? 0 }}</td>
                        <td class="amount">{{ number_format($paymentStats->get('mobile')->total ?? 0, 2, ',', ' ') }} €</td>
                        <td class="amount">{{ $session->total_sales > 0 ? number_format((($paymentStats->get('mobile')->total ?? 0) / $session->total_sales) * 100, 1) : 0 }}%</td>
                    </tr>
                    @if(($paymentStats->get('mixed')->count ?? 0) > 0)
                    <tr>
                        <td>🔀 Mixte</td>
                        <td class="count">{{ $paymentStats->get('mixed')->count ?? 0 }}</td>
                        <td class="amount">{{ number_format($paymentStats->get('mixed')->total ?? 0, 2, ',', ' ') }} €</td>
                        <td class="amount">{{ $session->total_sales > 0 ? number_format((($paymentStats->get('mixed')->total ?? 0) / $session->total_sales) * 100, 1) : 0 }}%</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if($topProducts->count() > 0)
        <div class="section">
            <div class="section-title">🏆 Top 10 Produits Vendus</div>
            <ul class="top-products">
                @foreach($topProducts as $product)
                <li>
                    <span class="name">{{ $product->name }}</span>
                    <span class="qty">{{ $product->total_quantity }} unités</span>
                    <span class="amount">{{ number_format($product->total_amount, 2, ',', ' ') }} €</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        @if($sales->count() > 0)
        <div class="section">
            <div class="section-title">📝 Liste des Ventes ({{ $sales->count() }} tickets)</div>
            <table class="sales">
                <thead>
                    <tr>
                        <th>N° Ticket</th>
                        <th>Heure</th>
                        <th class="center">Mode</th>
                        <th class="center">Articles</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                    <tr>
                        <td>{{ $sale->invoice_number }}</td>
                        <td>{{ $sale->created_at->format('H:i') }}</td>
                        <td class="center">
                            @switch($sale->payment_method)
                                @case('cash') 💵 @break
                                @case('card') 💳 @break
                                @case('mobile') 📱 @break
                                @default 🔀
                            @endswitch
                        </td>
                        <td class="center">{{ $sale->items->sum('quantity') }}</td>
                        <td class="right">{{ number_format($sale->total, 2, ',', ' ') }} €</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="footer">
            Rapport généré le {{ now()->format('d/m/Y à H:i') }} | GestStock POS
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport TVA - {{ $company->name }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
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
        }
        .container {
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }
        .report-title {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .period {
            font-size: 12px;
            color: #333;
            margin-top: 10px;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border: 1px solid #ddd;
        }
        .summary-item {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            text-align: center;
            border-right: 1px solid #ddd;
        }
        .summary-item:last-child {
            border-right: none;
        }
        .summary-label {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 16px;
            font-weight: bold;
        }
        .collected { color: #16a34a; }
        .deductible { color: #2563eb; }
        .balance-positive { color: #ea580c; }
        .balance-negative { color: #16a34a; }
        
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            padding: 8px 10px;
            background: #f3f4f6;
            border-left: 3px solid #2563eb;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background: #f9fafb;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
        }
        .legal-note {
            background: #fef3c7;
            padding: 10px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 9px;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            background: #e5e7eb;
            border-radius: 3px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="company-name">{{ $company->name }}</div>
            <div class="report-title">Rapport de TVA</div>
            <div class="period">
                Période du {{ $period['start'] }} au {{ $period['end'] }}
            </div>
        </div>

        <!-- Résumé -->
        <div class="summary">
            <div class="summary-item">
                <div class="summary-label">TVA Collectée</div>
                <div class="summary-value collected">
                    {{ number_format($report['vat_collected'], 2, ',', ' ') }} {{ $currency }}
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-label">TVA Déductible</div>
                <div class="summary-value deductible">
                    {{ number_format($report['vat_deductible'], 2, ',', ' ') }} {{ $currency }}
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-label">
                    {{ $report['vat_to_pay'] >= 0 ? 'TVA à reverser' : 'Crédit de TVA' }}
                </div>
                <div class="summary-value {{ $report['vat_to_pay'] >= 0 ? 'balance-positive' : 'balance-negative' }}">
                    {{ number_format(abs($report['vat_to_pay']), 2, ',', ' ') }} {{ $currency }}
                </div>
            </div>
        </div>

        <!-- TVA Collectée -->
        <div class="section">
            <div class="section-title">📈 TVA Collectée (Ventes)</div>
            <p style="margin-bottom: 10px; color: #666;">
                Chiffre d'affaires HT : {{ number_format($report['sales_ht'], 2, ',', ' ') }} {{ $currency }}
            </p>
            @if(count($collected) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Taux</th>
                            <th>Catégorie</th>
                            <th class="text-right">Base HT</th>
                            <th class="text-right">Montant TVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($collected as $row)
                            <tr>
                                <td>{{ number_format($row['rate'], 1) }}%</td>
                                <td><span class="badge">{{ $row['category'] ?? 'S' }}</span></td>
                                <td class="text-right">{{ number_format($row['base'], 2, ',', ' ') }} {{ $currency }}</td>
                                <td class="text-right">{{ number_format($row['amount'], 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="2">Total</td>
                            <td class="text-right">{{ number_format($report['sales_ht'], 2, ',', ' ') }} {{ $currency }}</td>
                            <td class="text-right">{{ number_format($report['vat_collected'], 2, ',', ' ') }} {{ $currency }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <p style="text-align: center; color: #999; padding: 20px;">Aucune vente sur cette période</p>
            @endif
        </div>

        <!-- TVA Déductible -->
        <div class="section">
            <div class="section-title">📉 TVA Déductible (Achats)</div>
            <p style="margin-bottom: 10px; color: #666;">
                Total achats HT : {{ number_format($report['purchases_ht'], 2, ',', ' ') }} {{ $currency }}
            </p>
            @if(count($deductible) > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Taux</th>
                            <th class="text-right">Base HT</th>
                            <th class="text-right">Montant TVA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deductible as $row)
                            <tr>
                                <td>{{ number_format($row['rate'], 1) }}%</td>
                                <td class="text-right">{{ number_format($row['base'], 2, ',', ' ') }} {{ $currency }}</td>
                                <td class="text-right">{{ number_format($row['amount'], 2, ',', ' ') }} {{ $currency }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td>Total</td>
                            <td class="text-right">{{ number_format($report['purchases_ht'], 2, ',', ' ') }} {{ $currency }}</td>
                            <td class="text-right">{{ number_format($report['vat_deductible'], 2, ',', ' ') }} {{ $currency }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <p style="text-align: center; color: #999; padding: 20px;">Aucun achat sur cette période</p>
            @endif
        </div>

        <!-- Note légale -->
        <div class="legal-note">
            <strong>⚠️ Avertissement :</strong> Ce document est fourni à titre indicatif uniquement et ne constitue pas 
            une déclaration fiscale officielle. Les montants doivent être vérifiés par un expert-comptable avant toute 
            déclaration de TVA (CA3, CA12...).
        </div>

        <!-- Pied de page -->
        <div class="footer">
            <p>
                Document généré le {{ now()->translatedFormat('d F Y à H:i') }}<br>
                {{ $company->name }} - SIRET : {{ $company->siret ?? 'Non renseigné' }}<br>
                {{ $company->address ?? '' }}
            </p>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>État des Stocks - {{ $generatedAt->format('d/m/Y H:i') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        @page {
            margin: 15mm;
            size: A4 landscape;
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
            border-bottom: 3px solid #7c3aed;
        }
        .header h1 {
            font-size: 20px;
            color: #7c3aed;
            margin-bottom: 3px;
        }
        .header .company {
            font-size: 12px;
            color: #666;
            margin-bottom: 2px;
        }
        .header .date {
            font-size: 10px;
            color: #888;
        }
        .stats-grid {
            width: 100%;
            margin-bottom: 15px;
        }
        .stats-grid td {
            padding: 8px;
            text-align: center;
            vertical-align: top;
        }
        .stat-box {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 10px;
        }
        .stat-box.warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
        }
        .stat-box.danger {
            background: #fee2e2;
            border: 1px solid #ef4444;
        }
        .stat-box.success {
            background: #d1fae5;
            border: 1px solid #10b981;
        }
        .stat-box.primary {
            background: #ede9fe;
            border: 1px solid #7c3aed;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }
        .stat-label {
            font-size: 9px;
            color: #6b7280;
            margin-top: 3px;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #7c3aed;
            padding: 6px 10px;
            background: #f3e8ff;
            border-radius: 4px;
            margin: 12px 0 8px 0;
        }
        table.products {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        table.products th {
            background: #7c3aed;
            color: white;
            padding: 6px 4px;
            text-align: left;
            font-weight: 600;
        }
        table.products td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.products tr:nth-child(even) {
            background: #f9fafb;
        }
        table.products .right {
            text-align: right;
        }
        table.products .center {
            text-align: center;
        }
        .status-ok {
            color: #10b981;
            font-weight: bold;
        }
        .status-low {
            color: #f59e0b;
            font-weight: bold;
        }
        .status-critical {
            color: #ef4444;
            font-weight: bold;
        }
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
        .page-break {
            page-break-after: always;
        }
        .category-header {
            background: #e5e7eb;
            font-weight: bold;
            color: #374151;
        }
        .category-header td {
            padding: 6px 8px !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 ÉTAT DES STOCKS</h1>
        <div class="company">{{ $company->name ?? 'Entreprise' }}</div>
        <div class="date">Généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>
    </div>

    {{-- Statistiques globales --}}
    <table class="stats-grid">
        <tr>
            <td width="20%">
                <div class="stat-box primary">
                    <div class="stat-value">{{ number_format($stats['total_products']) }}</div>
                    <div class="stat-label">Produits</div>
                </div>
            </td>
            <td width="20%">
                <div class="stat-box success">
                    <div class="stat-value">{{ number_format($stats['total_value'], 2, ',', ' ') }} €</div>
                    <div class="stat-label">Valeur (prix achat)</div>
                </div>
            </td>
            <td width="20%">
                <div class="stat-box success">
                    <div class="stat-value">{{ number_format($stats['total_sell_value'], 2, ',', ' ') }} €</div>
                    <div class="stat-label">Valeur (prix vente)</div>
                </div>
            </td>
            <td width="20%">
                <div class="stat-box warning">
                    <div class="stat-value">{{ $stats['low_stock_count'] }}</div>
                    <div class="stat-label">Stock bas</div>
                </div>
            </td>
            <td width="20%">
                <div class="stat-box danger">
                    <div class="stat-value">{{ $stats['out_of_stock_count'] }}</div>
                    <div class="stat-label">Rupture</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Liste des produits par catégorie --}}
    @foreach($productsByCategory as $categoryName => $categoryProducts)
        <div class="section-title">{{ $categoryName }} ({{ $categoryProducts->count() }} produits)</div>
        
        <table class="products">
            <thead>
                <tr>
                    <th width="8%">Référence</th>
                    <th width="10%">Code-barres</th>
                    <th width="22%">Désignation</th>
                    <th width="10%">Entrepôt</th>
                    <th width="8%" class="center">Stock</th>
                    <th width="8%" class="center">Min</th>
                    <th width="10%" class="right">P. Achat</th>
                    <th width="10%" class="right">P. Vente</th>
                    <th width="10%" class="right">Valeur</th>
                    <th width="6%" class="center">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryProducts as $product)
                    @php
                        $stockStatus = 'ok';
                        $stockClass = 'status-ok';
                        $stock = $product->stock ?? 0;
                        $minStock = $product->min_stock ?? 0;
                        if ($stock <= 0) {
                            $stockStatus = 'RUPTURE';
                            $stockClass = 'status-critical';
                        } elseif ($stock <= $minStock) {
                            $stockStatus = 'BAS';
                            $stockClass = 'status-low';
                        } else {
                            $stockStatus = 'OK';
                        }
                        $value = $stock * ($product->purchase_price ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $product->code ?? '-' }}</td>
                        <td>{{ $product->barcode ?? '-' }}</td>
                        <td>{{ Str::limit($product->name, 35) }}</td>
                        <td>{{ $product->warehouses->first()?->name ?? 'Principal' }}</td>
                        <td class="center {{ $stockClass }}">{{ number_format($stock) }}</td>
                        <td class="center">{{ number_format($minStock) }}</td>
                        <td class="right">{{ number_format($product->purchase_price ?? 0, 2, ',', ' ') }} €</td>
                        <td class="right">{{ number_format($product->price ?? 0, 2, ',', ' ') }} €</td>
                        <td class="right">{{ number_format($value, 2, ',', ' ') }} €</td>
                        <td class="center {{ $stockClass }}">{{ $stockStatus }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        {{ $company->name ?? 'GestStock' }} - État des stocks généré le {{ $generatedAt->format('d/m/Y H:i') }} - Page <span class="pagenum"></span>
    </div>
</body>
</html>

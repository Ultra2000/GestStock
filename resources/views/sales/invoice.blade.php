<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
      
        .company-info {
            margin-bottom: 20px;
        }
        .invoice-info {
            margin-bottom: 20px;
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
            background-color: #f5f5f5;
        }
        .total {
            text-align: right;
            margin-top: 20px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
        }
        .legal-info {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Facture de vente</h1>
    </div>

    <div class="company-info">
     
        <h3>{{ $company->name }}</h3>
        <p><strong>Adresse:</strong> {{ $company->address }}</p>
        <p><strong>Téléphone:</strong> {{ $company->phone }}</p>
        <p><strong>Email:</strong> {{ $company->email }}</p>
        @if($company->website)
            <p><strong>Site web:</strong> {{ $company->website }}</p>
        @endif
        @if($company->tax_number)
            <p><strong>N° Taxe:</strong> {{ $company->tax_number }}</p>
        @endif
        @if($company->registration_number)
            <p><strong>N° Enregistrement:</strong> {{ $company->registration_number }}</p>
        @endif
    </div>

    <div class="invoice-info">
        <h3>Client</h3>
        <p><strong>Nom:</strong> {{ $sale->customer->name ?? 'N/A' }}</p>
        <p><strong>Adresse:</strong> {{ $sale->customer->address ?? 'N/A' }}</p>
        <p><strong>Téléphone:</strong> {{ $sale->customer->phone ?? 'N/A' }}</p>
        <p><strong>Email:</strong> {{ $sale->customer->email ?? 'N/A' }}</p>
    </div>

    <div class="invoice-info">
        <p><strong>Numéro de facture:</strong> {{ $sale->invoice_number }}</p>
        <p><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Quantité</th>
                <th>Prix unitaire</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product->name ?? 'Produit supprimé' }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }} €</td>
                <td>{{ number_format($item->total_price, 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <h3>Total: {{ number_format($sale->total_amount, 2) }} €</h3>
    </div>

    @if($company->footer_text)
    <div class="footer">
        <p>{{ $company->footer_text }}</p>
    </div>
    @endif
</body>
</html> 
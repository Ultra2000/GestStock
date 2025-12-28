<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Vérification facture {{ strtoupper($type) }} #{{ $invoiceNumber }}</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:40px;background:#f3f4f6;color:#111}
        .card{max-width:760px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:32px;box-shadow:0 4px 12px rgba(0,0,0,.05)}
        h1{margin:0 0 8px;font-size:22px;letter-spacing:.5px}
        .meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin:24px 0}
        .box{background:#f9fafb;border:1px solid #e5e7eb;padding:14px 16px;border-radius:8px}
        .label{font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:#6b7280;margin-bottom:4px;display:block}
        .status-ok{background:#dcfce7;color:#166534;padding:6px 12px;font-size:12px;font-weight:600;border-radius:20px;display:inline-block}
        .status-fail{background:#fee2e2;color:#991b1b;padding:6px 12px;font-size:12px;font-weight:600;border-radius:20px;display:inline-block}
        footer{text-align:center;margin-top:40px;font-size:12px;color:#6b7280}
        a.back{display:inline-block;margin-top:24px;text-decoration:none;background:#111827;color:#fff;padding:10px 18px;border-radius:6px;font-size:13px}
        table.items{width:100%;border-collapse:collapse;margin-top:16px}
        table.items th,table.items td{padding:8px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:12px}
        table.items th{background:#111827;color:#fff;font-weight:600}
    </style>
</head>
<body>
<div class="card">
    <h1>Vérification de la facture #{{ $invoiceNumber }}</h1>
    <p style="margin:0 0 20px;font-size:14px;color:#374151">Cette page confirme l'authenticité des informations essentielles de la facture.</p>

    <div class="meta">
        <div class="box">
            <span class="label">Type</span>
            <div>{{ $type === 'purchase' ? 'Achat' : 'Vente' }}</div>
        </div>
        <div class="box">
            <span class="label">Montant total</span>
            <div><strong>{{ number_format($amount,0,',',' ') }} FCFA</strong></div>
        </div>
        <div class="box">
            <span class="label">Date</span>
            <div>{{ $date->format('d/m/Y H:i') }}</div>
        </div>
        <div class="box">
            <span class="label">Code de contrôle</span>
            <div style="font-family:monospace">{{ $computedCode }}</div>
        </div>
    </div>

    <h2 style="font-size:15px;margin:30px 0 10px;letter-spacing:.5px;text-transform:uppercase;color:#111827">Articles</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width:45%">Produit</th>
                <th style="width:10%">Qté</th>
                <th style="width:20%">Prix Unit.</th>
                <th style="width:20%">Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($model->items as $it)
            <tr>
                <td>{{ $it->product->name ?? 'Produit supprimé' }}</td>
                <td>{{ $it->quantity }}</td>
                <td>{{ number_format($it->unit_price,0,',',' ') }} FCFA</td>
                <td>{{ number_format($it->total_price,0,',',' ') }} FCFA</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p style="margin-top:28px;font-size:13px;">Si ce code et ces montants ne correspondent pas à la facture physique/PDF en votre possession, contactez immédiatement l'émetteur.</p>

    <footer>
        Page de vérification générée le {{ now()->format('d/m/Y H:i') }} | Lien signé Laravel
    </footer>
</div>
</body>
</html>

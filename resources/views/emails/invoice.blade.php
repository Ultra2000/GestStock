<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111;line-height:1.5;">
    <h2 style="margin-top:0;">Facture #{{ $model->invoice_number }}</h2>
    <p>Bonjour,</p>
    @if($customMessage)
        <p style="white-space:pre-line;">{{ $customMessage }}</p>
    @else
        <p>Veuillez trouver ci-joint la facture (format PDF).</p>
    @endif

    <p>
        Montant total TTC: <strong>{{ number_format($model->total,0,',',' ') }} FCFA</strong><br>
        Date: {{ $model->created_at->format('d/m/Y H:i') }}<br>
        Type: {{ $type === 'purchase' ? 'Achat' : 'Vente' }}
    </p>

    <p style="font-size:12px;color:#555;">Cet email a été généré automatiquement. Merci de ne pas répondre directement si ce n'est pas nécessaire.</p>
</body>
</html>

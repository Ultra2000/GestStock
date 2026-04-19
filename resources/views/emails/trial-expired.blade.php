<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Période d'évaluation terminée</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.8); margin: 6px 0 0; font-size: 14px; }
        .body { padding: 36px 40px; }
        .alert { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; }
        .alert p { margin: 0; color: #991b1b; font-size: 15px; font-weight: 600; }
        .body p { color: #374151; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .btn { display: inline-block; background: #4f46e5; color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 15px; margin: 8px 0 24px; }
        .pricing { display: flex; gap: 16px; margin: 24px 0; }
        .plan { flex: 1; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; text-align: center; }
        .plan.featured { border-color: #4f46e5; background: #eef2ff; }
        .plan .price { font-size: 22px; font-weight: 800; color: #111827; }
        .plan .label { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .footer { background: #f9fafb; border-top: 1px solid #f3f4f6; padding: 20px 40px; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
        .footer a { color: #6366f1; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>FRECORP ERP</h1>
        <p>Votre accès est temporairement suspendu</p>
    </div>
    <div class="body">
        <div class="alert">
            <p>🔒 Votre période d'évaluation est terminée</p>
        </div>

        <p>Bonjour,</p>
        <p>
            La période d'évaluation gratuite de <strong>{{ $company->name }}</strong> est arrivée à son terme.
            Votre accès à FRECORP est actuellement suspendu.
        </p>
        <p>
            Souscrivez un abonnement pour retrouver immédiatement accès à toutes vos données et fonctionnalités :
        </p>

        <div class="pricing">
            <div class="plan">
                <div class="price">30 €<span style="font-size:13px;font-weight:400;color:#6b7280">/mois</span></div>
                <div class="label">Mensuel · Sans engagement</div>
            </div>
            <div class="plan featured">
                <div class="price">300 €<span style="font-size:13px;font-weight:400;color:#6b7280">/an</span></div>
                <div class="label">Annuel · 2 mois offerts</div>
            </div>
        </div>

        <div style="text-align:center">
            <a href="{{ url('/admin/' . $company->slug . '/subscription-expired') }}" class="btn">
                Réactiver mon accès →
            </a>
        </div>

        <p style="color:#6b7280;font-size:13px">
            Vos données sont conservées. Dès votre abonnement activé, vous retrouvez tout tel quel.<br>
            Des questions ? <a href="mailto:contact@frecorp.fr" style="color:#4f46e5">contact@frecorp.fr</a>
        </p>
    </div>
    <div class="footer">
        <p>FRECORP ERP · <a href="https://frecorp.fr">frecorp.fr</a></p>
    </div>
</div>
</body>
</html>

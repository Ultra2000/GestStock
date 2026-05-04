<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour FRECORP ERP</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.8); margin: 8px 0 0; font-size: 14px; }
        .body { padding: 36px 40px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .message-box { background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; border-radius: 10px; padding: 20px 24px; margin: 24px 0; }
        .message-box p { margin: 0; color: #1e40af; font-size: 15px; line-height: 1.7; }
        .btn { display: inline-block; background: #4f46e5; color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 15px; margin: 8px 0 24px; }
        .footer { background: #f9fafb; border-top: 1px solid #f3f4f6; padding: 20px 40px; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
        .footer a { color: #6366f1; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h1>📢 Mise à jour FRECORP ERP</h1>
        <p>{{ now()->translatedFormat('d F Y') }}</p>
    </div>

    <div class="body">
        <p>Bonjour {{ $recipientName }},</p>

        <p>Nous avons le plaisir de vous informer d'une mise à jour de votre application FRECORP ERP.</p>

        <div class="message-box">
            <p>{{ $message }}</p>
        </div>

        <p>Cette mise à jour a été appliquée automatiquement. Vous n'avez aucune action à effectuer.</p>

        <p style="text-align:center; margin-top: 32px;">
            <a href="{{ config('app.url') }}" class="btn">Accéder à mon espace</a>
        </p>

        <p style="font-size:13px; color:#6b7280;">
            Si vous avez des questions ou remarques, n'hésitez pas à nous contacter à
            <a href="mailto:contact@frecorp.fr" style="color:#4f46e5;">contact@frecorp.fr</a>.
        </p>
    </div>

    <div class="footer">
        <p>FRECORP ERP · <a href="{{ config('app.url') }}">frecorp.fr</a></p>
        <p style="margin-top:6px;">Vous recevez cet email en tant qu'administrateur de votre espace.</p>
    </div>

</div>
</body>
</html>

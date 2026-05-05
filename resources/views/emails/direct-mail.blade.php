<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mailSubject }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 580px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 36px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: rgba(255,255,255,0.8); margin: 8px 0 0; font-size: 14px; }
        .body { padding: 36px 40px; }
        .body p { color: #374151; font-size: 15px; line-height: 1.7; margin: 0 0 16px; }
        .message-box { background: #f5f3ff; border: 1px solid #ddd6fe; border-left: 4px solid #6366f1; border-radius: 10px; padding: 20px 24px; margin: 24px 0; white-space: pre-line; }
        .message-box p { margin: 0; color: #1e1b4b; font-size: 15px; line-height: 1.7; }
        .btn { display: inline-block; background: #4f46e5; color: #fff !important; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 15px; margin: 8px 0 24px; }
        .footer { background: #f9fafb; border-top: 1px solid #f3f4f6; padding: 20px 40px; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 0; }
        .footer a { color: #6366f1; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <h1>✉️ {{ $mailSubject }}</h1>
        <p>{{ now()->translatedFormat('d F Y') }}</p>
    </div>

    <div class="body">
        <p>Bonjour {{ $recipientName }},</p>

        <div class="message-box">
            <p>{{ $mailBody }}</p>
        </div>

        <p style="text-align:center; margin-top: 32px;">
            <a href="{{ config('app.url') }}" class="btn">Accéder à mon espace</a>
        </p>

        <p style="font-size:13px; color:#6b7280;">
            Si vous avez des questions, contactez-nous à
            <a href="mailto:contact@frecorp.fr" style="color:#4f46e5;">contact@frecorp.fr</a>.
        </p>
    </div>

    <div class="footer">
        <p>FRECORP ERP · <a href="{{ config('app.url') }}">frecorp.fr</a></p>
    </div>

</div>
</body>
</html>

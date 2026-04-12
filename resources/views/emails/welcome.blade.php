<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur FRECORP ERP</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f5f9;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

                    <!-- Header gradient avec logo -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#1e1b4b 0%,#4c1d95 50%,#6d28d9 100%);padding:40px 48px;text-align:center;">
                            <!-- Logo SVG inline -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 50" width="160" height="40" style="display:block;margin:0 auto;">
                                <defs>
                                    <linearGradient id="icon-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#a5b4fc;stop-opacity:1"/>
                                        <stop offset="100%" style="stop-color:#c4b5fd;stop-opacity:1"/>
                                    </linearGradient>
                                    <linearGradient id="name-grad" x1="0%" y1="0%" x2="100%" y2="0%">
                                        <stop offset="0%" style="stop-color:#ffffff;stop-opacity:1"/>
                                        <stop offset="100%" style="stop-color:#a5b4fc;stop-opacity:1"/>
                                    </linearGradient>
                                </defs>
                                <rect x="2" y="5" width="40" height="40" rx="10" fill="url(#icon-grad)"/>
                                <rect x="8" y="28" width="14" height="10" rx="2" fill="white" opacity="0.7"/>
                                <rect x="12" y="22" width="14" height="10" rx="2" fill="white" opacity="0.85"/>
                                <rect x="16" y="16" width="14" height="10" rx="2" fill="white"/>
                                <text x="52" y="35" font-family="Inter, Arial, sans-serif" font-size="28" font-weight="800" fill="url(#name-grad)">FRECORP</text>
                            </svg>
                            <p style="margin:16px 0 0;color:#c4b5fd;font-size:13px;letter-spacing:2px;text-transform:uppercase;font-weight:600;">ERP · Gestion d'entreprise</p>
                        </td>
                    </tr>

                    <!-- Corps -->
                    <tr>
                        <td style="padding:48px 48px 32px;">

                            <!-- Icône de bienvenue -->
                            <div style="text-align:center;margin-bottom:32px;">
                                <div style="display:inline-block;background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:50%;width:72px;height:72px;line-height:72px;font-size:32px;">
                                    🎉
                                </div>
                            </div>

                            <h1 style="margin:0 0 8px;font-size:26px;font-weight:800;color:#1e1b4b;text-align:center;">
                                Bienvenue, {{ $userName }} !
                            </h1>
                            <p style="margin:0 0 32px;font-size:15px;color:#64748b;text-align:center;">
                                Votre compte FRECORP ERP a été créé avec succès.
                            </p>

                            <!-- Carte info compte -->
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border-radius:12px;border:1px solid #e2e8f0;margin-bottom:32px;">
                                <tr>
                                    <td style="padding:24px 28px;">
                                        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;">Votre compte</p>
                                        <p style="margin:0 0 16px;font-size:16px;font-weight:700;color:#1e293b;">{{ $userEmail }}</p>
                                        <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:1px;">Date d'inscription</p>
                                        <p style="margin:0;font-size:15px;color:#1e293b;">{{ $registeredAt }}</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Étapes suivantes -->
                            <p style="margin:0 0 16px;font-size:14px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:1px;">Prochaines étapes</p>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #f1f5f9;">
                                        <table cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;text-align:center;vertical-align:middle;color:white;font-weight:700;font-size:14px;">1</td>
                                                <td style="padding-left:16px;font-size:14px;color:#374151;vertical-align:middle;">Connectez-vous à votre espace</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #f1f5f9;">
                                        <table cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;text-align:center;vertical-align:middle;color:white;font-weight:700;font-size:14px;">2</td>
                                                <td style="padding-left:16px;font-size:14px;color:#374151;vertical-align:middle;">Créez votre entreprise avec votre numéro SIREN</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;">
                                        <table cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:50%;text-align:center;vertical-align:middle;color:white;font-weight:700;font-size:14px;">3</td>
                                                <td style="padding-left:16px;font-size:14px;color:#374151;vertical-align:middle;">Configurez vos modules et commencez à gérer</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Bouton CTA -->
                            <div style="text-align:center;margin-bottom:32px;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:16px 40px;border-radius:50px;letter-spacing:0.5px;">
                                    Accéder à mon espace →
                                </a>
                            </div>

                            <p style="margin:0;font-size:13px;color:#94a3b8;text-align:center;">
                                Des questions ? Écrivez-nous à <a href="mailto:frejusbouraima@frecorp.fr" style="color:#6366f1;text-decoration:none;">frejusbouraima@frecorp.fr</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 48px;text-align:center;">
                            <p style="margin:0 0 8px;font-size:12px;color:#94a3b8;">
                                © {{ date('Y') }} FRECORP ERP · Tous droits réservés
                            </p>
                            <p style="margin:0;font-size:11px;color:#cbd5e1;">
                                Vous recevez cet email car vous venez de créer un compte sur
                                <a href="{{ config('app.url') }}" style="color:#6366f1;text-decoration:none;">frecorp.fr</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis accepté</title>
</head>
<body style="margin:0;padding:0;font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background-color:#f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:32px;text-align:center;">
                            <div style="font-size:48px;margin-bottom:12px;">✅</div>
                            <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:700;">Devis accepté !</h1>
                            <p style="margin:8px 0 0;color:#d1fae5;font-size:15px;">
                                Un client vient d'accepter votre devis
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding:40px 32px;">
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#374151;">
                                Bonjour,<br><br>
                                <strong>{{ $quote->customer?->name ?? 'Un client' }}</strong> vient d'accepter le devis
                                <strong>{{ $quote->quote_number }}</strong> le {{ now()->format('d/m/Y à H:i') }}.
                            </p>

                            <!-- Info box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0fdf4;border-radius:8px;margin-bottom:32px;border:1px solid #bbf7d0;">
                                <tr>
                                    <td style="padding:24px;">
                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="color:#64748b;font-size:14px;font-weight:600;">N° Devis</td>
                                                <td style="color:#1e293b;font-size:14px;font-weight:700;text-align:right;">{{ $quote->quote_number }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color:#64748b;font-size:14px;font-weight:600;">Client</td>
                                                <td style="color:#1e293b;font-size:14px;text-align:right;">{{ $quote->customer?->name ?? '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td style="color:#64748b;font-size:14px;font-weight:600;">Date d'acceptation</td>
                                                <td style="color:#1e293b;font-size:14px;text-align:right;">{{ $quote->accepted_at?->format('d/m/Y à H:i') ?? now()->format('d/m/Y à H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="padding-top:12px;border-top:1px solid #bbf7d0;">
                                                    <table width="100%" cellpadding="8" cellspacing="0">
                                                        <tr>
                                                            <td style="color:#64748b;font-size:16px;font-weight:700;">Montant TTC</td>
                                                            <td style="color:#10b981;font-size:24px;font-weight:700;text-align:right;">
                                                                {{ number_format($quote->total, 2, ',', ' ') }} {{ $quote->company->currency ?? 'EUR' }}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#374151;">
                                Vous pouvez maintenant convertir ce devis en facture depuis votre espace de gestion.
                            </p>

                            <!-- CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/') }}"
                                           style="display:inline-block;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:8px;font-weight:600;font-size:16px;">
                                            Voir le devis dans l'ERP
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc;padding:24px 32px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;text-align:center;line-height:1.5;">
                                Notification automatique — {{ $quote->company->name }} · FRECORP ERP
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

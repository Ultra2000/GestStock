<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis {{ $quote->quote_number }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">
                                {{ $quote->user->company->name }}
                            </h1>
                            <p style="margin: 8px 0 0; color: #e0e7ff; font-size: 16px;">
                                Nouveau devis disponible
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 32px;">
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #374151;">
                                Bonjour <strong>{{ $quote->customer->name ?? 'cher client' }}</strong>,
                            </p>

                            @if($customMessage)
                                <div style="background-color: #f8fafc; border-left: 4px solid #667eea; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
                                    <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #475569;">
                                        {{ $customMessage }}
                                    </p>
                                </div>
                            @else
                                <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.6; color: #374151;">
                                    Nous avons le plaisir de vous transmettre notre proposition commerciale détaillée en pièce jointe.
                                </p>
                            @endif

                            <!-- Devis Info Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 8px; margin-bottom: 32px; border: 1px solid #e2e8f0;">
                                <tr>
                                    <td style="padding: 24px;">
                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="color: #64748b; font-size: 14px; font-weight: 600;">N° Devis :</td>
                                                <td style="color: #1e293b; font-size: 14px; font-weight: 700; text-align: right;">
                                                    {{ $quote->quote_number }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-size: 14px; font-weight: 600;">Date :</td>
                                                <td style="color: #1e293b; font-size: 14px; text-align: right;">
                                                    {{ $quote->quote_date->format('d/m/Y') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color: #64748b; font-size: 14px; font-weight: 600;">Valable jusqu'au :</td>
                                                <td style="color: #ef4444; font-size: 14px; font-weight: 600; text-align: right;">
                                                    {{ $quote->valid_until->format('d/m/Y') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="padding-top: 12px; border-top: 1px solid #e2e8f0;">
                                                    <table width="100%" cellpadding="8" cellspacing="0">
                                                        <tr>
                                                            <td style="color: #64748b; font-size: 16px; font-weight: 700;">Montant total TTC :</td>
                                                            <td style="color: #667eea; font-size: 24px; font-weight: 700; text-align: right;">
                                                                {{ number_format($quote->total, 2, ',', ' ') }} €
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 32px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $quote->getPublicUrl() }}" 
                                           style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 16px 40px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                                            📄 Consulter le devis en ligne
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #64748b; text-align: center;">
                                Vous pouvez <strong>accepter ou refuser ce devis</strong> directement en ligne en un clic.
                            </p>

                            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin-bottom: 24px; border-radius: 4px;">
                                <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #92400e;">
                                    <strong>⚠️ Important :</strong> Ce devis est valable jusqu'au <strong>{{ $quote->valid_until->format('d/m/Y') }}</strong>. 
                                    Passé ce délai, une nouvelle proposition devra être établie.
                                </p>
                            </div>

                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6; color: #374151;">
                                Le PDF du devis est également joint à cet email pour votre convenance.
                            </p>

                            <p style="margin: 0; font-size: 15px; line-height: 1.6; color: #374151;">
                                Pour toute question, n'hésitez pas à nous contacter.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 32px; border-top: 1px solid #e2e8f0;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="color: #64748b; font-size: 14px; line-height: 1.6;">
                                        <strong style="color: #1e293b; display: block; margin-bottom: 8px;">
                                            {{ $quote->user->company->name }}
                                        </strong>
                                        @if($quote->user->company->address)
                                            {{ $quote->user->company->address }}<br>
                                        @endif
                                        @if($quote->user->company->phone)
                                            Tél : {{ $quote->user->company->phone }}<br>
                                        @endif
                                        @if($quote->user->company->email)
                                            Email : {{ $quote->user->company->email }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 16px;">
                                        <p style="margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                                            Cet email a été généré automatiquement par GestStock. 
                                            Le lien de consultation du devis expire à la date de validité indiquée.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

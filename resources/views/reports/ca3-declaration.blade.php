<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déclaration CA3 - {{ $period['label'] }}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 3px solid #1e293b; }
        .company-name { font-size: 18px; font-weight: bold; color: #1e293b; }
        .company-info { font-size: 10px; color: #64748b; margin-top: 4px; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 16px; font-weight: bold; color: #1e293b; }
        .doc-title .period { font-size: 12px; color: #64748b; margin-top: 4px; }
        .doc-title .generated { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        /* Section headers */
        .section-header { background: #1e293b; color: #fff; padding: 6px 12px; font-size: 10px; font-weight: bold; letter-spacing: 0.05em; text-transform: uppercase; margin-top: 20px; margin-bottom: 0; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th { background: #f8fafc; padding: 8px 12px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        th.right, td.right { text-align: right; }
        td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
        tr.total td { background: #f8fafc; font-weight: bold; border-top: 2px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; }
        tr.result-due td { background: #fef2f2; color: #b91c1c; font-weight: bold; font-size: 12px; }
        tr.result-credit td { background: #f0fdf4; color: #15803d; font-weight: bold; font-size: 12px; }

        /* Case CA3 badge */
        .case-badge { display: inline-block; background: #e2e8f0; color: #475569; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }

        /* Summary boxes */
        .summary { display: flex; gap: 12px; margin-bottom: 20px; }
        .summary-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center; }
        .summary-box.due { border-color: #fca5a5; background: #fef2f2; }
        .summary-box.credit { border-color: #86efac; background: #f0fdf4; }
        .summary-box .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .summary-box .amount { font-size: 18px; font-weight: bold; color: #1e293b; margin-top: 4px; }
        .summary-box.due .amount { color: #b91c1c; }
        .summary-box.credit .amount { color: #15803d; }

        /* Footer */
        .footer { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e2e8f0; }
        .footer-warning { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 4px; padding: 8px 12px; font-size: 9px; color: #92400e; }
        .footer-meta { text-align: center; font-size: 9px; color: #94a3b8; margin-top: 10px; }
    </style>
</head>
<body>

    {{-- En-tête --}}
    <div class="header">
        <div>
            <div class="company-name">{{ $company->name }}</div>
            <div class="company-info">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->tax_number)N° TVA : {{ $company->tax_number }}<br>@endif
                @if($company->siret)SIRET : {{ $company->siret }}@endif
            </div>
        </div>
        <div class="doc-title">
            <h1>Déclaration de TVA (CA3)</h1>
            <div class="period">{{ $period['label'] }}</div>
            <div class="period" style="font-size:10px; color:#475569;">Du {{ $period['start'] }} au {{ $period['end'] }}</div>
            <div class="generated">Généré le {{ now()->translatedFormat('d F Y') }}</div>
        </div>
    </div>

    {{-- Résumé --}}
    <div class="summary">
        <div class="summary-box">
            <div class="label">TVA collectée (ligne 11)</div>
            <div class="amount">{{ number_format($declaration['total_vat_collected'], 2, ',', ' ') }} {{ $currency }}</div>
        </div>
        <div class="summary-box">
            <div class="label">TVA déductible (ligne 21)</div>
            <div class="amount">{{ number_format($declaration['total_vat_deductible'], 2, ',', ' ') }} {{ $currency }}</div>
        </div>
        @if($declaration['vat_due'] > 0)
        <div class="summary-box due">
            <div class="label">TVA à payer (ligne 55)</div>
            <div class="amount">{{ number_format($declaration['vat_due'], 2, ',', ' ') }} {{ $currency }}</div>
        </div>
        @else
        <div class="summary-box credit">
            <div class="label">Crédit de TVA (ligne 56)</div>
            <div class="amount">{{ number_format($declaration['vat_credit'], 2, ',', ' ') }} {{ $currency }}</div>
        </div>
        @endif
    </div>

    {{-- TVA collectée --}}
    <div class="section-header">Opérations imposables — TVA collectée</div>
    <table>
        <thead>
            <tr>
                <th>Taux</th>
                <th>Case CA3</th>
                <th class="right">Base HT ({{ $currency }})</th>
                <th class="right">TVA ({{ $currency }})</th>
            </tr>
        </thead>
        <tbody>
            @if($declaration['base_20'] > 0 || $declaration['vat_20'] > 0)
            <tr>
                <td>Taux normal 20 %</td>
                <td><span class="case-badge">01</span> <span class="case-badge">08</span></td>
                <td class="right">{{ number_format($declaration['base_20'], 2, ',', ' ') }}</td>
                <td class="right">{{ number_format($declaration['vat_20'], 2, ',', ' ') }}</td>
            </tr>
            @endif
            @if($declaration['base_10'] > 0 || $declaration['vat_10'] > 0)
            <tr>
                <td>Taux intermédiaire 10 %</td>
                <td><span class="case-badge">02</span> <span class="case-badge">09</span></td>
                <td class="right">{{ number_format($declaration['base_10'], 2, ',', ' ') }}</td>
                <td class="right">{{ number_format($declaration['vat_10'], 2, ',', ' ') }}</td>
            </tr>
            @endif
            @if($declaration['base_55'] > 0 || $declaration['vat_55'] > 0)
            <tr>
                <td>Taux réduit 5,5 %</td>
                <td><span class="case-badge">03</span> <span class="case-badge">9A</span></td>
                <td class="right">{{ number_format($declaration['base_55'], 2, ',', ' ') }}</td>
                <td class="right">{{ number_format($declaration['vat_55'], 2, ',', ' ') }}</td>
            </tr>
            @endif
            @if($declaration['base_21'] > 0 || $declaration['vat_21'] > 0)
            <tr>
                <td>Taux particulier 2,1 %</td>
                <td><span class="case-badge">05</span> <span class="case-badge">9B</span></td>
                <td class="right">{{ number_format($declaration['base_21'], 2, ',', ' ') }}</td>
                <td class="right">{{ number_format($declaration['vat_21'], 2, ',', ' ') }}</td>
            </tr>
            @endif
            @if($declaration['base_other'] > 0 || $declaration['vat_other'] > 0)
            <tr>
                <td>Autres taux</td>
                <td>—</td>
                <td class="right">{{ number_format($declaration['base_other'], 2, ',', ' ') }}</td>
                <td class="right">{{ number_format($declaration['vat_other'], 2, ',', ' ') }}</td>
            </tr>
            @endif
            <tr class="total">
                <td colspan="2">Total TVA brute <span class="case-badge">Ligne 11</span></td>
                <td class="right">—</td>
                <td class="right">{{ number_format($declaration['total_vat_collected'], 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- TVA déductible --}}
    <div class="section-header">TVA déductible</div>
    <table>
        <thead>
            <tr>
                <th>Nature</th>
                <th>Case CA3</th>
                <th class="right">Montant ({{ $currency }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>TVA sur biens et services</td>
                <td><span class="case-badge">Ligne 20</span></td>
                <td class="right">{{ number_format($declaration['vat_deductible_goods'], 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td>TVA sur immobilisations</td>
                <td><span class="case-badge">Ligne 19</span></td>
                <td class="right">{{ number_format($declaration['vat_deductible_assets'], 2, ',', ' ') }}</td>
            </tr>
            <tr class="total">
                <td colspan="2">Total TVA déductible <span class="case-badge">Ligne 21</span></td>
                <td class="right">{{ number_format($declaration['total_vat_deductible'], 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Résultat --}}
    <div class="section-header">Résultat de la déclaration</div>
    <table>
        <tbody>
            @if($declaration['vat_due'] > 0)
            <tr class="result-due">
                <td>TVA nette due — à reverser au Trésor Public</td>
                <td><span class="case-badge">Ligne 55</span></td>
                <td class="right">{{ number_format($declaration['vat_due'], 2, ',', ' ') }} {{ $currency }}</td>
            </tr>
            @else
            <tr class="result-credit">
                <td>Crédit de TVA — à reporter ou à rembourser</td>
                <td><span class="case-badge">Ligne 56</span></td>
                <td class="right">{{ number_format($declaration['vat_credit'], 2, ',', ' ') }} {{ $currency }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-warning">
            <strong>Document indicatif.</strong>
            Ces chiffres sont calculés à partir des ventes et achats enregistrés dans FRECORP ERP sur la période du {{ $period['start'] }} au {{ $period['end'] }}.
            Vérifiez les montants avant de reporter sur votre déclaration officielle sur impots.gouv.fr.
            En cas de doute, consultez votre expert-comptable.
        </div>
        <div class="footer-meta">
            {{ $company->name }} — Déclaration CA3 {{ $period['label'] }} — Généré par FRECORP ERP
        </div>
    </div>

</body>
</html>

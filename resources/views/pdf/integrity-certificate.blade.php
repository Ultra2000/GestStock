<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificat d'Intégrité Comptable</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1a1a1a;
            background: #fff;
        }

        .container {
            padding: 30px 40px;
        }

        /* Header */
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #0066cc;
            margin-bottom: 25px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #0066cc;
            letter-spacing: 2px;
        }

        .logo span {
            color: #ff6600;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a1a;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .subtitle {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .certificate-number {
            font-size: 10px;
            color: #999;
            margin-top: 10px;
        }

        /* Score Section */
        .score-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0066cc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .score-label {
            font-size: 14px;
            color: #0066cc;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .score-value {
            font-size: 56px;
            font-weight: bold;
            color: {{ $health_score['is_perfect'] ? '#16a34a' : '#dc2626' }};
        }

        .score-max {
            font-size: 24px;
            color: #666;
        }

        .score-status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            background: {{ $health_score['is_perfect'] ? '#dcfce7' : '#fee2e2' }};
            color: {{ $health_score['is_perfect'] ? '#166534' : '#991b1b' }};
        }

        /* Company Info */
        .company-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
        }

        .company-details {
            font-size: 11px;
            color: #64748b;
            margin-top: 5px;
        }

        /* Audit Details Grid */
        .audit-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .audit-row {
            display: table-row;
        }

        .audit-cell {
            display: table-cell;
            width: 50%;
            padding: 8px;
            vertical-align: top;
        }

        .audit-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            height: 100%;
        }

        .audit-card.valid {
            border-left: 4px solid #16a34a;
            background: #f0fdf4;
        }

        .audit-card.invalid {
            border-left: 4px solid #dc2626;
            background: #fef2f2;
        }

        .audit-title {
            font-size: 12px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 8px;
        }

        .audit-status {
            font-size: 11px;
            font-weight: bold;
        }

        .audit-status.valid {
            color: #16a34a;
        }

        .audit-status.invalid {
            color: #dc2626;
        }

        .audit-detail {
            font-size: 10px;
            color: #6b7280;
            margin-top: 5px;
        }

        /* Statistics Table */
        .stats-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .stats-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stats-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .stats-table td:first-child {
            color: #64748b;
            width: 60%;
        }

        .stats-table td:last-child {
            text-align: right;
            font-weight: bold;
            color: #1a1a1a;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        /* Hash Section */
        .hash-section {
            background: #1e293b;
            color: #fff;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .hash-title {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .hash-value {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9px;
            word-break: break-all;
            color: #22d3ee;
        }

        .hash-algo {
            font-size: 9px;
            color: #64748b;
            margin-top: 5px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        .attestation {
            font-size: 11px;
            color: #374151;
            text-align: justify;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .signature-line {
            margin-top: 30px;
            display: table;
            width: 100%;
        }

        .signature-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
        }

        .signature-label {
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 5px;
            margin-top: 40px;
            display: inline-block;
            width: 80%;
        }

        .timestamp {
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            margin-top: 20px;
        }

        .legal-notice {
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
            margin-top: 15px;
            font-style: italic;
        }

        /* Watermark for non-100 scores */
        @if(!$health_score['is_perfect'])
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(220, 38, 38, 0.1);
            font-weight: bold;
            z-index: -1;
        }
        @endif
    </style>
</head>
<body>
    @if(!$health_score['is_perfect'])
    <div class="watermark">ANOMALIES</div>
    @endif

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">FRE<span>CORP</span></div>
            <div class="title">Certificat d'Intégrité Comptable</div>
            <div class="subtitle">Auto-Audit du Système de Gestion</div>
            <div class="certificate-number">N° {{ $certificate_number }}</div>
        </div>

        <!-- Score Principal -->
        <div class="score-section">
            <div class="score-label">Score de Santé du Système</div>
            <div>
                <span class="score-value">{{ $health_score['score'] }}</span>
                <span class="score-max">/ {{ $health_score['max'] }}</span>
            </div>
            <div class="score-status">
                @if($health_score['is_perfect'])
                    ✓ SYSTÈME CONFORME
                @else
                    ⚠ ANOMALIES DÉTECTÉES
                @endif
            </div>
        </div>

        <!-- Informations Entreprise -->
        <div class="company-section">
            <div class="company-name">{{ $company->name ?? 'Entreprise' }}</div>
            <div class="company-details">
                @if($company->siret ?? false)
                    SIRET : {{ $company->siret }} |
                @endif
                Période auditée : {{ $period['start'] }} au {{ $period['end'] }}
                @if($settings->vat_regime ?? false)
                    | Régime TVA : {{ $settings->vat_regime === 'encaissements' ? 'Encaissements' : 'Débits' }}
                @endif
            </div>
        </div>

        <!-- Détails de l'Audit -->
        <div class="section-title">Résultats de l'Auto-Audit</div>
        
        <div class="audit-grid">
            <div class="audit-row">
                <div class="audit-cell">
                    <div class="audit-card {{ $audit_data['sales_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">📊 Intégrité des Ventes (30 pts)</div>
                        <div class="audit-status {{ $audit_data['sales_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['sales_integrity']['is_valid'] ? '✓ CONFORME' : '✗ ÉCART DÉTECTÉ' }}
                        </div>
                        <div class="audit-detail">
                            {{ $audit_data['sales_integrity']['count'] }} factures |
                            CA métier : {{ number_format($audit_data['sales_integrity']['metier_ht'], 2, ',', ' ') }} € |
                            CA comptable : {{ number_format($audit_data['sales_integrity']['comptable_ht'], 2, ',', ' ') }} €
                        </div>
                    </div>
                </div>
                <div class="audit-cell">
                    <div class="audit-card {{ $audit_data['purchases_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">📦 Intégrité des Achats (20 pts)</div>
                        <div class="audit-status {{ $audit_data['purchases_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['purchases_integrity']['is_valid'] ? '✓ CONFORME' : '✗ ÉCART DÉTECTÉ' }}
                        </div>
                        <div class="audit-detail">
                            {{ $audit_data['purchases_integrity']['count'] }} achats |
                            Charges métier : {{ number_format($audit_data['purchases_integrity']['metier_ht'], 2, ',', ' ') }} € |
                            Charges comptables : {{ number_format($audit_data['purchases_integrity']['comptable_ht'], 2, ',', ' ') }} €
                        </div>
                    </div>
                </div>
            </div>
            <div class="audit-row">
                <div class="audit-cell">
                    <div class="audit-card {{ $audit_data['sequence_audit']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">🔢 Continuité des Séquences (25 pts)</div>
                        <div class="audit-status {{ $audit_data['sequence_audit']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['sequence_audit']['is_valid'] ? '✓ SÉQUENCES CONTINUES' : '✗ RUPTURES DÉTECTÉES' }}
                        </div>
                        <div class="audit-detail">
                            {{ $audit_data['sequence_audit']['total_entries'] }} écritures FEC |
                            {{ $audit_data['sequence_audit']['total_invoices'] }} factures |
                            @if(!$audit_data['sequence_audit']['is_valid'])
                                {{ $audit_data['sequence_audit']['fec_gaps_count'] }} trou(s) FEC,
                                {{ $audit_data['sequence_audit']['invoice_gaps_count'] }} facture(s) manquante(s)
                            @else
                                Aucune rupture
                            @endif
                        </div>
                    </div>
                </div>
                <div class="audit-cell">
                    <div class="audit-card {{ $audit_data['vat_coherence']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">💶 Cohérence TVA (25 pts)</div>
                        <div class="audit-status {{ $audit_data['vat_coherence']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['vat_coherence']['is_valid'] ? '✓ TVA COHÉRENTE' : '✗ ÉCART TVA' }}
                        </div>
                        <div class="audit-detail">
                            Régime : {{ $audit_data['vat_coherence']['regime'] === 'encaissements' ? 'Encaissements' : 'Débits' }} |
                            TVA théorique : {{ number_format($audit_data['vat_coherence']['theoretical_vat'], 2, ',', ' ') }} € |
                            @if($audit_data['vat_coherence']['regime'] === 'encaissements')
                                En attente : {{ number_format($audit_data['vat_coherence']['pending_vat'], 2, ',', ' ') }} €
                            @else
                                Comptabilisée : {{ number_format($audit_data['vat_coherence']['accounted_vat'], 2, ',', ' ') }} €
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques Clés -->
        <div class="stats-section">
            <div class="section-title">Statistiques du Grand Livre</div>
            <table class="stats-table">
                <tr>
                    <td>Nombre total d'écritures comptables</td>
                    <td>{{ number_format($stats['total_entries'], 0, ',', ' ') }}</td>
                </tr>
                <tr>
                    <td>Dernière séquence FEC</td>
                    <td>{{ $stats['last_fec_sequence'] }}</td>
                </tr>
                <tr>
                    <td>Total des débits</td>
                    <td>{{ number_format($stats['total_debit'], 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <td>Total des crédits</td>
                    <td>{{ number_format($stats['total_credit'], 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <td>Journaux utilisés</td>
                    <td>{{ $stats['journals_used'] }}</td>
                </tr>
                <tr>
                    <td>Comptes utilisés</td>
                    <td>{{ $stats['accounts_used'] }}</td>
                </tr>
            </table>
        </div>

        <!-- Hash d'Intégrité -->
        <div class="hash-section">
            <div class="hash-title">🔐 Empreinte Numérique d'Intégrité</div>
            <div class="hash-value">{{ $integrity_hash }}</div>
            <div class="hash-algo">Algorithme : {{ $hash_algorithm }} | Cette empreinte garantit l'authenticité du document</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="attestation">
                <strong>ATTESTATION :</strong> Le présent certificat atteste qu'à la date de génération ci-dessous, 
                le système de gestion FRECORP a procédé à un auto-audit complet de son module comptable. 
                Les vérifications ont porté sur : l'intégrité des données entre le module commercial et le Grand Livre, 
                la continuité des séquences de numérotation (conformité NF525), et la cohérence des écritures de TVA.
                @if($health_score['is_perfect'])
                    <strong>Aucune anomalie n'a été détectée.</strong>
                @else
                    <strong style="color: #dc2626;">Des anomalies ont été détectées et doivent être corrigées.</strong>
                @endif
            </div>

            <div class="signature-line">
                <div class="signature-cell">
                    <div class="signature-label">Signature du Responsable</div>
                </div>
                <div class="signature-cell">
                    <div class="signature-label">Cachet de l'Entreprise</div>
                </div>
            </div>

            <div class="timestamp">
                Document généré automatiquement le {{ $generated_at->format('d/m/Y à H:i:s') }} (UTC)
            </div>

            <div class="legal-notice">
                Ce document est généré automatiquement par le système FRECORP et n'a pas de valeur légale en tant que tel.
                Il constitue un outil d'aide à la gestion et à la préparation des contrôles fiscaux.
                L'empreinte SHA-256 permet de vérifier que le document n'a pas été modifié après sa génération.
            </div>
        </div>
    </div>
</body>
</html>

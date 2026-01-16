<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificat d'Intégrité Comptable - {{ $certificate_number }}</title>
    <style>
        /* Reset et Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            margin: 0;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #2d3748;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Container principal */
        .page {
            width: 100%;
            min-height: 100%;
            padding: 25px 35px;
            position: relative;
        }

        /* Bordure décorative */
        .border-frame {
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 25px;
            min-height: 95%;
        }

        /* ========== HEADER ========== */
        .header {
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 3px solid #2563eb;
            margin-bottom: 20px;
        }

        .logo {
            font-size: 32pt;
            font-weight: bold;
            letter-spacing: 3px;
            margin-bottom: 5px;
        }

        .logo-fre {
            color: #2563eb;
        }

        .logo-corp {
            color: #f97316;
        }

        .doc-title {
            font-size: 18pt;
            font-weight: bold;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 10px;
        }

        .doc-subtitle {
            font-size: 9pt;
            color: #64748b;
            margin-top: 5px;
        }

        .cert-number {
            font-size: 8pt;
            color: #94a3b8;
            margin-top: 8px;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        /* ========== SCORE SECTION ========== */
        .score-box {
            background: #f0f9ff;
            border: 2px solid #2563eb;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }

        .score-label {
            font-size: 10pt;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .score-value {
            font-size: 48pt;
            font-weight: bold;
            color: {{ $health_score['is_perfect'] ? '#16a34a' : '#dc2626' }};
            line-height: 1;
        }

        .score-max {
            font-size: 20pt;
            color: #64748b;
            font-weight: normal;
        }

        .score-badge {
            display: inline-block;
            padding: 8px 25px;
            border-radius: 20px;
            font-size: 11pt;
            font-weight: bold;
            margin-top: 12px;
            background: {{ $health_score['is_perfect'] ? '#dcfce7' : '#fee2e2' }};
            color: {{ $health_score['is_perfect'] ? '#166534' : '#991b1b' }};
            border: 1px solid {{ $health_score['is_perfect'] ? '#86efac' : '#fca5a5' }};
        }

        /* ========== COMPANY INFO ========== */
        .company-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 18px;
        }

        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #1e293b;
        }

        .company-info {
            font-size: 9pt;
            color: #64748b;
            margin-top: 4px;
        }

        /* ========== SECTION TITLES ========== */
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a5f;
            padding-bottom: 6px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 12px;
            margin-top: 5px;
        }

        /* ========== AUDIT CARDS ========== */
        .audit-grid {
            width: 100%;
            margin-bottom: 18px;
        }

        .audit-row {
            width: 100%;
            margin-bottom: 10px;
        }

        .audit-row:after {
            content: "";
            display: table;
            clear: both;
        }

        .audit-card {
            width: 48%;
            float: left;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-right: 2%;
            background: #ffffff;
        }

        .audit-card:last-child {
            margin-right: 0;
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
            font-size: 9pt;
            font-weight: bold;
            color: #374151;
            margin-bottom: 6px;
        }

        .audit-status {
            font-size: 9pt;
            font-weight: bold;
        }

        .audit-status.valid {
            color: #16a34a;
        }

        .audit-status.invalid {
            color: #dc2626;
        }

        .audit-detail {
            font-size: 7.5pt;
            color: #6b7280;
            margin-top: 5px;
            line-height: 1.3;
        }

        /* ========== STATS TABLE ========== */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 9pt;
        }

        .stats-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .stats-table td:first-child {
            color: #64748b;
            width: 65%;
        }

        .stats-table td:last-child {
            text-align: right;
            font-weight: bold;
            color: #1e293b;
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 9pt;
        }

        /* ========== HASH SECTION ========== */
        .hash-box {
            background: #1e293b;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 18px;
        }

        .hash-title {
            font-size: 8pt;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .hash-value {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7.5pt;
            color: #22d3ee;
            word-break: break-all;
            line-height: 1.4;
        }

        .hash-algo {
            font-size: 7pt;
            color: #64748b;
            margin-top: 6px;
        }

        /* ========== FOOTER ========== */
        .footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
        }

        .attestation {
            font-size: 8.5pt;
            color: #374151;
            text-align: justify;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .attestation strong {
            color: #1e293b;
        }

        .signature-area {
            width: 100%;
            margin-top: 20px;
        }

        .signature-area:after {
            content: "";
            display: table;
            clear: both;
        }

        .signature-block {
            width: 45%;
            float: left;
            text-align: center;
        }

        .signature-block:last-child {
            float: right;
        }

        .signature-line {
            border-top: 1px solid #cbd5e1;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 8pt;
            color: #64748b;
        }

        .timestamp {
            text-align: center;
            font-size: 8pt;
            color: #94a3b8;
            margin-top: 20px;
        }

        .legal-notice {
            text-align: center;
            font-size: 7pt;
            color: #9ca3af;
            font-style: italic;
            margin-top: 10px;
            line-height: 1.4;
        }

        /* ========== WATERMARK ========== */
        @if(!$health_score['is_perfect'])
        .watermark {
            position: fixed;
            top: 45%;
            left: 15%;
            font-size: 70pt;
            color: rgba(220, 38, 38, 0.08);
            font-weight: bold;
            transform: rotate(-35deg);
            z-index: -1;
            letter-spacing: 10px;
        }
        @endif

        /* ========== SCORE DETAILS ========== */
        .score-details {
            width: 100%;
            margin-top: 15px;
        }

        .score-details:after {
            content: "";
            display: table;
            clear: both;
        }

        .score-item {
            width: 23%;
            float: left;
            margin-right: 2%;
            text-align: center;
            padding: 8px 5px;
            border-radius: 5px;
        }

        .score-item:last-child {
            margin-right: 0;
        }

        .score-item.valid {
            background: #dcfce7;
        }

        .score-item.invalid {
            background: #fee2e2;
        }

        .score-item-value {
            font-size: 14pt;
            font-weight: bold;
        }

        .score-item.valid .score-item-value {
            color: #16a34a;
        }

        .score-item.invalid .score-item-value {
            color: #dc2626;
        }

        .score-item-label {
            font-size: 7pt;
            color: #64748b;
            margin-top: 2px;
        }

        /* Clear float helper */
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    @if(!$health_score['is_perfect'])
    <div class="watermark">ANOMALIES</div>
    @endif

    <div class="page">
        <div class="border-frame">
            
            <!-- HEADER -->
            <div class="header">
                <div class="logo">
                    <span class="logo-fre">FRE</span><span class="logo-corp">CORP</span>
                </div>
                <div class="doc-title">Certificat d'Intégrité Comptable</div>
                <div class="doc-subtitle">Auto-Audit du Système de Gestion</div>
                <div class="cert-number">N° {{ $certificate_number }}</div>
            </div>

            <!-- SCORE PRINCIPAL -->
            <div class="score-box">
                <div class="score-label">Score de Santé du Système</div>
                <div>
                    <span class="score-value">{{ $health_score['score'] }}</span>
                    <span class="score-max">/ {{ $health_score['max'] }}</span>
                </div>
                <div class="score-badge">
                    @if($health_score['is_perfect'])
                        ✓ SYSTÈME CONFORME
                    @else
                        ⚠ ANOMALIES DÉTECTÉES
                    @endif
                </div>

                <!-- Détail des scores -->
                <div class="score-details clearfix">
                    <div class="score-item {{ $health_score['details']['sales']['valid'] ? 'valid' : 'invalid' }}">
                        <div class="score-item-value">{{ $health_score['details']['sales']['score'] }}/{{ $health_score['details']['sales']['max'] }}</div>
                        <div class="score-item-label">Ventes</div>
                    </div>
                    <div class="score-item {{ $health_score['details']['purchases']['valid'] ? 'valid' : 'invalid' }}">
                        <div class="score-item-value">{{ $health_score['details']['purchases']['score'] }}/{{ $health_score['details']['purchases']['max'] }}</div>
                        <div class="score-item-label">Achats</div>
                    </div>
                    <div class="score-item {{ $health_score['details']['sequences']['valid'] ? 'valid' : 'invalid' }}">
                        <div class="score-item-value">{{ $health_score['details']['sequences']['score'] }}/{{ $health_score['details']['sequences']['max'] }}</div>
                        <div class="score-item-label">Séquences</div>
                    </div>
                    <div class="score-item {{ $health_score['details']['vat']['valid'] ? 'valid' : 'invalid' }}">
                        <div class="score-item-value">{{ $health_score['details']['vat']['score'] }}/{{ $health_score['details']['vat']['max'] }}</div>
                        <div class="score-item-label">TVA</div>
                    </div>
                </div>
            </div>

            <!-- INFORMATIONS ENTREPRISE -->
            <div class="company-box">
                <div class="company-name">{{ $company->name ?? 'Entreprise' }}</div>
                <div class="company-info">
                    @if($company->siret ?? false)
                        SIRET : {{ $company->siret }} &nbsp;|&nbsp;
                    @endif
                    Période auditée : {{ $period['start'] }} au {{ $period['end'] }}
                    @if($settings->vat_regime ?? false)
                        &nbsp;|&nbsp; Régime TVA : {{ $settings->vat_regime === 'encaissements' ? 'Encaissements' : 'Débits' }}
                    @endif
                </div>
            </div>

            <!-- RÉSULTATS AUDIT -->
            <div class="section-title">Résultats de l'Auto-Audit</div>

            <div class="audit-grid">
                <!-- Ligne 1 : Ventes & Achats -->
                <div class="audit-row clearfix">
                    <div class="audit-card {{ $audit_data['sales_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">📊 Intégrité des Ventes</div>
                        <div class="audit-status {{ $audit_data['sales_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['sales_integrity']['is_valid'] ? '✓ CONFORME' : '✗ ÉCART DÉTECTÉ' }}
                        </div>
                        <div class="audit-detail">
                            {{ $audit_data['sales_integrity']['count'] }} factures<br>
                            CA métier : {{ number_format($audit_data['sales_integrity']['metier_ht'], 2, ',', ' ') }} €<br>
                            CA comptable : {{ number_format($audit_data['sales_integrity']['comptable_ht'], 2, ',', ' ') }} €
                        </div>
                    </div>
                    <div class="audit-card {{ $audit_data['purchases_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">📦 Intégrité des Achats</div>
                        <div class="audit-status {{ $audit_data['purchases_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['purchases_integrity']['is_valid'] ? '✓ CONFORME' : '✗ ÉCART DÉTECTÉ' }}
                        </div>
                        <div class="audit-detail">
                            {{ $audit_data['purchases_integrity']['count'] }} achats<br>
                            Charges métier : {{ number_format($audit_data['purchases_integrity']['metier_ht'], 2, ',', ' ') }} €<br>
                            Charges comptables : {{ number_format($audit_data['purchases_integrity']['comptable_ht'], 2, ',', ' ') }} €
                        </div>
                    </div>
                </div>

                <!-- Ligne 2 : Séquences & TVA -->
                <div class="audit-row clearfix">
                    <div class="audit-card {{ $audit_data['sequence_audit']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">🔢 Continuité des Séquences (NF525)</div>
                        <div class="audit-status {{ $audit_data['sequence_audit']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['sequence_audit']['is_valid'] ? '✓ SÉQUENCES CONTINUES' : '✗ RUPTURES DÉTECTÉES' }}
                        </div>
                        <div class="audit-detail">
                            {{ $audit_data['sequence_audit']['total_entries'] }} écritures FEC<br>
                            {{ $audit_data['sequence_audit']['total_invoices'] }} factures<br>
                            @if(!$audit_data['sequence_audit']['is_valid'])
                                {{ $audit_data['sequence_audit']['fec_gaps_count'] }} trou(s) FEC, {{ $audit_data['sequence_audit']['invoice_gaps_count'] }} facture(s) manquante(s)
                            @else
                                Aucune rupture de numérotation
                            @endif
                        </div>
                    </div>
                    <div class="audit-card {{ $audit_data['vat_coherence']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">💶 Cohérence TVA</div>
                        <div class="audit-status {{ $audit_data['vat_coherence']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['vat_coherence']['is_valid'] ? '✓ TVA COHÉRENTE' : '✗ ÉCART TVA' }}
                        </div>
                        <div class="audit-detail">
                            Régime : {{ $audit_data['vat_coherence']['regime'] === 'encaissements' ? 'Encaissements' : 'Débits' }}<br>
                            TVA théorique : {{ number_format($audit_data['vat_coherence']['theoretical_vat'], 2, ',', ' ') }} €<br>
                            @if($audit_data['vat_coherence']['regime'] === 'encaissements')
                                TVA en attente : {{ number_format($audit_data['vat_coherence']['pending_vat'], 2, ',', ' ') }} €
                            @else
                                TVA comptabilisée : {{ number_format($audit_data['vat_coherence']['accounted_vat'], 2, ',', ' ') }} €
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATISTIQUES -->
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
                    <td>Journaux comptables utilisés</td>
                    <td>{{ $stats['journals_used'] }}</td>
                </tr>
                <tr>
                    <td>Comptes du plan comptable utilisés</td>
                    <td>{{ $stats['accounts_used'] }}</td>
                </tr>
            </table>

            <!-- HASH D'INTÉGRITÉ -->
            <div class="hash-box">
                <div class="hash-title">🔐 Empreinte Numérique d'Intégrité</div>
                <div class="hash-value">{{ $integrity_hash }}</div>
                <div class="hash-algo">Algorithme : {{ $hash_algorithm }} — Cette empreinte garantit l'authenticité et l'intégrité du document</div>
            </div>

            <!-- FOOTER -->
            <div class="footer">
                <div class="attestation">
                    <strong>ATTESTATION :</strong> Le présent certificat atteste qu'à la date de génération ci-dessous, 
                    le système de gestion FRECORP a procédé à un auto-audit complet de son module comptable. 
                    Les vérifications ont porté sur : l'intégrité des données entre le module commercial et le Grand Livre, 
                    la continuité des séquences de numérotation (conformité NF525), et la cohérence des écritures de TVA.
                    @if($health_score['is_perfect'])
                        <strong style="color: #16a34a;">Aucune anomalie n'a été détectée.</strong>
                    @else
                        <strong style="color: #dc2626;">Des anomalies ont été détectées et doivent être corrigées avant certification.</strong>
                    @endif
                </div>

                <div class="signature-area clearfix">
                    <div class="signature-block">
                        <div class="signature-line">Signature du Responsable</div>
                    </div>
                    <div class="signature-block">
                        <div class="signature-line">Cachet de l'Entreprise</div>
                    </div>
                </div>

                <div class="timestamp">
                    Document généré automatiquement le {{ $generated_at->format('d/m/Y') }} à {{ $generated_at->format('H:i:s') }} (UTC)
                </div>

                <div class="legal-notice">
                    Ce document est généré automatiquement par le système FRECORP et constitue un outil d'aide à la gestion 
                    et à la préparation des contrôles fiscaux. L'empreinte SHA-256 permet de vérifier que le document 
                    n'a pas été modifié après sa génération.
                </div>
            </div>

        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificat d'Intégrité Comptable - {{ $certificate_number }}</title>
    <style>
        /* ============================================================
           CERTIFICAT D'INTÉGRITÉ - Design Professionnel & Moderne
           Compatible DomPDF - Sans Flexbox/Grid
           ============================================================ */
        
        /* Reset et Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            margin: 0;
            size: A4;
        }

        body {
            font-family: 'Helvetica', 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Container principal */
        .page {
            width: 100%;
            min-height: 100%;
            padding: 30px 40px;
            position: relative;
        }

        /* Bordure élégante et discrète */
        .border-frame {
            border: 1px solid #e2e8f0;
            padding: 35px 40px;
            min-height: 95%;
            background: #ffffff;
        }

        /* ========== HEADER - Style Institutionnel ========== */
        .header {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 28pt;
            font-weight: bold;
            letter-spacing: -1px;
            margin-bottom: 8px;
        }

        .logo-fre {
            color: #1e3a5f;
        }

        .logo-corp {
            color: #0ea5e9;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 12px;
        }

        .doc-subtitle {
            font-size: 9pt;
            color: #64748b;
            margin-top: 6px;
            font-weight: normal;
        }

        .cert-number {
            font-size: 8pt;
            color: #94a3b8;
            margin-top: 10px;
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            letter-spacing: 1px;
        }

        /* ========== SCORE SECTION - Style Card Moderne ========== */
        .score-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 28px;
            text-align: center;
        }

        .score-label {
            font-size: 9pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .score-value {
            font-size: 56pt;
            font-weight: 800;
            color: {{ $health_score['is_perfect'] ? '#10b981' : '#ef4444' }};
            line-height: 1;
            letter-spacing: -3px;
        }

        .score-max {
            font-size: 22pt;
            color: #94a3b8;
            font-weight: 400;
        }

        .score-badge {
            display: inline-block;
            padding: 10px 30px;
            border-radius: 25px;
            font-size: 10pt;
            font-weight: bold;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: {{ $health_score['is_perfect'] ? '#ecfdf5' : '#fef2f2' }};
            color: {{ $health_score['is_perfect'] ? '#047857' : '#b91c1c' }};
            border: 2px solid {{ $health_score['is_perfect'] ? '#10b981' : '#ef4444' }};
        }

        /* ========== COMPANY INFO - Style Discret ========== */
        .company-box {
            background: #fafafa;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 25px;
        }

        .company-name {
            font-size: 12pt;
            font-weight: bold;
            color: #0f172a;
        }

        .company-info {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 5px;
        }

        /* ========== SECTION TITLES - Style Épuré ========== */
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 2px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 15px;
            margin-top: 8px;
        }

        /* ========== AUDIT CARDS - Style Dashboard Moderne ========== */
        .audit-grid {
            width: 100%;
            margin-bottom: 25px;
        }

        .audit-row {
            width: 100%;
            margin-bottom: 12px;
        }

        .audit-row:after {
            content: "";
            display: table;
            clear: both;
        }

        .audit-card {
            width: 48%;
            float: left;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            padding: 15px;
            margin-right: 2%;
            background: #ffffff;
            border-top-width: 4px;
            border-top-style: solid;
        }

        .audit-card:last-child {
            margin-right: 0;
        }

        .audit-card.valid {
            border-top-color: #10b981;
            background: #ffffff;
        }

        .audit-card.invalid {
            border-top-color: #ef4444;
            background: #fffbfb;
        }

        .audit-icon {
            font-size: 14pt;
            margin-bottom: 5px;
        }

        .audit-title {
            font-size: 8.5pt;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .audit-status {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .audit-status.valid {
            color: #059669;
        }

        .audit-status.invalid {
            color: #dc2626;
        }

        .audit-detail {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 8px;
            line-height: 1.5;
            border-top: 1px solid #f1f5f9;
            padding-top: 8px;
        }

        /* ========== STATS TABLE - Style Minimaliste ========== */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 9pt;
        }

        .stats-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .stats-table tr:last-child td {
            border-bottom: none;
        }

        .stats-table td:first-child {
            color: #64748b;
            width: 65%;
        }

        .stats-table td:last-child {
            text-align: right;
            font-weight: 600;
            color: #0f172a;
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            font-size: 9pt;
        }

        /* ========== HASH SECTION - Style Code Terminal ========== */
        .hash-box {
            background: #0f172a;
            border-left: 4px solid #3b82f6;
            border-radius: 0 6px 6px 0;
            padding: 15px 18px;
            margin-bottom: 25px;
        }

        .hash-title {
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }

        .hash-value {
            font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
            font-size: 7pt;
            color: #38bdf8;
            word-break: break-all;
            line-height: 1.6;
            letter-spacing: 0.5px;
        }

        .hash-algo {
            font-size: 7pt;
            color: #475569;
            margin-top: 8px;
            font-style: italic;
        }

        /* ========== FOOTER - Plus Espacé ========== */
        .footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .attestation {
            font-size: 8pt;
            color: #475569;
            text-align: justify;
            line-height: 1.6;
            margin-bottom: 20px;
            padding: 15px;
            background: #fafafa;
            border-radius: 6px;
        }

        .attestation strong {
            color: #0f172a;
        }

        .signature-area {
            width: 100%;
            margin-top: 35px;
            padding-top: 15px;
        }

        .signature-area:after {
            content: "";
            display: table;
            clear: both;
        }

        .signature-block {
            width: 42%;
            float: left;
            text-align: center;
            padding: 0 15px;
        }

        .signature-block:last-child {
            float: right;
        }

        .signature-line {
            border-top: 1px solid #cbd5e1;
            margin-top: 60px;
            padding-top: 8px;
            font-size: 8pt;
            color: #64748b;
        }

        .timestamp {
            text-align: center;
            font-size: 7.5pt;
            color: #94a3b8;
            margin-top: 30px;
        }

        .legal-notice {
            text-align: center;
            font-size: 6.5pt;
            color: #9ca3af;
            font-style: italic;
            margin-top: 12px;
            line-height: 1.5;
            padding: 0 20px;
        }

        /* ========== WATERMARK ========== */
        @if(!$health_score['is_perfect'])
        .watermark {
            position: fixed;
            top: 45%;
            left: 12%;
            font-size: 65pt;
            color: rgba(239, 68, 68, 0.06);
            font-weight: bold;
            transform: rotate(-35deg);
            z-index: -1;
            letter-spacing: 8px;
            text-transform: uppercase;
        }
        @endif

        /* ========== SCORE DETAILS - Style Pill ========== */
        .score-details {
            width: 100%;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
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
            padding: 10px 8px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .score-item:last-child {
            margin-right: 0;
        }

        .score-item.valid {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .score-item.invalid {
            background: #fef2f2;
            border-color: #fecaca;
        }

        .score-item-value {
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .score-item.valid .score-item-value {
            color: #059669;
        }

        .score-item.invalid .score-item-value {
            color: #dc2626;
        }

        .score-item-label {
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
                        <div class="audit-title">Intégrité des Ventes</div>
                        <div class="audit-status {{ $audit_data['sales_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['sales_integrity']['is_valid'] ? '✓ Conforme' : '✗ Écart détecté' }}
                        </div>
                        <div class="audit-detail">
                            <strong>{{ $audit_data['sales_integrity']['count'] }}</strong> factures analysées<br>
                            CA métier : <strong>{{ number_format($audit_data['sales_integrity']['metier_ht'], 2, ',', ' ') }} €</strong><br>
                            CA comptable : <strong>{{ number_format($audit_data['sales_integrity']['comptable_ht'], 2, ',', ' ') }} €</strong>
                        </div>
                    </div>
                    <div class="audit-card {{ $audit_data['purchases_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">Intégrité des Achats</div>
                        <div class="audit-status {{ $audit_data['purchases_integrity']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['purchases_integrity']['is_valid'] ? '✓ Conforme' : '✗ Écart détecté' }}
                        </div>
                        <div class="audit-detail">
                            <strong>{{ $audit_data['purchases_integrity']['count'] }}</strong> achats analysés<br>
                            Charges métier : <strong>{{ number_format($audit_data['purchases_integrity']['metier_ht'], 2, ',', ' ') }} €</strong><br>
                            Charges comptables : <strong>{{ number_format($audit_data['purchases_integrity']['comptable_ht'], 2, ',', ' ') }} €</strong>
                        </div>
                    </div>
                </div>

                <!-- Ligne 2 : Séquences & TVA -->
                <div class="audit-row clearfix">
                    <div class="audit-card {{ $audit_data['sequence_audit']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">Continuité des Séquences NF525</div>
                        <div class="audit-status {{ $audit_data['sequence_audit']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['sequence_audit']['is_valid'] ? '✓ Séquences continues' : '✗ Ruptures détectées' }}
                        </div>
                        <div class="audit-detail">
                            <strong>{{ $audit_data['sequence_audit']['total_entries'] }}</strong> écritures FEC<br>
                            <strong>{{ $audit_data['sequence_audit']['total_invoices'] }}</strong> factures<br>
                            @if(!$audit_data['sequence_audit']['is_valid'])
                                <span style="color: #dc2626;">{{ $audit_data['sequence_audit']['fec_gaps_count'] }} trou(s) FEC, {{ $audit_data['sequence_audit']['invoice_gaps_count'] }} facture(s) manquante(s)</span>
                            @else
                                <span style="color: #059669;">Aucune rupture de numérotation</span>
                            @endif
                        </div>
                    </div>
                    <div class="audit-card {{ $audit_data['vat_coherence']['is_valid'] ? 'valid' : 'invalid' }}">
                        <div class="audit-title">Cohérence TVA</div>
                        <div class="audit-status {{ $audit_data['vat_coherence']['is_valid'] ? 'valid' : 'invalid' }}">
                            {{ $audit_data['vat_coherence']['is_valid'] ? '✓ TVA cohérente' : '✗ Écart TVA' }}
                        </div>
                        <div class="audit-detail">
                            Régime : <strong>{{ $audit_data['vat_coherence']['regime'] === 'encaissements' ? 'Encaissements' : 'Débits' }}</strong><br>
                            TVA théorique : <strong>{{ number_format($audit_data['vat_coherence']['theoretical_vat'], 2, ',', ' ') }} €</strong><br>
                            @if($audit_data['vat_coherence']['regime'] === 'encaissements')
                                TVA en attente : <strong>{{ number_format($audit_data['vat_coherence']['pending_vat'], 2, ',', ' ') }} €</strong>
                            @else
                                TVA comptabilisée : <strong>{{ number_format($audit_data['vat_coherence']['accounted_vat'], 2, ',', ' ') }} €</strong>
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
                <div class="hash-title">Empreinte Numérique d'Intégrité</div>
                <div class="hash-value">{{ $integrity_hash }}</div>
                <div class="hash-algo">{{ $hash_algorithm }} — Cette empreinte garantit l'authenticité et l'intégrité du document</div>
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

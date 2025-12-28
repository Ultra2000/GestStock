<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Facture {{ $sale->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #1e293b;
            --primary-light: #334155;
            --accent: #3b82f6;
            --accent-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 13px;
            color: var(--gray-800);
            background: #fff;
            line-height: 1.5;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        
        /* Header avec gradient */
        .invoice-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            box-shadow: 0 10px 40px rgba(30, 41, 59, 0.15);
        }
        
        .company-info h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        
        .company-info .subtitle {
            font-size: 13px;
            opacity: 0.8;
            font-weight: 400;
        }
        
        .company-details {
            margin-top: 16px;
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .invoice-meta {
            text-align: right;
            min-width: 200px;
        }
        
        .invoice-number {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -1px;
        }
        
        .invoice-number span {
            font-size: 14px;
            opacity: 0.7;
            font-weight: 400;
            display: block;
            margin-bottom: 4px;
        }
        
        .invoice-date {
            margin-top: 12px;
            font-size: 13px;
            opacity: 0.8;
        }
        
        .logo-container {
            margin-bottom: 16px;
        }
        
        .logo-container img {
            max-height: 60px;
            max-width: 150px;
            object-fit: contain;
            background-color: white;
            padding: 5px;
            border-radius: 4px;
        }
        
        /* Preview Banner */
        .preview-banner {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 1px solid var(--accent);
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--accent-dark);
        }
        
        .preview-banner a {
            color: var(--accent-dark);
            font-weight: 600;
            text-decoration: none;
            padding: 6px 16px;
            background: white;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .preview-banner a:hover {
            background: var(--accent-dark);
            color: white;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-completed {
            background: rgba(16, 185, 129, 0.15);
            color: #059669;
        }
        .status-completed::before { background: #10b981; }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
        }
        .status-pending::before { background: #f59e0b; }
        
        .status-cancelled {
            background: rgba(239, 68, 68, 0.15);
            color: #dc2626;
        }
        .status-cancelled::before { background: #ef4444; }
        
        /* Cards Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .info-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.2s;
        }
        
        .info-card:hover {
            border-color: var(--accent);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }
        
        .info-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .info-card-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        
        .info-card-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gray-500);
        }
        
        .info-card-content h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 6px;
        }
        
        .info-card-content p {
            font-size: 12px;
            color: var(--gray-500);
            line-height: 1.6;
        }
        
        /* Table moderne */
        .items-section {
            margin-bottom: 32px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: linear-gradient(180deg, var(--accent) 0%, var(--accent-dark) 100%);
            border-radius: 2px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .items-table thead {
            background: linear-gradient(135deg, var(--gray-800) 0%, var(--gray-700) 100%);
        }
        
        .items-table thead th {
            padding: 14px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
        }
        
        .items-table thead th:last-child {
            text-align: right;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.2s;
        }
        
        .items-table tbody tr:hover {
            background: var(--gray-50);
        }
        
        .items-table tbody tr:last-child {
            border-bottom: none;
        }
        
        .items-table tbody td {
            padding: 14px 16px;
            font-size: 13px;
        }
        
        .items-table tbody td:last-child {
            text-align: right;
            font-weight: 600;
        }
        
        .product-name {
            font-weight: 500;
            color: var(--gray-800);
        }
        
        .text-right { text-align: right; }
        .text-muted { color: var(--gray-500); }
        
        /* Totals Card */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 32px;
        }
        
        .totals-card {
            width: 320px;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 20px;
            border-bottom: 1px solid var(--gray-200);
            font-size: 13px;
        }
        
        .totals-row:last-child {
            border-bottom: none;
        }
        
        .totals-row .label {
            color: var(--gray-500);
        }
        
        .totals-row .value {
            font-weight: 500;
        }
        
        .totals-row.discount .value {
            color: var(--success);
        }
        
        .totals-row.grand-total {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 16px 20px;
        }
        
        .totals-row.grand-total .label {
            color: rgba(255,255,255,0.8);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 12px;
        }
        
        .totals-row.grand-total .value {
            font-size: 20px;
            font-weight: 700;
        }
        
        .amount-words {
            padding: 12px 20px;
            background: white;
            font-size: 11px;
            font-style: italic;
            color: var(--gray-500);
            border-top: 1px dashed var(--gray-300);
        }
        
        /* QR Verification */
        .verification-section {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .qr-code {
            flex-shrink: 0;
            padding: 8px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .qr-code img {
            display: block;
            width: 100px;
            height: 100px;
        }
        
        .verification-info h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .verification-info p {
            font-size: 11px;
            color: var(--gray-500);
            line-height: 1.6;
        }
        
        .verification-code {
            display: inline-block;
            font-family: 'SF Mono', Monaco, monospace;
            background: var(--gray-800);
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            margin-top: 6px;
        }
        
        /* Footer */
        .invoice-footer {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
            color: var(--gray-500);
            font-size: 11px;
            line-height: 1.6;
        }
        
        /* Actions */
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 32px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 41, 59, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }
        
        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--gray-400);
        }
        
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .invoice-container { padding: 20px; max-width: none; }
            .invoice-header { box-shadow: none; }
            .info-card:hover { border-color: var(--gray-200); box-shadow: none; }
        }
    </style>
</head>
<body>
@php
    $currency = $company->currency ?? 'FCFA';
    $status = $sale->status;
    $statusClass = 'status-' . ($status ?: 'pending');
    $computedTotal = $sale->items->sum('total_price');
    $discountPercent = $sale->discount_percent ?? 0;
    $taxPercent = $sale->tax_percent ?? 0;
    $discountAmount = $computedTotal * ($discountPercent / 100);
    $afterDiscount = $computedTotal - $discountAmount;
    $taxAmount = $afterDiscount * ($taxPercent / 100);
    $grandTotal = $sale->total ?? ($afterDiscount + $taxAmount);
    
    function amountToWordsFr($number) {
        $fmt = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
        return ucfirst($fmt->format((int) $number));
    }
    
    $statusLabels = [
        'completed' => 'Payée',
        'pending' => 'En attente',
        'cancelled' => 'Annulée'
    ];
@endphp

<div class="invoice-container">
    @if(!empty($previewMode))
        <div class="preview-banner no-print">
            <span>⚡ Mode prévisualisation — Cette facture n'est pas finalisée</span>
            <a href="{{ route('sales.invoice', $sale) }}">Télécharger PDF</a>
        </div>
    @endif

    <header class="invoice-header">
        <div class="company-info">
            @if($company->logo_path)
                <div class="logo-container">
                    <img src="{{ asset('storage/' . $company->logo_path) }}" alt="{{ $company->name }}">
                </div>
            @endif
            <h1>{{ $company->name ?: 'Votre Entreprise' }}</h1>
            <p class="subtitle">Facture de vente</p>
            <div class="company-details">
                @if($company->address){{ $company->address }}<br>@endif
                @if($company->phone)Tél: {{ $company->phone }}@endif
                @if($company->email) • {{ $company->email }}@endif
                @if($company->tax_number)<br>N° Fiscal: {{ $company->tax_number }}@endif
            </div>
        </div>
        <div class="invoice-meta">
            <div class="invoice-number">
                <span>Facture N°</span>
                {{ $sale->invoice_number }}
            </div>
            <div class="invoice-date">
                {{ $sale->created_at->format('d M Y') }}
            </div>
            <div style="margin-top: 16px;">
                <span class="status-badge {{ $statusClass }}">
                    {{ $statusLabels[$status] ?? $status }}
                </span>
            </div>
        </div>
    </header>

    <div class="info-grid">
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">👤</div>
                <span class="info-card-title">Client</span>
            </div>
            <div class="info-card-content">
                <h3>{{ $sale->customer->name ?? 'Client non défini' }}</h3>
                <p>
                    @if(optional($sale->customer)->address){{ $sale->customer->address }}<br>@endif
                    @if(optional($sale->customer)->phone)📞 {{ $sale->customer->phone }}<br>@endif
                    @if(optional($sale->customer)->email)✉️ {{ $sale->customer->email }}@endif
                </p>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon">📋</div>
                <span class="info-card-title">Détails</span>
            </div>
            <div class="info-card-content">
                <h3>Informations de paiement</h3>
                <p>
                    Mode: {{ ucfirst($sale->payment_method ?? 'Non spécifié') }}<br>
                    @if($sale->warehouse)Entrepôt: {{ $sale->warehouse->name }}@endif
                </p>
            </div>
        </div>
    </div>

    <div class="items-section">
        <h2 class="section-title">Articles commandés</h2>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%">Désignation</th>
                    <th style="width: 15%">Quantité</th>
                    <th style="width: 20%" class="text-right">Prix unitaire</th>
                    <th style="width: 20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sale->items as $item)
                    <tr>
                        <td><span class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</span></td>
                        <td>{{ $item->quantity }}</td>
                        <td class="text-right text-muted">{{ number_format($item->unit_price, 0, ',', ' ') }} {{ $currency }}</td>
                        <td class="text-right">{{ number_format($item->total_price, 0, ',', ' ') }} {{ $currency }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: var(--gray-400);">
                            Aucun article dans cette facture
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="totals-section">
        <div class="totals-card">
            <div class="totals-row">
                <span class="label">Sous-total</span>
                <span class="value">{{ number_format($computedTotal, 0, ',', ' ') }} {{ $currency }}</span>
            </div>
            @if($discountPercent > 0)
                <div class="totals-row discount">
                    <span class="label">Remise ({{ number_format($discountPercent, 1, ',', ' ') }}%)</span>
                    <span class="value">- {{ number_format($discountAmount, 0, ',', ' ') }} {{ $currency }}</span>
                </div>
            @endif
            @if($taxPercent > 0)
                <div class="totals-row">
                    <span class="label">TVA ({{ number_format($taxPercent, 1, ',', ' ') }}%)</span>
                    <span class="value">{{ number_format($taxAmount, 0, ',', ' ') }} {{ $currency }}</span>
                </div>
            @endif
            <div class="totals-row grand-total">
                <span class="label">Total TTC</span>
                <span class="value">{{ number_format($grandTotal, 0, ',', ' ') }} {{ $currency }}</span>
            </div>
            <div class="amount-words">
                {{ amountToWordsFr($grandTotal) }} francs CFA
            </div>
        </div>
    </div>

    @if(!empty($verificationUrl) && !empty($verificationCode))
        <div class="verification-section">
            <div class="qr-code">
                @php
                    try {
                        $qr = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->margin(0)->generate($verificationUrl));
                    } catch (\Throwable $e) { $qr = null; }
                @endphp
                @if($qr)
                    <img src="data:image/svg+xml;base64,{{ $qr }}" alt="QR Code">
                @else
                    <div style="width:100px;height:100px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:10px;">QR indisponible</div>
                @endif
            </div>
            <div class="verification-info">
                <h4>🔒 Vérification d'authenticité</h4>
                <p>
                    Scannez le QR code ou visitez l'URL ci-dessous pour vérifier l'authenticité de ce document.<br>
                    <span style="word-break:break-all;font-size:10px;">{{ $verificationUrl }}</span>
                </p>
                <span class="verification-code">{{ $verificationCode }}</span>
            </div>
        </div>
    @endif

    <footer class="invoice-footer">
        @if($company->footer_text)
            {{ $company->footer_text }}
        @else
            Merci pour votre confiance • Document généré automatiquement<br>
            {{ $company->name }} — {{ $company->phone ?? '' }} — {{ $company->email ?? '' }}
        @endif
    </footer>

    <div class="actions no-print">
        @if(empty($previewMode))
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimer
            </button>
        @else
            <a href="{{ route('sales.invoice', $sale) }}" class="btn btn-primary">
                📄 Télécharger PDF Factur-X
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                🖨️ Imprimer
            </button>
        @endif
    </div>

    {{-- Accordion XML Preview (only in preview mode) --}}
    @if(!empty($previewMode) && !empty($facturxXml))
        <div class="xml-accordion no-print">
            <button class="xml-accordion-toggle" onclick="toggleXmlAccordion()">
                <span class="xml-icon">📋</span>
                <span class="xml-title">Factur-X XML intégré (CII)</span>
                <span class="xml-badge">PDF/A-3</span>
                <span class="xml-chevron" id="xmlChevron">▼</span>
            </button>
            <div class="xml-content" id="xmlContent">
                <div class="xml-toolbar">
                    <span class="xml-info">
                        <span class="xml-profile">Profil: BASIC</span>
                        <span class="xml-standard">Norme: Factur-X / ZUGFeRD 2.1</span>
                    </span>
                    <button class="xml-copy-btn" onclick="copyXmlToClipboard()">
                        📋 Copier XML
                    </button>
                </div>
                <pre class="xml-code" id="xmlCode"><code>{{ $facturxXml }}</code></pre>
            </div>
        </div>
        
        <style>
            .xml-accordion {
                margin-top: 32px;
                border: 1px solid var(--gray-200);
                border-radius: 12px;
                overflow: hidden;
                background: white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            
            .xml-accordion-toggle {
                width: 100%;
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                color: white;
                border: none;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                text-align: left;
                transition: all 0.2s;
            }
            
            .xml-accordion-toggle:hover {
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            }
            
            .xml-icon {
                font-size: 18px;
            }
            
            .xml-title {
                flex: 1;
            }
            
            .xml-badge {
                background: #10b981;
                color: white;
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .xml-chevron {
                font-size: 12px;
                transition: transform 0.3s ease;
            }
            
            .xml-chevron.open {
                transform: rotate(180deg);
            }
            
            .xml-content {
                display: none;
                background: #0f172a;
            }
            
            .xml-content.open {
                display: block;
            }
            
            .xml-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 20px;
                background: #1e293b;
                border-bottom: 1px solid #334155;
            }
            
            .xml-info {
                display: flex;
                gap: 16px;
                font-size: 11px;
            }
            
            .xml-profile {
                color: #60a5fa;
                font-weight: 500;
            }
            
            .xml-standard {
                color: #94a3b8;
            }
            
            .xml-copy-btn {
                background: #3b82f6;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .xml-copy-btn:hover {
                background: #2563eb;
                transform: translateY(-1px);
            }
            
            .xml-copy-btn:active {
                transform: translateY(0);
            }
            
            .xml-code {
                margin: 0;
                padding: 20px;
                background: #0f172a;
                color: #e2e8f0;
                font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
                font-size: 11px;
                line-height: 1.6;
                overflow-x: auto;
                max-height: 500px;
                overflow-y: auto;
                white-space: pre-wrap;
                word-break: break-word;
            }
            
            .xml-code::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }
            
            .xml-code::-webkit-scrollbar-track {
                background: #1e293b;
            }
            
            .xml-code::-webkit-scrollbar-thumb {
                background: #475569;
                border-radius: 4px;
            }
            
            .xml-code::-webkit-scrollbar-thumb:hover {
                background: #64748b;
            }
            
            /* Syntax highlighting (basic) */
            .xml-code code {
                color: #e2e8f0;
            }
        </style>
        
        <script>
            function toggleXmlAccordion() {
                const content = document.getElementById('xmlContent');
                const chevron = document.getElementById('xmlChevron');
                content.classList.toggle('open');
                chevron.classList.toggle('open');
            }
            
            function copyXmlToClipboard() {
                const xmlCode = document.getElementById('xmlCode').textContent;
                navigator.clipboard.writeText(xmlCode).then(() => {
                    const btn = document.querySelector('.xml-copy-btn');
                    const originalText = btn.textContent;
                    btn.textContent = '✓ Copié !';
                    btn.style.background = '#10b981';
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.style.background = '#3b82f6';
                    }, 2000);
                }).catch(err => {
                    alert('Erreur lors de la copie: ' + err);
                });
            }
            
            // Syntax highlighting (simple XML colorization)
            document.addEventListener('DOMContentLoaded', function() {
                const codeElement = document.querySelector('.xml-code code');
                if (codeElement) {
                    let xml = codeElement.innerHTML;
                    // Highlight tags
                    xml = xml.replace(/(&lt;\/?[\w:]+)/g, '<span style="color:#60a5fa">$1</span>');
                    // Highlight attributes
                    xml = xml.replace(/(\s[\w:]+)(=)/g, '<span style="color:#f472b6">$1</span>$2');
                    // Highlight attribute values
                    xml = xml.replace(/(".*?")/g, '<span style="color:#a5f3fc">$1</span>');
                    // Highlight comments
                    xml = xml.replace(/(&lt;!--.*?--&gt;)/g, '<span style="color:#6b7280">$1</span>');
                    codeElement.innerHTML = xml;
                }
            });
        </script>
    @endif
</div>
</body>
</html>

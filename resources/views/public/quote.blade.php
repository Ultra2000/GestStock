<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devis {{ $quote->quote_number }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #1e293b;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header-banner {
            background: white;
            padding: 24px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .company-name {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .quote-title {
            font-size: 18px;
            color: #64748b;
            font-weight: 500;
        }

        @if(session('success'))
        .alert-success {
            background: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            font-weight: 500;
        }
        @endif

        @if(session('error'))
        .alert-error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 16px;
            border-radius: 8px;
            margin: 16px 0;
            font-weight: 500;
        }
        @endif

        .status-banner {
            padding: 20px 24px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
        }

        .status-sent {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-accepted {
            background: #d1fae5;
            color: #065f46;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-expired {
            background: #fef3c7;
            color: #92400e;
        }

        .quote-content {
            background: white;
            padding: 32px;
        }

        .quote-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
        }

        .meta-item label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }

        .meta-item value {
            display: block;
            font-size: 16px;
            color: #1e293b;
            font-weight: 600;
        }

        .customer-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
        }

        .customer-info h3 {
            font-size: 14px;
            text-transform: uppercase;
            color: #667eea;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .customer-info p {
            color: #475569;
            line-height: 1.6;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .items-table thead th {
            background: #1e293b;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.05em;
        }

        .items-table thead th.text-right {
            text-align: right;
        }

        .items-table thead th.text-center {
            text-align: center;
        }

        .items-table tbody td {
            padding: 16px 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table tbody td.text-right {
            text-align: right;
        }

        .items-table tbody td.text-center {
            text-align: center;
        }

        .product-name {
            font-weight: 600;
            color: #1e293b;
        }

        .totals-box {
            background: #f8fafc;
            padding: 24px;
            border-radius: 8px;
            max-width: 400px;
            margin-left: auto;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }

        .total-row.grand-total {
            border-top: 2px solid #1e293b;
            margin-top: 8px;
            padding-top: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
        }

        .action-buttons {
            background: white;
            padding: 24px;
            border-radius: 0 0 12px 12px;
            display: flex;
            gap: 16px;
            justify-content: center;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .btn {
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-accept {
            background: #10b981;
            color: white;
        }

        .btn-accept:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-reject {
            background: #ef4444;
            color: white;
        }

        .btn-reject:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .reject-form {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .reject-form.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .modal-body {
            padding: 24px;
        }

        .reject-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            min-height: 120px;
        }

        .reject-form textarea:focus {
            outline: none;
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #94a3b8;
            color: white;
        }

        .btn-secondary:hover {
            background: #64748b;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .quote-content {
                padding: 20px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .items-table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-banner">
            <div class="company-name">{{ $quote->user->company->name ?? 'FRECORP ERP' }}</div>
            <div class="quote-title">Devis N° {{ $quote->quote_number }}</div>
        </div>

        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if($quote->status === 'accepted')
            <div class="status-banner status-accepted">
                ✓ Ce devis a été accepté le {{ $quote->accepted_at->format('d/m/Y à H:i') }}
            </div>
        @elseif($quote->status === 'rejected')
            <div class="status-banner status-rejected">
                ✗ Ce devis a été refusé le {{ $quote->rejected_at->format('d/m/Y à H:i') }}
                @if($quote->refusal_reason)
                    <div style="margin-top: 8px; font-weight: 400;">Motif : {{ $quote->refusal_reason }}</div>
                @endif
            </div>
        @elseif($quote->status === 'expired')
            <div class="status-banner status-expired">
                ⚠ Ce devis a expiré le {{ $quote->expires_at->format('d/m/Y') }}
            </div>
        @elseif($quote->status === 'sent')
            <div class="status-banner status-sent">
                Ce devis est en attente de votre réponse
            </div>
        @endif

        <div class="quote-content">
            <div class="quote-meta">
                <div class="meta-item">
                    <label>Date du devis</label>
                    <value>{{ $quote->quote_date->format('d/m/Y') }}</value>
                </div>
                <div class="meta-item">
                    <label>Valable jusqu'au</label>
                    <value>{{ $quote->valid_until->format('d/m/Y') }}</value>
                </div>
                <div class="meta-item">
                    <label>Montant total TTC</label>
                    <value>{{ number_format($quote->total, 2, ',', ' ') }} €</value>
                </div>
            </div>

            @if($quote->customer)
            <div class="customer-info">
                <h3>Client</h3>
                <p>
                    <strong>{{ $quote->customer->name }}</strong><br>
                    @if($quote->customer->address){{ $quote->customer->address }}<br>@endif
                    @if($quote->customer->zip_code || $quote->customer->city)
                        {{ $quote->customer->zip_code }} {{ $quote->customer->city }}<br>
                    @endif
                    @if($quote->customer->phone)Tél : {{ $quote->customer->phone }}<br>@endif
                    @if($quote->customer->email)Email : {{ $quote->customer->email }}@endif
                </p>
            </div>
            @endif

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th class="text-center">Qté</th>
                        <th class="text-right">P.U. HT</th>
                        <th class="text-center">TVA</th>
                        <th class="text-right">Total HT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->items as $item)
                        <tr>
                            <td>
                                <div class="product-name">{{ $item->product->name ?? $item->description }}</div>
                                @if($item->description && $item->product)
                                    <div style="font-size: 13px; color: #64748b;">{{ $item->description }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($item->quantity, 0, ',', ' ') }}</td>
                            <td class="text-right">{{ number_format($item->unit_price_ht ?? $item->unit_price, 2, ',', ' ') }} €</td>
                            <td class="text-center">{{ number_format($item->vat_rate ?? 20, 0) }}%</td>
                            <td class="text-right">{{ number_format($item->total_price_ht ?? ($item->total_price / (1 + ($item->vat_rate ?? 20) / 100)), 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals-box">
                <div class="total-row">
                    <span>Total HT</span>
                    <strong>{{ number_format($quote->total_ht, 2, ',', ' ') }} €</strong>
                </div>
                <div class="total-row">
                    <span>TVA</span>
                    <strong>{{ number_format($quote->total_vat, 2, ',', ' ') }} €</strong>
                </div>
                @if($quote->discount_amount > 0)
                <div class="total-row" style="color: #ef4444;">
                    <span>Remise</span>
                    <strong>- {{ number_format($quote->discount_amount, 2, ',', ' ') }} €</strong>
                </div>
                @endif
                <div class="total-row grand-total">
                    <span>Total TTC</span>
                    <strong>{{ number_format($quote->total, 2, ',', ' ') }} €</strong>
                </div>
            </div>

            @if($quote->notes)
            <div style="margin-top: 24px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                <strong style="display: block; margin-bottom: 8px; color: #1e293b;">Notes :</strong>
                <p style="color: #475569; line-height: 1.6;">{{ $quote->notes }}</p>
            </div>
            @endif
        </div>

        @if($quote->status === 'sent')
        <div class="action-buttons">
            <form method="POST" action="{{ route('public.quote.accept', $quote->public_token) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir accepter ce devis ?');">
                @csrf
                <button type="submit" class="btn btn-accept">
                    ✓ Accepter ce devis
                </button>
            </form>

            <button type="button" class="btn btn-reject" onclick="toggleRejectModal()">
                ✗ Refuser ce devis
            </button>
        </div>

        <!-- Modal de refus -->
        <div class="reject-form" id="rejectModal" onclick="closeModalOnBackdrop(event)">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Refuser le devis</h3>
                </div>
                <form method="POST" action="{{ route('public.quote.reject', $quote->public_token) }}">
                    @csrf
                    <div class="modal-body">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b;">
                            Pouvez-vous nous indiquer la raison de votre refus ? (optionnel)
                        </label>
                        <textarea 
                            name="reason" 
                            placeholder="Ex: Prix trop élevé, délai trop long, erreur dans la commande..."
                            style="margin-bottom: 8px;"
                        ></textarea>
                        <p style="font-size: 13px; color: #64748b; margin: 0;">
                            Votre retour nous aide à améliorer nos offres.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="toggleRejectModal()">Annuler</button>
                        <button type="submit" class="btn btn-reject">Confirmer le refus</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    <script>
        function toggleRejectModal() {
            document.getElementById('rejectModal').classList.toggle('active');
            if (document.getElementById('rejectModal').classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        function closeModalOnBackdrop(event) {
            if (event.target.id === 'rejectModal') {
                toggleRejectModal();
            }
        }

        // Fermer le modal avec la touche Echap
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && document.getElementById('rejectModal').classList.contains('active')) {
                toggleRejectModal();
            }
        });
    </script>
</body>
</html>

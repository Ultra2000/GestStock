@extends('sales.templates._layout')

@section('styles')
body {
    font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
    color: #1e293b;
}

/* --- Header --- */
.header { background: #1e293b; color: #fff; padding: 20px; border-radius: 8px; }
.logo { max-height: 45px; max-width: 100px; margin-bottom: 8px; background: #fff; padding: 4px; border-radius: 4px; }
.company-name { font-size: 20px; font-weight: bold; }
.company-subtitle { font-size: 11px; color: #94a3b8; margin-bottom: 10px; }
.company-details { font-size: 9px; color: #cbd5e1; }
.invoice-type-label { font-size: 10px; color: #94a3b8; margin-bottom: 2px; }
.invoice-number { font-size: 22px; font-weight: bold; color: #fff; }
.invoice-date { font-size: 10px; color: #94a3b8; margin-top: 8px; }

/* --- Status --- */
.status-badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-top: 10px; }
.status-completed { background: #065f46; color: #10b981; }
.status-pending { background: #78350f; color: #f59e0b; }
.status-cancelled { background: #7f1d1d; color: #ef4444; }

/* --- Info cards --- */
.info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
.info-card-label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #3b82f6; letter-spacing: 0.8px; border-bottom: 2px solid #3b82f6; padding-bottom: 8px; margin-bottom: 10px; }
.info-card-name { font-size: 12px; font-weight: bold; color: #1e293b; margin-bottom: 4px; }
.info-card-text { font-size: 9px; color: #64748b; }
.info-card-text strong { color: #334155; }

/* --- Items table --- */
.items-table thead tr { background: #1e293b; }
.items-table thead th { color: #fff; font-size: 8px; font-weight: bold; text-transform: uppercase; padding: 10px 8px; letter-spacing: 0.3px; }
.items-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.items-table tbody tr:nth-child(even) { background: #f8fafc; }
.items-table tbody td { padding: 10px 8px; font-size: 10px; }
.product-name { font-weight: 600; color: #1e293b; }
.item-muted { color: #64748b; }

/* --- Totals --- */
.totals-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
.totals-row { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
.totals-label { color: #64748b; font-size: 10px; }
.totals-value { text-align: right; font-weight: 600; font-size: 10px; color: #1e293b; }
.totals-discount { color: #10b981; }
.totals-grand { background: #1e293b; padding: 12px; }
.totals-grand-label { color: #94a3b8; font-weight: bold; text-transform: uppercase; font-size: 9px; }
.totals-grand-value { text-align: right; color: #fff; font-size: 14px; font-weight: bold; }
.totals-words { padding: 8px 12px; font-size: 8px; font-style: italic; color: #64748b; border-top: 1px dashed #cbd5e1; }

/* --- Legal --- */
.legal-section { padding: 8px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 8px; color: #64748b; }
.legal-section strong { color: #334155; }
.legal-row { margin-bottom: 3px; }

/* --- Notes --- */
.notes-box { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 6px; padding: 10px; font-size: 9px; }
.notes-title { color: #92400e; }

/* --- QR --- */
.qr-section { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
.qr-box { background: #fff; padding: 5px; border-radius: 4px; display: inline-block; }
.qr-title { font-size: 10px; font-weight: bold; color: #1e293b; margin-bottom: 4px; }
.qr-text { font-size: 8px; color: #64748b; }
.qr-code { display: inline-block; font-family: monospace; background: #1e293b; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 9px; margin-top: 5px; }

/* --- Footer --- */
.footer { text-align: center; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 8px; }
.footer-sub { font-size: 7px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
@endsection

@extends('sales.templates._layout')

@section('styles')
@page { margin: 20mm 18mm 20mm 18mm; }
body {
    font-family: 'DejaVu Sans Mono', 'Courier New', monospace;
    color: #cbd5e1;
    background: #0f172a;
}

/* --- Header --- */
.header { border-bottom: 1px solid #1e293b; padding-bottom: 24px; }
.logo { max-height: 40px; max-width: 80px; margin-bottom: 8px; border: 1px solid #1e293b; padding: 3px; }
.company-name { font-size: 20px; font-weight: bold; color: #818cf8; font-style: italic; }
.company-subtitle { display: none; }
.company-details { font-size: 9px; color: #475569; max-width: 200px; }
.invoice-type-label { font-size: 32px; font-weight: bold; color: #fff; letter-spacing: 4px; }
.invoice-number { color: #6366f1; font-weight: bold; font-size: 11px; margin-top: 4px; }
.invoice-date { font-size: 9px; color: #475569; margin-top: 4px; }

/* --- Status --- */
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-top: 8px; }
.status-completed { background: #064e3b; color: #34d399; }
.status-pending { background: #78350f; color: #fbbf24; }
.status-cancelled { background: #7f1d1d; color: #f87171; }

/* --- Info cards --- */
.info-card { }
.info-card-label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #6366f1; letter-spacing: 1px; margin-bottom: 8px; }
.info-card-name { font-size: 16px; font-weight: bold; color: #fff; margin-bottom: 4px; }
.info-card-text { font-size: 9px; color: #64748b; }
.info-card-text strong { color: #94a3b8; }

/* --- Items table --- */
.items-table { border: 1px solid #1e293b; }
.items-table thead tr { background: rgba(30, 41, 59, 0.5); }
.items-table thead th { color: #818cf8; font-size: 8px; font-weight: bold; text-transform: uppercase; padding: 10px 12px; letter-spacing: 0.5px; }
.items-table tbody tr { border-bottom: 1px solid #1e293b; }
.items-table tbody td { padding: 10px 12px; font-size: 10px; }
.product-name { color: #cbd5e1; }
.item-muted { color: #64748b; }

/* --- Totals --- */
.totals-card { text-align: right; }
.totals-row { padding: 4px 0; border-bottom: none; }
.totals-label { color: #64748b; font-size: 10px; }
.totals-value { text-align: right; color: #fff; font-size: 10px; }
.totals-discount { color: #34d399; }
.totals-grand { display: inline-block; background: #6366f1; color: #0f172a; padding: 10px 20px; margin-top: 10px; }
.totals-grand-label { font-size: 10px; font-weight: bold; color: #0f172a; }
.totals-grand-value { text-align: right; font-size: 18px; font-weight: bold; color: #0f172a; }
.totals-words { font-size: 8px; color: #475569; font-style: italic; margin-top: 6px; text-align: right; }

/* --- Legal --- */
.legal-section { font-size: 8px; color: #475569; padding: 8px 0; border-top: 1px solid #1e293b; }
.legal-section strong { color: #94a3b8; }
.legal-row { margin-bottom: 3px; }

/* --- Notes --- */
.notes-box { background: #1e293b; border: 1px solid #334155; border-radius: 4px; padding: 10px; font-size: 9px; color: #94a3b8; }
.notes-title { color: #818cf8; }

/* --- QR --- */
.qr-section { background: #1e293b; border: 1px solid #334155; border-radius: 4px; padding: 12px; }
.qr-box { display: inline-block; background: #fff; padding: 4px; }
.qr-title { font-size: 9px; font-weight: bold; color: #818cf8; margin-bottom: 3px; }
.qr-text { font-size: 7px; color: #475569; }
.qr-code { display: inline-block; font-family: monospace; background: #6366f1; color: #0f172a; padding: 2px 8px; border-radius: 2px; font-size: 8px; margin-top: 4px; }

/* --- Footer --- */
.footer { padding-top: 12px; border-top: 1px solid #1e293b; color: #334155; font-size: 7px; text-align: center; }
.footer-sub { color: #334155; margin-top: 4px; }
@endsection

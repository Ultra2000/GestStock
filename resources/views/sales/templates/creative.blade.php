@extends('sales.templates._layout')

@section('styles')
body {
    font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
    color: #334155;
}

/* --- Header --- */
.header { margin-bottom: 35px; }
.logo { max-height: 40px; margin-bottom: 8px; }
.company-name { font-size: 22px; font-weight: bold; color: #312e81; }
.company-subtitle { display: none; }
.company-details { font-size: 8px; color: #a5b4fc; margin-top: 4px; }
.invoice-type-label { display: inline-block; background: #4f46e5; color: #fff; padding: 8px 20px; border-radius: 20px; font-size: 12px; font-weight: bold; }
.invoice-number { display: none; }
.invoice-date { font-size: 9px; color: #a78bfa; margin-top: 6px; }

/* --- Status --- */
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-left: 8px; }
.status-completed { background: #dcfce7; color: #166534; }
.status-pending { background: #fef9c3; color: #854d0e; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

/* --- Info cards --- */
.info-card { }
.info-card-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 6px; color: #c026d3; }
.info-card-cell:last-child .info-card-label { color: #818cf8; }
.info-card-name { font-size: 16px; font-weight: bold; color: #1e293b; margin-bottom: 4px; }
.info-card-text { font-size: 9px; color: #64748b; }

/* --- Items table (card style) --- */
.items-table { border-spacing: 0 6px; }
.items-table thead th { font-size: 8px; color: #a78bfa; font-weight: bold; text-transform: uppercase; padding: 0 8px 8px 8px; letter-spacing: 0.5px; }
.items-table tbody tr { background: #faf5ff; }
.items-table tbody td { padding: 12px 14px; font-size: 10px; border-top: 1px solid #f3e8ff; border-bottom: 1px solid #f3e8ff; }
.items-table tbody td:first-child { border-left: 1px solid #f3e8ff; border-radius: 8px 0 0 8px; }
.items-table tbody td:last-child { border-right: 1px solid #f3e8ff; border-radius: 0 8px 8px 0; }
.product-name { font-weight: bold; color: #1e293b; font-size: 11px; }
.item-muted { color: #a78bfa; font-size: 8px; }

/* --- Totals --- */
.totals-card { background: #4f46e5; color: #fff; border-radius: 12px; padding: 16px 20px; overflow: hidden; }
.totals-row { padding: 3px 0; border-bottom: none; }
.totals-label { color: rgba(255,255,255,0.7); font-size: 10px; }
.totals-value { text-align: right; color: rgba(255,255,255,0.8); font-weight: 600; font-size: 10px; }
.totals-discount { color: #86efac; }
.totals-grand { border-top: 1px solid rgba(255,255,255,0.3); margin-top: 8px; padding-top: 8px; }
.totals-grand-label { font-size: 10px; color: rgba(255,255,255,0.7); }
.totals-grand-value { text-align: right; font-size: 20px; font-weight: bold; color: #fff; }
.totals-words { font-size: 8px; font-style: italic; color: rgba(255,255,255,0.5); margin-top: 8px; text-align: right; }

/* --- Legal --- */
.legal-section { font-size: 8px; color: #a78bfa; padding: 8px 0; border-top: 1px solid #ede9fe; }
.legal-section strong { color: #7c3aed; }
.legal-row { margin-bottom: 3px; }

/* --- Notes --- */
.notes-box { background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 10px; font-size: 9px; }
.notes-title { color: #7c3aed; }

/* --- QR --- */
.qr-section { background: #f5f3ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 12px; }
.qr-box { display: inline-block; }
.qr-title { font-size: 9px; font-weight: bold; color: #4f46e5; margin-bottom: 3px; }
.qr-text { font-size: 7px; color: #a78bfa; }
.qr-code { display: inline-block; font-family: monospace; background: #4f46e5; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 8px; margin-top: 4px; }

/* --- Footer --- */
.footer { text-align: center; padding-top: 10px; border-top: 1px solid #ede9fe; color: #a78bfa; font-size: 8px; }
.footer-sub { color: #a78bfa; margin-top: 4px; }
@endsection

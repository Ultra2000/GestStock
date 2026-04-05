@extends('sales.templates._layout')

@section('styles')
body {
    font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
    color: #334155;
}

/* --- Header --- */
.header { margin-bottom: 40px; }
.logo { max-height: 40px; max-width: 90px; margin-bottom: 8px; }
.company-name { font-size: 18px; font-weight: bold; color: #0f172a; }
.company-subtitle { display: none; }
.company-details { font-size: 9px; color: #94a3b8; }
.invoice-type-label { font-size: 32px; font-weight: 300; color: #cbd5e1; text-transform: uppercase; letter-spacing: 6px; }
.invoice-number { font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 4px; }
.invoice-date { font-size: 9px; color: #94a3b8; margin-top: 2px; }

/* --- Status --- */
.status-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; margin-top: 6px; }
.status-completed { background: #dcfce7; color: #166534; }
.status-pending { background: #fef9c3; color: #854d0e; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

/* --- Info cards --- */
.info-grid { border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; margin-bottom: 30px; }
.info-card { padding: 16px 0; }
.info-card-label { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-bottom: 6px; }
.info-card-name { font-size: 12px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
.info-card-text { font-size: 9px; color: #64748b; }

/* --- Items table --- */
.items-table thead tr { border-bottom: 1px solid #e2e8f0; }
.items-table thead th { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #94a3b8; padding: 0 8px 10px 8px; letter-spacing: 0.5px; }
.items-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.items-table tbody td { padding: 14px 8px; font-size: 10px; }
.product-name { font-weight: 600; color: #0f172a; }
.item-muted { color: #64748b; }

/* --- Totals --- */
.totals-card { }
.totals-row { padding: 6px 0; border-bottom: none; }
.totals-label { color: #64748b; font-size: 10px; }
.totals-value { text-align: right; font-weight: 600; font-size: 10px; color: #0f172a; }
.totals-discount { color: #10b981; }
.totals-grand { border-top: 2px solid #0f172a; padding: 10px 0 0 0; }
.totals-grand-label { font-size: 16px; font-weight: bold; color: #0f172a; }
.totals-grand-value { text-align: right; font-size: 16px; font-weight: bold; color: #0f172a; }
.totals-words { font-size: 8px; font-style: italic; color: #94a3b8; margin-top: 6px; text-align: right; }

/* --- Legal --- */
.legal-section { font-size: 8px; color: #94a3b8; padding: 8px 0; border-top: 1px solid #f1f5f9; }
.legal-section strong { color: #64748b; }
.legal-row { margin-bottom: 3px; }

/* --- Notes --- */
.notes-box { background: #fefce8; border: 1px solid #fde68a; border-radius: 4px; padding: 10px; font-size: 9px; }
.notes-title { color: #92400e; }

/* --- QR --- */
.qr-section { }
.qr-box { display: inline-block; }
.qr-title { font-size: 9px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
.qr-text { font-size: 7px; color: #94a3b8; }
.qr-code { display: inline-block; font-family: monospace; background: #f1f5f9; color: #334155; padding: 2px 6px; border-radius: 3px; font-size: 8px; margin-top: 4px; }

/* --- Footer --- */
.footer { text-align: center; padding-top: 10px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 8px; }
.footer-sub { font-size: 7px; color: #94a3b8; margin-top: 4px; }
@endsection

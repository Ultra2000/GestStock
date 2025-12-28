<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .grid { display: flex; flex-wrap: wrap; }
        .label { width: calc(100% / {{ $columns }}); padding:4px 6px; box-sizing: border-box; border:1px dashed #ddd; margin-bottom:6px; text-align:center; }
        .name { font-weight:600; font-size:11px; line-height:1.1; }
        .price { font-size:10px; margin-top:2px; }
        .barcode { margin-top:4px; }
    </style>
</head>
<body>
<div class="grid">
@foreach($labels as $product)
    <div class="label">
        <div class="name">{{ Str::limit($product->name, 24) }}</div>
        @if($showPrice)
            <div class="price">{{ number_format($product->price, 0, ',', ' ') }} F</div>
        @endif
        <div class="barcode">
            {!! DNS1D::getBarcodeHTML($product->code, 'C128', 1, 36) !!}
            <div style="font-size:9px;line-height:1;">{{ $product->code }}</div>
        </div>
    </div>
@endforeach
</div>
<div style="margin-top:10px;font-size:9px;text-align:right;color:#666;">Généré: {{ $generatedAt }}</div>
</body>
</html>

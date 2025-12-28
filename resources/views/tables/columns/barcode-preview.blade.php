@php
    // Filament ViewColumn fournit $getRecord() pour accéder au modèle.
    $record = isset($getRecord) ? $getRecord() : ($record ?? null); // fallback si déjà présent
    $code = $record?->code;
@endphp
@if(!empty($code))
    <div class="flex flex-col items-center justify-center">
        {!! DNS1D::getBarcodeHTML($code, 'C128', 1, 32) !!}
        <span class="text-[10px] leading-none mt-1 font-mono">{{ $code }}</span>
    </div>
@else
    <span class="text-xs text-danger-600">(aucun)</span>
@endif

{{-- Shared legal mentions block - included by all PDF templates --}}
{{-- Expected CSS classes: .legal-section, .legal-row, .legal-title, .legal-text --}}

{{-- DATE DE LIVRAISON / PRESTATION --}}
@if($deliveryDate)
<div class="legal-row">
    <strong>Date de livraison / prestation :</strong> {{ $deliveryDate->format('d/m/Y') }}
</div>
@endif

{{-- NATURE DE L'OPÉRATION --}}
@if($natureOp)
<div class="legal-row">
    <strong>Nature de l'opération :</strong> {{ $natureOpLabels[$natureOp] ?? $natureOp }}
</div>
@endif

{{-- ADRESSE DE LIVRAISON --}}
@if($deliveryAddr)
<div class="legal-row">
    <strong>Adresse de livraison :</strong> {{ $deliveryAddr }}
</div>
@endif

{{-- TVA SUR LES DÉBITS --}}
@if($isVatOnDebits)
<div class="legal-row">
    <strong>TVA acquittée sur les débits</strong>
</div>
@endif

{{-- FRANCHISE TVA (Art. 293 B du CGI) --}}
@if($isVatFranchise)
<div class="legal-row">
    <strong>TVA non applicable, art. 293 B du CGI</strong>
</div>
@endif

{{-- CONDITIONS DE PAIEMENT --}}
<div class="legal-row">
    <strong>Échéance :</strong> {{ $dueDate instanceof \Carbon\Carbon ? $dueDate->format('d/m/Y') : \Carbon\Carbon::parse($dueDate)->format('d/m/Y') }}
    @if($paymentTerms)
        — {{ $paymentTerms }}
    @else
        — Paiement par {{ ucfirst($sale->payment_method ?? 'virement bancaire') }}
    @endif
</div>

{{-- ESCOMPTE (obligatoire même si inapplicable) --}}
<div class="legal-row">
    <strong>Escompte pour paiement anticipé :</strong> Pas d'escompte.
</div>

{{-- PÉNALITÉS DE RETARD (B2B obligatoire) --}}
<div class="legal-row">
    @if($penaltyRate)
        <strong>Pénalités de retard :</strong> {{ number_format($penaltyRate, 2, ',', '') }} % par mois de retard.
    @else
        <strong>Pénalités de retard :</strong> Taux égal à 3 fois le taux d'intérêt légal en vigueur, exigibles dès le lendemain de la date d'échéance.
    @endif
    <strong>Indemnité forfaitaire de recouvrement :</strong> {{ number_format($recoveryFee, 2, ',', ' ') }} € (art. L441-10 C. com.).
</div>

{{-- IDENTIFIANTS VENDEUR --}}
<div class="legal-row">
    @if($legalFormLine){{ $legalFormLine }}@endif
    @if($company->registration_number) — SIREN : {{ $company->registration_number }}@endif
    @if($company->siret) — SIRET : {{ $company->siret }}@endif
    @if($codeNaf) — NAF : {{ $codeNaf }}@endif
    @if($rcsNumber) — {{ $rcsNumber }}@endif
    @if($rmNumber) — {{ $rmNumber }}@endif
    @if($company->tax_number) — TVA Intra : {{ $company->tax_number }}@endif
</div>

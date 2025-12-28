<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
    </x-filament-panels::form>

    @php
        $report = $this->getReportData();
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <x-filament::section>
            <x-slot name="heading">Recettes (TTC)</x-slot>
            <div class="text-2xl font-bold text-success-600">
                {{ number_format($report['income'], 2, ',', ' ') }} €
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Dépenses (TTC)</x-slot>
            <div class="text-2xl font-bold text-danger-600">
                {{ number_format($report['expenses'], 2, ',', ' ') }} €
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Résultat</x-slot>
            <div class="text-2xl font-bold {{ $report['balance'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                {{ number_format($report['balance'], 2, ',', ' ') }} €
            </div>
        </x-filament::section>
    </div>

    <x-filament::section class="mt-4">
        <x-slot name="heading">Estimation TVA (20%)</x-slot>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <span class="text-gray-500">Collectée :</span>
                <span class="font-bold">{{ number_format($report['vat_collected'], 2, ',', ' ') }} €</span>
            </div>
            <div>
                <span class="text-gray-500">Déductible :</span>
                <span class="font-bold">{{ number_format($report['vat_deductible'], 2, ',', ' ') }} €</span>
            </div>
            <div>
                <span class="text-gray-500">À payer :</span>
                <span class="font-bold {{ $report['vat_to_pay'] > 0 ? 'text-danger-600' : 'text-success-600' }}">
                    {{ number_format($report['vat_to_pay'], 2, ',', ' ') }} €
                </span>
            </div>
        </div>
        <div class="text-xs text-gray-400 mt-2">
            * Calcul estimatif basé sur un taux unique de 20%. Pour une déclaration réelle, consultez votre expert-comptable.
        </div>
    </x-filament::section>

</x-filament-panels::page>

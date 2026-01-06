<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-4">
            <x-filament::button wire:click="$refresh" type="button">
                Actualiser
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    @php
        $report = $this->getReportData();
        $currency = $this->getCurrency();
        $collectedBreakdown = $this->getVatCollectedBreakdown();
        $deductibleBreakdown = $this->getVatDeductibleBreakdown();
    @endphp

    {{-- Résumé financier --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-banknotes class="w-5 h-5 text-warning-600" />
                    Ventes Cash
                </div>
            </x-slot>
            <div class="text-xl font-bold text-warning-600">
                {{ number_format($report['cash_income'], 2, ',', ' ') }} {{ $currency }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-credit-card class="w-5 h-5 text-success-600" />
                    Ventes Bancaires (CB/Virement/Chèque)
                </div>
            </x-slot>
            <div class="text-xl font-bold text-success-600">
                {{ number_format($report['income'], 2, ',', ' ') }} {{ $currency }}
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-globe-alt class="w-5 h-5 text-info-600" />
                    Autres (SEPA/PayPal)
                </div>
            </x-slot>
            <div class="text-xl font-bold text-info-600">
                {{ number_format($report['other_income'], 2, ',', ' ') }} {{ $currency }}
            </div>
        </x-filament::section>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-success-600" />
                    Total Recettes (CA)
                </div>
            </x-slot>
            <div class="text-2xl font-bold text-success-600">
                {{ number_format($report['total_revenue'], 2, ',', ' ') }} {{ $currency }}
            </div>
            <div class="text-sm text-gray-500 mt-1">
                Toutes ventes confondues
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-trending-down class="w-5 h-5 text-danger-600" />
                    Total Dépenses (Achats)
                </div>
            </x-slot>
            <div class="text-2xl font-bold text-danger-600">
                {{ number_format($report['expenses'], 2, ',', ' ') }} {{ $currency }}
            </div>
            <div class="text-sm text-gray-500 mt-1">
                Achats fournisseurs
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-calculator class="w-5 h-5" />
                    Résultat Brut
                </div>
            </x-slot>
            <div class="text-2xl font-bold {{ $report['balance'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                {{ number_format($report['balance'], 2, ',', ' ') }} {{ $currency }}
            </div>
            <div class="text-sm text-gray-500 mt-1">
                Recettes - Dépenses
            </div>
        </x-filament::section>
    </div>

    {{-- Section TVA détaillée --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        {{-- TVA Collectée --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-success-600">
                    <x-heroicon-o-banknotes class="w-5 h-5" />
                    TVA Collectée (Ventes)
                </div>
            </x-slot>
            <div class="text-2xl font-bold text-success-600 mb-2">
                {{ number_format($report['vat_collected'], 2, ',', ' ') }} {{ $currency }}
            </div>
            <div class="text-sm text-gray-500 mb-3">
                CA HT : {{ number_format($report['sales_ht'], 2, ',', ' ') }} {{ $currency }}
            </div>
            
            @if(count($collectedBreakdown) > 0)
                <div class="border-t pt-2 space-y-1">
                    @foreach($collectedBreakdown as $row)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ number_format($row['rate'], 1) }}%</span>
                            <span class="font-medium">{{ number_format($row['amount'], 2, ',', ' ') }} {{ $currency }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- TVA Déductible --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 text-info-600">
                    <x-heroicon-o-receipt-refund class="w-5 h-5" />
                    TVA Déductible (Achats)
                </div>
            </x-slot>
            <div class="text-2xl font-bold text-info-600 mb-2">
                {{ number_format($report['vat_deductible'], 2, ',', ' ') }} {{ $currency }}
            </div>
            <div class="text-sm text-gray-500 mb-3">
                Achats HT : {{ number_format($report['purchases_ht'], 2, ',', ' ') }} {{ $currency }}
            </div>
            
            @if(count($deductibleBreakdown) > 0)
                <div class="border-t pt-2 space-y-1">
                    @foreach($deductibleBreakdown as $row)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ number_format($row['rate'], 1) }}%</span>
                            <span class="font-medium">{{ number_format($row['amount'], 2, ',', ' ') }} {{ $currency }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- Solde TVA --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2 {{ $report['vat_to_pay'] >= 0 ? 'text-warning-600' : 'text-success-600' }}">
                    <x-heroicon-o-scale class="w-5 h-5" />
                    {{ $report['vat_to_pay'] >= 0 ? 'TVA à reverser' : 'Crédit de TVA' }}
                </div>
            </x-slot>
            <div class="text-2xl font-bold {{ $report['vat_to_pay'] >= 0 ? 'text-warning-600' : 'text-success-600' }}">
                {{ number_format(abs($report['vat_to_pay']), 2, ',', ' ') }} {{ $currency }}
            </div>
            <div class="text-sm text-gray-500 mt-2">
                @if($report['vat_to_pay'] >= 0)
                    À déclarer et payer à l'État
                @else
                    Reportable ou remboursable
                @endif
            </div>
            
            <div class="border-t pt-3 mt-3">
                <div class="text-xs space-y-1">
                    <div class="flex justify-between text-gray-500">
                        <span>Collectée</span>
                        <span>+ {{ number_format($report['vat_collected'], 2, ',', ' ') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <span>Déductible</span>
                        <span>- {{ number_format($report['vat_deductible'], 2, ',', ' ') }}</span>
                    </div>
                    <div class="flex justify-between font-medium border-t pt-1">
                        <span>Solde</span>
                        <span>= {{ number_format($report['vat_to_pay'], 2, ',', ' ') }}</span>
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Note légale --}}
    <x-filament::section class="mt-6" collapsible collapsed>
        <x-slot name="heading">
            ℹ️ Informations importantes
        </x-slot>
        
        <div class="prose prose-sm max-w-none text-gray-600">
            <p>Ce rapport utilise les données réelles de TVA enregistrées sur chaque vente et achat.</p>
            <ul>
                <li><strong>TVA Collectée</strong> : Montant total de TVA facturée à vos clients (ventes complétées)</li>
                <li><strong>TVA Déductible</strong> : Montant total de TVA payée à vos fournisseurs (achats complétés)</li>
                <li><strong>TVA à reverser</strong> : Différence à déclarer et payer à l'administration fiscale</li>
                <li><strong>Crédit de TVA</strong> : Différence négative, reportable ou remboursable</li>
            </ul>
            <p class="text-warning-600">
                <strong>⚠️ Avertissement :</strong> Ce rapport est fourni à titre indicatif et ne constitue pas une déclaration fiscale officielle. 
                Consultez votre expert-comptable pour établir vos déclarations de TVA (CA3, CA12...).
            </p>
        </div>
    </x-filament::section>
</x-filament-panels::page>

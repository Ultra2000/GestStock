<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Formulaire période --}}
        <form wire:submit="calculate">
            {{ $this->form }}
            <div class="mt-4">
                <x-filament::button type="submit" icon="heroicon-o-calculator" size="lg">
                    Calculer la déclaration
                </x-filament::button>
            </div>
        </form>

        @if($result)
        <div class="space-y-6">

            {{-- En-tête résumé --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 text-center shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">TVA collectée (ligne 11)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($result['total_vat_collected'], 2, ',', ' ') }} {{ $this->getCurrency() }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 text-center shadow-sm">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">TVA déductible (ligne 21)</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($result['total_vat_deductible'], 2, ',', ' ') }} {{ $this->getCurrency() }}
                    </p>
                </div>
                @if($result['vat_due'] > 0)
                <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-5 text-center shadow-sm">
                    <p class="text-sm font-medium text-red-600 dark:text-red-400">TVA à payer (ligne 55)</p>
                    <p class="mt-1 text-2xl font-bold text-red-700 dark:text-red-300">
                        {{ number_format($result['vat_due'], 2, ',', ' ') }} {{ $this->getCurrency() }}
                    </p>
                </div>
                @else
                <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-5 text-center shadow-sm">
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Crédit de TVA (ligne 56)</p>
                    <p class="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">
                        {{ number_format($result['vat_credit'], 2, ',', ' ') }} {{ $this->getCurrency() }}
                    </p>
                </div>
                @endif
            </div>

            {{-- Tableau CA3 --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Détail des cases CA3</h3>
                </div>

                {{-- TVA collectée --}}
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Opérations imposables — TVA collectée</p>
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Taux</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Case CA3</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Base HT</th>
                            <th class="px-6 py-3 text-right font-medium text-gray-500 dark:text-gray-400">TVA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @if($result['base_20'] > 0 || $result['vat_20'] > 0)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">20 %</td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">Ligne 01 / 08</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">{{ number_format($result['base_20'], 2, ',', ' ') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($result['vat_20'], 2, ',', ' ') }}</td>
                        </tr>
                        @endif
                        @if($result['base_10'] > 0 || $result['vat_10'] > 0)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">10 %</td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">Ligne 02 / 09</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">{{ number_format($result['base_10'], 2, ',', ' ') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($result['vat_10'], 2, ',', ' ') }}</td>
                        </tr>
                        @endif
                        @if($result['base_55'] > 0 || $result['vat_55'] > 0)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">5,5 %</td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">Ligne 03 / 9A</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">{{ number_format($result['base_55'], 2, ',', ' ') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($result['vat_55'], 2, ',', ' ') }}</td>
                        </tr>
                        @endif
                        @if($result['base_21'] > 0 || $result['vat_21'] > 0)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">2,1 %</td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">Ligne 05 / 9B</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">{{ number_format($result['base_21'], 2, ',', ' ') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($result['vat_21'], 2, ',', ' ') }}</td>
                        </tr>
                        @endif
                        @if($result['base_other'] > 0 || $result['vat_other'] > 0)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">Autres</td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">—</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">{{ number_format($result['base_other'], 2, ',', ' ') }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($result['vat_other'], 2, ',', ' ') }}</td>
                        </tr>
                        @endif
                        <tr class="bg-gray-50 dark:bg-gray-800/50 font-semibold">
                            <td class="px-6 py-3 text-gray-900 dark:text-white" colspan="3">Total TVA collectée (ligne 11)</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">{{ number_format($result['total_vat_collected'], 2, ',', ' ') }}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- TVA déductible --}}
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">TVA déductible</p>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">TVA sur biens et services (ligne 20)</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($result['vat_deductible_goods'], 2, ',', ' ') }}</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-3 text-gray-700 dark:text-gray-300">TVA sur immobilisations (ligne 19)</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($result['vat_deductible_assets'], 2, ',', ' ') }}</td>
                        </tr>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 font-semibold">
                            <td class="px-6 py-3 text-gray-900 dark:text-white">Total TVA déductible (ligne 21)</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">{{ number_format($result['total_vat_deductible'], 2, ',', ' ') }}</td>
                        </tr>
                    </tbody>
                </table>

                {{-- Résultat --}}
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Résultat</p>
                </div>
                <table class="w-full text-sm">
                    <tbody>
                        @if($result['vat_due'] > 0)
                        <tr class="bg-red-50 dark:bg-red-900/10">
                            <td class="px-6 py-4 font-bold text-red-700 dark:text-red-400">TVA nette à payer (ligne 55)</td>
                            <td class="px-6 py-4 text-right font-bold text-red-700 dark:text-red-400 text-lg">{{ number_format($result['vat_due'], 2, ',', ' ') }} {{ $this->getCurrency() }}</td>
                        </tr>
                        @else
                        <tr class="bg-green-50 dark:bg-green-900/10">
                            <td class="px-6 py-4 font-bold text-green-700 dark:text-green-400">Crédit de TVA (ligne 56)</td>
                            <td class="px-6 py-4 text-right font-bold text-green-700 dark:text-green-400 text-lg">{{ number_format($result['vat_credit'], 2, ',', ' ') }} {{ $this->getCurrency() }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap gap-3">
                @if(!$declaration)
                <x-filament::button wire:click="saveDeclaration" icon="heroicon-o-archive-box" color="success">
                    Sauvegarder la déclaration
                </x-filament::button>
                @else
                    @if($declaration->isDraft())
                    <x-filament::button wire:click="validateDeclaration" icon="heroicon-o-check-circle" color="success">
                        Marquer comme validée
                    </x-filament::button>
                    @else
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-green-100 dark:bg-green-900/30 px-4 py-2 text-sm font-medium text-green-800 dark:text-green-300">
                        <x-heroicon-s-check-circle class="h-4 w-4" /> Déclaration validée
                    </span>
                    @endif
                @endif
            </div>

            {{-- Avertissement légal --}}
            <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    <strong>Document indicatif.</strong> Ces chiffres sont calculés à partir des ventes et achats validés dans FRECORP ERP.
                    Vérifiez les montants avant de les reporter sur votre déclaration officielle sur <strong>impots.gouv.fr</strong>.
                    En cas de doute, consultez votre expert-comptable.
                </p>
            </div>

        </div>
        @endif

        {{-- Historique des déclarations --}}
        @php $history = $this->getHistory(); @endphp
        @if($history->isNotEmpty())
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Historique des déclarations</h3>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Période</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500 dark:text-gray-400">TVA collectée</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500 dark:text-gray-400">TVA déductible</th>
                        <th class="px-6 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Net</th>
                        <th class="px-6 py-3 text-center font-medium text-gray-500 dark:text-gray-400">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($history as $decl)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $decl->period_label ?? $decl->period_start->format('d/m/Y') . ' – ' . $decl->period_end->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($decl->total_vat_collected, 2, ',', ' ') }}</td>
                        <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($decl->total_vat_deductible, 2, ',', ' ') }}</td>
                        <td class="px-6 py-3 text-right font-semibold {{ $decl->vat_due > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            @if($decl->vat_due > 0)
                                À payer : {{ number_format($decl->vat_due, 2, ',', ' ') }}
                            @else
                                Crédit : {{ number_format($decl->vat_credit, 2, ',', ' ') }}
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center">
                            @if($decl->isValidated())
                                <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-300">
                                    <x-heroicon-s-check-circle class="h-3 w-3" /> Validée
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:text-yellow-300">
                                    Brouillon
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>
</x-filament-panels::page>

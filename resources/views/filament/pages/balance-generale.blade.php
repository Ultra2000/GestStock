<x-filament-panels::page>
    {{-- En-tête avec résumé --}}
    <div class="mb-6">
        @php
            $totals = $this->getBalanceTotals();
            $byClass = $this->getBalanceByClass();
        @endphp

        {{-- Carte de vérification de l'équilibre --}}
        <div class="p-6 rounded-xl {{ $totals['is_balanced'] ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if($totals['is_balanced'])
                        <div class="p-3 bg-green-100 dark:bg-green-800 rounded-full">
                            <x-heroicon-o-check-circle class="w-8 h-8 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-green-800 dark:text-green-200">
                                ✅ Balance Équilibrée
                            </h2>
                            <p class="text-green-600 dark:text-green-400">
                                Votre comptabilité est parfaitement équilibrée. Total Débits = Total Crédits.
                            </p>
                        </div>
                    @else
                        <div class="p-3 bg-red-100 dark:bg-red-800 rounded-full">
                            <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-red-800 dark:text-red-200">
                                ❌ Déséquilibre Détecté
                            </h2>
                            <p class="text-red-600 dark:text-red-400">
                                Différence de {{ number_format(abs($totals['difference']), 2) }} € - Vérifiez vos écritures.
                            </p>
                        </div>
                    @endif
                </div>
                
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Période analysée</p>
                    <p class="font-semibold text-gray-700 dark:text-gray-300">
                        {{ now()->startOfYear()->format('d/m/Y') }} - {{ now()->format('d/m/Y') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Totaux principaux --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Débits</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ number_format($totals['total_debit'], 2, ',', ' ') }} €
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-red-500">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Crédits</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                {{ number_format($totals['total_credit'], 2, ',', ' ') }} €
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-500 dark:text-gray-400">Soldes Débiteurs</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ number_format($totals['solde_debiteur'], 2, ',', ' ') }} €
            </p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border-l-4 border-purple-500">
            <p class="text-sm text-gray-500 dark:text-gray-400">Soldes Créditeurs</p>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                {{ number_format($totals['solde_crediteur'], 2, ',', ' ') }} €
            </p>
        </div>
    </div>

    {{-- Balance par classe de comptes --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow mb-6 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                📊 Synthèse par Classe de Comptes
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Classe</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Total Débit</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Total Crédit</th>
                        <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Solde</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @foreach($byClass as $class)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                    @switch($class['classe'])
                                        @case('1') bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200 @break
                                        @case('2') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 @break
                                        @case('3') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 @break
                                        @case('4') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @break
                                        @case('5') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 @break
                                        @case('6') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @break
                                        @case('7') bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 @break
                                        @default bg-gray-100 text-gray-800 @break
                                    @endswitch
                                ">
                                    {{ $class['classe'] }} - {{ $class['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-green-600 dark:text-green-400">
                                {{ number_format($class['total_debit'], 2, ',', ' ') }} €
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-red-600 dark:text-red-400">
                                {{ number_format($class['total_credit'], 2, ',', ' ') }} €
                            </td>
                            <td class="px-4 py-2 text-right font-mono font-bold {{ $class['solde'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $class['solde'] >= 0 ? '' : '-' }}{{ number_format(abs($class['solde']), 2, ',', ' ') }} €
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 dark:bg-gray-700 font-bold">
                    <tr>
                        <td class="px-4 py-2">TOTAL</td>
                        <td class="px-4 py-2 text-right font-mono text-green-700 dark:text-green-300">
                            {{ number_format($totals['total_debit'], 2, ',', ' ') }} €
                        </td>
                        <td class="px-4 py-2 text-right font-mono text-red-700 dark:text-red-300">
                            {{ number_format($totals['total_credit'], 2, ',', ' ') }} €
                        </td>
                        <td class="px-4 py-2 text-right font-mono {{ $totals['is_balanced'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                            {{ $totals['is_balanced'] ? '✓ 0,00 €' : number_format($totals['difference'], 2, ',', ' ') . ' €' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Légende --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6 border border-blue-200 dark:border-blue-800">
        <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">📖 Lecture de la Balance</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-700 dark:text-blue-300">
            <div>
                <p><strong>Solde Débiteur</strong> = Le compte a plus de débits que de crédits</p>
                <ul class="list-disc list-inside ml-2 text-xs mt-1">
                    <li>Normal pour : Clients (411), Charges (6xx), Banque/Caisse si positif</li>
                </ul>
            </div>
            <div>
                <p><strong>Solde Créditeur</strong> = Le compte a plus de crédits que de débits</p>
                <ul class="list-disc list-inside ml-2 text-xs mt-1">
                    <li>Normal pour : Fournisseurs (401), TVA (445), Produits (7xx)</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Tableau détaillé --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                📋 Détail par Compte
            </h3>
        </div>
        {{ $this->table }}
    </div>
</x-filament-panels::page>

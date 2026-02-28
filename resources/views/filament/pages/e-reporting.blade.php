<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Info banner --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
                        E-Reporting — Obligations 2026
                    </h3>
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Le <strong>E-Reporting</strong> concerne la transmission à l'administration fiscale des données de transactions 
                        qui ne sont <strong>pas couvertes par la facturation électronique B2B</strong> (Chorus Pro / PPF).
                    </p>
                    <ul class="mt-2 text-xs text-blue-700 dark:text-blue-300 space-y-1">
                        <li>• <strong>B2C France</strong> : Ventes aux particuliers (sans SIRET client)</li>
                        <li>• <strong>B2B Intra-UE</strong> : Ventes à des entreprises européennes (hors France)</li>
                        <li>• <strong>B2C International</strong> : Ventes aux particuliers étrangers</li>
                        <li>• <strong>Export hors UE</strong> : Ventes hors Union Européenne</li>
                        <li>• Les ventes <strong>B2B France</strong> (avec SIRET) sont gérées par Chorus Pro et ne figurent pas ici.</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Period selection --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Sélection de la période
            </h2>

            <form wire:submit="generateReport">
                {{ $this->form }}

                <div class="flex gap-3 mt-4">
                    <x-filament::button type="submit" size="lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Générer le rapport
                    </x-filament::button>

                    @if($showReport && $report)
                    <x-filament::button
                        type="button"
                        wire:click="exportCsv"
                        color="success"
                        size="lg"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        wire:click="exportXml"
                        color="info"
                        size="lg"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        Export XML
                    </x-filament::button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Report results --}}
        @if($showReport && $report)
        <div class="space-y-6">

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total ventes</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $report['summary']['total_sales'] }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-yellow-200 dark:border-yellow-800 p-4">
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 uppercase tracking-wide">B2C France</p>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300 mt-1">{{ $report['summary']['b2c_france'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ number_format($report['totals']['b2c_france']['ttc'] ?? 0, 2, ',', ' ') }} €</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-purple-200 dark:border-purple-800 p-4">
                    <p class="text-xs text-purple-600 dark:text-purple-400 uppercase tracking-wide">B2B Intra-UE</p>
                    <p class="text-2xl font-bold text-purple-700 dark:text-purple-300 mt-1">{{ $report['summary']['b2b_intra_eu'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ number_format($report['totals']['b2b_intra_eu']['ttc'] ?? 0, 2, ',', ' ') }} €</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-orange-200 dark:border-orange-800 p-4">
                    <p class="text-xs text-orange-600 dark:text-orange-400 uppercase tracking-wide">B2C International</p>
                    <p class="text-2xl font-bold text-orange-700 dark:text-orange-300 mt-1">{{ $report['summary']['b2c_international'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ number_format($report['totals']['b2c_international']['ttc'] ?? 0, 2, ',', ' ') }} €</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-green-200 dark:border-green-800 p-4">
                    <p class="text-xs text-green-600 dark:text-green-400 uppercase tracking-wide">Export hors UE</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">{{ $report['summary']['export'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ number_format($report['totals']['export']['ttc'] ?? 0, 2, ',', ' ') }} €</p>
                </div>
            </div>

            {{-- E-Reporting total banner --}}
            <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-primary-900 dark:text-primary-100">
                            Opérations soumises à E-Reporting
                        </p>
                        <p class="text-xs text-primary-700 dark:text-primary-300 mt-0.5">
                            Hors B2B France ({{ $report['summary']['b2b_domestic'] }} facture(s) gérées par Chorus Pro)
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-primary-700 dark:text-primary-300">{{ $report['summary']['e_reporting_count'] }}</p>
                        <p class="text-xs text-primary-600 dark:text-primary-400">facture(s)</p>
                    </div>
                </div>
            </div>

            {{-- VAT breakdown --}}
            @if(!empty($report['vat_breakdown']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Ventilation TVA (E-Reporting)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-4 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Taux TVA</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Base HT</th>
                                <th class="px-4 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Montant TVA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['vat_breakdown'] as $vat)
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="px-4 py-2 text-gray-900 dark:text-white font-medium">{{ number_format($vat['rate'], 1) }} %</td>
                                <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($vat['base'], 2, ',', ' ') }} €</td>
                                <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ number_format($vat['amount'], 2, ',', ' ') }} €</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Detailed lines --}}
            @if(!empty($report['lines']))
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Détail des opérations ({{ count($report['lines']) }})
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">N° Facture</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Date</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Type</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Catégorie</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Client</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-600 dark:text-gray-300">Pays</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">HT</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">TVA</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-600 dark:text-gray-300">TTC</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['lines'] as $line)
                            <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-3 py-2 text-gray-900 dark:text-white font-mono text-xs">{{ $line['invoice_number'] }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($line['date'])->format('d/m/Y') }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        {{ $line['type'] === 'AVOIR' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' }}">
                                        {{ $line['type'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        @switch($line['category'])
                                            @case('B2C_FR') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300 @break
                                            @case('B2B_UE') bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300 @break
                                            @case('B2C_INT') @case('B2B_INT') bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300 @break
                                            @case('EXPORT') bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 @break
                                            @default bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300
                                        @endswitch
                                    ">
                                        {{ $line['category_label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 max-w-[200px] truncate" title="{{ $line['customer_name'] }}">{{ $line['customer_name'] }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                        {{ $line['customer_country'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300 font-mono">{{ number_format($line['total_ht'], 2, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-300 font-mono">{{ number_format($line['total_vat'], 2, ',', ' ') }}</td>
                                <td class="px-3 py-2 text-right text-gray-900 dark:text-white font-semibold font-mono">{{ number_format($line['total_ttc'], 2, ',', ' ') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 border-t-2 border-gray-300 dark:border-gray-600">
                                <td colspan="6" class="px-3 py-2 font-semibold text-gray-900 dark:text-white">TOTAL E-REPORTING</td>
                                <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-white font-mono">
                                    {{ number_format(
                                        ($report['totals']['b2c_france']['ht'] ?? 0) +
                                        ($report['totals']['b2b_intra_eu']['ht'] ?? 0) +
                                        ($report['totals']['b2c_international']['ht'] ?? 0) +
                                        ($report['totals']['export']['ht'] ?? 0),
                                        2, ',', ' '
                                    ) }}
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-gray-900 dark:text-white font-mono">
                                    {{ number_format(
                                        ($report['totals']['b2c_france']['vat'] ?? 0) +
                                        ($report['totals']['b2b_intra_eu']['vat'] ?? 0) +
                                        ($report['totals']['b2c_international']['vat'] ?? 0) +
                                        ($report['totals']['export']['vat'] ?? 0),
                                        2, ',', ' '
                                    ) }}
                                </td>
                                <td class="px-3 py-2 text-right font-bold text-primary-700 dark:text-primary-300 font-mono text-base">
                                    {{ number_format(
                                        ($report['totals']['b2c_france']['ttc'] ?? 0) +
                                        ($report['totals']['b2b_intra_eu']['ttc'] ?? 0) +
                                        ($report['totals']['b2c_international']['ttc'] ?? 0) +
                                        ($report['totals']['export']['ttc'] ?? 0),
                                        2, ',', ' '
                                    ) }} €
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @else
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Aucune opération soumise à E-Reporting sur cette période.
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Toutes les ventes sont en B2B France (gérées par Chorus Pro).
                </p>
            </div>
            @endif

        </div>
        @endif

    </div>
</x-filament-panels::page>

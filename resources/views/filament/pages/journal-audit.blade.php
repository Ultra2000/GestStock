<x-filament-panels::page>
    @php
        $audit = $this->getAuditData();
        $health = $this->getHealthScore();
    @endphp

    {{-- Score Global de Santé --}}
    <div class="mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Score de Santé : {{ $health['score'] }}/{{ $health['max'] }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Dernier audit : {{ $audit['last_audit'] }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="inline-flex items-center px-4 py-2 rounded-full text-lg font-semibold
                        @if($health['status'] === 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($health['status'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @endif">
                        @if($health['status'] === 'success')
                            <x-heroicon-o-check-circle class="w-6 h-6 mr-2" />
                        @elseif($health['status'] === 'warning')
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 mr-2" />
                        @else
                            <x-heroicon-o-x-circle class="w-6 h-6 mr-2" />
                        @endif
                        {{ $health['label'] }}
                    </div>
                </div>
            </div>

            {{-- Barre de progression --}}
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 mb-4">
                <div class="h-4 rounded-full transition-all duration-500
                    @if($health['status'] === 'success') bg-green-500
                    @elseif($health['status'] === 'warning') bg-yellow-500
                    @else bg-red-500
                    @endif"
                    style="width: {{ $health['percentage'] }}%">
                </div>
            </div>

            {{-- Détails des scores --}}
            <div class="grid grid-cols-4 gap-4 text-center">
                @foreach($health['details'] as $key => $detail)
                    <div class="p-3 rounded-lg 
                        @if($detail['score'] === $detail['max']) bg-green-50 dark:bg-green-900/20
                        @else bg-red-50 dark:bg-red-900/20
                        @endif">
                        <div class="text-2xl font-bold 
                            @if($detail['score'] === $detail['max']) text-green-600 dark:text-green-400
                            @else text-red-600 dark:text-red-400
                            @endif">
                            {{ $detail['score'] }}/{{ $detail['max'] }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $detail['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Grille principale des audits --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        
        {{-- PILIER 1A : Intégrité des Ventes --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between
                @if($audit['sales_integrity']['is_valid']) bg-green-50 dark:bg-green-900/20
                @else bg-red-50 dark:bg-red-900/20
                @endif">
                <div class="flex items-center">
                    @if($audit['sales_integrity']['is_valid'])
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-500 mr-3" />
                    @else
                        <x-heroicon-o-x-circle class="w-6 h-6 text-red-500 mr-3" />
                    @endif
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Intégrité des Ventes</h3>
                </div>
                <span class="text-sm font-medium 
                    @if($audit['sales_integrity']['is_valid']) text-green-600 dark:text-green-400
                    @else text-red-600 dark:text-red-400
                    @endif">
                    {{ $audit['sales_integrity']['message'] }}
                </span>
            </div>
            <div class="p-6">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">∑ Ventes TTC (Métier)</td>
                            <td class="py-2 text-right font-mono font-semibold text-gray-900 dark:text-white">
                                {{ number_format($audit['sales_integrity']['metier_ttc'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">− TVA Collectée</td>
                            <td class="py-2 text-right font-mono text-gray-900 dark:text-white">
                                {{ number_format($audit['sales_integrity']['metier_vat'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                            <td class="py-2 font-semibold text-gray-700 dark:text-gray-300">= CA HT Théorique</td>
                            <td class="py-2 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($audit['sales_integrity']['metier_ht'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">∑ Classe 7 (Comptable)</td>
                            <td class="py-2 text-right font-mono font-semibold text-gray-900 dark:text-white">
                                {{ number_format($audit['sales_integrity']['comptable_ht'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                            <td class="py-2 font-semibold 
                                @if($audit['sales_integrity']['is_valid']) text-green-600 dark:text-green-400
                                @else text-red-600 dark:text-red-400
                                @endif">
                                Écart
                            </td>
                            <td class="py-2 text-right font-mono font-bold 
                                @if($audit['sales_integrity']['is_valid']) text-green-600 dark:text-green-400
                                @else text-red-600 dark:text-red-400
                                @endif">
                                {{ number_format($audit['sales_integrity']['difference'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PILIER 1B : Intégrité des Achats --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between
                @if($audit['purchases_integrity']['is_valid']) bg-green-50 dark:bg-green-900/20
                @else bg-red-50 dark:bg-red-900/20
                @endif">
                <div class="flex items-center">
                    @if($audit['purchases_integrity']['is_valid'])
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-500 mr-3" />
                    @else
                        <x-heroicon-o-x-circle class="w-6 h-6 text-red-500 mr-3" />
                    @endif
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Intégrité des Achats</h3>
                </div>
                <span class="text-sm font-medium 
                    @if($audit['purchases_integrity']['is_valid']) text-green-600 dark:text-green-400
                    @else text-red-600 dark:text-red-400
                    @endif">
                    {{ $audit['purchases_integrity']['message'] }}
                </span>
            </div>
            <div class="p-6">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">∑ Achats TTC (Métier)</td>
                            <td class="py-2 text-right font-mono font-semibold text-gray-900 dark:text-white">
                                {{ number_format($audit['purchases_integrity']['metier_ttc'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">− TVA Déductible</td>
                            <td class="py-2 text-right font-mono text-gray-900 dark:text-white">
                                {{ number_format($audit['purchases_integrity']['metier_vat'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                            <td class="py-2 font-semibold text-gray-700 dark:text-gray-300">= Charges HT Théorique</td>
                            <td class="py-2 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ number_format($audit['purchases_integrity']['metier_ht'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-gray-600 dark:text-gray-400">∑ Classe 6 (Comptable)</td>
                            <td class="py-2 text-right font-mono font-semibold text-gray-900 dark:text-white">
                                {{ number_format($audit['purchases_integrity']['comptable_ht'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                            <td class="py-2 font-semibold 
                                @if($audit['purchases_integrity']['is_valid']) text-green-600 dark:text-green-400
                                @else text-red-600 dark:text-red-400
                                @endif">
                                Écart
                            </td>
                            <td class="py-2 text-right font-mono font-bold 
                                @if($audit['purchases_integrity']['is_valid']) text-green-600 dark:text-green-400
                                @else text-red-600 dark:text-red-400
                                @endif">
                                {{ number_format($audit['purchases_integrity']['difference'], 2, ',', ' ') }} €
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- PILIER 2 : Audit de Séquence --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between
                @if($audit['sequence_audit']['details']['fec_sequence']['is_valid']) bg-green-50 dark:bg-green-900/20
                @else bg-red-50 dark:bg-red-900/20
                @endif">
                <div class="flex items-center">
                    @if($audit['sequence_audit']['details']['fec_sequence']['is_valid'])
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-500 mr-3" />
                    @else
                        <x-heroicon-o-x-circle class="w-6 h-6 text-red-500 mr-3" />
                    @endif
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Séquence FEC</h3>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                    Conformité NF525
                </span>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600 dark:text-gray-400">Écritures analysées</span>
                    <span class="font-mono font-bold text-gray-900 dark:text-white">
                        {{ $audit['sequence_audit']['details']['fec_sequence']['total'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600 dark:text-gray-400">Dernière séquence</span>
                    <span class="font-mono font-bold text-gray-900 dark:text-white">
                        {{ $audit['sequence_audit']['details']['fec_sequence']['last_sequence'] ?? '-' }}
                    </span>
                </div>
                <div class="p-4 rounded-lg 
                    @if($audit['sequence_audit']['details']['fec_sequence']['is_valid']) bg-green-50 dark:bg-green-900/20
                    @else bg-red-50 dark:bg-red-900/20
                    @endif">
                    <p class="text-sm font-medium 
                        @if($audit['sequence_audit']['details']['fec_sequence']['is_valid']) text-green-700 dark:text-green-300
                        @else text-red-700 dark:text-red-300
                        @endif">
                        {{ $audit['sequence_audit']['details']['fec_sequence']['message'] }}
                    </p>
                    @if(!empty($audit['sequence_audit']['details']['fec_sequence']['gaps']))
                        <p class="text-xs mt-2 text-red-600 dark:text-red-400">
                            Numéros manquants : {{ implode(', ', $audit['sequence_audit']['details']['fec_sequence']['gaps']) }}
                            @if($audit['sequence_audit']['details']['fec_sequence']['gaps_count'] > 10)
                                ... et {{ $audit['sequence_audit']['details']['fec_sequence']['gaps_count'] - 10 }} autres
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between
                @if($audit['sequence_audit']['details']['invoice_sequence']['is_valid']) bg-green-50 dark:bg-green-900/20
                @else bg-red-50 dark:bg-red-900/20
                @endif">
                <div class="flex items-center">
                    @if($audit['sequence_audit']['details']['invoice_sequence']['is_valid'])
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-500 mr-3" />
                    @else
                        <x-heroicon-o-x-circle class="w-6 h-6 text-red-500 mr-3" />
                    @endif
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Séquence Factures</h3>
                </div>
                <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                    Art. 289 CGI
                </span>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-gray-600 dark:text-gray-400">Factures analysées</span>
                    <span class="font-mono font-bold text-gray-900 dark:text-white">
                        {{ $audit['sequence_audit']['details']['invoice_sequence']['total'] }}
                    </span>
                </div>
                <div class="p-4 rounded-lg 
                    @if($audit['sequence_audit']['details']['invoice_sequence']['is_valid']) bg-green-50 dark:bg-green-900/20
                    @else bg-red-50 dark:bg-red-900/20
                    @endif">
                    <p class="text-sm font-medium 
                        @if($audit['sequence_audit']['details']['invoice_sequence']['is_valid']) text-green-700 dark:text-green-300
                        @else text-red-700 dark:text-red-300
                        @endif">
                        {{ $audit['sequence_audit']['details']['invoice_sequence']['message'] }}
                    </p>
                    @if(!empty($audit['sequence_audit']['details']['invoice_sequence']['gaps']))
                        <p class="text-xs mt-2 text-red-600 dark:text-red-400">
                            Manquantes : {{ implode(', ', $audit['sequence_audit']['details']['invoice_sequence']['gaps']) }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- PILIER 3 : Cohérence TVA --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between
            @if($audit['vat_coherence']['is_valid']) bg-green-50 dark:bg-green-900/20
            @else bg-red-50 dark:bg-red-900/20
            @endif">
            <div class="flex items-center">
                @if($audit['vat_coherence']['is_valid'])
                    <x-heroicon-o-check-circle class="w-6 h-6 text-green-500 mr-3" />
                @else
                    <x-heroicon-o-x-circle class="w-6 h-6 text-red-500 mr-3" />
                @endif
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cohérence TVA</h3>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-medium 
                @if($audit['vat_coherence']['regime'] === 'encaissements') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                @endif">
                {{ $audit['vat_coherence']['regime_label'] }}
            </span>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">TVA Théorique</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                        {{ number_format($audit['vat_coherence']['theoretical_vat'], 2, ',', ' ') }} €
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">Depuis les factures</p>
                </div>
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">TVA Collectée (4457)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                        {{ number_format($audit['vat_coherence']['accounted_vat'], 2, ',', ' ') }} €
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">Comptabilisée</p>
                </div>
                @if($audit['vat_coherence']['regime'] === 'encaissements')
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <p class="text-sm text-purple-600 dark:text-purple-400 mb-1">TVA en Attente (44574)</p>
                    <p class="text-2xl font-bold text-purple-700 dark:text-purple-300 font-mono">
                        {{ number_format($audit['vat_coherence']['pending_vat'], 2, ',', ' ') }} €
                    </p>
                    <p class="text-xs text-purple-500 dark:text-purple-500">Factures non payées</p>
                </div>
                @else
                <div class="text-center p-4 rounded-lg 
                    @if($audit['vat_coherence']['is_valid']) bg-green-50 dark:bg-green-900/20
                    @else bg-red-50 dark:bg-red-900/20
                    @endif">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Écart</p>
                    <p class="text-2xl font-bold font-mono
                        @if($audit['vat_coherence']['is_valid']) text-green-600 dark:text-green-400
                        @else text-red-600 dark:text-red-400
                        @endif">
                        {{ number_format($audit['vat_coherence']['difference'], 2, ',', ' ') }} €
                    </p>
                    <p class="text-xs 
                        @if($audit['vat_coherence']['is_valid']) text-green-500
                        @else text-red-500
                        @endif">
                        {{ $audit['vat_coherence']['is_valid'] ? 'Parfait' : 'À vérifier' }}
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Statistiques de Lettrage --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Taux de Lettrage</h3>
                <span class="px-2 py-1 rounded-full text-xs font-medium
                    @if($audit['lettering_stats']['status'] === 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($audit['lettering_stats']['status'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @endif">
                    {{ $audit['lettering_stats']['percentage'] }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-4">
                <div class="h-3 rounded-full transition-all duration-500
                    @if($audit['lettering_stats']['status'] === 'success') bg-green-500
                    @elseif($audit['lettering_stats']['status'] === 'warning') bg-yellow-500
                    @else bg-red-500
                    @endif"
                    style="width: {{ $audit['lettering_stats']['percentage'] }}%">
                </div>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">
                    <span class="font-semibold text-green-600 dark:text-green-400">{{ $audit['lettering_stats']['lettered'] }}</span> lettrées
                </span>
                <span class="text-gray-600 dark:text-gray-400">
                    <span class="font-semibold text-orange-600 dark:text-orange-400">{{ $audit['lettering_stats']['unlettered'] }}</span> en attente
                </span>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Anomalies Détectées
                @if(count($audit['anomalies']) > 0)
                    <span class="ml-2 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        {{ count($audit['anomalies']) }}
                    </span>
                @endif
            </h3>
            
            @if(empty($audit['anomalies']))
                <div class="flex items-center justify-center py-8 text-green-600 dark:text-green-400">
                    <x-heroicon-o-check-circle class="w-8 h-8 mr-3" />
                    <span class="text-lg font-medium">Aucune anomalie détectée</span>
                </div>
            @else
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($audit['anomalies'] as $anomaly)
                        <div class="flex items-start p-3 rounded-lg 
                            @if($anomaly['type'] === 'danger') bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                            @elseif($anomaly['type'] === 'warning') bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800
                            @else bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800
                            @endif">
                            <div class="flex-shrink-0 mr-3">
                                @if($anomaly['type'] === 'danger')
                                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-500" />
                                @elseif($anomaly['type'] === 'warning')
                                    <x-heroicon-o-exclamation-circle class="w-5 h-5 text-yellow-500" />
                                @else
                                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500" />
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold 
                                    @if($anomaly['type'] === 'danger') text-red-800 dark:text-red-200
                                    @elseif($anomaly['type'] === 'warning') text-yellow-800 dark:text-yellow-200
                                    @else text-blue-800 dark:text-blue-200
                                    @endif">
                                    {{ $anomaly['title'] }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $anomaly['description'] }}
                                </p>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-500">{{ $anomaly['date'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Bouton Rafraîchir --}}
    <div class="flex justify-end">
        <button 
            wire:click="refreshAudit"
            class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
            <x-heroicon-o-arrow-path class="w-5 h-5 mr-2" wire:loading.class="animate-spin" />
            Rafraîchir l'audit
        </button>
    </div>
</x-filament-panels::page>

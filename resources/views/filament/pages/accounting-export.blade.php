<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Informations sur le FEC -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
                        À propos du FEC
                    </h3>
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Le <strong>Fichier des Écritures Comptables (FEC)</strong> est un export obligatoire en France pour les entreprises tenant leur comptabilité de manière informatisée. 
                        Il doit être présenté lors d'un contrôle fiscal et contient l'ensemble des écritures comptables de l'exercice.
                    </p>
                </div>
            </div>
        </div>

        <!-- Formulaire d'export -->
        <form wire:submit="exportFec" class="space-y-6">
            {{ $this->form }}

            <div class="flex gap-3">
                <x-filament::button 
                    type="button" 
                    color="info"
                    wire:click="previewFec"
                    size="lg"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Prévisualiser
                </x-filament::button>

                <x-filament::button type="submit" size="lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Télécharger le FEC
                </x-filament::button>

                <x-filament::button 
                    type="button" 
                    color="gray"
                    wire:click="exportCsv"
                    size="lg"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Télécharger en CSV
                </x-filament::button>
            </div>
        </form>

        <!-- Informations complémentaires -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Format FEC
                </h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>• Fichier texte avec séparateur pipe (|)</li>
                    <li>• Encodage UTF-8</li>
                    <li>• Extension .txt ou .fec</li>
                    <li>• Nom: SirenFECYYYYMMDD.txt</li>
                    <li>• Conforme à l'article A47 A-1 du LPF</li>
                </ul>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                <h4 class="font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Contenu exporté
                </h4>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li>• Toutes les ventes (factures payées)</li>
                    <li>• Tous les achats (complétés)</li>
                    <li>• Écritures de TVA (collectée et déductible)</li>
                    <li>• Mouvements de trésorerie</li>
                    <li>• Comptes clients et fournisseurs</li>
                </ul>
            </div>
        </div>

        <!-- Avertissement paramètres comptables -->
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-1">
                        Configuration requise
                    </h4>
                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                        Assurez-vous d'avoir configuré vos paramètres comptables (numéros de comptes, journaux, SIREN) 
                        dans les <strong>paramètres de l'entreprise</strong> avant de générer un export FEC.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale de prévisualisation -->
    @if($showPreview && $previewData)
    <div 
        x-data="{ show: @entangle('showPreview') }"
        x-show="show"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <!-- Backdrop -->
        <div 
            class="fixed inset-0 bg-black/50 transition-opacity"
            @click="$wire.closePreview()"
        ></div>

        <!-- Modale -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <div 
                class="relative w-full max-w-7xl bg-white dark:bg-gray-800 rounded-xl shadow-2xl"
                @click.stop
            >
                <!-- En-tête -->
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Prévisualisation des Écritures Comptables
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Période : {{ \Carbon\Carbon::parse($previewData['start_date'])->format('d/m/Y') }} 
                            au {{ \Carbon\Carbon::parse($previewData['end_date'])->format('d/m/Y') }}
                            • {{ $previewData['total_count'] }} écritures
                            @if($previewData['total_count'] > 100)
                                (affichage limité aux 100 premières)
                            @endif
                        </p>
                    </div>
                    <button 
                        @click="$wire.closePreview()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Contenu scrollable -->
                <div class="overflow-x-auto max-h-[70vh] p-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900 sticky top-0 z-10">
                            <tr>
                                @foreach($previewData['header'] as $column)
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                        {{ $column }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($previewData['entries'] as $index => $entry)
                                @if(!empty($entry[0]))
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    @foreach($entry as $cellIndex => $cell)
                                        <td class="px-3 py-2 whitespace-nowrap
                                            @if($cellIndex === 11)
                                                @if((float)$cell > 0)
                                                    text-emerald-600 dark:text-emerald-400 font-bold
                                                @else
                                                    text-gray-400 dark:text-gray-600
                                                @endif
                                            @elseif($cellIndex === 12)
                                                @if((float)$cell > 0)
                                                    text-red-600 dark:text-red-400 font-bold
                                                @else
                                                    text-gray-400 dark:text-gray-600
                                                @endif
                                            @else
                                                text-gray-900 dark:text-gray-100
                                            @endif
                                        ">
                                            {{ $cell }}
                                        </td>
                                    @endforeach
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pied de page -->
                <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-4 bg-gray-50 dark:bg-gray-900">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Légende :</strong> 
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold">Débit</span> • 
                        <span class="text-red-600 dark:text-red-400 font-bold">Crédit</span> • 
                        <span class="text-gray-400 dark:text-gray-600">Montant nul (0.00)</span>
                    </div>
                    <div class="flex gap-3">
                        <x-filament::button 
                            color="gray"
                            @click="$wire.closePreview()"
                        >
                            Fermer
                        </x-filament::button>
                        <x-filament::button 
                            wire:click="exportFec"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Télécharger le FEC
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>

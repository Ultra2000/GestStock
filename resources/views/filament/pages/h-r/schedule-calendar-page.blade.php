<x-filament-panels::page>
    <div class="space-y-6">
        {{-- En-tête avec lien vers le planning grille --}}
        <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Vue Calendrier
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Visualisez et gérez les plannings en mode calendrier interactif
                </p>
            </div>
            <a href="{{ \App\Filament\Pages\SchedulePlanning::getUrl() }}" 
               class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-2">
                <x-heroicon-o-table-cells class="w-4 h-4"/>
                Vue Grille
            </a>
        </div>

        {{-- Le calendrier est affiché via getHeaderWidgets() dans la Page --}}
    </div>
</x-filament-panels::page>

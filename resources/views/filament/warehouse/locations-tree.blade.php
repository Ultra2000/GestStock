{{-- Vue arbre des emplacements --}}
<div class="p-4 space-y-4">
    {{-- Légende --}}
    <div class="flex flex-wrap gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-sm">
        <span class="inline-flex items-center gap-1">🗺️ <span class="text-gray-600 dark:text-gray-400">Zone</span></span>
        <span class="inline-flex items-center gap-1">↔️ <span class="text-gray-600 dark:text-gray-400">Allée</span></span>
        <span class="inline-flex items-center gap-1">📦 <span class="text-gray-600 dark:text-gray-400">Rack</span></span>
        <span class="inline-flex items-center gap-1">📚 <span class="text-gray-600 dark:text-gray-400">Étagère</span></span>
        <span class="inline-flex items-center gap-1">📍 <span class="text-gray-600 dark:text-gray-400">Bin</span></span>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-4 gap-4">
        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $warehouse->locations->count() }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Total emplacements</div>
        </div>
        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $warehouse->locations->where('is_active', true)->count() }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Actifs</div>
        </div>
        <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg text-center">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $warehouse->locations->whereNull('parent_id')->count() }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Racines</div>
        </div>
        <div class="p-3 bg-orange-50 dark:bg-orange-900/30 rounded-lg text-center">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $warehouse->locations->where('type', 'bin')->count() }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Emplacements (Bins)</div>
        </div>
    </div>

    {{-- Arbre --}}
    <div class="border dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-900 max-h-[500px] overflow-y-auto">
        @if($locations->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <x-heroicon-o-folder-open class="w-12 h-12 mx-auto mb-2 opacity-50" />
                <p>Aucun emplacement créé</p>
                <p class="text-sm">Utilisez "Nouvel emplacement" ou "Génération en masse" pour commencer</p>
            </div>
        @else
            <ul class="tree-view space-y-1">
                @foreach($locations as $location)
                    @include('filament.warehouse.partials.location-tree-item', ['location' => $location, 'level' => 0])
                @endforeach
            </ul>
        @endif
    </div>
</div>

<style>
    .tree-view .tree-item {
        position: relative;
    }
    .tree-view .tree-children {
        margin-left: 1.5rem;
        padding-left: 0.75rem;
        border-left: 2px dashed #e5e7eb;
    }
    .dark .tree-view .tree-children {
        border-left-color: #374151;
    }
    .tree-view .tree-toggle {
        cursor: pointer;
        user-select: none;
    }
    .tree-view .tree-toggle:hover {
        background: rgba(0, 0, 0, 0.05);
    }
    .dark .tree-view .tree-toggle:hover {
        background: rgba(255, 255, 255, 0.05);
    }
</style>

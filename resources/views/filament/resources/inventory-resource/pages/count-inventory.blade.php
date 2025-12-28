<x-filament-panels::page>
    {{-- Header avec statistiques --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $record->total_items }}</div>
                <div class="text-sm text-gray-500">Total articles</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-info-600">{{ $record->items_counted }}</div>
                <div class="text-sm text-gray-500">Comptés</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-warning-600">{{ $record->total_items - $record->items_counted }}</div>
                <div class="text-sm text-gray-500">Restants</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold {{ $record->discrepancies_count > 0 ? 'text-danger-600' : 'text-success-600' }}">
                    {{ $record->discrepancies_count }}
                </div>
                <div class="text-sm text-gray-500">Écarts</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Barre de progression --}}
    <div class="mb-6">
        <div class="flex justify-between mb-2">
            <span class="text-sm font-medium">Progression</span>
            <span class="text-sm font-medium">{{ $record->progress_percent }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
            <div class="bg-primary-600 h-3 rounded-full transition-all duration-300" style="width: {{ $record->progress_percent }}%"></div>
        </div>
    </div>

    {{-- Filtres --}}
    <x-filament::section>
        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher un produit..."
                    />
                </x-filament::input.wrapper>
            </div>
            <div class="flex gap-2">
                <x-filament::button
                    wire:click="$set('filter', 'all')"
                    :color="$filter === 'all' ? 'primary' : 'gray'"
                    size="sm"
                >
                    Tous
                </x-filament::button>
                <x-filament::button
                    wire:click="$set('filter', 'pending')"
                    :color="$filter === 'pending' ? 'warning' : 'gray'"
                    size="sm"
                >
                    À compter
                </x-filament::button>
                <x-filament::button
                    wire:click="$set('filter', 'counted')"
                    :color="$filter === 'counted' ? 'success' : 'gray'"
                    size="sm"
                >
                    Comptés
                </x-filament::button>
                <x-filament::button
                    wire:click="$set('filter', 'discrepancy')"
                    :color="$filter === 'discrepancy' ? 'danger' : 'gray'"
                    size="sm"
                >
                    Écarts
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- Liste des produits --}}
    <x-filament::section class="mt-4">
        <div class="space-y-2">
            @forelse($this->filteredItems as $item)
                <div class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 
                    {{ $item->is_counted ? ($item->quantity_difference == 0 ? 'border-l-4 border-l-success-500' : 'border-l-4 border-l-warning-500') : '' }}">
                    
                    {{-- Info produit --}}
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ $item->product->name }}</div>
                        <div class="text-xs text-gray-500">
                            Code: {{ $item->product->code }}
                            @if($item->location)
                                | Emplacement: {{ $item->location->name }}
                            @endif
                        </div>
                    </div>

                    {{-- Stock attendu --}}
                    <div class="text-center w-24">
                        <div class="text-xs text-gray-500">Attendu</div>
                        <div class="font-semibold">{{ number_format($item->quantity_expected, 2, ',', ' ') }}</div>
                    </div>

                    {{-- Input quantité --}}
                    <div class="w-32">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="number"
                                step="0.0001"
                                min="0"
                                wire:model.defer="counts.{{ $item->id }}"
                                placeholder="Qté"
                                class="text-center"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        <x-filament::icon-button
                            wire:click="copyExpected({{ $item->id }})"
                            icon="heroicon-o-document-duplicate"
                            color="gray"
                            size="sm"
                            tooltip="Copier la valeur attendue"
                        />
                        <x-filament::icon-button
                            wire:click="countItem({{ $item->id }})"
                            icon="heroicon-o-check"
                            color="success"
                            size="sm"
                            tooltip="Valider le comptage"
                        />
                        @if($item->is_counted)
                            <x-filament::icon-button
                                wire:click="resetItem({{ $item->id }})"
                                icon="heroicon-o-arrow-path"
                                color="warning"
                                size="sm"
                                tooltip="Réinitialiser"
                            />
                        @endif
                    </div>

                    {{-- Statut --}}
                    <div class="w-20 text-center">
                        @if($item->is_counted)
                            @if($item->quantity_difference == 0)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-success-100 text-success-800 dark:bg-success-800 dark:text-success-100">
                                    ✓ OK
                                </span>
                            @elseif($item->quantity_difference > 0)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-info-100 text-info-800 dark:bg-info-800 dark:text-info-100">
                                    +{{ number_format($item->quantity_difference, 2, ',', ' ') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-danger-100 text-danger-800 dark:bg-danger-800 dark:text-danger-100">
                                    {{ number_format($item->quantity_difference, 2, ',', ' ') }}
                                </span>
                            @endif
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100">
                                En attente
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-gray-500">
                    Aucun produit trouvé
                </div>
            @endforelse
        </div>
    </x-filament::section>

    {{-- Actions de bas de page --}}
    @if($record->items_counted > 0)
        <div class="flex justify-end gap-3 mt-6">
            <x-filament::button
                color="gray"
                tag="a"
                href="{{ $this->getResource()::getUrl('view', ['record' => $record]) }}"
            >
                Retour
            </x-filament::button>
            
            <x-filament::button
                color="success"
                wire:click="submitForValidation"
                wire:confirm="Voulez-vous soumettre cet inventaire pour validation ?"
            >
                Soumettre pour validation
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>

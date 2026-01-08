@php
    $hasChildren = $location->children && $location->children->count() > 0;
    $icon = match($location->type) {
        'zone' => '🗺️',
        'aisle' => '↔️',
        'rack' => '📦',
        'shelf' => '📚',
        'bin' => '📍',
        default => '📁',
    };
    $stock = $location->getStock();
    $usage = $location->getUsagePercent();
@endphp

<li class="tree-item" x-data="{ open: {{ $level < 2 ? 'true' : 'false' }} }">
    <div class="tree-toggle flex items-center gap-2 py-1.5 px-2 rounded {{ $location->is_active ? '' : 'opacity-50' }}">
        {{-- Chevron pour les enfants --}}
        @if($hasChildren)
            <button @click="open = !open" class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-90' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                </svg>
            </button>
        @else
            <span class="w-5 h-5"></span>
        @endif

        {{-- Icône type --}}
        <span class="text-base">{{ $icon }}</span>

        {{-- Code et nom --}}
        <span class="font-mono text-sm font-semibold text-primary-600 dark:text-primary-400">{{ $location->code }}</span>
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $location->name }}</span>

        {{-- Badges --}}
        <div class="flex items-center gap-2 ml-auto">
            @if($stock > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                    {{ number_format($stock, 0) }} unités
                </span>
            @endif

            @if($location->capacity)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                    {{ $usage >= 90 ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : ($usage >= 70 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                    {{ $usage }}%
                </span>
            @endif

            @if(!$location->is_active)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                    Inactif
                </span>
            @endif

            @if($location->barcode)
                <span class="text-gray-400 dark:text-gray-500" title="Code-barres: {{ $location->barcode }}">
                    <x-heroicon-o-qr-code class="w-4 h-4" />
                </span>
            @endif
        </div>
    </div>

    {{-- Enfants --}}
    @if($hasChildren)
        <ul class="tree-children" x-show="open" x-collapse>
            @foreach($location->children->sortBy('code') as $child)
                @include('filament.warehouse.partials.location-tree-item', ['location' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>

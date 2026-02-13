<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-4">
            
            {{-- Section Info Utilisateur (Remplacement du AccountWidget) --}}
            <div class="flex items-center gap-4">
                <x-filament-panels::avatar.user size="lg" :user="filament()->auth()->user()" />
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Bonjour, {{ filament()->auth()->user()->name }} 👋
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Bienvenue sur votre tableau de bord FRECORP ERP.
                    </p>
                </div>
            </div>
            
            {{-- Bouton Action Rapide --}}
            <a href="{{ \App\Filament\Resources\SaleResource::getUrl('create') }}" 
               class="flex items-center gap-2 px-4 py-2 font-bold text-white transition duration-300 rounded-lg bg-primary-600 hover:bg-primary-500 focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900 shadow-md">
                <x-heroicon-o-shopping-cart class="w-5 h-5" />
                <span>Nouvelle Vente</span>
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

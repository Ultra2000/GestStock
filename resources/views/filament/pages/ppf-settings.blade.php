<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header avec statut --}}
        @php
            $company = \Filament\Facades\Filament::getTenant();
            $integration = $company->integrations()->where('service_name', 'ppf')->first();
            $isConfigured = $integration && $integration->is_active && !empty($integration->settings['client_id']);
            $environment = $integration?->settings['environment'] ?? 'sandbox';
        @endphp
        
        <div class="p-4 rounded-lg {{ $isConfigured ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' }}">
            <div class="flex items-center gap-3">
                @if($isConfigured)
                    <x-heroicon-o-check-circle class="w-8 h-8 text-green-600 dark:text-green-400" />
                    <div>
                        <h3 class="font-semibold text-green-700 dark:text-green-300">
                            Facturation électronique configurée
                        </h3>
                        <p class="text-sm text-green-600 dark:text-green-400">
                            Environnement : {{ $environment === 'production' ? '🚀 Production' : '🧪 Sandbox (Test)' }}
                            @if($integration?->last_success_at)
                                — Dernière connexion : {{ $integration->last_success_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                @else
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-amber-600 dark:text-amber-400" />
                    <div>
                        <h3 class="font-semibold text-amber-700 dark:text-amber-300">
                            Configuration requise
                        </h3>
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            Remplissez le formulaire ci-dessous pour activer la facturation électronique
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Formulaire --}}
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex gap-3">
                <x-filament::button type="submit">
                    Sauvegarder
                </x-filament::button>
                
                <x-filament::button 
                    type="button" 
                    color="gray" 
                    wire:click="testConnection"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="testConnection">
                        Tester la connexion
                    </span>
                    <span wire:loading wire:target="testConnection">
                        Test en cours...
                    </span>
                </x-filament::button>
            </div>
        </form>

        {{-- Historique des factures envoyées --}}
        @if($isConfigured)
            <div class="mt-8">
                <h3 class="text-lg font-semibold mb-4">Dernières factures envoyées</h3>
                @php
                    $recentSales = $company->sales()
                        ->whereNotNull('ppf_status')
                        ->orderByDesc('updated_at')
                        ->take(5)
                        ->get();
                @endphp
                
                @if($recentSales->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left">Facture</th>
                                    <th class="px-4 py-2 text-left">Client</th>
                                    <th class="px-4 py-2 text-left">Montant</th>
                                    <th class="px-4 py-2 text-left">Statut PPF</th>
                                    <th class="px-4 py-2 text-left">Date d'envoi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentSales as $sale)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="px-4 py-2 font-medium">{{ $sale->reference }}</td>
                                        <td class="px-4 py-2">{{ $sale->customer?->company_name ?? $sale->customer?->name ?? '-' }}</td>
                                        <td class="px-4 py-2">{{ number_format($sale->total_amount, 2, ',', ' ') }} €</td>
                                        <td class="px-4 py-2">
                                            @php
                                                $statusColors = [
                                                    'DEPOSEE' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                    'MISE_A_DISPOSITION' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
                                                    'PRISE_EN_CHARGE' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
                                                    'MISE_EN_PAIEMENT' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
                                                    'PAYEE' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                    'REJETEE' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                ];
                                                $color = $statusColors[$sale->ppf_status] ?? 'bg-gray-100 text-gray-800';
                                            @endphp
                                            <span class="px-2 py-1 text-xs rounded-full {{ $color }}">
                                                {{ $sale->ppf_status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-500">
                                            {{ $sale->ppf_sent_at?->format('d/m/Y H:i') ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        <a href="{{ route('filament.admin.resources.sales.index', ['tenant' => $company->slug]) }}" class="text-primary-600 hover:underline">
                            Voir toutes les factures →
                        </a>
                    </p>
                @else
                    <div class="p-8 text-center bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <x-heroicon-o-document-text class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                        <p class="text-gray-600 dark:text-gray-400">Aucune facture envoyée pour le moment</p>
                        <p class="text-sm text-gray-500 mt-1">
                            Créez une facture et cliquez sur "Envoyer au PPF" pour commencer
                        </p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>

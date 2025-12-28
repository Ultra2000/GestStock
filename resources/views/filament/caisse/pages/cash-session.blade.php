<x-filament-panels::page>
    <div x-data="cashSessionManager()" x-init="init()" class="space-y-6">
        {{-- Session en cours --}}
        <template x-if="currentSession">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold">Session en cours</h2>
                            <p class="text-emerald-100 mt-1">Ouverte le <span x-text="stats.opened_at"></span></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></span>
                            <span class="text-emerald-100">Active</span>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    {{-- Statistiques --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-gray-800" x-text="stats.total_sales"></div>
                            <div class="text-sm text-gray-500">Ventes</div>
                        </div>
                        <div class="bg-emerald-50 rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-emerald-600" x-text="formatPrice(stats.total_cash)"></div>
                            <div class="text-sm text-gray-500">💵 Espèces</div>
                        </div>
                        <div class="bg-blue-50 rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-blue-600" x-text="formatPrice(stats.total_card)"></div>
                            <div class="text-sm text-gray-500">💳 Carte</div>
                        </div>
                        <div class="bg-purple-50 rounded-xl p-4 text-center">
                            <div class="text-3xl font-bold text-purple-600" x-text="formatPrice(stats.total_mobile)"></div>
                            <div class="text-sm text-gray-500">📱 Mobile</div>
                        </div>
                    </div>

                    {{-- Résumé financier --}}
                    <div class="bg-gray-50 rounded-xl p-6 mb-6">
                        <h3 class="font-bold text-lg mb-4">Résumé financier</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Fond de caisse (ouverture)</span>
                                <span class="font-medium" x-text="formatPrice(stats.opening_amount)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total des ventes</span>
                                <span class="font-medium" x-text="formatPrice(stats.total_cash + stats.total_card + stats.total_mobile + stats.total_other)"></span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-3">
                                <span>Montant attendu en caisse</span>
                                <span class="text-emerald-600" x-text="formatPrice(stats.expected_amount)"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Fermeture de caisse --}}
                    <div class="border-t pt-6">
                        <h3 class="font-bold text-lg mb-4">Fermer la session</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Montant compté en caisse (FCFA)</label>
                                <input 
                                    type="number" 
                                    wire:model="closingAmount"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-lg"
                                    placeholder="Entrez le montant..."
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optionnel)</label>
                                <input 
                                    type="text" 
                                    wire:model="notes"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    placeholder="Remarques..."
                                >
                            </div>
                        </div>
                        <button 
                            wire:click="closeSession"
                            class="mt-4 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium"
                        >
                            🔒 Fermer la session
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Pas de session --}}
        <template x-if="!currentSession">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="bg-gradient-to-r from-gray-600 to-gray-700 text-white p-6">
                    <h2 class="text-2xl font-bold">Ouvrir une session de caisse</h2>
                    <p class="text-gray-300 mt-1">Commencez par déclarer votre fond de caisse</p>
                </div>

                <div class="p-6">
                    <div class="max-w-md mx-auto">
                        <div class="text-center mb-6">
                            <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-800">Fond de caisse</h3>
                            <p class="text-gray-500 mt-1">Entrez le montant d'argent présent dans votre caisse</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Montant du fond de caisse (FCFA)</label>
                            <input 
                                type="number" 
                                wire:model="openingAmount"
                                class="w-full px-4 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-2xl text-center font-bold"
                                placeholder="0"
                                min="0"
                            >
                        </div>

                        {{-- Raccourcis montants --}}
                        <div class="grid grid-cols-4 gap-2 mb-6">
                            @foreach([5000, 10000, 25000, 50000] as $amount)
                                <button 
                                    wire:click="$set('openingAmount', {{ $amount }})"
                                    class="py-2 px-3 border-2 border-gray-200 rounded-lg hover:border-emerald-400 hover:bg-emerald-50 transition text-sm font-medium"
                                >
                                    {{ number_format($amount, 0, ',', ' ') }}
                                </button>
                            @endforeach
                        </div>

                        <button 
                            wire:click="openSession"
                            class="w-full py-4 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white text-xl font-bold rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition"
                        >
                            🚀 Ouvrir la caisse
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Historique des sessions --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h3 class="font-bold text-lg">Historique des sessions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ouverture</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ventes</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Fermeture</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Écart</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template x-for="session in history" :key="session.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm" x-text="new Date(session.closed_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })"></td>
                                <td class="px-4 py-3 text-sm text-right" x-text="formatPrice(session.opening_amount)"></td>
                                <td class="px-4 py-3 text-sm text-right font-medium" x-text="session.total_sales + ' (' + formatPrice(parseFloat(session.total_cash) + parseFloat(session.total_card) + parseFloat(session.total_mobile)) + ')'"></td>
                                <td class="px-4 py-3 text-sm text-right" x-text="formatPrice(session.closing_amount)"></td>
                                <td class="px-4 py-3 text-sm text-right font-medium" 
                                    :class="parseFloat(session.difference) === 0 ? 'text-emerald-600' : (parseFloat(session.difference) > 0 ? 'text-amber-600' : 'text-red-600')"
                                    x-text="(parseFloat(session.difference) >= 0 ? '+' : '') + formatPrice(session.difference)">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <template x-if="history.length === 0">
                    <div class="p-8 text-center text-gray-500">
                        Aucun historique de session
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function cashSessionManager() {
            return {
                currentSession: @json($this->currentSession),
                stats: {},
                history: [],

                async init() {
                    await this.loadStats();
                    await this.loadHistory();
                },

                async loadStats() {
                    if (this.currentSession) {
                        this.stats = await this.$wire.getSessionStats();
                    }
                },

                async loadHistory() {
                    this.history = await this.$wire.getSessionHistory();
                },

                formatPrice(value) {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(value || 0) + ' FCFA';
                }
            };
        }
    </script>
</x-filament-panels::page>

<x-filament-panels::page>
    <div x-data="salesHistory()" x-init="init()" class="space-y-6">
        {{-- En-tête --}}
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Ventes du jour</h2>
                    <p class="text-gray-500 mt-1" x-text="sales.length + ' vente(s) enregistrée(s)'"></p>
                </div>
                <button @click="loadSales()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Actualiser
                </button>
            </div>
        </div>

        {{-- Liste des ventes --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Articles</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paiement</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template x-for="sale in sales" :key="sale.id">
                            <tr class="hover:bg-gray-50" :class="sale.status === 'cancelled' ? 'bg-red-50 opacity-60' : ''">
                                <td class="px-4 py-3">
                                    <span class="font-mono font-medium" x-text="sale.invoice_number"></span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="new Date(sale.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })"></td>
                                <td class="px-4 py-3">
                                    <div class="text-sm">
                                        <template x-for="item in sale.items.slice(0, 2)" :key="item.id">
                                            <div class="text-gray-600" x-text="item.quantity + '× ' + (item.product?.name || 'Produit')"></div>
                                        </template>
                                        <template x-if="sale.items.length > 2">
                                            <div class="text-gray-400 text-xs" x-text="'+ ' + (sale.items.length - 2) + ' autre(s)'"></div>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full"
                                          :class="{
                                              'bg-emerald-100 text-emerald-700': sale.payment_method === 'cash',
                                              'bg-blue-100 text-blue-700': sale.payment_method === 'card',
                                              'bg-purple-100 text-purple-700': sale.payment_method === 'mobile',
                                              'bg-gray-100 text-gray-700': sale.payment_method === 'mixed'
                                          }"
                                          x-text="{'cash': '💵 Espèces', 'card': '💳 Carte', 'mobile': '📱 Mobile', 'mixed': '🔀 Mixte'}[sale.payment_method] || sale.payment_method">
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold" x-text="formatPrice(sale.total)"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 text-xs rounded-full"
                                          :class="{
                                              'bg-emerald-100 text-emerald-700': sale.status === 'completed',
                                              'bg-red-100 text-red-700': sale.status === 'cancelled',
                                              'bg-amber-100 text-amber-700': sale.status === 'pending'
                                          }"
                                          x-text="{'completed': 'Validée', 'cancelled': 'Annulée', 'pending': 'En attente'}[sale.status] || sale.status">
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="viewDetails(sale)" class="p-2 hover:bg-gray-100 rounded-lg" title="Détails">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            @click="printReceipt(sale)" 
                                            class="p-2 hover:bg-gray-100 rounded-lg" 
                                            title="Imprimer"
                                            x-show="sale.status !== 'cancelled'"
                                        >
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            @click="cancelSale(sale)" 
                                            class="p-2 hover:bg-red-100 rounded-lg" 
                                            title="Annuler"
                                            x-show="sale.status === 'completed'"
                                        >
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <template x-if="sales.length === 0">
                <div class="p-12 text-center">
                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="text-gray-500">Aucune vente aujourd'hui</p>
                </div>
            </template>
        </div>

        {{-- Modal détails --}}
        <template x-if="selectedSale">
            <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="selectedSale = null">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden">
                    <div class="bg-emerald-600 text-white p-4 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold" x-text="'Ticket ' + selectedSale.invoice_number"></h3>
                            <p class="text-emerald-100 text-sm" x-text="new Date(selectedSale.created_at).toLocaleString('fr-FR')"></p>
                        </div>
                        <button @click="selectedSale = null" class="hover:bg-emerald-700 rounded-full p-1">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="p-4 max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-2">Article</th>
                                    <th class="text-center py-2">Qté</th>
                                    <th class="text-right py-2">P.U.</th>
                                    <th class="text-right py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in selectedSale.items" :key="item.id">
                                    <tr class="border-b">
                                        <td class="py-2" x-text="item.product?.name || 'Produit'"></td>
                                        <td class="text-center py-2" x-text="item.quantity"></td>
                                        <td class="text-right py-2" x-text="formatPrice(item.unit_price)"></td>
                                        <td class="text-right py-2 font-medium" x-text="formatPrice(item.total_price)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="border-t p-4 bg-gray-50 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Sous-total</span>
                            <span x-text="formatPrice(selectedSale.items.reduce((s, i) => s + parseFloat(i.total_price), 0))"></span>
                        </div>
                        <template x-if="selectedSale.discount_percent > 0">
                            <div class="flex justify-between text-sm text-red-600">
                                <span x-text="'Remise (' + selectedSale.discount_percent + '%)'"></span>
                                <span x-text="'-' + formatPrice(selectedSale.items.reduce((s, i) => s + parseFloat(i.total_price), 0) * selectedSale.discount_percent / 100)"></span>
                            </div>
                        </template>
                        <template x-if="selectedSale.tax_percent > 0">
                            <div class="flex justify-between text-sm">
                                <span x-text="'TVA (' + selectedSale.tax_percent + '%)'"></span>
                                <span x-text="formatPrice((selectedSale.items.reduce((s, i) => s + parseFloat(i.total_price), 0) * (1 - selectedSale.discount_percent/100)) * selectedSale.tax_percent / 100)"></span>
                            </div>
                        </template>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t">
                            <span>TOTAL</span>
                            <span class="text-emerald-600" x-text="formatPrice(selectedSale.total)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function salesHistory() {
            return {
                sales: [],
                selectedSale: null,

                async init() {
                    await this.loadSales();
                },

                async loadSales() {
                    this.sales = await this.$wire.getTodaySales();
                },

                formatPrice(value) {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(value || 0) + ' FCFA';
                },

                viewDetails(sale) {
                    this.selectedSale = sale;
                },

                async cancelSale(sale) {
                    if (!confirm('Voulez-vous vraiment annuler cette vente ? Le stock sera restauré.')) return;
                    
                    const result = await this.$wire.cancelSale(sale.id);
                    if (result.success) {
                        await this.loadSales();
                        alert('Vente annulée');
                    } else {
                        alert(result.message || 'Erreur');
                    }
                },

                printReceipt(sale) {
                    // TODO: Implémenter impression
                    window.open('/sales/' + sale.id + '/invoice', '_blank');
                }
            };
        }
    </script>
</x-filament-panels::page>

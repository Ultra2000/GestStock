<x-filament-panels::page>
    <div class="space-y-6">
        {{-- En-tête --}}
        <div style="background: linear-gradient(to right, #7c3aed, #9333ea); border-radius: 1rem; padding: 1.5rem; color: white;">
            <div class="flex items-center gap-4">
                <div style="padding: 0.75rem; background: rgba(255,255,255,0.2); border-radius: 0.75rem;">
                    <x-heroicon-o-document-chart-bar class="w-8 h-8" />
                </div>
                <div>
                    <h2 style="font-size: 1.5rem; font-weight: bold; color: white;">Centre de Rapports</h2>
                    <p style="color: rgba(255,255,255,0.8);">Générez et téléchargez vos rapports PDF professionnels</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Carte État des Stocks --}}
            <div style="background: white; border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e5e7eb;" class="dark:!bg-gray-800 dark:!border-gray-700">
                <div style="background: #3b82f6; padding: 1rem;">
                    <div class="flex items-center gap-3" style="color: white;">
                        <x-heroicon-o-cube class="w-6 h-6" />
                        <h3 style="font-size: 1.125rem; font-weight: bold; color: white;">État des Stocks</h3>
                    </div>
                    <p style="color: #bfdbfe; font-size: 0.875rem; margin-top: 0.25rem;">Inventaire complet avec valorisation</p>
                </div>
                <div style="padding: 1.5rem;" class="space-y-4">
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;" class="dark:!text-gray-300">Entrepôt (optionnel)</label>
                        <select wire:model="stock_warehouse_id" style="width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.5rem;" class="dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white">
                            <option value="">Tous les entrepôts</option>
                            @foreach($this->getWarehouses() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="stock_low_only" id="low_stock" class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                        <label for="low_stock" style="font-size: 0.875rem; color: #374151;" class="dark:!text-gray-300">Stock bas uniquement</label>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button wire:click="downloadStockStatus" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #3b82f6; color: white !important; font-weight: 600; border-radius: 0.75rem; border: none; cursor: pointer;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                            <span style="color: white !important;">Télécharger PDF</span>
                        </button>
                        <button wire:click="downloadInventoryCsv" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #22c55e; color: white !important; font-weight: 600; border-radius: 0.75rem; border: none; cursor: pointer;" onmouseover="this.style.background='#16a34a'" onmouseout="this.style.background='#22c55e'">
                            <x-heroicon-o-table-cells class="w-5 h-5" />
                            <span style="color: white !important;">CSV</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Carte Bilan Comptable --}}
            <div style="background: white; border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e5e7eb;" class="dark:!bg-gray-800 dark:!border-gray-700">
                <div style="background: #8b5cf6; padding: 1rem;">
                    <div class="flex items-center gap-3" style="color: white;">
                        <x-heroicon-o-calculator class="w-6 h-6" />
                        <h3 style="font-size: 1.125rem; font-weight: bold; color: white;">Bilan Comptable</h3>
                    </div>
                    <p style="color: #ddd6fe; font-size: 0.875rem; margin-top: 0.25rem;">Synthèse financière complète</p>
                </div>
                <div style="padding: 1.5rem;" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;" class="dark:!text-gray-300">Date début</label>
                            <input type="date" wire:model="financial_start_date" style="width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.5rem;" class="dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;" class="dark:!text-gray-300">Date fin</label>
                            <input type="date" wire:model="financial_end_date" style="width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.5rem;" class="dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white">
                        </div>
                    </div>
                    <div style="background: #f5f3ff; border-radius: 0.75rem; padding: 1rem;" class="dark:!bg-violet-900/20">
                        <p style="font-size: 0.875rem; color: #6d28d9;" class="dark:!text-violet-300">
                            <strong>Inclut :</strong> CA, achats, marge brute, TVA, évolution mensuelle, top clients/fournisseurs
                        </p>
                    </div>
                    <button wire:click="downloadFinancialReport" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #8b5cf6; color: white !important; font-weight: 600; border-radius: 0.75rem; border: none; cursor: pointer;" onmouseover="this.style.background='#7c3aed'" onmouseout="this.style.background='#8b5cf6'">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        <span style="color: white !important;">Télécharger le Bilan PDF</span>
                    </button>
                </div>
            </div>

            {{-- Carte Journal des Ventes --}}
            <div style="background: white; border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e5e7eb;" class="dark:!bg-gray-800 dark:!border-gray-700">
                <div style="background: #10b981; padding: 1rem;">
                    <div class="flex items-center gap-3" style="color: white;">
                        <x-heroicon-o-banknotes class="w-6 h-6" />
                        <h3 style="font-size: 1.125rem; font-weight: bold; color: white;">Journal des Ventes</h3>
                    </div>
                    <p style="color: #a7f3d0; font-size: 0.875rem; margin-top: 0.25rem;">Liste détaillée des factures émises</p>
                </div>
                <div style="padding: 1.5rem;" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;" class="dark:!text-gray-300">Date début</label>
                            <input type="date" wire:model="sales_start_date" style="width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.5rem;" class="dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;" class="dark:!text-gray-300">Date fin</label>
                            <input type="date" wire:model="sales_end_date" style="width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.5rem;" class="dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white">
                        </div>
                    </div>
                    <div style="background: #ecfdf5; border-radius: 0.75rem; padding: 1rem;" class="dark:!bg-emerald-900/20">
                        <p style="font-size: 0.875rem; color: #047857;" class="dark:!text-emerald-300">
                            <strong>Inclut :</strong> N° facture, date, client, mode paiement, HT, TVA, TTC
                        </p>
                    </div>
                    <button wire:click="downloadSalesJournal" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #10b981; color: white !important; font-weight: 600; border-radius: 0.75rem; border: none; cursor: pointer;" onmouseover="this.style.background='#059669'" onmouseout="this.style.background='#10b981'">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        <span style="color: white !important;">Télécharger PDF</span>
                    </button>
                </div>
            </div>

            {{-- Carte Journal des Achats --}}
            <div style="background: white; border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e5e7eb;" class="dark:!bg-gray-800 dark:!border-gray-700">
                <div style="background: #ef4444; padding: 1rem;">
                    <div class="flex items-center gap-3" style="color: white;">
                        <x-heroicon-o-shopping-cart class="w-6 h-6" />
                        <h3 style="font-size: 1.125rem; font-weight: bold; color: white;">Journal des Achats</h3>
                    </div>
                    <p style="color: #fecaca; font-size: 0.875rem; margin-top: 0.25rem;">Liste des commandes fournisseurs</p>
                </div>
                <div style="padding: 1.5rem;" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;" class="dark:!text-gray-300">Date début</label>
                            <input type="date" wire:model="purchases_start_date" style="width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.5rem;" class="dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.5rem;" class="dark:!text-gray-300">Date fin</label>
                            <input type="date" wire:model="purchases_end_date" style="width: 100%; border-radius: 0.5rem; border: 1px solid #d1d5db; padding: 0.5rem;" class="dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white">
                        </div>
                    </div>
                    <div style="background: #fef2f2; border-radius: 0.75rem; padding: 1rem;" class="dark:!bg-red-900/20">
                        <p style="font-size: 0.875rem; color: #b91c1c;" class="dark:!text-red-300">
                            <strong>Inclut :</strong> N° commande, fournisseur, statut, HT, TVA déductible, TTC
                        </p>
                    </div>
                    <button wire:click="downloadPurchasesJournal" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #ef4444; color: white !important; font-weight: 600; border-radius: 0.75rem; border: none; cursor: pointer;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                        <span style="color: white !important;">Télécharger PDF</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Section Rapports Rapides --}}
        <div style="background: white; border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); padding: 1.5rem; border: 1px solid #e5e7eb;" class="dark:!bg-gray-800 dark:!border-gray-700">
            <h3 style="font-size: 1.125rem; font-weight: bold; color: #111827; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;" class="dark:!text-white">
                <x-heroicon-o-bolt class="w-5 h-5" style="color: #f59e0b;" />
                Rapports Rapides
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('reports.stock-status', ['companyId' => $this->getCompanyId(), 'low_stock_only' => 1]) }}" 
                   target="_blank"
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #fffbeb; border-radius: 0.75rem; text-decoration: none; transition: background 0.2s;" 
                   class="dark:!bg-amber-900/20 hover:!bg-amber-100 dark:hover:!bg-amber-900/30">
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8" style="color: #f59e0b;" />
                    <div>
                        <p style="font-weight: 600; color: #111827;" class="dark:!text-white">Alertes Stock</p>
                        <p style="font-size: 0.75rem; color: #6b7280;" class="dark:!text-gray-400">Produits en rupture</p>
                    </div>
                </a>
                
                <a href="{{ route('reports.financial', ['companyId' => $this->getCompanyId(), 'start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->toDateString()]) }}" 
                   target="_blank"
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f5f3ff; border-radius: 0.75rem; text-decoration: none; transition: background 0.2s;"
                   class="dark:!bg-violet-900/20 hover:!bg-violet-100 dark:hover:!bg-violet-900/30">
                    <x-heroicon-o-calendar class="w-8 h-8" style="color: #8b5cf6;" />
                    <div>
                        <p style="font-weight: 600; color: #111827;" class="dark:!text-white">Bilan du mois</p>
                        <p style="font-size: 0.75rem; color: #6b7280;" class="dark:!text-gray-400">Mois en cours</p>
                    </div>
                </a>
                
                <a href="{{ route('reports.sales-journal', ['companyId' => $this->getCompanyId(), 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString()]) }}" 
                   target="_blank"
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #ecfdf5; border-radius: 0.75rem; text-decoration: none; transition: background 0.2s;"
                   class="dark:!bg-emerald-900/20 hover:!bg-emerald-100 dark:hover:!bg-emerald-900/30">
                    <x-heroicon-o-clock class="w-8 h-8" style="color: #10b981;" />
                    <div>
                        <p style="font-weight: 600; color: #111827;" class="dark:!text-white">Ventes du jour</p>
                        <p style="font-size: 0.75rem; color: #6b7280;" class="dark:!text-gray-400">Aujourd'hui</p>
                    </div>
                </a>
                
                <a href="{{ route('reports.inventory-export', ['companyId' => $this->getCompanyId()]) }}" 
                   target="_blank"
                   style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f0fdf4; border-radius: 0.75rem; text-decoration: none; transition: background 0.2s;"
                   class="dark:!bg-green-900/20 hover:!bg-green-100 dark:hover:!bg-green-900/30">
                    <x-heroicon-o-table-cells class="w-8 h-8" style="color: #22c55e;" />
                    <div>
                        <p style="font-weight: 600; color: #111827;" class="dark:!text-white">Export Excel</p>
                        <p style="font-size: 0.75rem; color: #6b7280;" class="dark:!text-gray-400">Inventaire CSV</p>
                    </div>
                </a>
            </div>
        </div>

        {{-- Info sur les autres rapports --}}
        <div style="background: #f9fafb; border-radius: 0.75rem; padding: 1rem; border: 1px solid #e5e7eb;" class="dark:!bg-gray-800/50 dark:!border-gray-700">
            <p style="font-size: 0.875rem; color: #4b5563;" class="dark:!text-gray-400">
                <strong>💡 Astuce :</strong> Les factures individuelles peuvent être téléchargées depuis les pages Ventes et Achats. 
                Les rapports de caisse sont disponibles depuis l'historique des sessions POS.
            </p>
        </div>
    </div>
</x-filament-panels::page>

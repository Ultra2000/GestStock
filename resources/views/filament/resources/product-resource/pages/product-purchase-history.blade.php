<x-filament-panels::page>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-primary-600">{{ number_format($kpis['total_units'], 0, ',', ' ') }}</div>
                <div class="text-sm text-gray-500 mt-1">Unités achetées</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-success-600">{{ number_format($kpis['total_ht'], 2, ',', ' ') }} {{ $kpis['currency'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Total HT</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-info-600">{{ $kpis['order_count'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Commandes</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-warning-600">{{ $kpis['avg_discount'] }}%</div>
                <div class="text-sm text-gray-500 mt-1">Remise moy.</div>
            </div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-center">
                <div class="text-2xl font-bold text-danger-600">{{ number_format($kpis['total_discount'], 2, ',', ' ') }} {{ $kpis['currency'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Remises obtenues</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Filtres --}}
    <x-filament::section class="mb-6">
        <div class="flex flex-col md:flex-row gap-4 items-end">
            {{-- Période --}}
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Période</label>
                <select wire:model.live="period"
                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-lg shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="this_month">Ce mois-ci</option>
                    <option value="last_month">Mois dernier</option>
                    <option value="this_quarter">Ce trimestre</option>
                    <option value="last_quarter">Trimestre dernier</option>
                    <option value="this_year">Cette année</option>
                    <option value="last_year">Année dernière</option>
                    <option value="custom">Période personnalisée</option>
                </select>
            </div>

            {{-- Plage personnalisée --}}
            @if($period === 'custom')
            <div class="flex gap-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Du</label>
                    <input type="date" wire:model.live="dateFrom"
                        class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-lg shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Au</label>
                    <input type="date" wire:model.live="dateTo"
                        class="border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-lg shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500" />
                </div>
            </div>
            @endif

            {{-- Fournisseur --}}
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fournisseur</label>
                <select wire:model.live="supplierFilter"
                    class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 rounded-lg shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500">
                    <option value="">Tous les fournisseurs</option>
                    @foreach($suppliers as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    {{-- Tableau --}}
    <x-filament::section>
        @if($purchaseLines->isEmpty())
            <div class="text-center py-12 text-gray-500">
                <x-heroicon-o-shopping-bag class="mx-auto h-12 w-12 text-gray-300 mb-3" />
                <p class="text-sm">Aucun achat trouvé pour cette période.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">Date</th>
                            <th class="text-left py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">N° Facture</th>
                            <th class="text-left py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">Fournisseur</th>
                            <th class="text-right py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">Qté</th>
                            <th class="text-right py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">P.U. HT</th>
                            <th class="text-right py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">Remise %</th>
                            <th class="text-right py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">Remise €</th>
                            <th class="text-right py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">Total HT</th>
                            <th class="text-center py-3 px-3 font-semibold text-gray-600 dark:text-gray-400">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($purchaseLines as $line)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="py-3 px-3 text-gray-700 dark:text-gray-300">
                                    {{ $line->purchase->created_at->format('d/m/Y') }}
                                </td>
                                <td class="py-3 px-3">
                                    <a href="{{ \App\Filament\Resources\PurchaseResource::getUrl('edit', ['record' => $line->purchase_id]) }}"
                                       class="text-primary-600 hover:text-primary-800 dark:text-primary-400 font-medium">
                                        {{ $line->purchase->invoice_number }}
                                    </a>
                                </td>
                                <td class="py-3 px-3 text-gray-700 dark:text-gray-300">
                                    {{ $line->purchase->supplier?->name ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-right font-medium text-gray-900 dark:text-gray-100">
                                    {{ number_format($line->quantity, 0, ',', ' ') }}
                                </td>
                                <td class="py-3 px-3 text-right text-gray-700 dark:text-gray-300">
                                    {{ number_format($line->unit_price, 2, ',', ' ') }}
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($line->discount_percent > 0)
                                        <span class="text-warning-600 dark:text-warning-400 font-medium">
                                            {{ number_format($line->discount_percent, 1, ',', ' ') }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right">
                                    @if($line->discount_amount > 0)
                                        <span class="text-warning-600 dark:text-warning-400">
                                            -{{ number_format($line->discount_amount, 2, ',', ' ') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format($line->total_price_ht, 2, ',', ' ') }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    @php
                                        $status = $line->purchase->status;
                                        $badge = match($status) {
                                            'completed' => ['bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-400', 'Terminé'],
                                            'cancelled' => ['bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-400', 'Annulé'],
                                            default     => ['bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-400', 'En attente'],
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge[0] }}">
                                        {{ $badge[1] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50">
                            <td colspan="3" class="py-3 px-3 font-semibold text-gray-700 dark:text-gray-300">
                                Total ({{ $purchaseLines->count() }} ligne{{ $purchaseLines->count() > 1 ? 's' : '' }})
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-gray-900 dark:text-gray-100">
                                {{ number_format($purchaseLines->sum('quantity'), 0, ',', ' ') }}
                            </td>
                            <td class="py-3 px-3"></td>
                            <td class="py-3 px-3"></td>
                            <td class="py-3 px-3 text-right font-bold text-warning-600 dark:text-warning-400">
                                -{{ number_format($purchaseLines->sum('discount_amount'), 2, ',', ' ') }}
                            </td>
                            <td class="py-3 px-3 text-right font-bold text-success-600 dark:text-success-400">
                                {{ number_format($purchaseLines->sum('total_price_ht'), 2, ',', ' ') }}
                            </td>
                            <td class="py-3 px-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-filament::section>

</x-filament-panels::page>

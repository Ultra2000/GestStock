{{-- Contenu d'un emplacement (produits) --}}
<div class="p-4 space-y-4">
    {{-- Info emplacement --}}
    <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center text-2xl rounded-lg bg-white dark:bg-gray-700 shadow">
            @switch($location->type)
                @case('zone') 🗺️ @break
                @case('aisle') ↔️ @break
                @case('rack') 📦 @break
                @case('shelf') 📚 @break
                @case('bin') 📍 @break
                @default 📁
            @endswitch
        </div>
        <div class="flex-1">
            <h3 class="font-semibold text-lg text-gray-900 dark:text-white">{{ $location->name }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Code: <span class="font-mono font-semibold">{{ $location->full_code }}</span>
                @if($location->barcode)
                    | Barcode: <span class="font-mono">{{ $location->barcode }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-col items-end gap-1">
            @if($location->capacity)
                <div class="text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Capacité:</span>
                    <span class="font-semibold">{{ number_format($location->capacity, 0) }}</span>
                </div>
                <div class="w-32 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    @php $usage = $location->getUsagePercent(); @endphp
                    <div class="h-full {{ $usage >= 90 ? 'bg-red-500' : ($usage >= 70 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                         style="width: {{ min($usage, 100) }}%"></div>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $usage }}% utilisé</span>
            @endif
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $products->count() }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Produits différents</div>
        </div>
        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($location->getStock(), 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Unités totales</div>
        </div>
        <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg text-center">
            @php
                $totalValue = $products->sum(function($p) {
                    return ($p->stock_quantity ?? 0) * ($p->purchase_price ?? 0);
                });
            @endphp
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($totalValue, 0) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Valeur stock ({{ config('settings.currency_symbol', '€') }})</div>
        </div>
    </div>

    {{-- Liste des produits --}}
    @if($products->isEmpty())
        <div class="text-center py-8 text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
            <x-heroicon-o-cube class="w-12 h-12 mx-auto mb-2 opacity-50" />
            <p class="font-medium">Emplacement vide</p>
            <p class="text-sm">Aucun produit stocké dans cet emplacement</p>
        </div>
    @else
        <div class="border dark:border-gray-700 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Produit</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">SKU</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Quantité</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Réservé</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Disponible</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Valeur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($products as $product)
                        @php
                            $qty = $product->stock_quantity ?? 0;
                            $reserved = $product->stock_reserved ?? 0;
                            $available = $qty - $reserved;
                            $value = $qty * ($product->purchase_price ?? 0);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($product->image)
                                        <img src="{{ Storage::url($product->image) }}" alt="" class="w-10 h-10 object-cover rounded">
                                    @else
                                        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                                            <x-heroicon-o-cube class="w-5 h-5 text-gray-400" />
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $product->name }}</div>
                                        @if($product->category)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $product->category->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-gray-600 dark:text-gray-400">{{ $product->sku ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold">{{ number_format($qty, 0) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($reserved > 0)
                                    <span class="text-orange-600 dark:text-orange-400">{{ number_format($reserved, 0) }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="{{ $available <= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} font-semibold">
                                    {{ number_format($available, 0) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-gray-600 dark:text-gray-400">{{ number_format($value, 2) }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800 font-semibold">
                    <tr>
                        <td colspan="2" class="px-4 py-3 text-gray-600 dark:text-gray-300">Total</td>
                        <td class="px-4 py-3 text-right">{{ number_format($products->sum('stock_quantity'), 0) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($products->sum('stock_reserved'), 0) }}</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">
                            {{ number_format($products->sum(fn($p) => ($p->stock_quantity ?? 0) - ($p->stock_reserved ?? 0)), 0) }}
                        </td>
                        <td class="px-4 py-3 text-right text-purple-600 dark:text-purple-400">
                            {{ number_format($totalValue, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>

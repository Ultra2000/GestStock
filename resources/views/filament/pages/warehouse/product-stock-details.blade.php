<div class="space-y-4">
    @if(count($stocks) > 0)
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-2 text-left">Entrepôt</th>
                    <th class="px-4 py-2 text-center">Code</th>
                    <th class="px-4 py-2 text-right">Quantité</th>
                    <th class="px-4 py-2 text-right">Réservé</th>
                    <th class="px-4 py-2 text-right">Disponible</th>
                    <th class="px-4 py-2 text-center">Min</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($stocks as $stock)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $stock->name }}</td>
                        <td class="px-4 py-2 text-center text-gray-500">{{ $stock->code }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($stock->quantity, 2, ',', ' ') }}</td>
                        <td class="px-4 py-2 text-right text-warning-600">{{ number_format($stock->reserved_quantity ?? 0, 2, ',', ' ') }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-success-600">
                            {{ number_format($stock->quantity - ($stock->reserved_quantity ?? 0), 2, ',', ' ') }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if($stock->min_quantity)
                                <span class="@if($stock->quantity <= $stock->min_quantity) text-danger-600 font-bold @else text-gray-500 @endif">
                                    {{ number_format($stock->min_quantity, 2, ',', ' ') }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-100 dark:bg-gray-700 font-semibold">
                <tr>
                    <td class="px-4 py-2" colspan="2">Total</td>
                    <td class="px-4 py-2 text-right">{{ number_format(collect($stocks)->sum('quantity'), 2, ',', ' ') }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format(collect($stocks)->sum('reserved_quantity'), 2, ',', ' ') }}</td>
                    <td class="px-4 py-2 text-right text-success-600">
                        {{ number_format(collect($stocks)->sum(fn($s) => $s->quantity - ($s->reserved_quantity ?? 0)), 2, ',', ' ') }}
                    </td>
                    <td class="px-4 py-2"></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="text-center py-4 text-gray-500">
            <p>Ce produit n'est pas encore enregistré dans un entrepôt.</p>
            <p class="text-sm">Stock actuel (simplifié) : {{ number_format($product->stock ?? 0, 2, ',', ' ') }} {{ $product->unit }}</p>
        </div>
    @endif
</div>

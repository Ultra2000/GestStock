<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ $record->sourceWarehouse->name }} → {{ $record->destinationWarehouse->name }}
        </x-slot>
        <x-slot name="description">
            Statut: {{ $record->status_label }} | Expédié le: {{ $record->shipped_date?->format('d/m/Y') ?? '-' }}
        </x-slot>

        <div class="space-y-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Produit</th>
                        <th class="px-4 py-3 text-center font-medium">Expédié</th>
                        <th class="px-4 py-3 text-center font-medium">Déjà reçu</th>
                        <th class="px-4 py-3 text-center font-medium">En attente</th>
                        <th class="px-4 py-3 text-center font-medium">À recevoir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($record->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $item->product->name }}</div>
                                <div class="text-xs text-gray-500">{{ $item->product->code }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ number_format($item->quantity_shipped, 2, ',', ' ') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ number_format($item->quantity_received, 2, ',', ' ') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php $pending = $item->quantity_shipped - $item->quantity_received; @endphp
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
                                    {{ $pending > 0 ? 'bg-warning-100 text-warning-800 dark:bg-warning-800 dark:text-warning-100' : 'bg-success-100 text-success-800 dark:bg-success-800 dark:text-success-100' }}">
                                    {{ number_format($pending, 2, ',', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($pending > 0)
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            max="{{ $pending }}"
                                            wire:model="quantities.{{ $item->id }}"
                                            class="text-center w-24"
                                        />
                                    </x-filament::input.wrapper>
                                @else
                                    <span class="text-success-600">✓ Complet</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>

    <div class="flex justify-end gap-3 mt-6">
        <x-filament::button
            color="gray"
            tag="a"
            href="{{ $this->getResource()::getUrl('view', ['record' => $record]) }}"
        >
            Annuler
        </x-filament::button>
        
        <x-filament::button
            color="warning"
            wire:click="receiveAll"
        >
            Tout réceptionner
        </x-filament::button>
        
        <x-filament::button
            color="success"
            wire:click="receive"
        >
            Valider la réception
        </x-filament::button>
    </div>
</x-filament-panels::page>

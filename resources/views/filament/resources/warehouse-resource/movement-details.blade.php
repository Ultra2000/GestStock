<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="text-sm text-gray-500">Type</span>
            <div class="font-medium">
                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
                    @if(in_array($movement->type, ['purchase', 'transfer_in', 'adjustment_in', 'return_in', 'production_in', 'initial']))
                        bg-success-100 text-success-800 dark:bg-success-800 dark:text-success-100
                    @else
                        bg-danger-100 text-danger-800 dark:bg-danger-800 dark:text-danger-100
                    @endif
                ">
                    {{ $movement->type_label }}
                </span>
            </div>
        </div>
        <div>
            <span class="text-sm text-gray-500">Date</span>
            <div class="font-medium">{{ $movement->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="border-t pt-4">
        <span class="text-sm text-gray-500">Produit</span>
        <div class="font-medium">{{ $movement->product->name }}</div>
        <div class="text-xs text-gray-500">Code: {{ $movement->product->code }}</div>
    </div>

    <div class="grid grid-cols-3 gap-4 border-t pt-4">
        <div>
            <span class="text-sm text-gray-500">Quantité avant</span>
            <div class="font-medium">{{ number_format($movement->quantity_before, 4, ',', ' ') }}</div>
        </div>
        <div>
            <span class="text-sm text-gray-500">Mouvement</span>
            <div class="font-bold @if($movement->quantity >= 0) text-success-600 @else text-danger-600 @endif">
                {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 4, ',', ' ') }}
            </div>
        </div>
        <div>
            <span class="text-sm text-gray-500">Quantité après</span>
            <div class="font-medium">{{ number_format($movement->quantity_after, 4, ',', ' ') }}</div>
        </div>
    </div>

    <div class="border-t pt-4">
        <span class="text-sm text-gray-500">Entrepôt</span>
        <div class="font-medium">{{ $movement->warehouse->name }}</div>
        @if($movement->location)
            <div class="text-xs text-gray-500">Emplacement: {{ $movement->location->name }}</div>
        @endif
    </div>

    @if($movement->reference)
        <div class="border-t pt-4">
            <span class="text-sm text-gray-500">Référence</span>
            <div class="font-medium">{{ $movement->reference }}</div>
        </div>
    @endif

    @if($movement->reason)
        <div class="border-t pt-4">
            <span class="text-sm text-gray-500">Motif</span>
            <div class="font-medium">{{ $movement->reason }}</div>
        </div>
    @endif

    @if($movement->user)
        <div class="border-t pt-4">
            <span class="text-sm text-gray-500">Effectué par</span>
            <div class="font-medium">{{ $movement->user->name }}</div>
        </div>
    @endif

    @if($movement->batch_number)
        <div class="border-t pt-4">
            <span class="text-sm text-gray-500">N° de lot</span>
            <div class="font-medium">{{ $movement->batch_number }}</div>
        </div>
    @endif
</div>

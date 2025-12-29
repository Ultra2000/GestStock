<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Caissier</p>
            <p class="font-medium">{{ $session->user?->name ?? 'Inconnu' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Statut</p>
            <p class="font-medium">
                @if($session->closed_at)
                    <span class="text-success-600">Fermée</span>
                @else
                    <span class="text-warning-600">En cours</span>
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Ouverture</p>
            <p class="font-medium">{{ $session->opened_at->format('d/m/Y à H:i') }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Fermeture</p>
            <p class="font-medium">
                {{ $session->closed_at ? $session->closed_at->format('d/m/Y à H:i') : '-' }}
            </p>
        </div>
    </div>

    <hr class="border-gray-200 dark:border-gray-700">

    <div class="space-y-2">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Fond de caisse</span>
            <span class="font-medium">{{ number_format($session->opening_amount, 2, ',', ' ') }} €</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Total des ventes</span>
            <span class="font-medium text-success-600">{{ number_format($session->total_sales ?? 0, 2, ',', ' ') }} €</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Espèces encaissées</span>
            <span class="font-medium">{{ number_format($session->cash_payments ?? 0, 2, ',', ' ') }} €</span>
        </div>
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">CB encaissées</span>
            <span class="font-medium">{{ number_format($session->card_payments ?? 0, 2, ',', ' ') }} €</span>
        </div>
    </div>

    @if($session->closed_at)
    <hr class="border-gray-200 dark:border-gray-700">
    
    <div class="space-y-2">
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Montant déclaré à la clôture</span>
            <span class="font-medium">{{ number_format($session->closing_amount, 2, ',', ' ') }} €</span>
        </div>
        @php
            $expectedCash = $session->opening_amount + ($session->cash_payments ?? 0);
            $difference = $session->closing_amount - $expectedCash;
        @endphp
        <div class="flex justify-between">
            <span class="text-gray-600 dark:text-gray-400">Attendu en caisse</span>
            <span class="font-medium">{{ number_format($expectedCash, 2, ',', ' ') }} €</span>
        </div>
        <div class="flex justify-between {{ $difference == 0 ? 'text-success-600' : ($difference > 0 ? 'text-warning-600' : 'text-danger-600') }}">
            <span>Écart</span>
            <span class="font-bold">{{ number_format($difference, 2, ',', ' ') }} €</span>
        </div>
    </div>
    @endif

    @if($session->notes)
    <hr class="border-gray-200 dark:border-gray-700">
    <div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Notes</p>
        <p class="mt-1">{{ $session->notes }}</p>
    </div>
    @endif
</div>

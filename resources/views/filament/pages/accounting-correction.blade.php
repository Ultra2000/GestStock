<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-4">
            {{-- Balance indicator --}}
            @php
                $lines = $this->data['lines'] ?? [];
                $totalDebit = collect($lines)->sum('debit');
                $totalCredit = collect($lines)->sum('credit');
                $isBalanced = abs($totalDebit - $totalCredit) < 0.01;
            @endphp
            
            <div class="flex-1">
                <div class="flex items-center gap-6 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-500 dark:text-gray-400">Total Débit:</span>
                        <span class="font-bold text-primary-600">{{ number_format($totalDebit, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-500 dark:text-gray-400">Total Crédit:</span>
                        <span class="font-bold text-primary-600">{{ number_format($totalCredit, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($isBalanced && ($totalDebit > 0 || $totalCredit > 0))
                            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                            <span class="text-success-600 font-medium">Équilibré</span>
                        @elseif($totalDebit > 0 || $totalCredit > 0)
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-500" />
                            <span class="text-danger-600 font-medium">
                                Écart: {{ number_format(abs($totalDebit - $totalCredit), 2, ',', ' ') }} €
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <x-filament::button type="submit" size="lg">
                <x-heroicon-o-check class="w-5 h-5 mr-2" />
                Créer l'écriture OD
            </x-filament::button>
        </div>
    </form>

    {{-- Help section --}}
    <x-filament::section class="mt-8" collapsible collapsed>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-question-mark-circle class="w-5 h-5" />
                Aide : Types d'écritures OD
            </div>
        </x-slot>

        <div class="grid md:grid-cols-2 gap-6 text-sm">
            <div>
                <h4 class="font-bold text-primary-600 mb-2">🔄 Reclassement de compte</h4>
                <p class="text-gray-600 dark:text-gray-400 mb-2">
                    Pour corriger un compte mal imputé :
                </p>
                <ul class="list-disc list-inside text-gray-500 space-y-1">
                    <li>Ligne 1 : Contre-passer l'ancien compte (inverse du mouvement original)</li>
                    <li>Ligne 2 : Imputer le nouveau compte (même sens que l'original)</li>
                </ul>
                <div class="mt-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs">
                    <strong>Exemple :</strong> Reclasser 100€ de 707000 vers 706000<br>
                    • 707000 : Débit 100€ (annule le crédit original)<br>
                    • 706000 : Crédit 100€ (nouvelle imputation)
                </div>
            </div>

            <div>
                <h4 class="font-bold text-primary-600 mb-2">📝 Régularisation</h4>
                <p class="text-gray-600 dark:text-gray-400 mb-2">
                    Pour comptabiliser des ajustements :
                </p>
                <ul class="list-disc list-inside text-gray-500 space-y-1">
                    <li>Charges/Produits constatés d'avance</li>
                    <li>Factures non parvenues</li>
                    <li>Provisions</li>
                    <li>Amortissements</li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold text-primary-600 mb-2">↩️ Extourne</h4>
                <p class="text-gray-600 dark:text-gray-400 mb-2">
                    Pour annuler une écriture précédente :
                </p>
                <ul class="list-disc list-inside text-gray-500 space-y-1">
                    <li>Utilisez le journal EX (Extourne)</li>
                    <li>Passez les montants en sens inverse</li>
                    <li>Référencez l'écriture originale dans le libellé</li>
                </ul>
            </div>

            <div>
                <h4 class="font-bold text-primary-600 mb-2">🔢 À Nouveau</h4>
                <p class="text-gray-600 dark:text-gray-400 mb-2">
                    Pour la reprise des soldes en début d'exercice :
                </p>
                <ul class="list-disc list-inside text-gray-500 space-y-1">
                    <li>Utilisez le journal AN (À Nouveau)</li>
                    <li>Reprise des comptes de bilan (classes 1-5)</li>
                    <li>Au 1er jour du nouvel exercice</li>
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>

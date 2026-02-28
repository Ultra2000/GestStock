<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Info banner --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-1">
                        Import de factures électroniques
                    </h3>
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        Importez les factures de vos fournisseurs au format <strong>UBL (EN16931)</strong> ou <strong>CII (Factur-X / ZUGFeRD)</strong>.
                        Le système détecte automatiquement le format, extrait les données du fournisseur et crée l'achat en statut « En attente ».
                    </p>
                    <ul class="mt-2 text-xs text-blue-700 dark:text-blue-300 space-y-1">
                        <li>• <strong>CII</strong> : Factur-X (France), ZUGFeRD (Allemagne), Cross Industry Invoice</li>
                        <li>• <strong>UBL</strong> : Peppol BIS, CIUS-FR, EN16931 UBL</li>
                        <li>• Le fournisseur est automatiquement créé ou associé (SIRET, TVA intra, nom)</li>
                        <li>• Les produits sont associés par EAN, référence ou nom</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Upload zone --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                1. Sélectionner le fichier XML
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Fichier XML (UBL ou CII)
                    </label>
                    <input
                        type="file"
                        wire:model="xmlFile"
                        accept=".xml,.txt"
                        class="block w-full text-sm text-gray-500 dark:text-gray-400
                            file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                            file:text-sm file:font-semibold
                            file:bg-primary-50 file:text-primary-700
                            dark:file:bg-primary-900/50 dark:file:text-primary-400
                            hover:file:bg-primary-100 dark:hover:file:bg-primary-900/70
                            cursor-pointer"
                    />
                    @error('xmlFile')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div wire:loading wire:target="xmlFile" class="mt-2 text-sm text-gray-500">
                        <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Chargement du fichier…
                    </div>
                </div>

                @if($xmlFile)
                <div class="flex gap-3">
                    <x-filament::button
                        wire:click="previewFile"
                        color="info"
                        size="lg"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Prévisualiser
                    </x-filament::button>

                    <x-filament::button
                        wire:click="importFile"
                        color="success"
                        size="lg"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Importer la facture
                    </x-filament::button>

                    <x-filament::button
                        wire:click="resetForm"
                        color="gray"
                        size="lg"
                    >
                        Réinitialiser
                    </x-filament::button>
                </div>
                @endif
            </div>
        </div>

        {{-- Preview results --}}
        @if($showPreview && $preview)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                2. Aperçu de la facture
            </h2>

            @if($preview['valid'])
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Format</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $preview['format'] }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">N° Facture</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $preview['invoice_number'] ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Date</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $preview['date'] ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Fournisseur</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $preview['supplier'] ?? '—' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total HT</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($preview['total_ht'] ?? 0, 2, ',', ' ') }} {{ $preview['currency'] ?? 'EUR' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">TVA</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ number_format($preview['total_vat'] ?? 0, 2, ',', ' ') }} {{ $preview['currency'] ?? 'EUR' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total TTC</p>
                    <p class="text-lg font-bold text-primary-600 dark:text-primary-400 mt-1">{{ number_format($preview['total_ttc'] ?? 0, 2, ',', ' ') }} {{ $preview['currency'] ?? 'EUR' }}</p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Lignes</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $preview['items_count'] ?? 0 }} article(s)</p>
                </div>
            </div>

            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-sm text-green-800 dark:text-green-200">
                    ✅ Le fichier est valide et prêt à être importé. Cliquez sur <strong>Importer la facture</strong> pour créer l'achat.
                </p>
            </div>
            @else
            <div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-200">
                    ❌ {{ $preview['error'] ?? 'Le fichier n\'est pas valide.' }}
                </p>
            </div>
            @endif
        </div>
        @endif

        {{-- Import result --}}
        @if($showResult && $importResult)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            @if($importResult['success'])
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">Import réussi</h3>
                        <p class="text-sm text-green-600 dark:text-green-400">{{ $importResult['message'] }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">N° Achat</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $importResult['invoice_number'] }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Fournisseur</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $importResult['supplier'] }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Articles</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ $importResult['items_count'] }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Total TTC</p>
                        <p class="text-lg font-bold text-primary-600 dark:text-primary-400 mt-1">{{ number_format($importResult['total_ttc'] ?? 0, 2, ',', ' ') }} €</p>
                    </div>
                </div>

                @if(!empty($importResult['warnings']))
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                    <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-1">⚠️ Avertissements :</p>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                        @foreach($importResult['warnings'] as $warning)
                        <li>• {{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="flex gap-3">
                    <a href="{{ \App\Filament\Resources\PurchaseResource::getUrl('edit', ['record' => $importResult['purchase_id']]) }}"
                       class="fi-btn fi-btn-size-lg fi-color-custom fi-color-primary inline-flex items-center gap-1 rounded-lg px-4 py-2 text-sm font-semibold bg-primary-600 text-white hover:bg-primary-500">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Voir / modifier l'achat
                    </a>
                    <x-filament::button wire:click="resetForm" color="gray" size="lg">
                        Importer une autre facture
                    </x-filament::button>
                </div>
            </div>
            @else
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-red-800 dark:text-red-200">Échec de l'import</h3>
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $importResult['message'] }}</p>
                </div>
            </div>
            @endif
        </div>
        @endif

    </div>
</x-filament-panels::page>

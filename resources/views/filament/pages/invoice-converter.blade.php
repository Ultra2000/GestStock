<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Info banner --}}
        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-5">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 bg-indigo-100 dark:bg-indigo-800 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-indigo-900 dark:text-indigo-100 mb-1">
                        Convertisseur intelligent de factures
                    </h3>
                    <p class="text-sm text-indigo-800 dark:text-indigo-200">
                        Importez un <strong>PDF</strong>, une <strong>image</strong> (JPEG, PNG) ou un fichier <strong>Excel/CSV</strong>
                        et notre IA extraira automatiquement les données pour générer une <strong>facture Factur-X</strong> conforme EN16931.
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            ✓ PDF
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            ✓ Images (JPEG, PNG, WebP)
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            ✓ Excel / CSV
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                            → Factur-X EN16931
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Upload zone --}}
        @if(!$showPreview && !$showResult)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-sm font-bold">1</span>
                Importer votre facture
            </h2>

            <div class="space-y-4">
                {{-- Drop zone --}}
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center hover:border-indigo-400 dark:hover:border-indigo-500 transition-colors">
                    <svg class="mx-auto w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <label class="cursor-pointer">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Glissez votre fichier ici ou
                            <span class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">parcourez</span>
                        </span>
                        <input
                            type="file"
                            wire:model="uploadedFile"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.csv"
                            class="hidden"
                        />
                    </label>
                    <p class="mt-1 text-xs text-gray-500">PDF, JPEG, PNG, WebP, Excel, CSV — Max 10 Mo</p>
                </div>

                @error('uploadedFile')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror

                {{-- Loading indicator --}}
                <div wire:loading wire:target="uploadedFile" class="flex items-center gap-2 text-sm text-gray-500">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Chargement du fichier…
                </div>

                {{-- File info --}}
                @if($uploadedFile)
                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $uploadedFile->getClientOriginalName() }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($uploadedFile->getSize() / 1024, 1) }} Ko</p>
                        </div>
                    </div>
                    <button wire:click="$set('uploadedFile', null)" class="text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                @endif

                {{-- Extract button --}}
                @if($uploadedFile)
                <div class="flex justify-end">
                    <button
                        wire:click="extractData"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-wait"
                        class="inline-flex items-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="extractData">
                            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="extractData">
                            <svg class="w-5 h-5 mr-2 -ml-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="extractData">Extraire les données</span>
                        <span wire:loading wire:target="extractData">Extraction en cours…</span>
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Error display --}}
        @if($errorMessage)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-red-800 dark:text-red-200">{{ $errorMessage }}</p>
            </div>
        </div>
        @endif

        {{-- Preview / Edit form --}}
        @if($showPreview)
        <div class="space-y-6">
            {{-- AI badge --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-sm font-bold">2</span>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Vérifier et corriger les données</h2>
                </div>
                <div class="flex items-center gap-2">
                    @if($aiProvider)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                        IA: {{ $aiProvider }}
                    </span>
                    @endif
                    @if($processingTimeMs)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ number_format($processingTimeMs / 1000, 1) }}s
                    </span>
                    @endif
                </div>
            </div>

            {{-- Seller / Buyer --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Seller --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-4">Émetteur</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Raison sociale *</label>
                            <input type="text" wire:model="sellerName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            @error('sellerName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Adresse</label>
                            <input type="text" wire:model="sellerAddress" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Code postal</label>
                                <input type="text" wire:model="sellerZipCode" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Ville</label>
                                <input type="text" wire:model="sellerCity" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">SIRET</label>
                                <input type="text" wire:model="sellerSiret" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">N° TVA</label>
                                <input type="text" wire:model="sellerVatNumber" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Buyer --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-4">Destinataire</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Raison sociale *</label>
                            <input type="text" wire:model="buyerName" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            @error('buyerName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Adresse</label>
                            <input type="text" wire:model="buyerAddress" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Code postal</label>
                                <input type="text" wire:model="buyerZipCode" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Ville</label>
                                <input type="text" wire:model="buyerCity" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">SIRET</label>
                                <input type="text" wire:model="buyerSiret" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">N° TVA</label>
                                <input type="text" wire:model="buyerVatNumber" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Invoice details --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-4">Informations de la facture</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">N° Facture *</label>
                        <input type="text" wire:model="invoiceNumber" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                        @error('invoiceNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Date *</label>
                        <input type="date" wire:model="invoiceDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                        @error('invoiceDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Échéance</label>
                        <input type="date" wire:model="invoiceDueDate" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Devise</label>
                        <select wire:model="invoiceCurrency" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="EUR">EUR (€)</option>
                            <option value="USD">USD ($)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="CHF">CHF</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Notes / Mentions</label>
                    <textarea wire:model="invoiceNotes" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"></textarea>
                </div>
            </div>

            {{-- Line items --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Lignes de facture</h3>
                    <button wire:click="addLine" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Ajouter une ligne
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <th class="text-left pb-2 pr-2" style="min-width:250px">Description</th>
                                <th class="text-center pb-2 px-2" style="width:80px">Qté</th>
                                <th class="text-right pb-2 px-2" style="width:110px">P.U. HT</th>
                                <th class="text-center pb-2 px-2" style="width:80px">TVA %</th>
                                <th class="text-right pb-2 px-2" style="width:110px">Total HT</th>
                                <th class="pb-2 pl-2" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lines as $index => $line)
                            <tr class="border-t border-gray-100 dark:border-gray-700">
                                <td class="py-2 pr-2">
                                    <input type="text" wire:model="lines.{{ $index }}.description" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm py-1.5" />
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" wire:model="lines.{{ $index }}.quantity" wire:change="recalculateTotals" step="0.01" min="0" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-center py-1.5" />
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" wire:model="lines.{{ $index }}.unit_price_ht" wire:change="recalculateTotals" step="0.01" min="0" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-right py-1.5" />
                                </td>
                                <td class="py-2 px-2">
                                    <select wire:model="lines.{{ $index }}.vat_rate" wire:change="recalculateTotals" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm text-center py-1.5">
                                        <option value="20">20%</option>
                                        <option value="10">10%</option>
                                        <option value="5.5">5,5%</option>
                                        <option value="2.1">2,1%</option>
                                        <option value="0">0%</option>
                                    </select>
                                </td>
                                <td class="py-2 px-2 text-right font-medium text-gray-900 dark:text-white">
                                    {{ number_format($line['total_ht'] ?? 0, 2, ',', ' ') }} €
                                </td>
                                <td class="py-2 pl-2 text-center">
                                    <button wire:click="removeLine({{ $index }})" class="text-gray-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach

                            @if(empty($lines))
                            <tr>
                                <td colspan="6" class="py-8 text-center text-gray-400 dark:text-gray-500">
                                    Aucune ligne détectée. Cliquez « Ajouter une ligne » pour commencer.
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="mt-4 flex justify-end">
                    <div class="w-72 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Total HT</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ number_format((float)$totalHt, 2, ',', ' ') }} €</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Total TVA</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ number_format((float)$totalVat, 2, ',', ' ') }} €</span>
                        </div>
                        <div class="flex justify-between text-base pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="font-bold text-gray-900 dark:text-white">Total TTC</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ number_format((float)$totalTtc, 2, ',', ' ') }} €</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between">
                <button wire:click="resetForm" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    Recommencer
                </button>

                <button
                    wire:click="generateFacturX"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="generateFacturX">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <span wire:loading wire:target="generateFacturX">
                        <svg class="w-5 h-5 mr-2 -ml-1 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </span>
                    <span wire:loading.remove wire:target="generateFacturX">Générer le Factur-X</span>
                    <span wire:loading wire:target="generateFacturX">Génération…</span>
                </button>
            </div>
        </div>
        @endif

        {{-- Result / Download --}}
        @if($showResult)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-green-200 dark:border-green-800 p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Factur-X généré avec succès !</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Votre facture est conforme au standard Factur-X EN16931.
            </p>

            <div class="flex items-center justify-center gap-4">
                <button wire:click="downloadPdf" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Télécharger le PDF
                </button>
                <button wire:click="downloadXml" class="inline-flex items-center px-5 py-2.5 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                    Télécharger le XML
                </button>
            </div>

            <div class="mt-6">
                <button wire:click="resetForm" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    ← Convertir une autre facture
                </button>
            </div>
        </div>
        @endif

        {{-- Recent conversions history --}}
        @if(!empty($recentConversions))
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider mb-4">Conversions récentes</h3>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($recentConversions as $conv)
                <div class="flex items-center justify-between py-3">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $conv['filename'] }}</p>
                            <p class="text-xs text-gray-500">{{ $conv['created_at'] }} · {{ $conv['formatted_size'] }} · {{ ucfirst($conv['ai_provider']) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            @if($conv['status_color'] === 'success') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                            @elseif($conv['status_color'] === 'danger') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                            @elseif($conv['status_color'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300
                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                            @endif
                        ">
                            {{ $conv['status_label'] }}
                        </span>
                        @if($conv['has_output'])
                        <button wire:click="redownload({{ $conv['id'] }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300" title="Re-télécharger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</x-filament-panels::page>

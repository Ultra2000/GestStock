<x-filament-panels::page>

    {{-- ===== ZONE UPLOAD ===== --}}
    @if(!$extractedData)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-arrow-up class="w-5 h-5 text-violet-600" />
                    Importer une facture fournisseur
                </div>
            </x-slot>
            <x-slot name="description">
                L'intelligence artificielle (Claude) va lire votre document (PDF ou image) et en extraire automatiquement les lignes articles, quantités, prix et TVA.
            </x-slot>

            <form wire:submit="extract" class="space-y-6">

                <div
                    x-data="{ dragging: false }"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="dragging = false"
                    :class="dragging ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50'"
                    class="relative flex flex-col items-center justify-center gap-3 border-2 border-dashed rounded-xl p-10 transition-colors cursor-pointer"
                    onclick="document.getElementById('pdfInput').click()"
                >
                    <x-heroicon-o-document-arrow-up class="w-12 h-12 text-violet-400" />
                    <div class="text-center">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Cliquez ou déposez votre fichier ici</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PDF, JPEG, PNG, WebP · max 10 Mo</p>
                    </div>

                    @if($pdfFile)
                        <div class="flex items-center gap-2 px-4 py-2 bg-violet-100 dark:bg-violet-900/40 rounded-lg text-violet-700 dark:text-violet-300 text-sm font-medium">
                            <x-heroicon-o-document-text class="w-4 h-4" />
                            {{ $pdfFile->getClientOriginalName() }}
                        </div>
                    @endif

                    <input
                        id="pdfInput"
                        type="file"
                        wire:model="pdfFile"
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,application/pdf,image/*"
                        class="absolute inset-0 opacity-0 cursor-pointer"
                        onclick="event.stopPropagation()"
                    >
                </div>

                @error('pdfFile')
                    <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4" />{{ $message }}
                    </p>
                @enderror

                @if($errorMessage)
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-300">
                        <p class="font-semibold mb-1">Erreur d'extraction</p>
                        <p>{{ $errorMessage }}</p>
                    </div>
                @endif

                <div class="flex justify-end">
                    <x-filament::button type="submit" color="primary" icon="heroicon-o-sparkles" :disabled="!$pdfFile || $isExtracting" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="extract">Extraire avec l'IA</span>
                        <span wire:loading wire:target="extract" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Extraction en cours…
                        </span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Comment ça marche ?</x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600 font-bold text-xs">1</div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">Importez la facture</p>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">PDF ou photo de la facture fournisseur.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600 font-bold text-xs">2</div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">L'IA extrait les données</p>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">Claude lit et structure fournisseur, articles, prix, TVA.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600 font-bold text-xs">3</div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">Associez et importez</p>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">Liez chaque ligne à un produit du catalogue, puis créez le bon d'achat.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>

    @else

    {{-- ===== RÉSULTATS + MAPPING ===== --}}

    @php
        $seller   = $extractedData['seller']  ?? [];
        $invoice  = $extractedData['invoice'] ?? [];
        $totals   = $extractedData['totals']  ?? [];
        $currency = $invoice['currency'] ?? 'EUR';
        $mappedCount = $this->getMappedLinesCount();
        $totalLines  = count($linesMappings);
    @endphp

    {{-- Bande succès --}}
    <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-5 py-4 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 text-emerald-700 dark:text-emerald-300">
            <x-heroicon-o-check-circle class="w-6 h-6 flex-shrink-0" />
            <div>
                <p class="font-semibold text-sm">Extraction réussie — {{ $totalLines }} ligne(s) détectée(s)</p>
                <p class="text-xs opacity-75 mt-0.5">Associez chaque ligne à un produit de votre catalogue, puis créez le bon d'achat.</p>
            </div>
        </div>
        <x-filament::button color="gray" size="sm" icon="heroicon-o-arrow-path" wire:click="resetExtraction">
            Recommencer
        </x-filament::button>
    </div>

    {{-- Résumé facture --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 px-4 py-3">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Fournisseur détecté</p>
            <p class="font-semibold text-gray-800 dark:text-gray-200 mt-1">{{ $seller['name'] ?? '—' }}</p>
            @if(!empty($seller['siret']))<p class="text-xs text-gray-500 mt-0.5">SIRET : {{ $seller['siret'] }}</p>@endif
        </div>
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 px-4 py-3">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">N° Facture</p>
            <p class="font-semibold font-mono text-gray-800 dark:text-gray-200 mt-1">{{ $invoice['number'] ?? '—' }}</p>
            @if(!empty($invoice['date']))<p class="text-xs text-gray-500 mt-0.5">{{ \Carbon\Carbon::parse($invoice['date'])->format('d/m/Y') }}</p>@endif
        </div>
        <div class="rounded-lg bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 px-4 py-3">
            <p class="text-xs text-violet-600 dark:text-violet-400 uppercase tracking-wide font-semibold">Total TTC</p>
            <p class="font-bold text-violet-700 dark:text-violet-300 text-lg mt-1">{{ number_format($totals['total_ttc'] ?? 0, 2, ',', ' ') }} {{ $currency }}</p>
            <p class="text-xs text-violet-500 mt-0.5">HT : {{ number_format($totals['total_ht'] ?? 0, 2, ',', ' ') }} · TVA : {{ number_format($totals['total_vat'] ?? 0, 2, ',', ' ') }}</p>
        </div>
    </div>

    {{-- ===== SECTION 4b : MAPPING ===== --}}

    {{-- Remise globale détectée --}}
    @if($globalDiscountAmount !== null)
        <div class="rounded-xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-4 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-start gap-3 flex-1">
                <x-heroicon-o-tag class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        Remise globale détectée dans la facture
                    </p>
                    <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                        Une ligne de remise ({{ number_format($globalDiscountAmount, 2, ',', ' ') }} {{ $currency }}) a été exclue du tableau articles et convertie en remise globale sur le bon d'achat.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <label class="text-sm font-medium text-amber-800 dark:text-amber-200 whitespace-nowrap">Remise globale %</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    wire:model="globalDiscountPercent"
                    class="w-24 rounded-lg border border-amber-300 dark:border-amber-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-amber-500 text-center font-semibold"
                >
                <span class="text-sm font-bold text-amber-700 dark:text-amber-300">%</span>
            </div>
        </div>
    @endif

    {{-- Sélecteur fournisseur --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-building-office class="w-4 h-4 text-violet-600" />
                Fournisseur
            </div>
        </x-slot>
        <x-slot name="description">
            Associez la facture à un fournisseur existant dans votre base.
            @if(!empty($seller['name'])) L'IA a détecté : <strong>{{ $seller['name'] }}</strong>.@endif
        </x-slot>

        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fournisseur du catalogue</label>
                <select
                    wire:model="supplierId"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                >
                    <option value="">— Sélectionner un fournisseur —</option>
                    @foreach($this->suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected($supplierId === $supplier->id)>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($supplierId)
                <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 text-sm font-medium mt-5 sm:mt-0">
                    <x-heroicon-o-check-circle class="w-4 h-4" />
                    Fournisseur associé
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- Tableau de mapping des lignes --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrows-right-left class="w-4 h-4 text-violet-600" />
                    Association lignes → produits catalogue
                </div>
                <span class="text-xs font-medium px-2 py-1 rounded-full {{ $mappedCount === $totalLines && $totalLines > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                    {{ $mappedCount }} / {{ $totalLines }} associé(s)
                </span>
            </div>
        </x-slot>
        <x-slot name="description">
            Pour chaque ligne extraite, sélectionnez le produit correspondant dans votre catalogue. Le prix d'achat et la TVA sont automatiquement pré-remplis depuis votre fiche produit.
        </x-slot>

        <div class="space-y-3">
            @foreach($linesMappings as $i => $line)
                @php $isMapped = !empty($line['product_id']); @endphp
                <div class="rounded-xl border {{ $isMapped ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/40 dark:bg-emerald-900/10' : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/50' }} p-4 transition-colors">

                    {{-- Label IA --}}
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center text-xs font-bold">
                                {{ $i + 1 }}
                            </span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate" title="{{ $line['description'] }}">
                                {{ $line['description'] ?: '(sans description)' }}
                            </span>
                        </div>
                        @if($isMapped)
                            <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-500 flex-shrink-0" />
                        @else
                            <x-heroicon-o-exclamation-circle class="w-5 h-5 text-amber-400 flex-shrink-0" />
                        @endif
                    </div>

                    {{-- Sélecteur produit --}}
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                            Produit du catalogue
                        </label>
                        <select
                            wire:change="selectProduct({{ $i }}, $event.target.value)"
                            class="w-full rounded-lg border {{ $isMapped ? 'border-emerald-300 dark:border-emerald-700' : 'border-gray-300 dark:border-gray-600' }} bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                        >
                            <option value="">— Sélectionner un produit —</option>
                            @foreach($this->products as $product)
                                <option value="{{ $product->id }}" @selected((int)($line['product_id'] ?? 0) === $product->id)>
                                    {{ $product->name }}@if($product->code) ({{ $product->code }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Champs éditables --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Quantité</label>
                            <input
                                type="number"
                                step="0.001"
                                wire:model="linesMappings.{{ $i }}.quantity"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-violet-500"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">P.U. HT ({{ $currency }})</label>
                            <input
                                type="number"
                                step="0.01"
                                wire:model="linesMappings.{{ $i }}.unit_price"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-violet-500"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">Remise %</label>
                            <input
                                type="number"
                                step="0.1"
                                min="0"
                                max="100"
                                wire:model="linesMappings.{{ $i }}.discount_percent"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-violet-500"
                            >
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1 font-medium">TVA %</label>
                            <input
                                type="number"
                                step="0.1"
                                wire:model="linesMappings.{{ $i }}.vat_rate"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 text-sm px-3 py-2 focus:ring-2 focus:ring-violet-500"
                            >
                        </div>
                    </div>

                    {{-- Total calculé --}}
                    @php
                        $qty   = (float)($line['quantity'] ?? 0);
                        $pu    = (float)($line['unit_price'] ?? 0);
                        $disc  = (float)($line['discount_percent'] ?? 0);
                        $gross = $qty * $pu;
                        $lineTotal = $gross * (1 - $disc / 100);
                    @endphp
                    <div class="mt-2 flex justify-end">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Total HT : <strong class="text-gray-700 dark:text-gray-300">{{ number_format($lineTotal, 2, ',', ' ') }} {{ $currency }}</strong>
                        </span>
                    </div>

                </div>
            @endforeach
        </div>
    </x-filament::section>

    {{-- Actions finales --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="resetExtraction">
            Recommencer avec un autre fichier
        </x-filament::button>

        <div class="flex items-center gap-3">
            @if($mappedCount === 0)
                <p class="text-sm text-amber-600 dark:text-amber-400">
                    Associez au moins une ligne à un produit pour continuer.
                </p>
            @elseif(!$supplierId)
                <p class="text-sm text-amber-600 dark:text-amber-400">
                    Sélectionnez un fournisseur pour continuer.
                </p>
            @else
                <p class="text-sm text-emerald-600 dark:text-emerald-400 font-medium">
                    {{ $mappedCount }} ligne(s) prête(s) · fournisseur OK
                </p>
            @endif

            <x-filament::button
                color="primary"
                icon="heroicon-o-shopping-bag"
                :disabled="$mappedCount === 0 || !$supplierId"
                title="{{ $mappedCount === 0 ? 'Associez des produits' : (!$supplierId ? 'Choisissez un fournisseur' : 'Créer le bon d\'achat') }}"
            >
                Créer le bon d'achat
            </x-filament::button>
        </div>
    </div>

    @endif

</x-filament-panels::page>

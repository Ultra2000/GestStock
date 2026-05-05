<x-filament-panels::page>

    {{-- ===== ZONE UPLOAD ===== --}}
    @if(!$extractedData)
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-document-arrow-up class="w-5 h-5 text-violet-600" />
                    Importer un PDF de facture fournisseur
                </div>
            </x-slot>
            <x-slot name="description">
                L'intelligence artificielle (Claude) va lire votre facture PDF et en extraire automatiquement les lignes articles, quantités, prix et TVA.
            </x-slot>

            <form wire:submit="extract" class="space-y-6">

                {{-- Zone drag-and-drop / file input --}}
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
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Cliquez ou déposez votre PDF ici
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PDF uniquement · max 10 Mo</p>
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
                        accept=".pdf,application/pdf"
                        class="absolute inset-0 opacity-0 cursor-pointer"
                        onclick="event.stopPropagation()"
                    >
                </div>

                @error('pdfFile')
                    <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                        <x-heroicon-o-exclamation-circle class="w-4 h-4" />
                        {{ $message }}
                    </p>
                @enderror

                @if($errorMessage)
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-300">
                        <p class="font-semibold mb-1">Erreur d'extraction</p>
                        <p>{{ $errorMessage }}</p>
                    </div>
                @endif

                {{-- Bouton extraction --}}
                <div class="flex justify-end">
                    <x-filament::button
                        type="submit"
                        color="primary"
                        icon="heroicon-o-sparkles"
                        :disabled="!$pdfFile || $isExtracting"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="extract">
                            Extraire avec l'IA
                        </span>
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

        {{-- Info box --}}
        <x-filament::section>
            <x-slot name="heading">Comment ça marche ?</x-slot>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600 font-bold text-xs">1</div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">Importez le PDF</p>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">Chargez la facture de votre fournisseur au format PDF.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600 font-bold text-xs">2</div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">L'IA extrait les données</p>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">Claude lit et structure automatiquement : fournisseur, articles, quantités, prix, TVA.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-600 font-bold text-xs">3</div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-200">Vérifiez et importez</p>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">Associez chaque ligne à un produit de votre catalogue, puis créez le bon d'achat.</p>
                    </div>
                </div>
            </div>
        </x-filament::section>

    @else

    {{-- ===== RÉSULTATS DE L'EXTRACTION ===== --}}

        @php
            $seller  = $extractedData['seller']  ?? [];
            $invoice = $extractedData['invoice'] ?? [];
            $lines   = $extractedData['lines']   ?? [];
            $totals  = $extractedData['totals']  ?? [];
            $currency = $invoice['currency'] ?? 'EUR';
        @endphp

        {{-- Bande succès --}}
        <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-5 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 text-emerald-700 dark:text-emerald-300">
                <x-heroicon-o-check-circle class="w-6 h-6 flex-shrink-0" />
                <div>
                    <p class="font-semibold text-sm">Extraction réussie — {{ count($lines) }} ligne(s) détectée(s)</p>
                    <p class="text-xs opacity-75 mt-0.5">Vérifiez les données extraites ci-dessous avant de créer le bon d'achat.</p>
                </div>
            </div>
            <x-filament::button
                color="gray"
                size="sm"
                icon="heroicon-o-arrow-path"
                wire:click="resetExtraction"
            >
                Recommencer
            </x-filament::button>
        </div>

        {{-- Cards info fournisseur + facture --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Fournisseur extrait --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-building-office class="w-4 h-4 text-violet-600" />
                        Fournisseur détecté
                    </div>
                </x-slot>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Nom</dt>
                        <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ $seller['name'] ?? '—' }}</dd>
                    </div>
                    @if(!empty($seller['address']))
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Adresse</dt>
                            <dd class="text-right text-gray-700 dark:text-gray-300">{{ $seller['address'] }}@if(!empty($seller['zip_code'])) {{ $seller['zip_code'] }}@endif @if(!empty($seller['city'])) {{ $seller['city'] }}@endif</dd>
                        </div>
                    @endif
                    @if(!empty($seller['siret']))
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">SIRET</dt>
                            <dd class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $seller['siret'] }}</dd>
                        </div>
                    @endif
                    @if(!empty($seller['vat_number']))
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">N° TVA</dt>
                            <dd class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $seller['vat_number'] }}</dd>
                        </div>
                    @endif
                    @if(!empty($seller['phone']))
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Téléphone</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $seller['phone'] }}</dd>
                        </div>
                    @endif
                    @if(!empty($seller['email']))
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $seller['email'] }}</dd>
                        </div>
                    @endif
                </dl>
            </x-filament::section>

            {{-- Infos facture --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-violet-600" />
                        Informations facture
                    </div>
                </x-slot>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">N° Facture</dt>
                        <dd class="font-semibold font-mono text-gray-800 dark:text-gray-200">{{ $invoice['number'] ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Date</dt>
                        <dd class="text-gray-700 dark:text-gray-300">
                            @if(!empty($invoice['date']))
                                {{ \Carbon\Carbon::parse($invoice['date'])->format('d/m/Y') }}
                            @else —
                            @endif
                        </dd>
                    </div>
                    @if(!empty($invoice['due_date']))
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Échéance</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($invoice['due_date'])->format('d/m/Y') }}</dd>
                        </div>
                    @endif
                    @if(!empty($invoice['payment_method']))
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Paiement</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $invoice['payment_method'] }}</dd>
                        </div>
                    @endif
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-2 mt-2 space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Total HT</dt>
                            <dd class="font-medium text-gray-800 dark:text-gray-200">{{ number_format($totals['total_ht'] ?? 0, 2, ',', ' ') }} {{ $currency }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">TVA</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ number_format($totals['total_vat'] ?? 0, 2, ',', ' ') }} {{ $currency }}</dd>
                        </div>
                        <div class="flex justify-between font-semibold text-base">
                            <dt class="text-violet-700 dark:text-violet-300">Total TTC</dt>
                            <dd class="text-violet-700 dark:text-violet-300">{{ number_format($totals['total_ttc'] ?? 0, 2, ',', ' ') }} {{ $currency }}</dd>
                        </div>
                    </div>
                </dl>
            </x-filament::section>
        </div>

        {{-- Tableau des lignes extraites --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-4 h-4 text-violet-600" />
                    Lignes extraites ({{ count($lines) }})
                </div>
            </x-slot>
            <x-slot name="description">
                Ces lignes ont été lues automatiquement depuis la facture. L'étape suivante vous permettra d'associer chaque ligne à un produit de votre catalogue.
            </x-slot>

            @if(count($lines) > 0)
                <div class="overflow-x-auto -mx-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800 text-left">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">#</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Désignation</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Qté</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">P.U. HT</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">TVA</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">Total HT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($lines as $i => $line)
                                <tr class="{{ $i % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50 dark:bg-gray-800/50' }}">
                                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500 text-xs">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium text-gray-800 dark:text-gray-200">
                                            {{ $line['description'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ rtrim(rtrim(number_format((float)($line['quantity'] ?? 0), 3, ',', ' '), '0'), ',') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                        {{ number_format((float)($line['unit_price_ht'] ?? 0), 2, ',', ' ') }} {{ $currency }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">
                                        {{ rtrim(rtrim(number_format((float)($line['vat_rate'] ?? 0), 2, ',', ' '), '0'), ',') }}%
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-gray-200">
                                        {{ number_format((float)($line['total_ht'] ?? 0), 2, ',', ' ') }} {{ $currency }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-violet-50 dark:bg-violet-900/20 border-t-2 border-violet-200 dark:border-violet-800">
                                <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-violet-700 dark:text-violet-300">Total HT</td>
                                <td class="px-4 py-3 text-right font-bold text-violet-700 dark:text-violet-300">
                                    {{ number_format((float)($totals['total_ht'] ?? 0), 2, ',', ' ') }} {{ $currency }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center gap-3 py-10 text-gray-400 dark:text-gray-600">
                    <x-heroicon-o-document-magnifying-glass class="w-10 h-10" />
                    <p class="text-sm">Aucune ligne d'article détectée dans ce PDF.</p>
                </div>
            @endif
        </x-filament::section>

        {{-- Actions --}}
        <div class="flex justify-between items-center">
            <x-filament::button
                color="gray"
                icon="heroicon-o-arrow-path"
                wire:click="resetExtraction"
            >
                Recommencer avec un autre PDF
            </x-filament::button>

            @if(count($lines) > 0)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Prochaine étape : associer les lignes à vos produits
                    </span>
                    <x-filament::button
                        color="primary"
                        icon="heroicon-o-arrow-right"
                        disabled
                        title="Disponible dans la prochaine version"
                    >
                        Créer le bon d'achat
                    </x-filament::button>
                </div>
            @endif
        </div>

    @endif

</x-filament-panels::page>

<x-filament-panels::page>
    @php
        $invoices      = $this->getInvoices();
        $failedInvoice = $this->getFailedInvoice();
    @endphp

    <div class="space-y-4">

        {{-- Bannière échec de paiement --}}
        @if ($failedInvoice)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
                    <div>
                        <p class="font-semibold text-red-700 dark:text-red-400">Échec de paiement — Facture {{ $failedInvoice['number'] }}</p>
                        <p class="text-sm text-red-600 dark:text-red-500 mt-0.5">
                            Le prélèvement de <strong>{{ $failedInvoice['amount'] }}</strong> n'a pas pu être effectué.
                            Cliquez sur le bouton pour régler cette facture et maintenir votre accès.
                        </p>
                    </div>
                </div>
                <a href="{{ $failedInvoice['hosted_url'] }}" target="_blank"
                   class="flex-shrink-0 inline-flex items-center gap-2 bg-red-600 hover:bg-red-500 text-white font-bold px-5 py-2.5 rounded-xl transition text-sm">
                    <x-heroicon-o-arrow-path class="w-4 h-4" />
                    Relancer le paiement
                </a>
            </div>
        @endif

        @if (empty($invoices))
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-10 text-center">
                <x-heroicon-o-credit-card class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400 text-sm">Aucune facture disponible pour le moment.</p>
                <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Les factures apparaîtront ici dès votre premier paiement.</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">N° Facture</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hidden md:table-cell">Période</th>
                            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Montant</th>
                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Facture</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach ($invoices as $invoice)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300 font-mono text-xs">
                                    {{ $invoice['number'] }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-700 dark:text-gray-300">
                                    {{ $invoice['date'] }}
                                </td>
                                <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 hidden md:table-cell text-xs">
                                    {{ $invoice['period'] }}
                                </td>
                                <td class="px-5 py-3.5 text-right font-semibold text-gray-900 dark:text-white">
                                    {{ $invoice['amount'] }}
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($invoice['status'] === 'paid')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            <x-heroicon-o-check-circle class="w-3 h-3" /> Payée
                                        </span>
                                    @elseif ($invoice['status'] === 'open')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                            <x-heroicon-o-clock class="w-3 h-3" /> En attente
                                        </span>
                                    @elseif ($invoice['status'] === 'void')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                            Annulée
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                            {{ $invoice['status'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($invoice['pdf_url'])
                                        <a href="{{ $invoice['pdf_url'] }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-indigo-500 hover:text-indigo-400 text-xs font-medium transition">
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> PDF
                                        </a>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 text-center">
                Les 24 dernières factures sont affichées.
                Pour votre historique complet, contactez <a href="mailto:contact@frecorp.fr" class="underline">contact@frecorp.fr</a>.
            </p>
        @endif

    </div>
</x-filament-panels::page>

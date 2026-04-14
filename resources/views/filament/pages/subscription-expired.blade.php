<x-filament-panels::page>
    @php $company = $this->getCompany(); @endphp

    <div class="min-h-[60vh] flex items-center justify-center">
        <div class="max-w-lg w-full text-center space-y-6">

            {{-- Icône --}}
            <div class="flex justify-center">
                <div class="w-20 h-20 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <x-heroicon-o-lock-closed class="w-10 h-10 text-amber-600 dark:text-amber-400" />
                </div>
            </div>

            {{-- Titre --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Votre période d'essai est terminée
                </h1>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                    Les 6 mois gratuits pour <strong>{{ $company->name }}</strong> sont écoulés.
                    Pour continuer à utiliser FRECORP, choisissez un abonnement.
                </p>
            </div>

            {{-- Plan Standard --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-indigo-500 p-6 shadow-lg text-left space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-indigo-500">Plan Standard</span>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            30€<span class="text-base font-normal text-gray-400">/mois</span>
                        </div>
                    </div>
                    <span class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-xs font-bold px-3 py-1 rounded-full">
                        Recommandé
                    </span>
                </div>

                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Tous les modules inclus</li>
                    <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Utilisateurs illimités</li>
                    <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Jusqu'à 5 entreprises</li>
                    <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Support email prioritaire</li>
                    <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Mises à jour incluses</li>
                </ul>

                {{-- TODO: Remplacer ce lien par le lien Stripe Checkout --}}
                <a
                    href="mailto:contact@frecorp.fr?subject=Abonnement Standard - {{ $company->name }}"
                    class="block w-full text-center bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-xl transition"
                >
                    S'abonner maintenant
                </a>
            </div>

            {{-- Contact --}}
            <p class="text-xs text-gray-400">
                Une question ? Contactez-nous à
                <a href="mailto:contact@frecorp.fr" class="text-indigo-400 hover:underline">contact@frecorp.fr</a>
            </p>

        </div>
    </div>
</x-filament-panels::page>

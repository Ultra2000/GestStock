<x-filament-panels::page>
    @php $company = $this->getCompany(); @endphp

    <div class="min-h-[60vh] flex items-center justify-center">
        <div class="max-w-2xl w-full text-center space-y-6">

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
                    Choisissez un plan pour continuer à utiliser FRECORP.
                </p>
            </div>

            {{-- Plans --}}
            <div class="grid md:grid-cols-2 gap-4 text-left">

                {{-- Mensuel --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Mensuel</span>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            30€<span class="text-base font-normal text-gray-400">/mois</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Sans engagement, résiliable à tout moment</p>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Tous les modules inclus</li>
                        <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Utilisateurs illimités</li>
                        <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Support email prioritaire</li>
                    </ul>
                    <form method="POST" action="{{ route('stripe.checkout') }}">
                        @csrf
                        <input type="hidden" name="plan" value="monthly">
                        <input type="hidden" name="company_slug" value="{{ $company->slug }}">
                        <button type="submit"
                            class="block w-full text-center bg-gray-800 hover:bg-gray-700 text-white font-bold py-3 rounded-xl transition">
                            S'abonner — 30€/mois
                        </button>
                    </form>
                </div>

                {{-- Annuel --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border-2 border-indigo-500 p-6 space-y-4 relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-xs font-bold px-4 py-1 rounded-full">
                        2 MOIS OFFERTS
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-indigo-400">Annuel</span>
                        <div class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            300€<span class="text-base font-normal text-gray-400">/an</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Soit 25€/mois — économisez 60€ par an</p>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Tout le plan mensuel</li>
                        <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> 2 mois offerts</li>
                        <li class="flex items-center gap-2"><x-heroicon-o-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" /> Facturation annuelle unique</li>
                    </ul>
                    <form method="POST" action="{{ route('stripe.checkout') }}">
                        @csrf
                        <input type="hidden" name="plan" value="yearly">
                        <input type="hidden" name="company_slug" value="{{ $company->slug }}">
                        <button type="submit"
                            class="block w-full text-center bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 rounded-xl transition">
                            S'abonner — 300€/an
                        </button>
                    </form>
                </div>

            </div>

            {{-- Contact --}}
            <p class="text-xs text-gray-400">
                Une question ? <a href="mailto:contact@frecorp.fr" class="text-indigo-400 hover:underline">contact@frecorp.fr</a>
            </p>

        </div>
    </div>
</x-filament-panels::page>

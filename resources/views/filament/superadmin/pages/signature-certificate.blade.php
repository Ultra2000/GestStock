<x-filament-panels::page>
    @php $info = $this->getCertificateInfo(); @endphp

    <div class="space-y-6">

        {{-- Current certificate status --}}
        <x-filament::section>
            <x-slot name="heading">Certificat actuel</x-slot>

            @if ($info)
                <div class="flex items-start gap-4">
                    <div class="rounded-full bg-success-100 p-3 text-success-600">
                        <x-heroicon-o-shield-check class="h-6 w-6" />
                    </div>
                    <div class="space-y-1">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $info['subject'] }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $info['self_signed'] ? 'Certificat auto-signé' : 'Signé par CA' }}
                            &mdash; valide jusqu'au <strong>{{ $info['valid_to'] }}</strong>
                        </p>
                        <p class="text-xs text-gray-400">
                            Stocké dans <code>storage/app/signature/certificate.pem</code>
                        </p>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3 text-warning-600">
                    <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                    <span>Aucun certificat configuré. Générez un certificat pour activer la signature numérique.</span>
                </div>
            @endif
        </x-filament::section>

        {{-- Generate new certificate form --}}
        <x-filament::section>
            <x-slot name="heading">Générer un nouveau certificat auto-signé</x-slot>
            <x-slot name="description">
                Le certificat sera utilisé pour signer numériquement les PDFs Factur-X.
                Un certificat auto-signé est suffisant pour la conformité PAF (piste d'audit fiable).
            </x-slot>

            <form wire:submit="generate">
                {{ $this->form }}
            </form>
        </x-filament::section>

        {{-- Informational note --}}
        <x-filament::section>
            <x-slot name="heading">À savoir</x-slot>
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <ul>
                    <li>La signature numérique est <strong>optionnelle</strong> pour la conformité française mais renforce l'authenticité.</li>
                    <li>Le format Factur-X (PDF/A-3 + XML CII) constitue déjà une <strong>piste d'audit fiable (PAF)</strong> au sens de l'article 289 du CGI.</li>
                    <li>Pour activer la signature sur les factures d'une entreprise, allez dans <strong>Entreprises → Réglages → Signature numérique</strong>.</li>
                    <li>Un certificat auto-signé suffit pour l'usage interne. Pour une signature reconnue par des tiers, utilisez un certificat d'une CA accréditée (ex. CertEurope, Certigna).</li>
                </ul>
            </div>
        </x-filament::section>

    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Sélection du site --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                <x-heroicon-o-building-office class="w-5 h-5 inline-block mr-2" />
                Sélectionner le site
            </h3>

            @if(count($warehouses) === 0)
                <div class="text-center py-8">
                    <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto text-warning-500" />
                    <p class="mt-4 text-gray-600 dark:text-gray-400">
                        Aucun site n'a la vérification QR Code activée.
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-500">
                        Activez "Scan QR requis" dans les paramètres d'un entrepôt.
                    </p>
                </div>
            @else
                <select wire:model.live="warehouseId" wire:change="selectWarehouse" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse['id'] }}">{{ $warehouse['name'] }} ({{ $warehouse['city'] ?? 'Non localisé' }})</option>
                    @endforeach
                </select>
            @endif
        </div>

        @if($selectedWarehouse && $currentToken)
            {{-- Affichage du QR Code --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $selectedWarehouse->name }}</h2>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">Scannez ce QR Code pour pointer</p>

                    {{-- QR Code --}}
                    <div class="inline-block p-6 bg-white rounded-2xl shadow-lg" wire:poll.5s="refreshToken">
                        <div id="qrcode" class="mx-auto" style="width: 300px; height: 300px;"></div>
                    </div>

                    {{-- Timer d'expiration --}}
                    <div class="mt-6" x-data="{ 
                        secondsLeft: {{ $this->getExpiresInSeconds() }},
                        interval: null,
                        init() {
                            this.interval = setInterval(() => {
                                this.secondsLeft--;
                                if (this.secondsLeft <= 0) {
                                    this.secondsLeft = 0;
                                }
                            }, 1000);
                        },
                        formatTime() {
                            const minutes = Math.floor(this.secondsLeft / 60);
                            const seconds = this.secondsLeft % 60;
                            return `${minutes}:${seconds.toString().padStart(2, '0')}`;
                        },
                        getColor() {
                            if (this.secondsLeft <= 30) return 'text-danger-600';
                            if (this.secondsLeft <= 60) return 'text-warning-600';
                            return 'text-success-600';
                        }
                    }" x-init="init()" @refresh-token.window="secondsLeft = {{ $this->getExpiresInSeconds() }}">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Expire dans</p>
                        <p class="text-3xl font-bold" :class="getColor()" x-text="formatTime()"></p>
                    </div>

                    {{-- Bouton de rafraîchissement manuel --}}
                    <button wire:click="refreshToken" class="mt-4 px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors">
                        <x-heroicon-o-arrow-path class="w-4 h-4 inline-block mr-2" />
                        Générer un nouveau QR
                    </button>
                </div>
            </div>

            {{-- Informations --}}
            <div class="bg-blue-50 dark:bg-blue-950 rounded-xl border border-blue-200 dark:border-blue-800 p-4">
                <div class="flex gap-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <p class="font-medium">Instructions :</p>
                        <ul class="mt-1 list-disc list-inside space-y-1">
                            <li>Affichez cette page sur un écran visible par les employés</li>
                            <li>Le QR Code se renouvelle automatiquement toutes les {{ $tokenValidity }} minutes</li>
                            <li>Chaque QR Code ne peut être utilisé qu'une seule fois</li>
                            <li>Les employés doivent scanner ce code avec leur téléphone lors du pointage</li>
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        document.addEventListener('livewire:navigated', generateQR);
        document.addEventListener('livewire:init', generateQR);
        
        Livewire.hook('morph.updated', ({ el, component }) => {
            generateQR();
        });

        function generateQR() {
            const qrContent = @json($qrContent);
            const container = document.getElementById('qrcode');
            
            if (container && qrContent) {
                container.innerHTML = '';
                QRCode.toCanvas(qrContent, { 
                    width: 300,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#ffffff'
                    }
                }, function(error, canvas) {
                    if (!error) {
                        container.appendChild(canvas);
                    }
                });
            }
        }
    </script>
    @endpush
</x-filament-panels::page>

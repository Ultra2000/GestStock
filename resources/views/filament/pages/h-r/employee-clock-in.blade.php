<x-filament-panels::page>
    @if(!$employee)
        <div class="p-6 bg-danger-50 dark:bg-danger-950 rounded-xl border border-danger-200 dark:border-danger-800">
            <div class="flex items-center gap-4">
                <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-danger-500" />
                <div>
                    <h3 class="text-lg font-semibold text-danger-700 dark:text-danger-300">Aucun profil employé</h3>
                    <p class="text-danger-600 dark:text-danger-400">Votre compte utilisateur n'est pas associé à un profil employé. Contactez votre administrateur.</p>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-6">
            {{-- Statut actuel --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        @if($employee->photo)
                            <img src="{{ Storage::url($employee->photo) }}" alt="{{ $employee->full_name }}" class="w-16 h-16 rounded-full object-cover">
                        @else
                            <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                <span class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $employee->full_name }}</h2>
                            <p class="text-gray-500 dark:text-gray-400">{{ $employee->position }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ now()->format('l d F Y') }}</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white" x-data x-text="new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'})" x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'}), 1000)"></p>
                    </div>
                </div>

                {{-- Badge de statut --}}
                <div class="mt-4 flex items-center gap-2">
                    @switch($currentStatus)
                        @case('not_clocked_in')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                Non pointé
                            </span>
                            @break
                        @case('clocked_in')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300">
                                <span class="w-2 h-2 rounded-full bg-success-500 animate-pulse"></span>
                                En service depuis {{ $clockInTime }}
                            </span>
                            @break
                        @case('completed')
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-info-100 text-info-700 dark:bg-info-900 dark:text-info-300">
                                <span class="w-2 h-2 rounded-full bg-info-500"></span>
                                Journée terminée ({{ $hoursWorked }}h travaillées)
                            </span>
                            @break
                    @endswitch
                </div>
            </div>

            {{-- Sélection du site --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <x-heroicon-o-building-office class="w-5 h-5 inline-block mr-2" />
                    Site de travail
                </h3>

                <select wire:model.live="warehouseId" wire:change="selectWarehouse" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                    <option value="">-- Sélectionner un site --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse['id'] }}">{{ $warehouse['name'] }} ({{ $warehouse['city'] ?? 'Non localisé' }})</option>
                    @endforeach
                </select>

                @if($selectedWarehouse)
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($selectedWarehouse->requires_gps_check)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                <x-heroicon-m-map-pin class="w-3 h-3" />
                                Vérification GPS
                            </span>
                        @endif
                        @if($selectedWarehouse->requires_qr_check)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                                <x-heroicon-m-qr-code class="w-3 h-3" />
                                Scan QR requis
                            </span>
                        @endif
                        @if(!$selectedWarehouse->requires_gps_check && !$selectedWarehouse->requires_qr_check)
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                <x-heroicon-m-check class="w-3 h-3" />
                                Pointage libre
                            </span>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Zone de pointage --}}
            @if($selectedWarehouse && $currentStatus !== 'completed')
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    @if($step === 'ready')
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            @if($currentStatus === 'not_clocked_in')
                                <button wire:click="startClockIn" class="flex-1 sm:flex-none px-8 py-4 bg-success-600 hover:bg-success-700 text-white rounded-xl font-bold text-lg shadow-lg transition-all flex items-center justify-center gap-3">
                                    <x-heroicon-o-arrow-right-start-on-rectangle class="w-6 h-6" />
                                    Pointer l'entrée
                                </button>
                            @endif

                            @if($currentStatus === 'clocked_in')
                                <button wire:click="startClockOut" class="flex-1 sm:flex-none px-8 py-4 bg-warning-600 hover:bg-warning-700 text-white rounded-xl font-bold text-lg shadow-lg transition-all flex items-center justify-center gap-3">
                                    <x-heroicon-o-arrow-right-end-on-rectangle class="w-6 h-6" />
                                    Pointer la sortie
                                </button>
                            @endif
                        </div>

                    @elseif($step === 'check_gps')
                        <div class="text-center py-8" x-data x-init="
                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        $wire.dispatch('gps-position-received', {
                                            latitude: position.coords.latitude,
                                            longitude: position.coords.longitude,
                                            accuracy: Math.round(position.coords.accuracy)
                                        });
                                    },
                                    (error) => {
                                        let message = 'Erreur de géolocalisation';
                                        switch(error.code) {
                                            case error.PERMISSION_DENIED:
                                                message = 'Accès à la géolocalisation refusé';
                                                break;
                                            case error.POSITION_UNAVAILABLE:
                                                message = 'Position non disponible';
                                                break;
                                            case error.TIMEOUT:
                                                message = 'Délai dépassé';
                                                break;
                                        }
                                        $wire.dispatch('gps-error', { error: message });
                                    },
                                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                                );
                            } else {
                                $wire.dispatch('gps-error', { error: 'Géolocalisation non supportée par ce navigateur' });
                            }
                        ">
                            @if($gpsLoading)
                                <x-heroicon-o-map-pin class="w-16 h-16 mx-auto text-primary-500 animate-bounce" />
                                <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Vérification de votre position...</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Veuillez autoriser la géolocalisation</p>
                            @endif

                            @if($gpsError)
                                <x-heroicon-o-x-circle class="w-16 h-16 mx-auto text-danger-500" />
                                <p class="mt-4 text-lg font-medium text-danger-600 dark:text-danger-400">{{ $gpsError }}</p>
                                <button wire:click="cancelAction" class="mt-4 px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                                    Annuler
                                </button>
                            @endif
                        </div>

                    @elseif($step === 'scan_qr')
                        <div class="text-center py-8">
                            <div x-data="qrScanner()" x-init="init()" class="max-w-md mx-auto">
                                <div class="relative aspect-square bg-black rounded-xl overflow-hidden">
                                    <video x-ref="video" class="w-full h-full object-cover"></video>
                                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                        <div class="w-48 h-48 border-2 border-white/50 rounded-lg"></div>
                                    </div>
                                </div>
                                <p class="mt-4 text-gray-600 dark:text-gray-400">Scannez le QR Code affiché sur le site</p>
                                
                                @if($qrError)
                                    <p class="mt-2 text-danger-600 dark:text-danger-400">{{ $qrError }}</p>
                                @endif

                                <button wire:click="cancelAction" class="mt-4 px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                                    Annuler
                                </button>
                            </div>
                        </div>

                    @elseif($step === 'processing')
                        <div class="text-center py-8">
                            <x-heroicon-o-arrow-path class="w-16 h-16 mx-auto text-primary-500 animate-spin" />
                            <p class="mt-4 text-lg font-medium text-gray-700 dark:text-gray-300">Enregistrement en cours...</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Historique du jour --}}
            @if($clockInTime || $clockOutTime)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        <x-heroicon-o-clock class="w-5 h-5 inline-block mr-2" />
                        Historique du jour
                    </h3>

                    <div class="flex items-center gap-8">
                        @if($clockInTime)
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-success-100 dark:bg-success-900 flex items-center justify-center">
                                    <x-heroicon-o-arrow-right-start-on-rectangle class="w-5 h-5 text-success-600 dark:text-success-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Entrée</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $clockInTime }}</p>
                                </div>
                            </div>
                        @endif

                        @if($clockOutTime)
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-warning-100 dark:bg-warning-900 flex items-center justify-center">
                                    <x-heroicon-o-arrow-right-end-on-rectangle class="w-5 h-5 text-warning-600 dark:text-warning-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Sortie</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $clockOutTime }}</p>
                                </div>
                            </div>
                        @endif

                        @if($hoursWorked)
                            <div class="flex items-center gap-3 ml-auto">
                                <div class="w-10 h-10 rounded-full bg-info-100 dark:bg-info-900 flex items-center justify-center">
                                    <x-heroicon-o-calculator class="w-5 h-5 text-info-600 dark:text-info-400" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Heures travaillées</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $hoursWorked }}h</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

    @push('scripts')
    <script src="https://unpkg.com/@aspect-build/qr-scanner@0.1.0/dist/qr-scanner.min.js"></script>
    <script>
        function qrScanner() {
            return {
                scanner: null,
                init() {
                    const video = this.$refs.video;
                    
                    if (typeof QrScanner === 'undefined') {
                        // Fallback si la librairie n'est pas chargée
                        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                            .then(stream => {
                                video.srcObject = stream;
                                video.play();
                            })
                            .catch(err => {
                                Livewire.dispatch('qr-error', { error: 'Impossible d\'accéder à la caméra' });
                            });
                        return;
                    }

                    this.scanner = new QrScanner(
                        video,
                        result => {
                            this.scanner.stop();
                            Livewire.dispatch('qr-scanned', { data: result.data });
                        },
                        {
                            preferredCamera: 'environment',
                            highlightScanRegion: true,
                            highlightCodeOutline: true,
                        }
                    );
                    this.scanner.start().catch(err => {
                        Livewire.dispatch('qr-error', { error: 'Impossible d\'accéder à la caméra: ' + err.message });
                    });
                },
                destroy() {
                    if (this.scanner) {
                        this.scanner.stop();
                        this.scanner.destroy();
                    }
                }
            }
        }
    </script>
    @endpush
</x-filament-panels::page>

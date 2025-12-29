<x-filament-panels::page>
    <div class="max-w-4xl mx-auto">
        {{-- Progress bar --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                @php
                    $steps = [
                        1 => 'Bienvenue',
                        2 => 'Créer compte',
                        3 => 'Identifiants',
                        4 => 'Vérification',
                        5 => 'Terminé',
                    ];
                @endphp
                
                @foreach($steps as $step => $label)
                    <div class="flex items-center {{ $step < count($steps) ? 'flex-1' : '' }}">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 
                            {{ $currentStep > $step 
                                ? 'bg-primary-600 border-primary-600 text-white' 
                                : ($currentStep === $step 
                                    ? 'border-primary-600 text-primary-600 bg-primary-50 dark:bg-primary-900/30' 
                                    : 'border-gray-300 dark:border-gray-600 text-gray-400') 
                            }}">
                            @if($currentStep > $step)
                                <x-heroicon-s-check class="w-5 h-5" />
                            @else
                                {{ $step }}
                            @endif
                        </div>
                        <span class="ml-2 text-sm font-medium hidden sm:block
                            {{ $currentStep >= $step ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">
                            {{ $label }}
                        </span>
                        
                        @if($step < count($steps))
                            <div class="flex-1 h-0.5 mx-4 {{ $currentStep > $step ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Form content --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-6 md:p-8">
            {{ $this->form }}
        </div>

        {{-- Navigation buttons --}}
        <div class="flex justify-between mt-6">
            <div>
                @if($currentStep > 1 && $currentStep < 5)
                    <x-filament::button 
                        color="gray" 
                        wire:click="previousStep"
                        icon="heroicon-o-arrow-left"
                    >
                        Précédent
                    </x-filament::button>
                @endif
            </div>

            <div class="flex gap-3">
                @if($currentStep === 1)
                    <x-filament::button wire:click="nextStep" icon="heroicon-o-arrow-right" icon-position="after">
                        Commencer
                    </x-filament::button>
                @elseif($currentStep === 2)
                    <x-filament::button wire:click="nextStep" icon="heroicon-o-arrow-right" icon-position="after">
                        J'ai créé mon compte technique
                    </x-filament::button>
                @elseif($currentStep === 3)
                    <x-filament::button wire:click="nextStep" icon="heroicon-o-arrow-right" icon-position="after">
                        Continuer
                    </x-filament::button>
                @elseif($currentStep === 4)
                    @if(!$testPassed)
                        <x-filament::button 
                            wire:click="testConnection" 
                            wire:loading.attr="disabled"
                            color="gray"
                            icon="heroicon-o-signal"
                        >
                            <span wire:loading.remove wire:target="testConnection">Tester la connexion</span>
                            <span wire:loading wire:target="testConnection">Test en cours...</span>
                        </x-filament::button>
                    @endif
                    
                    @if($testPassed)
                        <x-filament::button 
                            wire:click="finish" 
                            color="success"
                            icon="heroicon-o-check"
                        >
                            Terminer la configuration
                        </x-filament::button>
                    @endif
                @elseif($currentStep === 5)
                    <x-filament::button 
                        wire:click="goToSales" 
                        icon="heroicon-o-document-text"
                    >
                        Aller aux ventes
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    <x-filament-panels::form wire:submit="send">
        {{ $this->form }}

        <div class="flex justify-end mt-4">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane" color="primary">
                Envoyer
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>

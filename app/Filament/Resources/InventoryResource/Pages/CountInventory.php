<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use App\Models\Inventory;
use App\Models\InventoryItem;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;

class CountInventory extends Page
{
    protected static string $resource = InventoryResource::class;

    protected static string $view = 'filament.resources.inventory-resource.pages.count-inventory';

    public ?Inventory $record = null;

    public string $search = '';
    public string $filter = 'all';
    public array $counts = [];

    public function mount(int | string $record): void
    {
        $this->record = Inventory::with(['items.product', 'items.location', 'warehouse'])
            ->findOrFail($record);

        if ($this->record->status !== 'in_progress') {
            Notification::make()
                ->title('Cet inventaire n\'est pas en cours de comptage')
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
            return;
        }

        foreach ($this->record->items as $item) {
            $this->counts[$item->id] = $item->quantity_counted;
        }
    }

    public function getTitle(): string | Htmlable
    {
        return 'Comptage - ' . $this->record->name;
    }

    #[Computed]
    public function filteredItems()
    {
        $query = $this->record->items()->with(['product', 'location']);

        if ($this->search) {
            $query->whereHas('product', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('code', 'like', '%' . $this->search . '%');
            });
        }

        switch ($this->filter) {
            case 'pending':
                $query->where('is_counted', false);
                break;
            case 'counted':
                $query->where('is_counted', true);
                break;
            case 'discrepancy':
                $query->where('is_counted', true)
                    ->whereRaw('quantity_counted != quantity_expected');
                break;
        }

        return $query->orderBy('id')->get();
    }

    public function countItem(int $itemId): void
    {
        $item = InventoryItem::find($itemId);
        $quantity = $this->counts[$itemId] ?? 0;

        if ($item && $quantity !== null) {
            $item->count((float) $quantity);

            Notification::make()
                ->title('Comptage enregistré')
                ->body($item->product->name . ': ' . $quantity)
                ->success()
                ->duration(2000)
                ->send();
        }
    }

    public function resetItem(int $itemId): void
    {
        $item = InventoryItem::find($itemId);
        
        if ($item) {
            $item->reset();
            $this->counts[$itemId] = null;

            Notification::make()
                ->title('Comptage réinitialisé')
                ->success()
                ->send();
        }
    }

    public function copyExpected(int $itemId): void
    {
        $item = InventoryItem::find($itemId);
        
        if ($item) {
            $this->counts[$itemId] = $item->quantity_expected;
        }
    }

    public function submitForValidation(): void
    {
        try {
            $this->record->submitForValidation();

            Notification::make()
                ->title('Inventaire soumis pour validation')
                ->success()
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Retour')
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('view', ['record' => $this->record]))
                ->color('gray'),
        ];
    }
}

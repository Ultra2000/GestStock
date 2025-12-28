<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use App\Models\StockTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

class ReceiveStockTransfer extends Page
{
    use InteractsWithRecord;

    protected static string $resource = StockTransferResource::class;

    protected static string $view = 'filament.resources.stock-transfer-resource.pages.receive-stock-transfer';

    public array $quantities = [];

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        if (!$this->record->canBeReceived()) {
            Notification::make()
                ->title('Ce transfert ne peut pas être réceptionné')
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
            return;
        }

        // Initialize quantities with pending quantities
        foreach ($this->record->items as $item) {
            $this->quantities[$item->id] = $item->quantity_shipped - $item->quantity_received;
        }
    }

    public function getTitle(): string | Htmlable
    {
        return 'Réception du transfert ' . $this->record->reference;
    }

    public function receive(): void
    {
        try {
            $this->record->receive($this->quantities);

            Notification::make()
                ->title('Transfert réceptionné')
                ->body('Le stock a été ajouté à l\'entrepôt destination.')
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

    public function receiveAll(): void
    {
        foreach ($this->record->items as $item) {
            $this->quantities[$item->id] = $item->quantity_shipped - $item->quantity_received;
        }

        $this->receive();
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

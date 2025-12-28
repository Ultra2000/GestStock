<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarehousesRelationManager extends RelationManager
{
    protected static string $relationship = 'warehouses';

    protected static ?string $title = 'Stock par entrepôt';

    protected static ?string $modelLabel = 'entrepôt';
    protected static ?string $pluralModelLabel = 'entrepôts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantité')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Entrepôt')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Emplacement')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pivot.quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state, $record) => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('pivot.updated_at')
                    ->label('Dernière mise à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Ajouter à un entrepôt')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Entrepôt'),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité initiale')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Ajuster')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Forms\Components\Radio::make('type')
                            ->label('Type d\'ajustement')
                            ->options([
                                'add' => 'Ajouter au stock',
                                'remove' => 'Retirer du stock',
                                'set' => 'Définir le stock',
                            ])
                            ->default('add')
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(1),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data, $record) {
                        $productId = $this->ownerRecord->id;
                        $warehouseId = $record->id;
                        $currentQty = $record->pivot->quantity ?? 0;
                        
                        $newQty = match ($data['type']) {
                            'add' => $currentQty + $data['quantity'],
                            'remove' => max(0, $currentQty - $data['quantity']),
                            'set' => $data['quantity'],
                            default => $currentQty,
                        };

                        // Mettre à jour le pivot
                        $this->ownerRecord->warehouses()->updateExistingPivot($warehouseId, [
                            'quantity' => $newQty,
                        ]);

                        // Créer un mouvement de stock si le modèle existe
                        if (class_exists(\App\Models\StockMovement::class)) {
                            \App\Models\StockMovement::create([
                                'product_id' => $productId,
                                'warehouse_id' => $warehouseId,
                                'company_id' => $this->ownerRecord->company_id,
                                'type' => $data['type'] === 'remove' ? 'out' : 'in',
                                'quantity' => abs($newQty - $currentQty),
                                'reason' => $data['reason'] ?? 'Ajustement manuel',
                                'user_id' => auth()->id(),
                            ]);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Stock ajusté')
                            ->body("Nouvelle quantité: {$newQty}")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\DetachAction::make()
                    ->label('Retirer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Retirer la sélection'),
                ]),
            ])
            ->emptyStateHeading('Aucun stock dans les entrepôts')
            ->emptyStateDescription('Ce produit n\'est assigné à aucun entrepôt.')
            ->emptyStateIcon('heroicon-o-building-storefront');
    }
}

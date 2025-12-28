<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Models\InventoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles à inventorier';
    protected static ?string $modelLabel = 'Article';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Emplacement')
                    ->default('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity_expected')
                    ->label('Attendu')
                    ->numeric(4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_counted')
                    ->label('Compté')
                    ->numeric(4)
                    ->placeholder('-')
                    ->color(fn (InventoryItem $record) => 
                        $record->is_counted 
                            ? ($record->quantity_difference == 0 ? 'success' : 'warning')
                            : 'gray'
                    ),
                Tables\Columns\TextColumn::make('quantity_difference')
                    ->label('Écart')
                    ->numeric(4)
                    ->placeholder('-')
                    ->color(fn ($state) => 
                        $state === null ? 'gray' : ($state > 0 ? 'info' : ($state < 0 ? 'danger' : 'success'))
                    )
                    ->formatStateUsing(fn ($state) => 
                        $state === null ? '-' : (($state > 0 ? '+' : '') . number_format($state, 4, ',', ' '))
                    ),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'À compter',
                        'ok' => 'Conforme',
                        'surplus' => 'Excédent',
                        'shortage' => 'Manquant',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'ok',
                        'info' => 'surplus',
                        'danger' => 'shortage',
                    ]),
                Tables\Columns\TextColumn::make('value_difference')
                    ->label('Écart valeur')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->toggleable()
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('countedByUser.name')
                    ->label('Compté par')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('counted_at')
                    ->label('Date comptage')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'À compter',
                        'ok' => 'Conforme',
                        'surplus' => 'Excédent',
                        'shortage' => 'Manquant',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        return match($data['value']) {
                            'pending' => $query->where('is_counted', false),
                            'ok' => $query->where('is_counted', true)->where('quantity_difference', 0),
                            'surplus' => $query->where('is_counted', true)->where('quantity_difference', '>', 0),
                            'shortage' => $query->where('is_counted', true)->where('quantity_difference', '<', 0),
                            default => $query,
                        };
                    }),
                Tables\Filters\TernaryFilter::make('is_counted')
                    ->label('Compté'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('addProducts')
                    ->label('Ajouter des produits')
                    ->icon('heroicon-o-plus')
                    ->visible(fn () => $this->ownerRecord->status === 'draft' && $this->ownerRecord->type !== 'full')
                    ->form([
                        Forms\Components\Select::make('product_ids')
                            ->label('Produits')
                            ->multiple()
                            ->options(function () {
                                $warehouseId = $this->ownerRecord->warehouse_id;
                                $existingIds = $this->ownerRecord->items()->pluck('product_id');

                                return \DB::table('product_warehouse')
                                    ->join('products', 'products.id', '=', 'product_warehouse.product_id')
                                    ->where('product_warehouse.warehouse_id', $warehouseId)
                                    ->whereNotIn('products.id', $existingIds)
                                    ->pluck('products.name', 'products.id');
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $this->ownerRecord->initializeItems($data['product_ids']);
                        
                        Notification::make()
                            ->title('Produits ajoutés')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('quickCount')
                    ->label('Compter')
                    ->icon('heroicon-o-calculator')
                    ->visible(fn () => $this->ownerRecord->status === 'in_progress')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité comptée')
                            ->numeric()
                            ->required()
                            ->autofocus(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])
                    ->action(function (InventoryItem $record, array $data) {
                        $record->count($data['quantity'], null, $data['notes']);
                        
                        Notification::make()
                            ->title('Comptage enregistré')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reset')
                    ->label('Réinitialiser')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (InventoryItem $record) => $record->is_counted && $this->ownerRecord->status === 'in_progress')
                    ->action(function (InventoryItem $record) {
                        $record->reset();
                        
                        Notification::make()
                            ->title('Comptage réinitialisé')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('product.name');
    }
}

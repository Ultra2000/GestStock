<?php

namespace App\Filament\Resources\StockTransferResource\RelationManagers;

use App\Models\StockTransferItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Produits';
    protected static ?string $modelLabel = 'Ligne';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('quantity_requested')
                    ->label('Demandé')
                    ->numeric(4),
                Tables\Columns\TextColumn::make('quantity_shipped')
                    ->label('Expédié')
                    ->numeric(4)
                    ->color(fn (StockTransferItem $record) => 
                        $record->quantity_shipped >= $record->quantity_requested ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('quantity_received')
                    ->label('Reçu')
                    ->numeric(4)
                    ->color(fn (StockTransferItem $record) => 
                        $record->quantity_received >= $record->quantity_shipped ? 'success' : 
                        ($record->quantity_received > 0 ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('pending_quantity')
                    ->label('En attente')
                    ->numeric(4)
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'complete' => 'Complet',
                        'partial' => 'Partiel',
                        'shipped' => 'Expédié',
                        'pending' => 'En attente',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'complete',
                        'warning' => 'partial',
                        'info' => 'shipped',
                        'gray' => 'pending',
                    ]),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Coût')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Total')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Lot')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sourceLocation.name')
                    ->label('Empl. source')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('destinationLocation.name')
                    ->label('Empl. dest.')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'shipped' => 'Expédié',
                        'partial' => 'Partiel',
                        'complete' => 'Complet',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }
                        
                        return match($data['value']) {
                            'pending' => $query->where('quantity_shipped', 0),
                            'shipped' => $query->where('quantity_shipped', '>', 0)->where('quantity_received', 0),
                            'partial' => $query->where('quantity_received', '>', 0)->whereRaw('quantity_received < quantity_shipped'),
                            'complete' => $query->whereRaw('quantity_received >= quantity_shipped'),
                            default => $query,
                        };
                    }),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Aucun produit')
            ->emptyStateDescription('Ce transfert ne contient pas encore de produits.');
    }
}

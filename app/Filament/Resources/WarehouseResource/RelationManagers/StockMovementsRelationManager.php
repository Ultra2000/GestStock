<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Historique des mouvements';
    protected static ?string $modelLabel = 'Mouvement';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => StockMovement::getMovementTypes()[$state] ?? $state)
                    ->colors([
                        'success' => fn ($state) => in_array($state, StockMovement::getIncomingTypes()),
                        'danger' => fn ($state) => in_array($state, StockMovement::getOutgoingTypes()),
                        'info' => 'inventory',
                    ]),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric(4)
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '') . number_format($state, 4, ',', ' ')),
                Tables\Columns\TextColumn::make('quantity_before')
                    ->label('Avant')
                    ->numeric(4)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('quantity_after')
                    ->label('Après')
                    ->numeric(4)
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Emplacement')
                    ->default('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motif')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->reason)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Par')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(StockMovement::getMovementTypes())
                    ->multiple(),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('incoming')
                    ->label('Entrées')
                    ->query(fn ($query) => $query->where('quantity', '>', 0)),
                Tables\Filters\Filter::make('outgoing')
                    ->label('Sorties')
                    ->query(fn ($query) => $query->where('quantity', '<', 0)),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(fn (StockMovement $record) => view('filament.resources.warehouse-resource.movement-details', [
                        'movement' => $record,
                    ])),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}

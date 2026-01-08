<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Produits en stock';
    protected static ?string $modelLabel = 'Produit';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produit')
                    ->options(fn () => Product::query()
                        ->where('company_id', filament()->getTenant()->id)
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\Select::make('location_id')
                    ->label('Emplacement')
                    ->options(fn () => $this->ownerRecord->locations()
                        ->where('is_active', true)
                        ->pluck('name', 'id'))
                    ->searchable(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('reserved_quantity')
                    ->label('Quantité réservée')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('min_quantity')
                    ->label('Stock minimum')
                    ->numeric()
                    ->helperText('Alerte si stock inférieur'),
                Forms\Components\TextInput::make('max_quantity')
                    ->label('Stock maximum')
                    ->numeric(),
                Forms\Components\TextInput::make('reorder_point')
                    ->label('Point de réappro')
                    ->numeric()
                    ->helperText('Déclenche une suggestion de réapprovisionnement'),
                Forms\Components\TextInput::make('reorder_quantity')
                    ->label('Quantité de réappro')
                    ->numeric()
                    ->helperText('Quantité à commander lors du réapprovisionnement'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.location_id')
                    ->label('Emplacement')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        $location = \App\Models\WarehouseLocation::find($state);
                        return $location ? $location->code : '-';
                    })
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('pivot.quantity')
                    ->label('Stock')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->pivot->min_quantity && $record->pivot->quantity <= $record->pivot->min_quantity
                            ? 'danger'
                            : 'success'
                    ),
                Tables\Columns\TextColumn::make('pivot.reserved_quantity')
                    ->label('Réservé')
                    ->numeric(2)
                    ->color('warning'),
                Tables\Columns\TextColumn::make('available')
                    ->label('Disponible')
                    ->state(fn ($record) => $record->pivot->quantity - $record->pivot->reserved_quantity)
                    ->numeric(2)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('pivot.min_quantity')
                    ->label('Min')
                    ->numeric(2)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pivot.reorder_point')
                    ->label('Réappro')
                    ->numeric(2)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unité'),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valeur')
                    ->state(fn ($record) => number_format(
                        $record->pivot->quantity * ($record->purchase_price ?? 0),
                        0, ',', ' '
                    ) . ' ' . \Filament\Facades\Filament::getTenant()->currency)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Emplacement')
                    ->options(fn () => $this->ownerRecord->locations()
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn ($loc) => [$loc->id => $loc->code . ' - ' . $loc->name])
                        ->toArray())
                    ->query(fn ($query, $data) => $data['value']
                        ? $query->wherePivot('location_id', $data['value'])
                        : $query),
                Tables\Filters\Filter::make('no_location')
                    ->label('Sans emplacement')
                    ->query(fn ($query) => $query->wherePivotNull('location_id')),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock bas')
                    ->query(fn ($query) => $query
                        ->whereNotNull('product_warehouse.min_quantity')
                        ->whereRaw('product_warehouse.quantity <= product_warehouse.min_quantity')),
                Tables\Filters\Filter::make('needs_reorder')
                    ->label('À réapprovisionner')
                    ->query(fn ($query) => $query
                        ->whereNotNull('product_warehouse.reorder_point')
                        ->whereRaw('product_warehouse.quantity <= product_warehouse.reorder_point')),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Ajouter produit')
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Produit')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('location_id')
                            ->label('Emplacement')
                            ->options(fn () => $this->ownerRecord->locations()
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité initiale')
                            ->numeric()
                            ->required()
                            ->default(0),
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Stock minimum')
                            ->numeric(),
                        Forms\Components\TextInput::make('reorder_point')
                            ->label('Point de réappro')
                            ->numeric(),
                        Forms\Components\TextInput::make('reorder_quantity')
                            ->label('Quantité de réappro')
                            ->numeric(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = filament()->getTenant()->id;
                        $data['reserved_quantity'] = 0;
                        return $data;
                    })
                    ->after(function (Product $record, array $data) {
                        // Create initial stock movement
                        $this->ownerRecord->adjustStock(
                            $record->id,
                            $data['quantity'],
                            'initial',
                            'Stock initial',
                            $data['location_id'] ?? null
                        );
                    }),
                Tables\Actions\Action::make('adjustStock')
                    ->label('Ajuster stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Forms\Components\Select::make('product_id')
                            ->label('Produit')
                            ->options(fn () => $this->ownerRecord->products()->pluck('products.name', 'products.id'))
                            ->searchable()
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('location_id')
                            ->label('Emplacement')
                            ->options(fn () => $this->ownerRecord->locations()
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité (+/-)')
                            ->numeric()
                            ->required()
                            ->helperText('Positif pour ajouter, négatif pour retirer'),
                        Forms\Components\Select::make('type')
                            ->label('Type de mouvement')
                            ->options([
                                'adjustment_in' => 'Ajustement entrée',
                                'adjustment_out' => 'Ajustement sortie',
                                'waste' => 'Perte/Casse',
                            ])
                            ->required()
                            ->default('adjustment_in'),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $quantity = $data['type'] === 'adjustment_in'
                                ? abs($data['quantity'])
                                : -abs($data['quantity']);

                            $this->ownerRecord->adjustStock(
                                $data['product_id'],
                                $quantity,
                                $data['type'],
                                $data['reason'],
                                $data['location_id'] ?? null
                            );

                            Notification::make()
                                ->title('Stock ajusté')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('quickAdjust')
                    ->label('Ajuster')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité (+/-)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif')
                            ->required(),
                    ])
                    ->action(function (Product $record, array $data) {
                        try {
                            $type = $data['quantity'] >= 0 ? 'adjustment_in' : 'adjustment_out';
                            
                            $this->ownerRecord->adjustStock(
                                $record->id,
                                $data['quantity'],
                                $type,
                                $data['reason'],
                                $record->pivot->location_id ?? null
                            );

                            Notification::make()
                                ->title('Stock ajusté')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DetachAction::make()
                    ->label('Retirer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}

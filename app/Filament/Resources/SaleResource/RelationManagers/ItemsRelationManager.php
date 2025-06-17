<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Articles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            if ($product) {
                                $set('unit_price', $product->price);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantité')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $this->updateTotalPrice($set, $get);
                    }),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Prix unitaire')
                    ->required()
                    ->numeric()
                    ->prefix('FCFA')
                    ->suffix('F')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' '))
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $this->updateTotalPrice($set, $get);
                    }),
                Forms\Components\TextInput::make('total_price')
                    ->label('Prix total')
                    ->required()
                    ->numeric()
                    ->prefix('FCFA')
                    ->suffix('F')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' '))
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Prix unitaire')
                    ->money('XOF'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Prix total')
                    ->money('XOF'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un article')
                    ->after(function ($record) {
                        // Mettre à jour le stock du produit
                        $product = $record->product;
                        $product->stock -= $record->quantity;
                        $product->save();

                        // Mettre à jour le total de la vente
                        $sale = $record->sale;
                        $sale->total = $sale->items->sum('total_price');
                        $sale->save();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier')
                    ->after(function ($record) {
                        // Mettre à jour le stock du produit
                        $product = $record->product;
                        $product->stock += $record->getOriginal('quantity') - $record->quantity;
                        $product->save();

                        // Mettre à jour le total de la vente
                        $sale = $record->sale;
                        $sale->total = $sale->items->sum('total_price');
                        $sale->save();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->after(function ($record) {
                        // Remettre le stock du produit
                        $product = $record->product;
                        $product->stock += $record->quantity;
                        $product->save();

                        // Mettre à jour le total de la vente
                        $sale = $record->sale;
                        $sale->total = $sale->items->sum('total_price');
                        $sale->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection')
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                // Remettre le stock du produit
                                $product = $record->product;
                                $product->stock += $record->quantity;
                                $product->save();
                            }

                            // Mettre à jour le total de la vente
                            $sale = $records->first()->sale;
                            $sale->total = $sale->items->sum('total_price');
                            $sale->save();
                        }),
                ]),
            ]);
    }

    protected function updateTotalPrice(Forms\Set $set, Forms\Get $get): void
    {
        $quantity = $get('quantity');
        $unitPrice = $get('unit_price');

        if ($quantity && $unitPrice) {
            $set('total_price', $quantity * $unitPrice);
        }
    }
} 
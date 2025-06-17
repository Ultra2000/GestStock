<?php

namespace App\Filament\Resources\PurchaseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            $set('unit_price', $product->price);
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantité')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $set('total_price', $state * $get('unit_price'));
                    }),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Prix unitaire')
                    ->required()
                    ->numeric()
                    ->prefix('FCFA')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $set('total_price', $state * $get('quantity'));
                    }),
                Forms\Components\TextInput::make('total_price')
                    ->label('Prix total')
                    ->numeric()
                    ->prefix('FCFA')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Prix unitaire')
                    ->money('FCFA'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Prix total')
                    ->money('FCFA'),
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
                        $product->stock += $record->quantity;
                        $product->save();

                        // Mettre à jour le total de l'achat
                        $purchase = $record->purchase;
                        $purchase->total = $purchase->items->sum('total_price');
                        $purchase->save();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier')
                    ->after(function ($record) {
                        // Mettre à jour le total de l'achat
                        $purchase = $record->purchase;
                        $purchase->total = $purchase->items->sum('total_price');
                        $purchase->save();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->after(function ($record) {
                        // Mettre à jour le stock du produit
                        $product = $record->product;
                        $product->stock -= $record->quantity;
                        $product->save();

                        // Mettre à jour le total de l'achat
                        $purchase = $record->purchase;
                        $purchase->total = $purchase->items->sum('total_price');
                        $purchase->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection')
                        ->after(function ($records) {
                            foreach ($records as $record) {
                                // Mettre à jour le stock du produit
                                $product = $record->product;
                                $product->stock -= $record->quantity;
                                $product->save();
                            }

                            // Mettre à jour le total de l'achat
                            $purchase = $records->first()->purchase;
                            $purchase->total = $purchase->items->sum('total_price');
                            $purchase->save();
                        }),
                ]),
            ]);
    }
} 
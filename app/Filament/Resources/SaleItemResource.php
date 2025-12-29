<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleItemResource\Pages;
use App\Filament\Resources\SaleItemResource\RelationManagers;
use App\Models\SaleItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleItemResource extends Resource
{
    protected static ?string $model = SaleItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    // Ce Resource n'est pas destiné à être visible dans le menu
    // Les SaleItems sont gérés via SaleResource (relation)
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('sale_id')
                    ->relationship('sale', 'invoice_number')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $product = \App\Models\Product::find($state);
                            $set('unit_price', $product->price);
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $set('total_price', $state * $get('unit_price'));
                    }),
                Forms\Components\TextInput::make('unit_price')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                        $set('total_price', $state * $get('quantity'));
                    }),
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale.invoice_number')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaleItems::route('/'),
            'create' => Pages\CreateSaleItem::route('/create'),
            'edit' => Pages\EditSaleItem::route('/{record}/edit'),
        ];
    }
}

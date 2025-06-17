<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Gestion du stock';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Ventes';
    protected static ?string $modelLabel = 'Vente';
    protected static ?string $pluralModelLabel = 'Ventes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de la vente')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Numéro de facture')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => 'FACT-' . strtoupper(Str::random(8)))
                            ->disabled(),
                        Forms\Components\Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'completed' => 'Terminée',
                                'cancelled' => 'Annulée',
                            ])
                            ->required()
                            ->default('pending'),
                    ])->columns(3),

                Forms\Components\Section::make('Articles')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Articles')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->options(function () {
                                        return Product::where('stock', '>', 0)
                                            ->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->price);
                                                $set('quantity', 1);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        if ($productId) {
                                            $product = Product::find($productId);
                                            return $product ? $product->stock : 1;
                                        }
                                        return 1;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $quantity = $state;
                                        $unitPrice = $get('unit_price');
                                        if ($quantity && $unitPrice) {
                                            $set('total_price', $quantity * $unitPrice);
                                        }
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
                                        $quantity = $get('quantity');
                                        $unitPrice = $state;
                                        if ($quantity && $unitPrice) {
                                            $set('total_price', $quantity * $unitPrice);
                                        }
                                    }),
                                Forms\Components\TextInput::make('total_price')
                                    ->label('Prix total')
                                    ->required()
                                    ->numeric()
                                    ->prefix('FCFA')
                                    ->suffix('F')
                                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' '))
                                    ->disabled(),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Numéro de facture')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                        default => 'En attente',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
                Tables\Actions\Action::make('invoice')
                    ->label('Facture')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Sale $record): string => route('sales.invoice', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}

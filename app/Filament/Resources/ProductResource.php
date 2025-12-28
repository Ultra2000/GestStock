<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;
use App\Models\Product as ProductModel;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Gestion du stock';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Produits';
    protected static ?string $modelLabel = 'Produit';
    protected static ?string $pluralModelLabel = 'Produits';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->maxLength(255)
                            ->helperText('Généré automatiquement à la création')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Forms\Components\Select::make('barcode_type')
                            ->label('Type code-barres')
                            ->options([
                                'code128' => 'Code 128',
                            ])
                            ->default('code128')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Prix')
                    ->schema([
                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Prix d\'achat')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix(fn () => Filament::getTenant()->currency ?? 'FCFA'),
                        Forms\Components\TextInput::make('price')
                            ->label('Prix de vente')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix(fn () => Filament::getTenant()->currency ?? 'FCFA'),
                    ])->columns(2),

                Forms\Components\Section::make('Stock')
                    ->schema([
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock initial')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Stock initial assigné à l\'entrepôt par défaut')
                            ->visibleOn('create'),
                        Forms\Components\Placeholder::make('total_stock_display')
                            ->label('Stock total (tous entrepôts)')
                            ->content(fn ($record) => $record ? number_format($record->total_stock, 0, ',', ' ') . ' ' . ($record->unit ?? 'unités') : '-')
                            ->visibleOn('edit'),
                        Forms\Components\TextInput::make('unit')
                            ->label('Unité')
                            ->required()
                            ->default('pièce')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('min_stock')
                            ->label('Stock minimum (alerte)')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                Forms\Components\Section::make('Fournisseur')
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->label('Fournisseur')
                            ->relationship(
                                'supplier', 
                                'name',
                                fn ($query) => $query->where('company_id', \Filament\Facades\Filament::getTenant()?->id)
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required(),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Téléphone'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\ViewColumn::make('barcode_preview')
                    ->label('Aperçu')
                    ->view('tables.columns.barcode-preview')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('barcode_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'code128',
                        // 'success' => 'ean13',
                    ])
                    ->formatStateUsing(fn($state) => strtoupper($state)),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Prix d\'achat')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Prix de vente')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock total')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record && $record->total_stock <= $record->min_stock ? 'danger' : 'success')
                    ->tooltip(fn ($record) => $record && $record->total_stock <= $record->min_stock ? 'Stock faible!' : null),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unité')
                    ->searchable(),
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Stock minimum')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
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
                Action::make('regen_code')
                    ->label('Régénérer code')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalDescription('Cette action va générer un nouveau code unique pour ce produit. Cette opération est irréversible.')
                    ->visible(fn ($record) => auth()->user()?->isAdmin())
                    ->action(function ($record) {
                        // Bypass model protection avec DB::table direct
                        $newCode = ProductModel::generateInternalCode();
                        \Illuminate\Support\Facades\DB::table('products')
                            ->where('id', $record->id)
                            ->update(['code' => $newCode]);
                        $record->refresh();
                    })
                    ->after(function ($record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Nouveau code: '.$record->code)
                            ->success()
                            ->send();
                    }),
                Action::make('print_labels_single')
                    ->label('Imprimer étiquettes')
                    ->icon('heroicon-o-printer')
                    ->modalHeading('Imprimer étiquettes produit')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantité')
                            ->numeric()
                            ->default(1)
                            ->minValue(1),
                        Forms\Components\Select::make('columns')
                            ->label('Colonnes par ligne')
                            ->options([2=>2,3=>3,4=>4])
                            ->default(3),
                        Forms\Components\Toggle::make('show_price')
                            ->label('Afficher le prix')
                            ->default(false),
                    ])
                    ->action(function($record, array $data){
                        $qty = (int)($data['quantity'] ?? 1);
                        if($qty < 1){ $qty = 1; }
                        $params = [
                            'ids' => $record->id,
                            'q' => $record->id . ':' . $qty,
                            'cols' => $data['columns'] ?? 3,
                        ];
                        if(!empty($data['show_price'])){ $params['price'] = 1; }
                        return redirect()->route('products.labels.print', $params);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection'),
                    Tables\Actions\BulkAction::make('print_labels')
                        ->label('Imprimer étiquettes')
                        ->icon('heroicon-o-printer')
                        ->form([
                            Forms\Components\TextInput::make('quantities')
                                ->label('Quantités (ex: id:qty,id:qty)')
                                ->helperText('Exemple: 5:3,8:2 pour 3 étiquettes produit 5 et 2 étiquettes produit 8')
                                ->placeholder(''),
                            Forms\Components\Select::make('columns')
                                ->label('Colonnes')
                                ->options([2=>2,3=>3,4=>4])
                                ->default(3),
                            Forms\Components\Toggle::make('show_price')
                                ->label('Afficher le prix')
                                ->default(false),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $ids = $records->pluck('id')->implode(',');
                            $params = [
                                'ids' => $ids,
                                'cols' => $data['columns'] ?? 3,
                            ];
                            if (!empty($data['quantities'])) {
                                $params['q'] = $data['quantities'];
                            }
                            if (!empty($data['show_price'])) {
                                $params['price'] = 1;
                            }
                            $url = route('products.labels.print', $params);
                            return redirect($url);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WarehousesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

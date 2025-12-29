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
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Tables\Actions\Action;
use App\Models\Product as ProductModel;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Stocks & Achats';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Produits';
    protected static ?string $modelLabel = 'Produit';
    protected static ?string $pluralModelLabel = 'Produits';

    /**
     * Optimisation: Eager loading des relations pour éviter N+1
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['supplier', 'warehouses']);
    }

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

                Forms\Components\Section::make('Prix et TVA')
                    ->description('Saisissez les prix HT - les prix TTC seront calculés automatiquement')
                    ->schema([
                        // Toggle pour choisir si on saisit en HT ou TTC
                        Forms\Components\Toggle::make('prices_include_vat')
                            ->label('Les prix saisis sont TTC')
                            ->default(false)
                            ->helperText('Activez si vous saisissez des prix TTC (ex: prix affichés en magasin)')
                            ->live()
                            ->columnSpanFull(),

                        // Section Achat
                        Forms\Components\Fieldset::make('Prix d\'achat (fournisseur)')
                            ->schema([
                                Forms\Components\TextInput::make('purchase_price')
                                    ->label(fn (Forms\Get $get) => $get('prices_include_vat') ? 'Prix d\'achat TTC' : 'Prix d\'achat HT')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->suffix(fn () => Filament::getTenant()->currency ?? 'EUR')
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $vatRate = (float) ($get('vat_rate_purchase') ?? 20);
                                        $pricesTtc = $get('prices_include_vat');
                                        
                                        if ($pricesTtc && $vatRate > 0) {
                                            // Prix saisi est TTC, calculer HT
                                            $ht = round((float)$state / (1 + $vatRate / 100), 2);
                                            $set('purchase_price_ht', $ht);
                                        } else {
                                            // Prix saisi est HT
                                            $set('purchase_price_ht', (float)$state);
                                        }
                                    }),
                                Forms\Components\Select::make('vat_rate_purchase')
                                    ->label('TVA Achat')
                                    ->options(Product::getCommonVatRates())
                                    ->default(20.00)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $price = (float) ($get('purchase_price') ?? 0);
                                        $vatRate = (float) $state;
                                        $pricesTtc = $get('prices_include_vat');
                                        
                                        if ($pricesTtc && $vatRate > 0) {
                                            $ht = round($price / (1 + $vatRate / 100), 2);
                                            $set('purchase_price_ht', $ht);
                                        } else {
                                            $set('purchase_price_ht', $price);
                                        }
                                    }),
                                Forms\Components\Placeholder::make('purchase_price_calculated')
                                    ->label(fn (Forms\Get $get) => $get('prices_include_vat') ? 'Prix HT calculé' : 'Prix TTC calculé')
                                    ->content(function (Forms\Get $get) {
                                        $price = (float) ($get('purchase_price') ?? 0);
                                        $vatRate = (float) ($get('vat_rate_purchase') ?? 20);
                                        $pricesTtc = $get('prices_include_vat');
                                        $currency = Filament::getTenant()->currency ?? 'EUR';
                                        
                                        if ($pricesTtc) {
                                            $ht = $vatRate > 0 ? round($price / (1 + $vatRate / 100), 2) : $price;
                                            return number_format($ht, 2, ',', ' ') . ' ' . $currency . ' HT';
                                        } else {
                                            $ttc = round($price * (1 + $vatRate / 100), 2);
                                            return number_format($ttc, 2, ',', ' ') . ' ' . $currency . ' TTC';
                                        }
                                    }),
                            ])->columns(3),

                        // Section Vente
                        Forms\Components\Fieldset::make('Prix de vente (client)')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label(fn (Forms\Get $get) => $get('prices_include_vat') ? 'Prix de vente TTC' : 'Prix de vente HT')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->suffix(fn () => Filament::getTenant()->currency ?? 'EUR')
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $vatRate = (float) ($get('vat_rate_sale') ?? 20);
                                        $pricesTtc = $get('prices_include_vat');
                                        
                                        if ($pricesTtc && $vatRate > 0) {
                                            $ht = round((float)$state / (1 + $vatRate / 100), 2);
                                            $set('sale_price_ht', $ht);
                                        } else {
                                            $set('sale_price_ht', (float)$state);
                                        }
                                    }),
                                Forms\Components\Select::make('vat_rate_sale')
                                    ->label('TVA Vente')
                                    ->options(Product::getCommonVatRates())
                                    ->default(20.00)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $price = (float) ($get('price') ?? 0);
                                        $vatRate = (float) $state;
                                        $pricesTtc = $get('prices_include_vat');
                                        
                                        if ($pricesTtc && $vatRate > 0) {
                                            $ht = round($price / (1 + $vatRate / 100), 2);
                                            $set('sale_price_ht', $ht);
                                        } else {
                                            $set('sale_price_ht', $price);
                                        }
                                    }),
                                Forms\Components\Placeholder::make('sale_price_calculated')
                                    ->label(fn (Forms\Get $get) => $get('prices_include_vat') ? 'Prix HT calculé' : 'Prix TTC calculé')
                                    ->content(function (Forms\Get $get) {
                                        $price = (float) ($get('price') ?? 0);
                                        $vatRate = (float) ($get('vat_rate_sale') ?? 20);
                                        $pricesTtc = $get('prices_include_vat');
                                        $currency = Filament::getTenant()->currency ?? 'EUR';
                                        
                                        if ($pricesTtc) {
                                            $ht = $vatRate > 0 ? round($price / (1 + $vatRate / 100), 2) : $price;
                                            return number_format($ht, 2, ',', ' ') . ' ' . $currency . ' HT';
                                        } else {
                                            $ttc = round($price * (1 + $vatRate / 100), 2);
                                            return number_format($ttc, 2, ',', ' ') . ' ' . $currency . ' TTC';
                                        }
                                    }),
                            ])->columns(3),

                        // Catégorie TVA pour Chorus Pro
                        Forms\Components\Select::make('vat_category')
                            ->label('Catégorie TVA (Chorus Pro)')
                            ->options(Product::getVatCategories())
                            ->default('S')
                            ->helperText('Utilisé pour la facturation électronique')
                            ->columnSpan(1),

                        // Affichage de la marge
                        Forms\Components\Placeholder::make('margin_info')
                            ->label('📊 Marge')
                            ->content(function (Forms\Get $get) {
                                $purchasePrice = (float) ($get('purchase_price') ?? 0);
                                $salePrice = (float) ($get('price') ?? 0);
                                $vatPurchase = (float) ($get('vat_rate_purchase') ?? 20);
                                $vatSale = (float) ($get('vat_rate_sale') ?? 20);
                                $pricesTtc = $get('prices_include_vat');
                                $currency = Filament::getTenant()->currency ?? 'EUR';
                                
                                // Calculer les prix HT
                                if ($pricesTtc) {
                                    $purchaseHt = $vatPurchase > 0 ? $purchasePrice / (1 + $vatPurchase / 100) : $purchasePrice;
                                    $saleHt = $vatSale > 0 ? $salePrice / (1 + $vatSale / 100) : $salePrice;
                                } else {
                                    $purchaseHt = $purchasePrice;
                                    $saleHt = $salePrice;
                                }
                                
                                $margin = $saleHt - $purchaseHt;
                                $marginPercent = $purchaseHt > 0 ? ($margin / $purchaseHt) * 100 : 0;
                                $markupPercent = $saleHt > 0 ? ($margin / $saleHt) * 100 : 0;
                                
                                $color = $margin > 0 ? 'text-green-600' : ($margin < 0 ? 'text-red-600' : 'text-gray-600');
                                
                                return new \Illuminate\Support\HtmlString("
                                    <div class='text-sm space-y-1'>
                                        <div class='{$color} font-semibold'>
                                            Marge brute: " . number_format($margin, 2, ',', ' ') . " {$currency}
                                        </div>
                                        <div class='text-gray-500'>
                                            Taux de marge: " . number_format($marginPercent, 1, ',', ' ') . "%
                                            <span class='mx-2'>|</span>
                                            Taux de marque: " . number_format($markupPercent, 1, ',', ' ') . "%
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpan(2),

                        // Champs cachés pour stocker les prix HT calculés
                        Forms\Components\Hidden::make('purchase_price_ht'),
                        Forms\Components\Hidden::make('sale_price_ht'),
                    ])->columns(3),

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
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Achat HT')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('vat_rate_purchase')
                    ->label('TVA Achat')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->label('Vente HT')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('vat_rate_sale')
                    ->label('TVA Vente')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%'),
                Tables\Columns\TextColumn::make('margin')
                    ->label('Marge')
                    ->getStateUsing(fn ($record) => $record->margin ?? 0)
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw('(price - purchase_price) ' . $direction)),
                Tables\Columns\TextColumn::make('margin_percent')
                    ->label('Marge %')
                    ->getStateUsing(fn ($record) => $record->margin_percent ?? 0)
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . '%')
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record && $record->total_stock <= $record->min_stock ? 'danger' : 'success')
                    ->tooltip(fn ($record) => $record && $record->total_stock <= $record->min_stock ? 'Stock faible!' : null),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unité')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Stock min.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vat_category')
                    ->label('Cat. TVA')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->deferLoading() // Optimisation: Chargement différé via AJAX
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
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

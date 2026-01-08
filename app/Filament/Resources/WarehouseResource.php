<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Filament\Resources\WarehouseResource\RelationManagers;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Stocks & Achats';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('stock') ?? true;
    }
    protected static ?string $navigationLabel = 'Entrepôts';
    protected static ?string $modelLabel = 'Entrepôt';
    protected static ?string $pluralModelLabel = 'Entrepôts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('WH001'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'warehouse' => 'Entrepôt',
                                'store' => 'Magasin',
                                'supplier' => 'Dépôt Fournisseur',
                                'customer' => 'Dépôt Client',
                            ])
                            ->default('warehouse'),
                        Forms\Components\TextInput::make('manager_name')
                            ->label('Responsable')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Adresse')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Adresse')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('postal_code')
                            ->label('Code postal')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('country')
                            ->label('Pays')
                            ->default('Sénégal')
                            ->maxLength(100),
                    ])->columns(3),

                Forms\Components\Section::make('Contact')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Géolocalisation & Pointage')
                    ->description('Configuration pour le système de pointage des employés')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->placeholder('48.8566')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('getLocation')
                                            ->icon('heroicon-o-map-pin')
                                            ->tooltip('Obtenir ma position')
                                            ->action(function ($livewire) {
                                                $livewire->dispatch('get-current-location');
                                            })
                                    ),
                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->placeholder('2.3522'),
                                Forms\Components\TextInput::make('gps_radius')
                                    ->label('Rayon autorisé (mètres)')
                                    ->numeric()
                                    ->default(100)
                                    ->minValue(10)
                                    ->maxValue(1000)
                                    ->suffix('m')
                                    ->helperText('Distance max pour valider le pointage'),
                            ]),
                        Forms\Components\Placeholder::make('map_preview')
                            ->label('')
                            ->content(fn ($record) => $record && $record->latitude && $record->longitude 
                                ? new \Illuminate\Support\HtmlString(
                                    '<div class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">' .
                                    '<iframe width="100%" height="200" frameborder="0" style="border:0" ' .
                                    'src="https://www.openstreetmap.org/export/embed.html?bbox=' . 
                                    ($record->longitude - 0.005) . ',' . ($record->latitude - 0.003) . ',' . 
                                    ($record->longitude + 0.005) . ',' . ($record->latitude + 0.003) . 
                                    '&layer=mapnik&marker=' . $record->latitude . ',' . $record->longitude . '" ' .
                                    'allowfullscreen></iframe></div>'
                                )
                                : new \Illuminate\Support\HtmlString(
                                    '<div class="p-4 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-lg">' .
                                    '<x-heroicon-o-map class="w-8 h-8 mx-auto mb-2 opacity-50" />' .
                                    'Renseignez les coordonnées GPS pour afficher l\'aperçu de la carte' .
                                    '</div>'
                                )
                            )
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record !== null),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('requires_gps_check')
                                    ->label('Vérification GPS requise')
                                    ->helperText('Les employés doivent être physiquement sur site pour pointer')
                                    ->live(),
                                Forms\Components\Toggle::make('requires_qr_check')
                                    ->label('Scan QR Code requis')
                                    ->helperText('Les employés doivent scanner le QR Code affiché sur place')
                                    ->live(),
                            ]),
                        Forms\Components\Placeholder::make('pointage_info')
                            ->label('')
                            ->content(function ($get) {
                                $gps = $get('requires_gps_check');
                                $qr = $get('requires_qr_check');
                                
                                if (!$gps && !$qr) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-sm text-gray-600 dark:text-gray-400">' .
                                        '<strong>Mode libre :</strong> Les employés peuvent pointer sans vérification de localisation ni QR Code.' .
                                        '</div>'
                                    );
                                }
                                
                                $checks = [];
                                if ($gps) $checks[] = '📍 Position GPS vérifiée';
                                if ($qr) $checks[] = '📱 Scan QR Code obligatoire';
                                
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="p-3 bg-primary-50 dark:bg-primary-950 rounded-lg text-sm text-primary-700 dark:text-primary-300">' .
                                    '<strong>Vérifications actives :</strong><br>' . implode('<br>', $checks) .
                                    '</div>'
                                );
                            })
                            ->columnSpanFull(),
                    ])->columns(1),

                Forms\Components\Section::make('Options')
                    ->schema([
                        Forms\Components\Toggle::make('is_default')
                            ->label('Entrepôt par défaut')
                            ->helperText('Utilisé par défaut pour les nouvelles entrées de stock'),
                        Forms\Components\Toggle::make('is_pos_location')
                            ->label('Point de vente')
                            ->helperText('Cet entrepôt est un point de vente (POS)'),
                        Forms\Components\Toggle::make('allow_negative_stock')
                            ->label('Autoriser stock négatif')
                            ->helperText('Permet les ventes même si le stock est insuffisant'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes internes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'warehouse' => 'Entrepôt',
                        'store' => 'Magasin',
                        'supplier' => 'Fournisseur',
                        'customer' => 'Client',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'warehouse',
                        'success' => 'store',
                        'warning' => 'supplier',
                        'info' => 'customer',
                    ]),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('manager_name')
                    ->label('Responsable')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Produits')
                    ->state(fn (Warehouse $record) => $record->products()->distinct('products.id')->count('products.id'))
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Défaut')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning'),
                Tables\Columns\IconColumn::make('is_pos_location')
                    ->label('POS')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'warehouse' => 'Entrepôt',
                        'store' => 'Magasin',
                        'supplier' => 'Fournisseur',
                        'customer' => 'Client',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                Tables\Filters\TernaryFilter::make('is_pos_location')
                    ->label('Point de vente'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('setDefault')
                        ->label('Définir par défaut')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Warehouse $record) => $record->setAsDefault())
                        ->hidden(fn (Warehouse $record) => $record->is_default),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informations générales')
                    ->schema([
                        Components\TextEntry::make('code')
                            ->label('Code'),
                        Components\TextEntry::make('name')
                            ->label('Nom'),
                        Components\TextEntry::make('type_label')
                            ->label('Type')
                            ->badge(),
                        Components\TextEntry::make('manager_name')
                            ->label('Responsable')
                            ->default('-'),
                    ])->columns(4),

                Components\Section::make('Statistiques')
                    ->schema([
                        Components\TextEntry::make('products_count')
                            ->label('Produits en stock')
                            ->state(fn (Warehouse $record) => $record->products()->distinct('products.id')->count('products.id'))
                            ->badge()
                            ->color('info'),
                        Components\TextEntry::make('total_stock_value')
                            ->label('Valeur du stock')
                            ->state(fn (Warehouse $record) => number_format($record->getTotalStockValue(), 0, ',', ' ') . ' ' . Filament::getTenant()->currency)
                            ->badge()
                            ->color('success'),
                        Components\TextEntry::make('low_stock_count')
                            ->label('Produits en rupture')
                            ->state(fn (Warehouse $record) => $record->getLowStockProducts()->count())
                            ->badge()
                            ->color('danger'),
                        Components\TextEntry::make('locations_count')
                            ->label('Emplacements')
                            ->state(fn (Warehouse $record) => $record->locations()->count())
                            ->badge(),
                    ])->columns(4),

                Components\Section::make('Contact')
                    ->schema([
                        Components\TextEntry::make('full_address')
                            ->label('Adresse'),
                        Components\TextEntry::make('phone')
                            ->label('Téléphone')
                            ->default('-'),
                        Components\TextEntry::make('email')
                            ->label('Email')
                            ->default('-'),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LocationsRelationManager::class,
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'view' => Pages\ViewWarehouse::route('/{record}'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}

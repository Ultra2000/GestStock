<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Filament\Resources\StockTransferResource\RelationManagers;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Stocks & Achats';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Transferts';
    protected static ?string $modelLabel = 'Transfert';
    protected static ?string $pluralModelLabel = 'Transferts';

    public static function shouldRegisterNavigation(): bool
    {
        return \Filament\Facades\Filament::getTenant()?->isModuleEnabled('stock') ?? true;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = StockTransfer::query()
            ->whereIn('status', ['pending', 'approved', 'in_transit'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du transfert')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->default(fn () => StockTransfer::generateReference(filament()->getTenant()->id))
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Select::make('source_warehouse_id')
                            ->label('Entrepôt source')
                            ->required()
                            ->options(fn () => Warehouse::query()
                                ->where('company_id', filament()->getTenant()->id)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('items', [])),
                        Forms\Components\Select::make('destination_warehouse_id')
                            ->label('Entrepôt destination')
                            ->required()
                            ->options(fn (Forms\Get $get) => Warehouse::query()
                                ->where('company_id', filament()->getTenant()->id)
                                ->where('is_active', true)
                                ->where('id', '!=', $get('source_warehouse_id'))
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->disabled(fn (Forms\Get $get) => !$get('source_warehouse_id')),
                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('Date de transfert')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('expected_date')
                            ->label('Date prévue d\'arrivée'),
                    ])->columns(2),

                Forms\Components\Section::make('Produits à transférer')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->required()
                                    ->options(function (Forms\Get $get) {
                                        $warehouseId = $get('../../source_warehouse_id');
                                        if (!$warehouseId) {
                                            return [];
                                        }

                                        return \DB::table('product_warehouse')
                                            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
                                            ->where('product_warehouse.warehouse_id', $warehouseId)
                                            ->where('product_warehouse.quantity', '>', 0)
                                            ->pluck('products.name', 'products.id');
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            $set('unit_cost', $product?->purchase_price ?? 0);
                                            
                                            $warehouseId = $get('../../source_warehouse_id');
                                            $stock = \DB::table('product_warehouse')
                                                ->where('product_id', $state)
                                                ->where('warehouse_id', $warehouseId)
                                                ->value('quantity') ?? 0;
                                            $set('available_stock', $stock);
                                        }
                                    })
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('available_stock')
                                    ->label('Disponible')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('quantity_requested')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.0001)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->label('Coût unitaire')
                                    ->numeric()
                                    ->prefix(fn () => \Filament\Facades\Filament::getTenant()->currency)
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('batch_number')
                                    ->label('N° Lot')
                                    ->maxLength(100)
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Date expiration')
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter un produit')
                            ->reorderable(false)
                            ->hidden(fn (Forms\Get $get) => !$get('source_warehouse_id')),
                    ]),

                Forms\Components\Section::make('Expédition')
                    ->schema([
                        Forms\Components\TextInput::make('carrier')
                            ->label('Transporteur')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tracking_number')
                            ->label('N° de suivi')
                            ->maxLength(255),
                    ])->columns(2)
                    ->collapsed(),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('sourceWarehouse.name')
                    ->label('Source')
                    ->searchable()
                    ->icon('heroicon-o-arrow-right-start-on-rectangle'),
                Tables\Columns\TextColumn::make('destinationWarehouse.name')
                    ->label('Destination')
                    ->searchable()
                    ->icon('heroicon-o-arrow-right-end-on-rectangle'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Brouillon',
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'in_transit' => 'En transit',
                        'partial' => 'Partiel',
                        'completed' => 'Terminé',
                        'cancelled' => 'Annulé',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'draft',
                        'warning' => fn ($state) => in_array($state, ['pending', 'partial']),
                        'info' => 'approved',
                        'primary' => 'in_transit',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('transfer_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Articles')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Quantité')
                    ->numeric(2),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Valeur')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('requestedBy.name')
                    ->label('Demandé par')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->multiple()
                    ->options([
                        'draft' => 'Brouillon',
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'in_transit' => 'En transit',
                        'partial' => 'Partiel',
                        'completed' => 'Terminé',
                        'cancelled' => 'Annulé',
                    ]),
                Tables\Filters\SelectFilter::make('source_warehouse_id')
                    ->label('Entrepôt source')
                    ->relationship('sourceWarehouse', 'name'),
                Tables\Filters\SelectFilter::make('destination_warehouse_id')
                    ->label('Entrepôt destination')
                    ->relationship('destinationWarehouse', 'name'),
                Tables\Filters\Filter::make('transfer_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('transfer_date', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('transfer_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (StockTransfer $record) => in_array($record->status, ['draft', 'pending'])),
                    Tables\Actions\Action::make('approve')
                        ->label('Approuver')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (StockTransfer $record) {
                            try {
                                $record->approve();
                                Notification::make()
                                    ->title('Transfert approuvé')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (StockTransfer $record) => $record->canBeApproved()),
                    Tables\Actions\Action::make('ship')
                        ->label('Expédier')
                        ->icon('heroicon-o-truck')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalDescription('Cette action va déduire le stock de l\'entrepôt source.')
                        ->action(function (StockTransfer $record) {
                            try {
                                $record->ship();
                                Notification::make()
                                    ->title('Transfert expédié')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (StockTransfer $record) => $record->canBeShipped()),
                    Tables\Actions\Action::make('cancel')
                        ->label('Annuler')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motif d\'annulation')
                                ->required(),
                        ])
                        ->action(function (StockTransfer $record, array $data) {
                            try {
                                $record->cancel($data['reason']);
                                Notification::make()
                                    ->title('Transfert annulé')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (StockTransfer $record) => $record->canBeCancelled()),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (StockTransfer $record) => $record->status === 'draft'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => false), // Désactivé pour les transferts
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informations du transfert')
                    ->schema([
                        Components\TextEntry::make('reference')
                            ->label('Référence')
                            ->copyable(),
                        Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'draft' => 'Brouillon',
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'in_transit' => 'En transit',
                                'partial' => 'Partiel',
                                'completed' => 'Terminé',
                                'cancelled' => 'Annulé',
                                default => $state,
                            })
                            ->color(fn ($state) => match($state) {
                                'draft' => 'gray',
                                'pending', 'partial' => 'warning',
                                'approved' => 'info',
                                'in_transit' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('transfer_date')
                            ->label('Date de transfert')
                            ->date('d/m/Y'),
                        Components\TextEntry::make('expected_date')
                            ->label('Date prévue')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ])->columns(4),

                Components\Section::make('Entrepôts')
                    ->schema([
                        Components\TextEntry::make('sourceWarehouse.name')
                            ->label('Source')
                            ->icon('heroicon-o-building-storefront'),
                        Components\TextEntry::make('destinationWarehouse.name')
                            ->label('Destination')
                            ->icon('heroicon-o-building-storefront'),
                    ])->columns(2),

                Components\Section::make('Suivi')
                    ->schema([
                        Components\TextEntry::make('requestedBy.name')
                            ->label('Demandé par')
                            ->placeholder('-'),
                        Components\TextEntry::make('approvedBy.name')
                            ->label('Approuvé par')
                            ->placeholder('-'),
                        Components\TextEntry::make('shippedBy.name')
                            ->label('Expédié par')
                            ->placeholder('-'),
                        Components\TextEntry::make('receivedBy.name')
                            ->label('Réceptionné par')
                            ->placeholder('-'),
                        Components\TextEntry::make('shipped_date')
                            ->label('Date expédition')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                        Components\TextEntry::make('received_date')
                            ->label('Date réception')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ])->columns(3),

                Components\Section::make('Totaux')
                    ->schema([
                        Components\TextEntry::make('total_items')
                            ->label('Nombre d\'articles')
                            ->badge(),
                        Components\TextEntry::make('total_quantity')
                            ->label('Quantité totale')
                            ->numeric(2),
                        Components\TextEntry::make('total_value')
                            ->label('Valeur totale')
                            ->money(fn () => \Filament\Facades\Filament::getTenant()->currency),
                        Components\TextEntry::make('progress_percent')
                            ->label('Progression')
                            ->formatStateUsing(fn ($state) => $state . '%')
                            ->badge()
                            ->color(fn ($state) => $state >= 100 ? 'success' : ($state > 0 ? 'warning' : 'gray')),
                    ])->columns(4),
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
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'view' => Pages\ViewStockTransfer::route('/{record}'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
            'receive' => Pages\ReceiveStockTransfer::route('/{record}/receive'),
        ];
    }
}

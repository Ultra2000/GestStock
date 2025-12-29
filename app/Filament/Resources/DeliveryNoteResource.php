<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryNoteResource\Pages;
use App\Models\DeliveryNote;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class DeliveryNoteResource extends Resource
{
    protected static ?string $model = DeliveryNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Bons de livraison';

    protected static ?string $modelLabel = 'Bon de livraison';

    protected static ?string $pluralModelLabel = 'Bons de livraison';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('delivery_number')
                                    ->label('N° Bon de livraison')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Généré automatiquement'),
                                Forms\Components\DatePicker::make('delivery_date')
                                    ->label('Date de livraison')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'pending' => 'En attente',
                                        'preparing' => 'En préparation',
                                        'ready' => 'Prêt',
                                        'shipped' => 'Expédié',
                                        'delivered' => 'Livré',
                                        'cancelled' => 'Annulé',
                                    ])
                                    ->default('pending')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('sale_id')
                                    ->label('Commande liée')
                                    ->relationship('sale', 'invoice_number')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $sale = Sale::with('customer')->find($state);
                                            if ($sale) {
                                                $set('customer_id', $sale->customer_id);
                                                $set('delivery_address', $sale->customer?->address);
                                            }
                                        }
                                    }),
                                Forms\Components\Select::make('customer_id')
                                    ->label('Client')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ]),

                Forms\Components\Section::make('Livraison')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('carrier')
                                    ->label('Transporteur')
                                    ->placeholder('Colissimo, Chronopost...'),
                                Forms\Components\TextInput::make('tracking_number')
                                    ->label('N° de suivi')
                                    ->placeholder('Numéro de tracking'),
                                Forms\Components\TextInput::make('total_packages')
                                    ->label('Nombre de colis')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),
                            ]),
                        Forms\Components\Textarea::make('delivery_address')
                            ->label('Adresse de livraison')
                            ->rows(2),
                    ]),

                Forms\Components\Section::make('Articles à livrer')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('description', $product->name);
                                            }
                                        }
                                    })
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('quantity_ordered')
                                    ->label('Qté commandée')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity_delivered')
                                    ->label('Qté livrée')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel('Ajouter un article'),
                    ]),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes / Instructions')
                            ->rows(2),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('delivery_number')
                    ->label('N° BL')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('sale.invoice_number')
                    ->label('Commande')
                    ->searchable()
                    ->url(fn ($record) => $record->sale_id ? SaleResource::getUrl('edit', ['record' => $record->sale_id]) : null),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('carrier')
                    ->label('Transporteur')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('N° Suivi')
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'pending',
                        'info' => 'preparing',
                        'warning' => 'ready',
                        'primary' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'En attente',
                        'preparing' => 'Préparation',
                        'ready' => 'Prêt',
                        'shipped' => 'Expédié',
                        'delivered' => 'Livré',
                        'cancelled' => 'Annulé',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('shipped_at')
                    ->label('Expédié le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('Livré le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'preparing' => 'En préparation',
                        'ready' => 'Prêt',
                        'shipped' => 'Expédié',
                        'delivered' => 'Livré',
                        'cancelled' => 'Annulé',
                    ]),
                Tables\Filters\SelectFilter::make('carrier')
                    ->label('Transporteur')
                    ->options(fn () => DeliveryNote::where('company_id', Filament::getTenant()?->id)
                        ->whereNotNull('carrier')
                        ->distinct()
                        ->pluck('carrier', 'carrier')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('prepare')
                        ->label('En préparation')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->color('info')
                        ->action(fn (DeliveryNote $record) => $record->update(['status' => 'preparing']))
                        ->visible(fn (DeliveryNote $record) => $record->status === 'pending'),
                    Tables\Actions\Action::make('ready')
                        ->label('Marquer prêt')
                        ->icon('heroicon-o-check')
                        ->color('warning')
                        ->action(fn (DeliveryNote $record) => $record->update(['status' => 'ready']))
                        ->visible(fn (DeliveryNote $record) => $record->status === 'preparing'),
                    Tables\Actions\Action::make('ship')
                        ->label('Expédier')
                        ->icon('heroicon-o-truck')
                        ->color('primary')
                        ->form([
                            Forms\Components\TextInput::make('carrier')
                                ->label('Transporteur')
                                ->default(fn (DeliveryNote $record) => $record->carrier),
                            Forms\Components\TextInput::make('tracking_number')
                                ->label('N° de suivi')
                                ->default(fn (DeliveryNote $record) => $record->tracking_number),
                        ])
                        ->action(fn (DeliveryNote $record, array $data) => $record->markAsShipped($data['carrier'], $data['tracking_number']))
                        ->visible(fn (DeliveryNote $record) => in_array($record->status, ['preparing', 'ready'])),
                    Tables\Actions\Action::make('deliver')
                        ->label('Marquer livré')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (DeliveryNote $record) => $record->markAsDelivered())
                        ->visible(fn (DeliveryNote $record) => $record->status === 'shipped'),
                    Tables\Actions\Action::make('pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->url(fn (DeliveryNote $record) => route('delivery-notes.pdf', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListDeliveryNotes::route('/'),
            'create' => Pages\CreateDeliveryNote::route('/create'),
            'edit' => Pages\EditDeliveryNote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }
}

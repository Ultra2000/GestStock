<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Filament\Resources\PurchaseResource\RelationManagers;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Stocks & Achats';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Achats';
    protected static ?string $modelLabel = 'Achat';
    protected static ?string $pluralModelLabel = 'Achats';

    /**
     * Optimisation: Eager loading des relations pour éviter N+1
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['supplier', 'warehouse', 'bankAccount']);
    }

    public static function form(Form $form): Form
    {
        $companyId = Filament::getTenant()?->id;

        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'achat')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Numéro de facture')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('supplier_id')
                            ->label('Fournisseur')
                            ->relationship('supplier', 'name', fn ($query) => $query->where('company_id', $companyId))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Entrepôt de réception')
                            ->options(fn () => Warehouse::where('company_id', $companyId)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->default(fn () => Warehouse::getDefault($companyId)?->id)
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'completed' => 'Terminé',
                                'cancelled' => 'Annulé',
                            ])
                            ->required()
                            ->default('pending')
                            ->live(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Mode de paiement')
                            ->options([
                                'cash' => 'Espèces',
                                'card' => 'Carte bancaire',
                                'transfer' => 'Virement SEPA',
                                'check' => 'Chèque',
                                'sepa_debit' => 'Prélèvement SEPA',
                                'paypal' => 'PayPal',
                            ])
                            ->required(),
                        Forms\Components\Select::make('bank_account_id')
                            ->label('Compte de paiement')
                            ->relationship('bankAccount', 'name', fn ($query) => $query->where('company_id', $companyId))
                            ->searchable()
                            ->preload()
                            ->required(fn (Forms\Get $get) => $get('status') === 'completed')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'completed'),
                    ])->columns(4),

                Forms\Components\Section::make('Paramètres financiers')
                    ->schema([
                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Remise globale %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->live(onBlur: true)
                            ->helperText('Appliquée sur le total TTC')
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) { $record->recalculateTotals(); }
                            }),
                        Forms\Components\Placeholder::make('total_ht_display')
                            ->label('Total HT')
                            ->content(fn (?Purchase $record) => $record ? number_format($record->total_ht ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'EUR') : '-'),
                        Forms\Components\Placeholder::make('total_vat_display')
                            ->label('TVA Déductible')
                            ->content(fn (?Purchase $record) => $record ? number_format($record->total_vat ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'EUR') : '-'),
                        Forms\Components\TextInput::make('total')
                            ->label('Total TTC')
                            ->numeric()
                            ->suffix(fn () => Filament::getTenant()->currency ?? 'EUR')
                            ->disabled(),
                    ])->columns(4),

                // Section Articles - Repeater pour ajouter les produits
                Forms\Components\Section::make('Articles')
                    ->description('Ajoutez les produits achetés avec leur TVA')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->options(fn () => Product::where('company_id', Filament::getTenant()?->id)
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->purchase_price ?? 0);
                                                $set('vat_rate', $product->vat_rate_purchase ?? 20);
                                                $set('quantity', 1);
                                                
                                                // Calculer le total
                                                $vatRate = $product->vat_rate_purchase ?? 20;
                                                $totalHt = $product->purchase_price ?? 0;
                                                $vat = round($totalHt * ($vatRate / 100), 2);
                                                $set('total_price', $totalHt + $vat);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qté')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $quantity = $state ?? 0;
                                        $unitPrice = $get('unit_price') ?? 0;
                                        $vatRate = $get('vat_rate') ?? 20;
                                        $totalHt = $quantity * $unitPrice;
                                        $vat = round($totalHt * ($vatRate / 100), 2);
                                        $set('total_price', $totalHt + $vat);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('P.U. HT')
                                    ->required()
                                    ->numeric()
                                    ->prefix(fn () => Filament::getTenant()->currency ?? 'EUR')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $quantity = $get('quantity') ?? 0;
                                        $unitPrice = $state ?? 0;
                                        $vatRate = $get('vat_rate') ?? 20;
                                        $totalHt = $quantity * $unitPrice;
                                        $vat = round($totalHt * ($vatRate / 100), 2);
                                        $set('total_price', $totalHt + $vat);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\Select::make('vat_rate')
                                    ->label('TVA')
                                    ->options(Product::getCommonVatRates())
                                    ->default(20.00)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $quantity = $get('quantity') ?? 0;
                                        $unitPrice = $get('unit_price') ?? 0;
                                        $vatRate = $state ?? 20;
                                        $totalHt = $quantity * $unitPrice;
                                        $vat = round($totalHt * ($vatRate / 100), 2);
                                        $set('total_price', $totalHt + $vat);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('total_price')
                                    ->label('Total TTC')
                                    ->numeric()
                                    ->prefix(fn () => Filament::getTenant()->currency ?? 'EUR')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->columnSpan(1),
                            ])
                            ->columns(6)
                            ->addActionLabel('+ Ajouter un article')
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['product_id']) 
                                    ? Product::find($state['product_id'])?->name . ' (x' . ($state['quantity'] ?? 1) . ')'
                                    : null
                            ),
                    ]),

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
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Numéro de facture')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Entrepôt')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Terminé',
                        'cancelled' => 'Annulé',
                        default => 'En attente',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'completed' => 'Terminé',
                        'cancelled' => 'Annulé',
                    ]),
            ])
            ->deferLoading() // Optimisation: Chargement différé via AJAX
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer'),
                Tables\Actions\Action::make('invoice')
                    ->label('Facture')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Purchase $record): string => route('purchases.invoice', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('preview')
                    ->label('Prévisualiser')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->url(fn (Purchase $record): string => route('purchases.invoice.preview', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_email')
                    ->label('Envoyer email')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Destinataire')
                            ->email()
                            ->required(),
                        Forms\Components\Textarea::make('message')
                            ->label('Message (optionnel)')
                            ->rows(3),
                    ])
                    ->action(function (array $data, Purchase $record) {
                        \Mail::to($data['email'])->send(new \App\Mail\InvoiceMail('purchase', $record, $data['message'] ?? ''));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer la facture par email')
                    ->modalButton('Envoyer')
                    ->color('success'),
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
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
} 
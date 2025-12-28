<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Filament\Resources\PurchaseResource\RelationManagers;
use App\Models\Purchase;
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
    protected static ?string $navigationGroup = 'Gestion du stock';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Achats';
    protected static ?string $modelLabel = 'Achat';
    protected static ?string $pluralModelLabel = 'Achats';

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
                                'transfer' => 'Virement',
                                'check' => 'Chèque',
                                'mobile_money' => 'Mobile Money',
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
                            ->label('Remise %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) { $record->recalculateTotals(); }
                            }),
                        Forms\Components\TextInput::make('tax_percent')
                            ->label('TVA %')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) { $record->recalculateTotals(); }
                            }),
                        Forms\Components\TextInput::make('total')
                            ->label('Total TTC')
                            ->numeric()
                            ->suffix(fn () => Filament::getTenant()->currency ?? 'FCFA')
                            ->disabled(),
                    ])->columns(3),

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
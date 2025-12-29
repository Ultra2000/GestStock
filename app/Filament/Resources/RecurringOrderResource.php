<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringOrderResource\Pages;
use App\Filament\Resources\RecurringOrderResource\RelationManagers;
use App\Models\RecurringOrder;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class RecurringOrderResource extends Resource
{
    protected static ?string $model = RecurringOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'Commande récurrente';

    protected static ?string $pluralModelLabel = 'Commandes récurrentes';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Informations générales')
                            ->schema([
                                Forms\Components\TextInput::make('reference')
                                    ->label('Référence')
                                    ->default(fn () => 'REC-' . date('Ym') . '-' . str_pad(RecurringOrder::whereMonth('created_at', now()->month)->count() + 1, 4, '0', STR_PAD_LEFT))
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom de l\'abonnement')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('customer_id')
                                    ->label('Client')
                                    ->relationship('customer', 'name', fn ($query) => $query->where('company_id', Filament::getTenant()?->id))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nom')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Téléphone'),
                                    ]),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2),
                            ])->columns(2),

                        Forms\Components\Section::make('Planification')
                            ->schema([
                                Forms\Components\Select::make('frequency')
                                    ->label('Fréquence')
                                    ->options([
                                        'daily' => 'Quotidien',
                                        'weekly' => 'Hebdomadaire',
                                        'biweekly' => 'Bimensuel',
                                        'monthly' => 'Mensuel',
                                        'quarterly' => 'Trimestriel',
                                        'yearly' => 'Annuel',
                                    ])
                                    ->default('monthly')
                                    ->required()
                                    ->live(),
                                Forms\Components\TextInput::make('frequency_value')
                                    ->label('Valeur (ex: tous les X jours)')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Date de début')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Date de fin (optionnel)')
                                    ->afterOrEqual('start_date'),
                                Forms\Components\DatePicker::make('next_execution')
                                    ->label('Prochaine exécution')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\TextInput::make('max_executions')
                                    ->label('Nombre max d\'exécutions (optionnel)')
                                    ->numeric()
                                    ->minValue(1),
                            ])->columns(3),

                        Forms\Components\Section::make('Articles')
                            ->schema([
                                Forms\Components\Repeater::make('items')
                                    ->relationship('items')
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label('Produit')
                                            ->options(fn () => Product::where('company_id', Filament::getTenant()?->id)->pluck('name', 'id'))
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $product = Product::find($state);
                                                    if ($product) {
                                                        $set('unit_price', $product->selling_price);
                                                    }
                                                }
                                            })
                                            ->columnSpan(4),
                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Qté')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0.01)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                $set('total', $state * $get('unit_price'));
                                            })
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('unit_price')
                                            ->label('Prix unit.')
                                            ->numeric()
                                            ->prefix('€')
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                $set('total', $get('quantity') * $state);
                                            })
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->prefix('€')
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),
                                    ])
                                    ->columns(10)
                                    ->defaultItems(1)
                                    ->addActionLabel('Ajouter un article')
                                    ->reorderable()
                                    ->cloneable()
                                    ->collapsible(),
                            ]),
                    ])->columnSpan(2),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Statut')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'active' => 'Actif',
                                        'paused' => 'En pause',
                                        'cancelled' => 'Annulé',
                                        'completed' => 'Terminé',
                                    ])
                                    ->default('active')
                                    ->required(),
                                Forms\Components\Toggle::make('auto_generate')
                                    ->label('Génération auto')
                                    ->helperText('Créer auto. les ventes à la date prévue')
                                    ->default(true),
                                Forms\Components\Toggle::make('auto_send_invoice')
                                    ->label('Envoi auto facture')
                                    ->helperText('Envoyer la facture par email')
                                    ->default(false),
                            ]),

                        Forms\Components\Section::make('Statistiques')
                            ->schema([
                                Forms\Components\Placeholder::make('executions_count')
                                    ->label('Exécutions')
                                    ->content(fn ($record) => $record ? $record->executions_count ?? 0 : 0),
                                Forms\Components\Placeholder::make('last_execution')
                                    ->label('Dernière exécution')
                                    ->content(fn ($record) => $record?->last_execution ? $record->last_execution->format('d/m/Y') : '-'),
                                Forms\Components\Placeholder::make('total_amount')
                                    ->label('CA généré')
                                    ->content(fn ($record) => $record ? number_format($record->sales->sum('total_amount'), 2, ',', ' ') . ' €' : '0,00 €'),
                            ])
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\Section::make('Totaux')
                            ->schema([
                                Forms\Components\Placeholder::make('subtotal_display')
                                    ->label('Sous-total')
                                    ->content(function (Forms\Get $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));
                                        return number_format($subtotal, 2, ',', ' ') . ' €';
                                    }),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('Taux TVA (%)')
                                    ->numeric()
                                    ->default(20)
                                    ->suffix('%')
                                    ->live(),
                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Total TTC / exécution')
                                    ->content(function (Forms\Get $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = collect($items)->sum(fn ($item) => ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0));
                                        $taxRate = $get('tax_rate') ?? 20;
                                        $total = $subtotal * (1 + $taxRate / 100);
                                        return number_format($total, 2, ',', ' ') . ' €';
                                    }),
                            ]),

                        Forms\Components\Section::make('Notes')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes internes')
                                    ->rows(3),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('frequency')
                    ->label('Fréquence')
                    ->colors([
                        'gray' => 'daily',
                        'info' => 'weekly',
                        'primary' => 'biweekly',
                        'success' => 'monthly',
                        'warning' => 'quarterly',
                        'danger' => 'yearly',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'daily' => 'Quotidien',
                        'weekly' => 'Hebdomadaire',
                        'biweekly' => 'Bimensuel',
                        'monthly' => 'Mensuel',
                        'quarterly' => 'Trimestriel',
                        'yearly' => 'Annuel',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('next_execution')
                    ->label('Prochaine')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->next_execution?->isPast() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('executions_count')
                    ->label('Exécutions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('auto_generate')
                    ->label('Auto')
                    ->boolean(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'paused',
                        'danger' => 'cancelled',
                        'gray' => 'completed',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'active' => 'Actif',
                        'paused' => 'En pause',
                        'cancelled' => 'Annulé',
                        'completed' => 'Terminé',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'paused' => 'En pause',
                        'cancelled' => 'Annulé',
                        'completed' => 'Terminé',
                    ]),
                Tables\Filters\SelectFilter::make('frequency')
                    ->label('Fréquence')
                    ->options([
                        'daily' => 'Quotidien',
                        'weekly' => 'Hebdomadaire',
                        'biweekly' => 'Bimensuel',
                        'monthly' => 'Mensuel',
                        'quarterly' => 'Trimestriel',
                        'yearly' => 'Annuel',
                    ]),
                Tables\Filters\SelectFilter::make('customer')
                    ->label('Client')
                    ->relationship('customer', 'name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('execute')
                        ->label('Exécuter maintenant')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Exécuter la commande récurrente')
                        ->modalDescription('Voulez-vous générer une vente maintenant ?')
                        ->action(function (RecurringOrder $record) {
                            $sale = $record->generateSale();
                            if ($sale) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Vente générée')
                                    ->body("Vente #{$sale->reference} créée avec succès")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->visible(fn (RecurringOrder $record) => $record->status === 'active'),
                    Tables\Actions\Action::make('pause')
                        ->label('Mettre en pause')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (RecurringOrder $record) => $record->update(['status' => 'paused']))
                        ->visible(fn (RecurringOrder $record) => $record->status === 'active'),
                    Tables\Actions\Action::make('resume')
                        ->label('Reprendre')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (RecurringOrder $record) => $record->update(['status' => 'active']))
                        ->visible(fn (RecurringOrder $record) => $record->status === 'paused'),
                    Tables\Actions\Action::make('cancel')
                        ->label('Annuler')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (RecurringOrder $record) => $record->update(['status' => 'cancelled']))
                        ->visible(fn (RecurringOrder $record) => in_array($record->status, ['active', 'paused'])),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Dupliquer')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function (RecurringOrder $record) {
                            $newOrder = $record->replicate(['reference', 'executions_count', 'last_execution']);
                            $newOrder->reference = 'REC-' . date('Ym') . '-' . str_pad(RecurringOrder::whereMonth('created_at', now()->month)->count() + 1, 4, '0', STR_PAD_LEFT);
                            $newOrder->status = 'active';
                            $newOrder->executions_count = 0;
                            $newOrder->last_execution = null;
                            $newOrder->next_execution = now();
                            $newOrder->save();

                            foreach ($record->items as $item) {
                                $newItem = $item->replicate();
                                $newItem->recurring_order_id = $newOrder->id;
                                $newItem->save();
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Commande dupliquée')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('pause_selected')
                        ->label('Mettre en pause')
                        ->icon('heroicon-o-pause')
                        ->action(fn ($records) => $records->each->update(['status' => 'paused']))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('activate_selected')
                        ->label('Activer')
                        ->icon('heroicon-o-play')
                        ->action(fn ($records) => $records->each->update(['status' => 'active']))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('next_execution', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SalesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringOrders::route('/'),
            'create' => Pages\CreateRecurringOrder::route('/create'),
            'view' => Pages\ViewRecurringOrder::route('/{record}'),
            'edit' => Pages\EditRecurringOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'active')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}

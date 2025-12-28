<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Multi-Entrepôts';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Inventaires';
    protected static ?string $modelLabel = 'Inventaire';
    protected static ?string $pluralModelLabel = 'Inventaires';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'inventaire')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label('Référence')
                            ->default(fn () => Inventory::generateReference(filament()->getTenant()->id))
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Inventaire mensuel Mars 2024'),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Entrepôt')
                            ->required()
                            ->options(fn () => Warehouse::query()
                                ->where('company_id', filament()->getTenant()->id)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable(),
                        Forms\Components\Select::make('type')
                            ->label('Type d\'inventaire')
                            ->required()
                            ->options([
                                'full' => 'Complet',
                                'partial' => 'Partiel',
                                'cycle' => 'Cyclique',
                            ])
                            ->default('full')
                            ->helperText('Complet: tous les produits. Partiel: produits sélectionnés. Cyclique: rotation ABC.'),
                        Forms\Components\DatePicker::make('inventory_date')
                            ->label('Date d\'inventaire')
                            ->default(now())
                            ->required(),
                    ])->columns(2),

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Entrepôt')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'full' => 'Complet',
                        'partial' => 'Partiel',
                        'cycle' => 'Cyclique',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'full',
                        'warning' => 'partial',
                        'info' => 'cycle',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Brouillon',
                        'in_progress' => 'En cours',
                        'pending_validation' => 'À valider',
                        'validated' => 'Validé',
                        'cancelled' => 'Annulé',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'draft',
                        'info' => 'in_progress',
                        'warning' => 'pending_validation',
                        'success' => 'validated',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('inventory_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('progress_percent')
                    ->label('Progression')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state > 0 ? 'warning' : 'gray')),
                Tables\Columns\TextColumn::make('discrepancies_count')
                    ->label('Écarts')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('value_difference')
                    ->label('Différence valeur')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label('Créé par')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->multiple()
                    ->options([
                        'draft' => 'Brouillon',
                        'in_progress' => 'En cours',
                        'pending_validation' => 'À valider',
                        'validated' => 'Validé',
                        'cancelled' => 'Annulé',
                    ]),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Entrepôt')
                    ->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'full' => 'Complet',
                        'partial' => 'Partiel',
                        'cycle' => 'Cyclique',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn (Inventory $record) => $record->status === 'draft'),
                    Tables\Actions\Action::make('start')
                        ->label('Démarrer')
                        ->icon('heroicon-o-play')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Inventory $record) {
                            try {
                                $record->start();
                                Notification::make()
                                    ->title('Inventaire démarré')
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
                        ->visible(fn (Inventory $record) => $record->status === 'draft'),
                    Tables\Actions\Action::make('count')
                        ->label('Compter')
                        ->icon('heroicon-o-calculator')
                        ->color('primary')
                        ->url(fn (Inventory $record) => static::getUrl('count', ['record' => $record]))
                        ->visible(fn (Inventory $record) => $record->status === 'in_progress'),
                    Tables\Actions\Action::make('validate')
                        ->label('Valider')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Cette action va appliquer les ajustements de stock. Cette opération est irréversible.')
                        ->action(function (Inventory $record) {
                            try {
                                $record->validate();
                                Notification::make()
                                    ->title('Inventaire validé')
                                    ->body('Les ajustements de stock ont été appliqués.')
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
                        ->visible(fn (Inventory $record) => $record->status === 'pending_validation'),
                    Tables\Actions\Action::make('cancel')
                        ->label('Annuler')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Inventory $record) {
                            try {
                                $record->cancel();
                                Notification::make()
                                    ->title('Inventaire annulé')
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
                        ->visible(fn (Inventory $record) => !in_array($record->status, ['validated', 'cancelled'])),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (Inventory $record) => $record->status === 'draft'),
                ]),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Informations de l\'inventaire')
                    ->schema([
                        Components\TextEntry::make('reference')
                            ->label('Référence')
                            ->copyable(),
                        Components\TextEntry::make('name')
                            ->label('Nom'),
                        Components\TextEntry::make('warehouse.name')
                            ->label('Entrepôt'),
                        Components\TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'full' => 'Complet',
                                'partial' => 'Partiel',
                                'cycle' => 'Cyclique',
                                default => $state,
                            }),
                        Components\TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn ($state) => match($state) {
                                'draft' => 'Brouillon',
                                'in_progress' => 'En cours',
                                'pending_validation' => 'À valider',
                                'validated' => 'Validé',
                                'cancelled' => 'Annulé',
                                default => $state,
                            })
                            ->color(fn ($state) => match($state) {
                                'draft' => 'gray',
                                'in_progress' => 'info',
                                'pending_validation' => 'warning',
                                'validated' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('inventory_date')
                            ->label('Date')
                            ->date('d/m/Y'),
                    ])->columns(3),

                Components\Section::make('Progression')
                    ->schema([
                        Components\TextEntry::make('total_items')
                            ->label('Total articles')
                            ->badge(),
                        Components\TextEntry::make('items_counted')
                            ->label('Articles comptés')
                            ->badge()
                            ->color('info'),
                        Components\TextEntry::make('discrepancies_count')
                            ->label('Écarts trouvés')
                            ->badge()
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                        Components\TextEntry::make('progress_percent')
                            ->label('Progression')
                            ->formatStateUsing(fn ($state) => $state . '%')
                            ->badge()
                            ->color(fn ($state) => $state >= 100 ? 'success' : 'warning'),
                    ])->columns(4),

                Components\Section::make('Valeurs')
                    ->schema([
                        Components\TextEntry::make('total_value_expected')
                            ->label('Valeur attendue')
                            ->money(fn () => \Filament\Facades\Filament::getTenant()->currency),
                        Components\TextEntry::make('total_value_counted')
                            ->label('Valeur comptée')
                            ->money(fn () => \Filament\Facades\Filament::getTenant()->currency),
                        Components\TextEntry::make('value_difference')
                            ->label('Différence')
                            ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                            ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),
                    ])->columns(3),

                Components\Section::make('Suivi')
                    ->schema([
                        Components\TextEntry::make('createdByUser.name')
                            ->label('Créé par')
                            ->placeholder('-'),
                        Components\TextEntry::make('validatedByUser.name')
                            ->label('Validé par')
                            ->placeholder('-'),
                        Components\TextEntry::make('validated_at')
                            ->label('Date validation')
                            ->date('d/m/Y H:i')
                            ->placeholder('-'),
                    ])->columns(3),
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
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'view' => Pages\ViewInventory::route('/{record}'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
            'count' => Pages\CountInventory::route('/{record}/count'),
        ];
    }
}

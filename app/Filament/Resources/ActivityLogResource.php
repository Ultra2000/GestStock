<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Historique des modifications';

    protected static ?string $modelLabel = 'Activité';

    protected static ?string $pluralModelLabel = 'Historique';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 100;

    // Désactiver le tenant ownership automatique car on filtre manuellement par company_id
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->label('Description')
                    ->disabled(),

                Forms\Components\TextInput::make('subject_type')
                    ->label('Type d\'entité')
                    ->disabled(),

                Forms\Components\TextInput::make('subject_id')
                    ->label('ID entité')
                    ->disabled(),

                Forms\Components\TextInput::make('causer_id')
                    ->label('Utilisateur')
                    ->disabled(),

                Forms\Components\KeyValue::make('properties')
                    ->label('Modifications')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('log_name')
                    ->label('Module')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sales' => 'success',
                        'purchases' => 'warning',
                        'products' => 'info',
                        'customers' => 'primary',
                        'suppliers' => 'danger',
                        'users' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sales' => 'Ventes',
                        'purchases' => 'Achats',
                        'products' => 'Produits',
                        'customers' => 'Clients',
                        'suppliers' => 'Fournisseurs',
                        'users' => 'Utilisateurs',
                        default => ucfirst($state),
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Action')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Entité')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? class_basename($state) : '-'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable()
                    ->default('Système'),

                Tables\Columns\TextColumn::make('event')
                    ->label('Événement')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Créé',
                        'updated' => 'Modifié',
                        'deleted' => 'Supprimé',
                        default => ucfirst($state),
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Module')
                    ->options([
                        'sales' => 'Ventes',
                        'purchases' => 'Achats',
                        'products' => 'Produits',
                        'customers' => 'Clients',
                        'suppliers' => 'Fournisseurs',
                        'users' => 'Utilisateurs',
                    ]),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Action')
                    ->options([
                        'created' => 'Créé',
                        'updated' => 'Modifié',
                        'deleted' => 'Supprimé',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Détails de l\'activité')
                    ->modalContent(fn (Activity $record) => view('filament.resources.activity-log.view', ['record' => $record])),
            ])
            ->bulkActions([
                // Pas de suppression en masse pour l'audit trail
            ])
            ->modifyQueryUsing(function ($query) {
                // Filtrer par company_id du tenant
                $companyId = filament()->getTenant()->id;
                return $query->where('company_id', $companyId);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Pas de création manuelle
    }

    public static function canEdit($record): bool
    {
        return false; // Pas de modification
    }

    public static function canDelete($record): bool
    {
        return false; // Pas de suppression (audit trail)
    }
}

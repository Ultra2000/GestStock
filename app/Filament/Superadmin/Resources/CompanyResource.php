<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\CompanyResource\Pages;
use App\Filament\Superadmin\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('registration_number')
                    ->label('SIRET')
                    ->maxLength(255),
                Forms\Components\Select::make('currency')
                    ->options([
                        'XOF' => 'XOF',
                        'EUR' => 'EUR',
                        'USD' => 'USD',
                    ])
                    ->default('EUR'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Compte Actif')
                    ->default(true)
                    ->helperText('Désactiver pour suspendre l\'accès à cette entreprise.'),
                Forms\Components\Section::make('Modules')
                    ->schema([
                        Forms\Components\Toggle::make('settings.modules.pos')
                            ->label('Point de Vente'),
                        Forms\Components\Toggle::make('settings.modules.stock')
                            ->label('Stock'),
                        Forms\Components\Toggle::make('settings.modules.hr')
                            ->label('RH'),
                        Forms\Components\Toggle::make('settings.modules.accounting')
                            ->label('Comptabilité'),
                        Forms\Components\Toggle::make('settings.modules.banking')
                            ->label('Banque'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Abonnement')
                    ->badge()
                    ->color(fn (string $value): string => match ($value) {
                        'active'   => 'success',
                        'trial'    => 'info',
                        'past_due' => 'warning',
                        'expired'  => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $value): string => match ($value) {
                        'active'   => 'Actif',
                        'trial'    => 'Évaluation',
                        'past_due' => 'Paiement échoué',
                        'expired'  => 'Expiré',
                        default    => $value,
                    }),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Fin d\'évaluation')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Actif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\Action::make('extend_trial')
                    ->label('Prolonger')
                    ->icon('heroicon-o-calendar-days')
                    ->color('info')
                    ->visible(fn (Company $record) => in_array($record->subscription_status, ['trial', 'expired']))
                    ->form([
                        Forms\Components\TextInput::make('days')
                            ->label('Nombre de jours à ajouter')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->default(30)
                            ->required()
                            ->suffix('jours'),
                    ])
                    ->action(function (Company $record, array $data): void {
                        $base = ($record->trial_ends_at && $record->trial_ends_at->isFuture())
                            ? $record->trial_ends_at
                            : now();

                        $record->forceFill([
                            'subscription_status' => 'trial',
                            'subscription_plan'   => 'trial',
                            'trial_ends_at'       => $base->addDays((int) $data['days']),
                        ])->save();

                        Notification::make()
                            ->title('Évaluation prolongée de ' . $data['days'] . ' jours')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('end_trial')
                    ->label('Terminer l\'éval.')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Company $record) => $record->subscription_status === 'trial')
                    ->requiresConfirmation()
                    ->modalHeading('Terminer la période d\'évaluation')
                    ->modalDescription(fn (Company $record) => "L'accès de {$record->name} sera bloqué immédiatement et ils devront souscrire un abonnement.")
                    ->modalSubmitActionLabel('Oui, terminer maintenant')
                    ->action(function (Company $record): void {
                        $record->forceFill([
                            'trial_ends_at' => now()->subSecond(),
                        ])->save();

                        Notification::make()
                            ->title('Période d\'évaluation terminée')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('login_as')
                    ->label('Gérer')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->url(fn (Company $record) => url('/admin/' . $record->slug))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}

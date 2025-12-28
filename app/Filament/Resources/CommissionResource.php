<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionResource\Pages;
use App\Models\Commission;
use App\Models\Employee;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationGroup = 'Ressources Humaines';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Commissions';

    protected static ?string $modelLabel = 'Commission';

    protected static ?string $pluralModelLabel = 'Commissions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->relationship('employee', 'first_name', fn ($query) => $query->where('company_id', Filament::getTenant()?->id))
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('sale_id')
                            ->label('Vente associée')
                            ->relationship('sale', 'invoice_number', fn ($query) => $query->where('company_id', Filament::getTenant()?->id))
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'paid' => 'Payée',
                                'cancelled' => 'Annulée',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Période et montants')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Début de période')
                            ->required(),
                        Forms\Components\DatePicker::make('period_end')
                            ->label('Fin de période')
                            ->required()
                            ->afterOrEqual('period_start'),
                        Forms\Components\TextInput::make('sale_amount')
                            ->label('Montant des ventes')
                            ->numeric()
                            ->prefix('€')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $rate = $get('commission_rate') ?? 0;
                                $set('commission_amount', round($state * ($rate / 100), 2));
                            }),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Taux de commission (%)')
                            ->numeric()
                            ->suffix('%')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $amount = $get('sale_amount') ?? 0;
                                $set('commission_amount', round($amount * ($state / 100), 2));
                            }),
                        Forms\Components\TextInput::make('commission_amount')
                            ->label('Montant de la commission')
                            ->numeric()
                            ->prefix('€')
                            ->required(),
                        Forms\Components\DatePicker::make('paid_at')
                            ->label('Date de paiement')
                            ->nullable()
                            ->visible(fn (Forms\Get $get) => $get('status') === 'paid'),
                    ])->columns(3),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => "{$record->employee->first_name} {$record->employee->last_name}")
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Période')
                    ->formatStateUsing(fn ($record) => $record->period_start && $record->period_end 
                        ? $record->period_start->format('d/m/Y') . ' - ' . $record->period_end->format('d/m/Y')
                        : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_amount')
                    ->label('Ventes')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Taux')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('EUR')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('EUR')),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'paid' => 'Payée',
                        'cancelled' => 'Annulée',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Payée le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'paid' => 'Payée',
                        'cancelled' => 'Annulée',
                    ]),
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'first_name', fn ($query) => $query->where('company_id', Filament::getTenant()?->id))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('period')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('to')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('period_start', '>=', $date))
                            ->when($data['to'], fn ($q, $date) => $q->whereDate('period_end', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'approved']))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('pay')
                    ->label('Marquer payée')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->action(fn ($record) => $record->update(['status' => 'paid', 'paid_at' => now()]))
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approuver la sélection')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['status' => 'approved']))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('pay_selected')
                        ->label('Marquer payées')
                        ->icon('heroicon-o-banknotes')
                        ->action(fn ($records) => $records->each->update(['status' => 'paid', 'paid_at' => now()]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'edit' => Pages\EditCommission::route('/{record}/edit'),
        ];
    }
}

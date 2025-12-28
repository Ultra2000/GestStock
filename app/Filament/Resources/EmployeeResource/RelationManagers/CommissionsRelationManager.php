<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    protected static ?string $title = 'Commissions';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Début période')
                            ->required(),
                        Forms\Components\DatePicker::make('period_end')
                            ->label('Fin période')
                            ->required(),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('sale_amount')
                            ->label('Montant des ventes')
                            ->numeric()
                            ->prefix('€')
                            ->required(),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Taux')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                        Forms\Components\TextInput::make('commission_amount')
                            ->label('Commission')
                            ->numeric()
                            ->prefix('€')
                            ->required(),
                    ]),
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
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Période')
                    ->formatStateUsing(fn ($record) => $record->period_start && $record->period_end 
                        ? $record->period_start->format('d/m/Y') . ' - ' . $record->period_end->format('d/m/Y')
                        : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_amount')
                    ->label('Ventes')
                    ->money('EUR'),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Taux')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Commission')
                    ->money('EUR')
                    ->weight('bold'),
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
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvée',
                        'paid' => 'Payée',
                        'cancelled' => 'Annulée',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = $this->ownerRecord->company_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn ($record) => $record->approve())
                    ->visible(fn ($record) => $record->status === 'pending'),
                Tables\Actions\Action::make('pay')
                    ->label('Marquer payée')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->action(fn ($record) => $record->markAsPaid())
                    ->visible(fn ($record) => $record->status === 'approved'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('period_start', 'desc');
    }
}

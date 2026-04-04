<?php

namespace App\Filament\Resources\RecurringOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sales';

    protected static ?string $title = 'Ventes générées';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('invoice_number')
                ->required()
                ->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Facture')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'warning',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                        default     => 'En attente',
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.app.resources.sales.edit', [
                        'record' => $record,
                        'tenant' => \Filament\Facades\Filament::getTenant(),
                    ])),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }
}

<?php

namespace App\Filament\Resources\EmployeeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Pointages';

    protected static ?string $recordTitleAttribute = 'date';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required(),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TimePicker::make('clock_in')
                            ->label('Entrée')
                            ->seconds(false),
                        Forms\Components\TimePicker::make('clock_out')
                            ->label('Sortie')
                            ->seconds(false),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TimePicker::make('break_start')
                            ->label('Début pause')
                            ->seconds(false),
                        Forms\Components\TimePicker::make('break_end')
                            ->label('Fin pause')
                            ->seconds(false),
                    ]),
                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options([
                        'present' => 'Présent',
                        'absent' => 'Absent',
                        'late' => 'En retard',
                        'half_day' => 'Demi-journée',
                        'holiday' => 'Congé',
                        'sick' => 'Maladie',
                        'remote' => 'Télétravail',
                    ])
                    ->default('present')
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
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('clock_in')
                    ->label('Entrée')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('clock_out')
                    ->label('Sortie')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Durée'),
                Tables\Columns\TextColumn::make('overtime_hours')
                    ->label('Heures sup.')
                    ->suffix('h')
                    ->color('warning'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'present',
                        'danger' => 'absent',
                        'warning' => 'late',
                        'info' => fn ($state) => in_array($state, ['half_day', 'remote']),
                        'primary' => 'holiday',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'present' => 'Présent',
                        'absent' => 'Absent',
                        'late' => 'Retard',
                        'half_day' => '½ journée',
                        'holiday' => 'Congé',
                        'sick' => 'Maladie',
                        'remote' => 'Télétravail',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Présent',
                        'absent' => 'Absent',
                        'late' => 'En retard',
                        'holiday' => 'Congé',
                        'sick' => 'Maladie',
                        'remote' => 'Télétravail',
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }
}

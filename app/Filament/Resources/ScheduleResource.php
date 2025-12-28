<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Ressources Humaines';

    protected static ?string $navigationLabel = 'Horaires';

    protected static ?string $modelLabel = 'Horaire';

    protected static ?string $pluralModelLabel = 'Horaires';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        return $user->isAdmin() || $user->hasPermission('schedule.view') || $user->hasPermission('schedule.manage');
    }

    public static function form(Form $form): Form
    {
        $companyId = Filament::getTenant()?->id;

        return $form
            ->schema([
                Forms\Components\Section::make('Informations du créneau')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->options(
                                Employee::where('company_id', $companyId)
                                    ->where('status', 'active')
                                    ->get()
                                    ->pluck('full_name', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Horaire récurrent')
                            ->helperText('Si activé, cet horaire se répète chaque semaine')
                            ->live()
                            ->default(false),

                        Forms\Components\DatePicker::make('date')
                            ->label('Date')
                            ->required(fn ($get) => !$get('is_recurring'))
                            ->visible(fn ($get) => !$get('is_recurring')),

                        Forms\Components\Select::make('day_of_week')
                            ->label('Jour de la semaine')
                            ->options([
                                1 => 'Lundi',
                                2 => 'Mardi',
                                3 => 'Mercredi',
                                4 => 'Jeudi',
                                5 => 'Vendredi',
                                6 => 'Samedi',
                                7 => 'Dimanche',
                            ])
                            ->required(fn ($get) => $get('is_recurring'))
                            ->visible(fn ($get) => $get('is_recurring')),
                    ])->columns(2),

                Forms\Components\Section::make('Horaires')
                    ->schema([
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Heure de début')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(15)
                            ->default('09:00'),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Heure de fin')
                            ->required()
                            ->seconds(false)
                            ->minutesStep(15)
                            ->default('17:00')
                            ->after('start_time'),

                        Forms\Components\TimePicker::make('break_duration')
                            ->label('Durée de pause')
                            ->seconds(false)
                            ->default('01:00')
                            ->helperText('Pause déjeuner, etc.'),

                        Forms\Components\Select::make('shift_type')
                            ->label('Type de poste')
                            ->options([
                                'morning' => 'Matin',
                                'afternoon' => 'Après-midi',
                                'night' => 'Nuit',
                                'full_day' => 'Journée complète',
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\TextInput::make('position')
                            ->label('Poste / Station')
                            ->placeholder('Ex: Caisse 1, Rayon fruits...')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('location')
                            ->label('Lieu')
                            ->placeholder('Ex: Magasin principal, Entrepôt...')
                            ->maxLength(255),

                        Forms\Components\ColorPicker::make('color')
                            ->label('Couleur'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Publié')
                            ->helperText('Visible par l\'employé')
                            ->default(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Récurrent'),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Jour')
                    ->formatStateUsing(fn ($state) => match($state) {
                        1 => 'Lundi',
                        2 => 'Mardi',
                        3 => 'Mercredi',
                        4 => 'Jeudi',
                        5 => 'Vendredi',
                        6 => 'Samedi',
                        7 => 'Dimanche',
                        default => '-',
                    })
                    ->visible(fn () => false), // Caché par défaut, visible via toggle

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Début')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Fin')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('hours')
                    ->label('Heures')
                    ->suffix('h')
                    ->numeric(1),

                Tables\Columns\BadgeColumn::make('shift_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'morning' => 'Matin',
                        'afternoon' => 'Après-midi',
                        'night' => 'Nuit',
                        'full_day' => 'Journée',
                        default => $state ?? '-',
                    })
                    ->colors([
                        'info' => 'morning',
                        'warning' => 'afternoon',
                        'gray' => 'night',
                        'success' => 'full_day',
                    ]),

                Tables\Columns\TextColumn::make('position')
                    ->label('Poste')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Publié')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Employé')
                    ->relationship('employee', 'first_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('shift_type')
                    ->label('Type de poste')
                    ->options([
                        'morning' => 'Matin',
                        'afternoon' => 'Après-midi',
                        'night' => 'Nuit',
                        'full_day' => 'Journée complète',
                    ]),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Publié'),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publier')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_published)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->publish()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish_selected')
                        ->label('Publier la sélection')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->publish())
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }
}

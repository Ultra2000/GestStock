<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleTemplateResource\Pages;
use App\Models\ScheduleTemplate;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduleTemplateResource extends Resource
{
    protected static ?string $model = ScheduleTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'RH';

    protected static ?string $navigationLabel = 'Templates Planning';

    protected static ?string $modelLabel = 'Template de Planning';

    protected static ?string $pluralModelLabel = 'Templates de Planning';

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('hr') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom du template')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Semaine standard, Mi-temps matin...'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('Description optionnelle du template'),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Template par défaut')
                            ->helperText('Ce template sera proposé en premier lors de la création de plannings'),
                    ]),

                Forms\Components\Section::make('Horaires par jour')
                    ->description('Définissez les horaires pour chaque jour de la semaine. Laissez vide pour les jours de repos.')
                    ->schema([
                        // Lundi
                        Forms\Components\Fieldset::make('Lundi')
                            ->schema([
                                Forms\Components\TimePicker::make('schedule_data.1.start_time')
                                    ->label('Début')
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('schedule_data.1.end_time')
                                    ->label('Fin')
                                    ->seconds(false),
                                Forms\Components\Select::make('schedule_data.1.break_duration')
                                    ->label('Pause')
                                    ->options([
                                        '00:00:00' => 'Pas de pause',
                                        '00:30:00' => '30 min',
                                        '00:45:00' => '45 min',
                                        '01:00:00' => '1h',
                                        '01:30:00' => '1h30',
                                        '02:00:00' => '2h',
                                    ])
                                    ->default('01:00:00'),
                                Forms\Components\Select::make('schedule_data.1.shift_type')
                                    ->label('Type')
                                    ->options([
                                        'morning' => 'Matin',
                                        'afternoon' => 'Après-midi',
                                        'evening' => 'Soir',
                                        'night' => 'Nuit',
                                        'full_day' => 'Journée',
                                    ]),
                            ])
                            ->columns(4),

                        // Mardi
                        Forms\Components\Fieldset::make('Mardi')
                            ->schema([
                                Forms\Components\TimePicker::make('schedule_data.2.start_time')
                                    ->label('Début')
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('schedule_data.2.end_time')
                                    ->label('Fin')
                                    ->seconds(false),
                                Forms\Components\Select::make('schedule_data.2.break_duration')
                                    ->label('Pause')
                                    ->options([
                                        '00:00:00' => 'Pas de pause',
                                        '00:30:00' => '30 min',
                                        '00:45:00' => '45 min',
                                        '01:00:00' => '1h',
                                        '01:30:00' => '1h30',
                                        '02:00:00' => '2h',
                                    ])
                                    ->default('01:00:00'),
                                Forms\Components\Select::make('schedule_data.2.shift_type')
                                    ->label('Type')
                                    ->options([
                                        'morning' => 'Matin',
                                        'afternoon' => 'Après-midi',
                                        'evening' => 'Soir',
                                        'night' => 'Nuit',
                                        'full_day' => 'Journée',
                                    ]),
                            ])
                            ->columns(4),

                        // Mercredi
                        Forms\Components\Fieldset::make('Mercredi')
                            ->schema([
                                Forms\Components\TimePicker::make('schedule_data.3.start_time')
                                    ->label('Début')
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('schedule_data.3.end_time')
                                    ->label('Fin')
                                    ->seconds(false),
                                Forms\Components\Select::make('schedule_data.3.break_duration')
                                    ->label('Pause')
                                    ->options([
                                        '00:00:00' => 'Pas de pause',
                                        '00:30:00' => '30 min',
                                        '00:45:00' => '45 min',
                                        '01:00:00' => '1h',
                                        '01:30:00' => '1h30',
                                        '02:00:00' => '2h',
                                    ])
                                    ->default('01:00:00'),
                                Forms\Components\Select::make('schedule_data.3.shift_type')
                                    ->label('Type')
                                    ->options([
                                        'morning' => 'Matin',
                                        'afternoon' => 'Après-midi',
                                        'evening' => 'Soir',
                                        'night' => 'Nuit',
                                        'full_day' => 'Journée',
                                    ]),
                            ])
                            ->columns(4),

                        // Jeudi
                        Forms\Components\Fieldset::make('Jeudi')
                            ->schema([
                                Forms\Components\TimePicker::make('schedule_data.4.start_time')
                                    ->label('Début')
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('schedule_data.4.end_time')
                                    ->label('Fin')
                                    ->seconds(false),
                                Forms\Components\Select::make('schedule_data.4.break_duration')
                                    ->label('Pause')
                                    ->options([
                                        '00:00:00' => 'Pas de pause',
                                        '00:30:00' => '30 min',
                                        '00:45:00' => '45 min',
                                        '01:00:00' => '1h',
                                        '01:30:00' => '1h30',
                                        '02:00:00' => '2h',
                                    ])
                                    ->default('01:00:00'),
                                Forms\Components\Select::make('schedule_data.4.shift_type')
                                    ->label('Type')
                                    ->options([
                                        'morning' => 'Matin',
                                        'afternoon' => 'Après-midi',
                                        'evening' => 'Soir',
                                        'night' => 'Nuit',
                                        'full_day' => 'Journée',
                                    ]),
                            ])
                            ->columns(4),

                        // Vendredi
                        Forms\Components\Fieldset::make('Vendredi')
                            ->schema([
                                Forms\Components\TimePicker::make('schedule_data.5.start_time')
                                    ->label('Début')
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('schedule_data.5.end_time')
                                    ->label('Fin')
                                    ->seconds(false),
                                Forms\Components\Select::make('schedule_data.5.break_duration')
                                    ->label('Pause')
                                    ->options([
                                        '00:00:00' => 'Pas de pause',
                                        '00:30:00' => '30 min',
                                        '00:45:00' => '45 min',
                                        '01:00:00' => '1h',
                                        '01:30:00' => '1h30',
                                        '02:00:00' => '2h',
                                    ])
                                    ->default('01:00:00'),
                                Forms\Components\Select::make('schedule_data.5.shift_type')
                                    ->label('Type')
                                    ->options([
                                        'morning' => 'Matin',
                                        'afternoon' => 'Après-midi',
                                        'evening' => 'Soir',
                                        'night' => 'Nuit',
                                        'full_day' => 'Journée',
                                    ]),
                            ])
                            ->columns(4),

                        // Samedi
                        Forms\Components\Fieldset::make('Samedi')
                            ->schema([
                                Forms\Components\TimePicker::make('schedule_data.6.start_time')
                                    ->label('Début')
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('schedule_data.6.end_time')
                                    ->label('Fin')
                                    ->seconds(false),
                                Forms\Components\Select::make('schedule_data.6.break_duration')
                                    ->label('Pause')
                                    ->options([
                                        '00:00:00' => 'Pas de pause',
                                        '00:30:00' => '30 min',
                                        '00:45:00' => '45 min',
                                        '01:00:00' => '1h',
                                        '01:30:00' => '1h30',
                                        '02:00:00' => '2h',
                                    ])
                                    ->default('01:00:00'),
                                Forms\Components\Select::make('schedule_data.6.shift_type')
                                    ->label('Type')
                                    ->options([
                                        'morning' => 'Matin',
                                        'afternoon' => 'Après-midi',
                                        'evening' => 'Soir',
                                        'night' => 'Nuit',
                                        'full_day' => 'Journée',
                                    ]),
                            ])
                            ->columns(4),

                        // Dimanche
                        Forms\Components\Fieldset::make('Dimanche')
                            ->schema([
                                Forms\Components\TimePicker::make('schedule_data.7.start_time')
                                    ->label('Début')
                                    ->seconds(false),
                                Forms\Components\TimePicker::make('schedule_data.7.end_time')
                                    ->label('Fin')
                                    ->seconds(false),
                                Forms\Components\Select::make('schedule_data.7.break_duration')
                                    ->label('Pause')
                                    ->options([
                                        '00:00:00' => 'Pas de pause',
                                        '00:30:00' => '30 min',
                                        '00:45:00' => '45 min',
                                        '01:00:00' => '1h',
                                        '01:30:00' => '1h30',
                                        '02:00:00' => '2h',
                                    ])
                                    ->default('01:00:00'),
                                Forms\Components\Select::make('schedule_data.7.shift_type')
                                    ->label('Type')
                                    ->options([
                                        'morning' => 'Matin',
                                        'afternoon' => 'Après-midi',
                                        'evening' => 'Soir',
                                        'night' => 'Nuit',
                                        'full_day' => 'Journée',
                                    ]),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('summary')
                    ->label('Aperçu')
                    ->wrap(),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Heures/semaine')
                    ->suffix('h')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Par défaut')
                    ->boolean(),

                Tables\Columns\TextColumn::make('schedules_count')
                    ->label('Utilisations')
                    ->counts('schedules'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Par défaut'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScheduleTemplates::route('/'),
            'create' => Pages\CreateScheduleTemplate::route('/create'),
            'edit' => Pages\EditScheduleTemplate::route('/{record}/edit'),
        ];
    }
}

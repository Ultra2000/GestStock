<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleTemplateResource\Pages;
use App\Models\ScheduleTemplate;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ScheduleTemplateResource extends Resource
{
    protected static ?string $model = ScheduleTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'RH';

    protected static ?string $navigationLabel = 'Templates Planning';

    protected static ?string $modelLabel = 'Template de Planning';

    protected static ?string $pluralModelLabel = 'Templates de Planning';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->isAdmin() || $user->hasPermission('schedule.view') || $user->hasPermission('schedule.manage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! static::canAccess()) {
            return false;
        }

        return Filament::getTenant()?->isModuleEnabled('hr') ?? false;
    }

    /**
     * Options de durée de pause réutilisables
     */
    protected static function breakDurationOptions(): array
    {
        return [
            '00:00:00' => 'Pas de pause',
            '00:30:00' => '30 min',
            '00:45:00' => '45 min',
            '01:00:00' => '1h',
            '01:30:00' => '1h30',
            '02:00:00' => '2h',
        ];
    }

    /**
     * Options de type de shift réutilisables
     */
    protected static function shiftTypeOptions(): array
    {
        return [
            'morning' => 'Matin',
            'afternoon' => 'Après-midi',
            'evening' => 'Soir',
            'night' => 'Nuit',
            'full_day' => 'Journée',
        ];
    }

    /**
     * Génère un fieldset pour un jour de la semaine
     */
    protected static function dayFieldset(string $label, int $dayNumber): Forms\Components\Fieldset
    {
        return Forms\Components\Fieldset::make($label)
            ->schema([
                Forms\Components\TimePicker::make("schedule_data.{$dayNumber}.start_time")
                    ->label('Début')
                    ->seconds(false),
                Forms\Components\TimePicker::make("schedule_data.{$dayNumber}.end_time")
                    ->label('Fin')
                    ->seconds(false)
                    ->after("schedule_data.{$dayNumber}.start_time")
                    ->validationMessages([
                        'after' => 'L\'heure de fin doit être après l\'heure de début.',
                    ]),
                Forms\Components\Select::make("schedule_data.{$dayNumber}.break_duration")
                    ->label('Pause')
                    ->options(static::breakDurationOptions())
                    ->default('01:00:00'),
                Forms\Components\Select::make("schedule_data.{$dayNumber}.shift_type")
                    ->label('Type')
                    ->options(static::shiftTypeOptions()),
            ])
            ->columns(4);
    }

    public static function form(Form $form): Form
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

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
                            ->helperText('Ce template sera proposé en premier lors de la création de plannings. Les autres templates par défaut seront automatiquement décochés.'),
                    ]),

                Forms\Components\Section::make('Horaires par jour')
                    ->description('Définissez les horaires pour chaque jour de la semaine. Laissez vide pour les jours de repos.')
                    ->schema(
                        collect($days)->map(
                            fn (string $label, int $dayNumber) => static::dayFieldset($label, $dayNumber)
                        )->values()->all()
                    ),
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

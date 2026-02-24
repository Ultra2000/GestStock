<?php

namespace App\Filament\Superadmin\Resources;

use App\Filament\Superadmin\Resources\TutorialVideoResource\Pages;
use App\Models\TutorialVideo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TutorialVideoResource extends Resource
{
    protected static ?string $model = TutorialVideo::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'Videos Tutoriels';
    protected static ?string $modelLabel = 'Video Tutoriel';
    protected static ?string $pluralModelLabel = 'Videos Tutoriels';
    protected static ?string $navigationGroup = 'Contenu';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('section')
                            ->label('Section du guide')
                            ->options(TutorialVideo::SECTIONS)
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('video_type')
                            ->label('Type de video')
                            ->options([
                                'youtube' => 'YouTube',
                                'vimeo' => 'Vimeo',
                                'direct' => 'URL directe (MP4, etc.)',
                            ])
                            ->default('youtube')
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('video_url')
                            ->label('URL de la video')
                            ->required()
                            ->url()
                            ->maxLength(500)
                            ->placeholder(fn (Forms\Get $get) => match ($get('video_type')) {
                                'youtube' => 'https://www.youtube.com/watch?v=...',
                                'vimeo' => 'https://vimeo.com/...',
                                default => 'https://example.com/video.mp4',
                            })
                            ->helperText(fn (Forms\Get $get) => match ($get('video_type')) {
                                'youtube' => 'Formats acceptes : youtube.com/watch?v=ID, youtu.be/ID',
                                'vimeo' => 'Format accepte : vimeo.com/ID',
                                default => 'URL directe vers un fichier video',
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('thumbnail_url')
                            ->label('URL de la miniature')
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://img.youtube.com/vi/.../hqdefault.jpg')
                            ->helperText('Optionnel. Pour YouTube, la miniature est generee automatiquement.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Options')
                    ->schema([
                        Forms\Components\TextInput::make('duration_seconds')
                            ->label('Duree (secondes)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(36000)
                            ->placeholder('Ex: 320 pour 5min 20s')
                            ->helperText('Optionnel. Sera affiche comme "5:20" aux utilisateurs.'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Les videos sont triees par ordre croissant.'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Desactivez pour masquer temporairement la video.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('section')
                    ->label('Section')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TutorialVideo::SECTIONS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'getting-started' => 'gray',
                        'dashboard' => 'info',
                        'sales' => 'success',
                        'stock' => 'warning',
                        'pos' => 'danger',
                        'accounting' => 'primary',
                        'banking' => 'info',
                        'hr' => 'success',
                        'admin' => 'gray',
                        'einvoicing' => 'warning',
                        'appendix' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('video_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'youtube' => 'YouTube',
                        'vimeo' => 'Vimeo',
                        'direct' => 'Direct',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'youtube' => 'danger',
                        'vimeo' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Duree')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Vues')
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        if ($state >= 1000000) return number_format($state / 1000000, 1) . 'M';
                        if ($state >= 1000) return number_format($state / 1000, 1) . 'k';
                        return $state;
                    })
                    ->icon('heroicon-o-eye')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cree le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('section')
                    ->label('Section')
                    ->options(TutorialVideo::SECTIONS),

                Tables\Filters\SelectFilter::make('video_type')
                    ->label('Type')
                    ->options([
                        'youtube' => 'YouTube',
                        'vimeo' => 'Vimeo',
                        'direct' => 'Direct',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTutorialVideos::route('/'),
            'create' => Pages\CreateTutorialVideo::route('/create'),
            'edit' => Pages\EditTutorialVideo::route('/{record}/edit'),
        ];
    }
}

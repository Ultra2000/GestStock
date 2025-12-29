<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceLogResource\Pages;
use App\Models\AttendanceLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceLogResource extends Resource
{
    protected static ?string $model = AttendanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'RH';
    protected static ?int $navigationSort = 99;
    protected static ?string $navigationLabel = 'Logs Pointage';
    protected static ?string $modelLabel = 'Log Pointage';
    protected static ?string $pluralModelLabel = 'Logs Pointage';
    protected static bool $shouldRegisterNavigation = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Heure')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Site')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('action')
                    ->label('Action')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'clock_in' => 'Entrée',
                        'clock_out' => 'Sortie',
                        'break_start' => 'Début pause',
                        'break_end' => 'Fin pause',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'clock_in',
                        'warning' => 'clock_out',
                        'info' => fn ($state) => in_array($state, ['break_start', 'break_end']),
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Résultat')
                    ->formatStateUsing(fn ($state) => $state === 'success' ? 'Succès' : 'Échec')
                    ->colors([
                        'success' => 'success',
                        'danger' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('failure_reason')
                    ->label('Raison échec')
                    ->formatStateUsing(fn ($record) => $record->failure_reason_label)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('distance_from_site')
                    ->label('Distance')
                    ->suffix(' m')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('qr_valid')
                    ->label('QR')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('Action')
                    ->options([
                        'clock_in' => 'Entrée',
                        'clock_out' => 'Sortie',
                        'break_start' => 'Début pause',
                        'break_end' => 'Fin pause',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Résultat')
                    ->options([
                        'success' => 'Succès',
                        'failed' => 'Échec',
                    ]),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'name')
                    ->label('Site'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Du'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Au'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceLogs::route('/'),
        ];
    }
}

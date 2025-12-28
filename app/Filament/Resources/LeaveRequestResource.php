<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Ressources Humaines';

    protected static ?string $navigationLabel = 'Congés';

    protected static ?string $modelLabel = 'Demande de congé';

    protected static ?string $pluralModelLabel = 'Demandes de congés';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Demande de congé')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Employé')
                            ->relationship('employee', 'first_name', fn ($query) => $query->where('company_id', Filament::getTenant()?->id)->where('status', '!=', 'terminated'))
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Type de congé')
                            ->options([
                                'paid' => 'Congé payé',
                                'unpaid' => 'Congé sans solde',
                                'sick' => 'Maladie',
                                'maternity' => 'Maternité',
                                'paternity' => 'Paternité',
                                'other' => 'Autre',
                            ])
                            ->default('paid')
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Date de début')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => 
                                        $set('days_count', static::calculateDays($state, $get('end_date')))),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Date de fin')
                                    ->required()
                                    ->afterOrEqual('start_date')
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set, Forms\Get $get) => 
                                        $set('days_count', static::calculateDays($get('start_date'), $state))),
                            ]),
                        Forms\Components\TextInput::make('days_count')
                            ->label('Nombre de jours')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif')
                            ->rows(3),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'rejected' => 'Refusé',
                                'cancelled' => 'Annulé',
                            ])
                            ->default('pending')
                            ->disabled(fn ($record) => !$record || $record->status !== 'pending'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif du refus')
                            ->rows(2)
                            ->visible(fn ($get) => $get('status') === 'rejected'),
                    ]),
            ]);
    }

    protected static function calculateDays($startDate, $endDate): float
    {
        if (!$startDate || !$endDate || $startDate === '-' || $endDate === '-') return 0;

        try {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            $days = 0;

            while ($start->lte($end)) {
                if (!$start->isWeekend()) {
                    $days++;
                }
                $start->addDay();
            }

            return $days;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Employé')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'unpaid',
                        'danger' => 'sick',
                        'info' => fn ($state) => in_array($state, ['maternity', 'paternity']),
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'paid' => 'Congé payé',
                        'unpaid' => 'Sans solde',
                        'sick' => 'Maladie',
                        'maternity' => 'Maternité',
                        'paternity' => 'Paternité',
                        'other' => 'Autre',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Du')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Au')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('days_count')
                    ->label('Jours')
                    ->suffix(' j')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Refusé',
                        'cancelled' => 'Annulé',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Traité par')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Demandé le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Refusé',
                        'cancelled' => 'Annulé',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'paid' => 'Congé payé',
                        'unpaid' => 'Sans solde',
                        'sick' => 'Maladie',
                        'maternity' => 'Maternité',
                        'paternity' => 'Paternité',
                        'other' => 'Autre',
                    ]),
                Tables\Filters\SelectFilter::make('employee')
                    ->label('Employé')
                    ->relationship('employee', 'first_name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->visible(fn ($record) => $record->status === 'pending'),
                    Tables\Actions\Action::make('approve')
                        ->label('Approuver')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (LeaveRequest $record) => $record->approve(auth()->id()))
                        ->visible(fn (LeaveRequest $record) => $record->status === 'pending'),
                    Tables\Actions\Action::make('reject')
                        ->label('Refuser')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Motif du refus')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(fn (LeaveRequest $record, array $data) => $record->reject(auth()->id(), $data['rejection_reason']))
                        ->visible(fn (LeaveRequest $record) => $record->status === 'pending'),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => $record->status === 'pending'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}

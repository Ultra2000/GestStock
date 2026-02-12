<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingEntryResource\Pages;
use App\Models\AccountingEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class AccountingEntryResource extends Resource
{
    protected static ?string $model = AccountingEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('accounting') ?? true;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }

    protected static ?string $navigationLabel = 'Grand Livre';

    protected static ?string $modelLabel = 'Écriture comptable';

    protected static ?string $pluralModelLabel = 'Grand Livre';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'écriture')
                    ->description('Les écritures comptables sont en lecture seule (immuabilité comptable)')
                    ->schema([
                        Forms\Components\DatePicker::make('entry_date')
                            ->label('Date')
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('piece_number')
                            ->label('N° Pièce')
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('journal_code')
                            ->label('Journal')
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('account_number')
                            ->label('N° Compte')
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('account_auxiliary')
                            ->label('Compte auxiliaire')
                            ->disabled(),

                        Forms\Components\TextInput::make('label')
                            ->label('Libellé')
                            ->required()
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('debit')
                            ->label('Débit')
                            ->numeric()
                            ->disabled()
                            ->prefix('€'),

                        Forms\Components\TextInput::make('credit')
                            ->label('Crédit')
                            ->numeric()
                            ->disabled()
                            ->prefix('€'),

                        Forms\Components\TextInput::make('vat_rate')
                            ->label('Taux TVA')
                            ->numeric()
                            ->disabled()
                            ->suffix('%'),

                        Forms\Components\TextInput::make('lettering')
                            ->label('Lettrage')
                            ->maxLength(10)
                            ->helperText('Seul le lettrage peut être modifié'),

                        Forms\Components\DatePicker::make('lettering_date')
                            ->label('Date lettrage'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Métadonnées')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('is_locked')
                            ->label('Verrouillée')
                            ->disabled(),

                        Forms\Components\TextInput::make('creation_source')
                            ->label('Source')
                            ->disabled(),

                        Forms\Components\TextInput::make('source_type')
                            ->label('Type document')
                            ->disabled(),

                        Forms\Components\TextInput::make('source_id')
                            ->label('ID document')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('journal_code')
                    ->label('Journal')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'VTE' => 'success',
                        'ACH' => 'warning',
                        'BQ' => 'info',
                        'CAI' => 'gray',
                        default => 'primary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('piece_number')
                    ->label('N° Pièce')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('account_number')
                    ->label('Compte')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->account_auxiliary),

                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->label),

                Tables\Columns\TextColumn::make('debit')
                    ->label('Débit')
                    ->money('EUR')
                    ->alignEnd()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('EUR')),

                Tables\Columns\TextColumn::make('credit')
                    ->label('Crédit')
                    ->money('EUR')
                    ->alignEnd()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('EUR')),

                Tables\Columns\TextColumn::make('lettering')
                    ->label('Lettr.')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('is_locked')
                    ->label('🔒')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->defaultSort('entry_date', 'desc')
            ->filters([
                SelectFilter::make('journal_code')
                    ->label('Journal')
                    ->options([
                        'VTE' => 'Ventes',
                        'ACH' => 'Achats',
                        'BQ' => 'Banque',
                        'CAI' => 'Caisse',
                        'OD' => 'Opérations diverses',
                    ]),

                Filter::make('account_class')
                    ->form([
                        Forms\Components\Select::make('class')
                            ->label('Classe de compte')
                            ->options([
                                '4' => 'Classe 4 - Tiers',
                                '5' => 'Classe 5 - Trésorerie',
                                '6' => 'Classe 6 - Charges',
                                '7' => 'Classe 7 - Produits',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['class'],
                            fn (Builder $query, $class): Builder => $query->where('account_number', 'like', $class . '%'),
                        );
                    }),

                Filter::make('entry_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('entry_date', '<=', $date),
                            );
                    }),

                Filter::make('unlettered')
                    ->label('Non lettrées uniquement')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereNull('lettering')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Lettrer')
                    ->icon('heroicon-o-link'),
                Tables\Actions\Action::make('reclasser')
                    ->label('Reclasser')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading('Reclasser cette écriture')
                    ->modalDescription('Créer une écriture OD pour reclasser ce montant vers un autre compte')
                    ->form([
                        Forms\Components\TextInput::make('new_account')
                            ->label('Nouveau compte')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('Ex: 706000'),
                        Forms\Components\Textarea::make('reason')
                            ->label('Motif du reclassement')
                            ->required()
                            ->placeholder('Ex: Changement de paramétrage comptable'),
                    ])
                    ->action(function (AccountingEntry $record, array $data) {
                        $pieceNumber = 'OD-' . date('Y') . '-' . str_pad(
                            AccountingEntry::where('company_id', $record->company_id)
                                ->where('piece_number', 'like', 'OD-' . date('Y') . '-%')
                                ->count() + 1,
                            5, '0', STR_PAD_LEFT
                        );

                        // Contre-passation de l'ancien compte
                        AccountingEntry::create([
                            'company_id' => $record->company_id,
                            'entry_date' => now()->toDateString(),
                            'piece_number' => $pieceNumber,
                            'journal_code' => 'OD',
                            'account_number' => $record->account_number,
                            'account_auxiliary' => $record->account_auxiliary,
                            'label' => "Reclassement {$record->piece_number} - {$data['reason']}",
                            'debit' => $record->credit, // Inverse
                            'credit' => $record->debit, // Inverse
                            'creation_source' => 'reclassement',
                            'created_by' => auth()->id(),
                        ]);

                        // Imputation sur le nouveau compte
                        AccountingEntry::create([
                            'company_id' => $record->company_id,
                            'entry_date' => now()->toDateString(),
                            'piece_number' => $pieceNumber,
                            'journal_code' => 'OD',
                            'account_number' => $data['new_account'],
                            'label' => "Reclassement depuis {$record->account_number} - {$data['reason']}",
                            'debit' => $record->debit,
                            'credit' => $record->credit,
                            'creation_source' => 'reclassement',
                            'created_by' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Reclassement effectué')
                            ->body("Écriture reclassée de {$record->account_number} vers {$data['new_account']}")
                            ->success()
                            ->send();
                    }),
                    // Le reclassement est toujours disponible car il ne modifie pas l'écriture originale
                    // mais crée de nouvelles écritures OD
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('letter')
                    ->label('Lettrer la sélection')
                    ->icon('heroicon-o-link')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('lettering_code')
                            ->label('Code de lettrage')
                            ->required()
                            ->maxLength(10)
                            ->placeholder('Ex: AA, AB, AC...'),
                    ])
                    ->action(function ($records, array $data) {
                        // Vérifier l'équilibre
                        $totalDebit = $records->sum('debit');
                        $totalCredit = $records->sum('credit');
                        
                        if (abs($totalDebit - $totalCredit) > 0.01) {
                            \Filament\Notifications\Notification::make()
                                ->title('Lettrage impossible')
                                ->body("Les écritures ne sont pas équilibrées (Débit: {$totalDebit}€, Crédit: {$totalCredit}€)")
                                ->danger()
                                ->send();
                            return;
                        }

                        foreach ($records as $record) {
                            $record->update([
                                'lettering' => $data['lettering_code'],
                                'lettering_date' => now(),
                            ]);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Lettrage effectué')
                            ->body($records->count() . ' écritures lettrées avec le code ' . $data['lettering_code'])
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Aucune écriture comptable')
            ->emptyStateDescription('Les écritures sont générées automatiquement lors de la validation des ventes et achats.')
            ->emptyStateIcon('heroicon-o-book-open');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountingEntries::route('/'),
            'view' => Pages\ViewAccountingEntry::route('/{record}'),
            'edit' => Pages\EditAccountingEntry::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Les écritures sont créées automatiquement
    }

    public static function canDelete($record): bool
    {
        return false; // Immutabilité comptable
    }
}

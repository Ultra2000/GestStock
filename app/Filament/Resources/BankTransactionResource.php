<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankTransactionResource\Pages;
use App\Filament\Resources\BankTransactionResource\RelationManagers;
use App\Models\BankTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class BankTransactionResource extends Resource
{
    protected static ?string $model = BankTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Transactions';
    protected static ?string $modelLabel = 'Transaction';
    protected static ?string $pluralModelLabel = 'Transactions';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();
        return ($tenant?->isModuleEnabled('accounting') || $tenant?->isModuleEnabled('banking')) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('bankAccount', function ($q) {
                $q->where('company_id', Filament::getTenant()?->id);
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bank_account_id')
                    ->label('Compte bancaire')
                    ->relationship('bankAccount', 'name', fn ($query) => 
                        $query->where('company_id', Filament::getTenant()?->id)
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('amount')
                    ->label('Montant')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'credit' => 'Crédit (Entrée)',
                        'debit' => 'Débit (Sortie)',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('label')
                    ->label('Libellé')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('reference')
                    ->label('Référence')
                    ->maxLength(255),
                Forms\Components\Select::make('accounting_category_id')
                    ->label('Catégorie comptable')
                    ->relationship('category', 'name', fn ($query) => 
                        $query->where('company_id', Filament::getTenant()?->id)
                    )
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'reconciled' => 'Lettré',
                    ])
                    ->required()
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bankAccount.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('EUR')
                    ->sortable()
                    ->color(fn (string $state, $record) => $record->type === 'credit' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'credit',
                        'danger' => 'debit',
                    ]),
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->category?->color ?? 'gray'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'reconciled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('applyRules')
                        ->label('Appliquer les règles auto')
                        ->icon('heroicon-o-play')
                        ->action(function (Collection $records) {
                            $count = 0;
                            $service = app(\App\Services\AccountingService::class);
                            foreach ($records as $record) {
                                if ($service->applyRules($record)) {
                                    $count++;
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->title("$count transactions catégorisées")
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListBankTransactions::route('/'),
            'create' => Pages\CreateBankTransaction::route('/create'),
            'edit' => Pages\EditBankTransaction::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankAccountResource\Pages;
use App\Filament\Resources\BankAccountResource\RelationManagers;
use App\Models\BankAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankAccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'Comptes bancaires';
    protected static ?string $modelLabel = 'Compte bancaire';
    protected static ?string $pluralModelLabel = 'Comptes bancaires';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();
        return ($tenant?->isModuleEnabled('accounting') || $tenant?->isModuleEnabled('banking')) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => Filament::getTenant()?->id),
                Forms\Components\TextInput::make('name')
                    ->label('Nom du compte')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('bank_name')
                    ->label('Banque')
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_number')
                    ->label('Numéro de compte / IBAN')
                    ->maxLength(255),
                Forms\Components\TextInput::make('currency')
                    ->label('Devise')
                    ->required()
                    ->maxLength(255)
                    ->default('EUR'),
                Forms\Components\TextInput::make('initial_balance')
                    ->label('Solde initial')
                    ->required()
                    ->numeric()
                    ->prefix('€')
                    ->default(0),
                Forms\Components\TextInput::make('current_balance')
                    ->label('Solde actuel')
                    ->numeric()
                    ->prefix('€')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_balance')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListBankAccounts::route('/'),
            'create' => Pages\CreateBankAccount::route('/create'),
            'edit' => Pages\EditBankAccount::route('/{record}/edit'),
        ];
    }
}

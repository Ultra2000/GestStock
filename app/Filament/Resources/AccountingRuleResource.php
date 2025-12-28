<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingRuleResource\Pages;
use App\Filament\Resources\AccountingRuleResource\RelationManagers;
use App\Models\AccountingRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccountingRuleResource extends Resource
{
    protected static ?string $model = AccountingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-variable';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('condition_type')
                    ->options([
                        'contains' => 'Contient',
                        'starts_with' => 'Commence par',
                        'ends_with' => 'Finit par',
                        'exact' => 'Est exactement',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('condition_value')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('accounting_category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
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
                Tables\Columns\TextColumn::make('condition_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('condition_value')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->numeric()
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
            'index' => Pages\ListAccountingRules::route('/'),
            'create' => Pages\CreateAccountingRule::route('/create'),
            'edit' => Pages\EditAccountingRule::route('/{record}/edit'),
        ];
    }
}

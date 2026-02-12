<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountingCategoryResource\Pages;
use App\Filament\Resources\AccountingCategoryResource\RelationManagers;
use App\Models\AccountingCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class AccountingCategoryResource extends Resource
{
    protected static ?string $model = AccountingCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?string $navigationLabel = 'Catégories comptables';
    protected static ?int $navigationSort = 3;

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
                    ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'income' => 'Recette',
                        'expense' => 'Dépense',
                    ])
                    ->required(),
                Forms\Components\ColorPicker::make('color'),
                Forms\Components\Toggle::make('is_system')
                    ->required(),
                Forms\Components\Select::make('parent_id')
                    ->relationship('parent', 'name', fn ($query) => 
                        $query->where('company_id', Filament::getTenant()?->id)
                    )
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ]),
                Tables\Columns\ColorColumn::make('color'),
                Tables\Columns\IconColumn::make('is_system')
                    ->boolean(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListAccountingCategories::route('/'),
            'create' => Pages\CreateAccountingCategory::route('/create'),
            'edit' => Pages\EditAccountingCategory::route('/{record}/edit'),
        ];
    }
}

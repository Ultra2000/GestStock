<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Fournisseurs';
    protected static ?string $navigationGroup = 'Stocks & Achats';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('siret')
                    ->label('SIRET')
                    ->maxLength(14)
                    ->placeholder('14 chiffres'),
                Forms\Components\TextInput::make('siren')
                    ->label('SIREN')
                    ->maxLength(9)
                    ->placeholder('9 chiffres'),
                Forms\Components\TextInput::make('tax_number')
                    ->label('N° TVA intracommunautaire')
                    ->maxLength(20)
                    ->placeholder('FR12345678901'),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Téléphone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->label('Adresse')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('zip_code')
                    ->label('Code postal')
                    ->maxLength(10),
                Forms\Components\TextInput::make('city')
                    ->label('Ville')
                    ->maxLength(255),
                Forms\Components\TextInput::make('country')
                    ->label('Pays')
                    ->maxLength(255),
                Forms\Components\Select::make('country_code')
                    ->label('Code pays')
                    ->options([
                        'FR' => 'France',
                        'BE' => 'Belgique',
                        'CH' => 'Suisse',
                        'DE' => 'Allemagne',
                        'ES' => 'Espagne',
                        'IT' => 'Italie',
                        'LU' => 'Luxembourg',
                        'NL' => 'Pays-Bas',
                        'GB' => 'Royaume-Uni',
                        'US' => 'États-Unis',
                        'PT' => 'Portugal',
                        'AT' => 'Autriche',
                    ])
                    ->default('FR')
                    ->searchable(),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
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
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->deferLoading() // Optimisation: Chargement différé via AJAX
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}

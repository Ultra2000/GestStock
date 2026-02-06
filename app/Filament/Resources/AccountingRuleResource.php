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
    protected static ?string $navigationLabel = 'Règles d\'imputation';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Règle d\'imputation')
                    ->description('Définissez les conditions pour catégoriser automatiquement vos transactions bancaires.')
                    ->schema([
                        Forms\Components\Hidden::make('company_id')
                            ->default(fn () => \Filament\Facades\Filament::getTenant()?->id),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom de la règle')
                            ->placeholder('Ex: Loyer mensuel, Achats Amazon...')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('condition_type')
                                    ->label('Type de condition')
                                    ->options([
                                        'contains' => 'Contient',
                                        'starts_with' => 'Commence par',
                                        'ends_with' => 'Finit par',
                                        'exact' => 'Est exactement',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('condition_value')
                                    ->label('Valeur à rechercher')
                                    ->placeholder('Ex: AMAZON, LOYER, ORANGE...')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Select::make('accounting_category_id')
                            ->label('Catégorie comptable')
                            ->relationship('category', 'name', fn ($query) => $query->where('company_id', \Filament\Facades\Filament::getTenant()?->id))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('priority')
                                    ->label('Priorité')
                                    ->helperText('Plus le nombre est élevé, plus la règle est prioritaire.')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Règle active')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
                
                Forms\Components\Section::make('💡 Comment ça marche ?')
                    ->description('Guide pour créer des règles efficaces')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('help')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="text-sm space-y-4">
                                    <div class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                        <p class="font-semibold text-blue-700 dark:text-blue-300 mb-2">🎯 Principe</p>
                                        <p class="text-blue-600 dark:text-blue-400">
                                            Quand une transaction bancaire est importée, le système analyse son libellé et applique automatiquement la catégorie correspondante.
                                        </p>
                                    </div>
                                    
                                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                        <p class="font-semibold mb-3">📋 Exemples de règles :</p>
                                        <table class="w-full text-xs">
                                            <thead class="bg-gray-100 dark:bg-gray-700">
                                                <tr>
                                                    <th class="p-2 text-left">Libellé transaction</th>
                                                    <th class="p-2 text-left">Condition</th>
                                                    <th class="p-2 text-left">Valeur</th>
                                                    <th class="p-2 text-left">Catégorie</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="border-b dark:border-gray-700">
                                                    <td class="p-2">PAIEMENT CB AMAZON</td>
                                                    <td class="p-2">Contient</td>
                                                    <td class="p-2 font-mono">AMAZON</td>
                                                    <td class="p-2">Achats divers</td>
                                                </tr>
                                                <tr class="border-b dark:border-gray-700">
                                                    <td class="p-2">VIR LOYER JANVIER</td>
                                                    <td class="p-2">Contient</td>
                                                    <td class="p-2 font-mono">LOYER</td>
                                                    <td class="p-2">Charges locatives</td>
                                                </tr>
                                                <tr class="border-b dark:border-gray-700">
                                                    <td class="p-2">ORANGE MOBILE 06...</td>
                                                    <td class="p-2">Commence par</td>
                                                    <td class="p-2 font-mono">ORANGE</td>
                                                    <td class="p-2">Téléphone</td>
                                                </tr>
                                                <tr>
                                                    <td class="p-2">SALAIRE DUPONT JEAN</td>
                                                    <td class="p-2">Contient</td>
                                                    <td class="p-2 font-mono">SALAIRE</td>
                                                    <td class="p-2">Charges personnel</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <div class="bg-yellow-50 dark:bg-yellow-950 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                        <p class="font-semibold text-yellow-700 dark:text-yellow-300 mb-2">⚠️ Conseils</p>
                                        <ul class="text-yellow-600 dark:text-yellow-400 list-disc list-inside space-y-1">
                                            <li>Utilisez des mots-clés <strong>uniques</strong> pour éviter les conflits</li>
                                            <li>La <strong>priorité</strong> permet de gérer les cas ambigus (la plus haute gagne)</li>
                                            <li>Testez vos règles avec quelques transactions avant de les généraliser</li>
                                        </ul>
                                    </div>
                                </div>
                            ')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
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

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers;
use App\Models\Quote;
use App\Models\Customer;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Devis';

    protected static ?string $modelLabel = 'Devis';

    protected static ?string $pluralModelLabel = 'Devis';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('quote_number')
                                    ->label('N° Devis')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Généré automatiquement'),
                                Forms\Components\DatePicker::make('quote_date')
                                    ->label('Date du devis')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\DatePicker::make('valid_until')
                                    ->label('Valide jusqu\'au')
                                    ->required()
                                    ->default(now()->addDays(30))
                                    ->after('quote_date'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('customer_id')
                                    ->label('Client')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nom')
                                            ->required(),
                                        Forms\Components\TextInput::make('email')
                                            ->email(),
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Téléphone'),
                                    ]),
                                Forms\Components\Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'draft' => 'Brouillon',
                                        'sent' => 'Envoyé',
                                        'accepted' => 'Accepté',
                                        'rejected' => 'Refusé',
                                        'expired' => 'Expiré',
                                        'converted' => 'Converti',
                                    ])
                                    ->default('draft')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Articles')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->options(function () {
                                        return Product::where('company_id', Filament::getTenant()?->id)
                                            ->pluck('name', 'id');
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->price);
                                                $set('description', $product->name);
                                            }
                                        }
                                    })
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qté')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(0.01)
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Prix unit.')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('discount_percent')
                                    ->label('Remise %')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->columnSpan(1),
                                Forms\Components\Placeholder::make('line_total')
                                    ->label('Total')
                                    ->content(function ($get) {
                                        $qty = floatval($get('quantity') ?? 0);
                                        $price = floatval($get('unit_price') ?? 0);
                                        $discount = floatval($get('discount_percent') ?? 0);
                                        $subtotal = $qty * $price;
                                        $total = $subtotal - ($subtotal * $discount / 100);
                                        return number_format($total, 2, ',', ' ') . ' €';
                                    })
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel('Ajouter un article')
                            ->reorderable()
                            ->collapsible(),
                    ]),

                Forms\Components\Section::make('Totaux')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Remise globale')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('€'),
                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('TVA %')
                                    ->numeric()
                                    ->default(20)
                                    ->suffix('%'),
                                Forms\Components\Placeholder::make('calculated_total')
                                    ->label('Total HT')
                                    ->content(fn ($record) => $record ? number_format($record->subtotal, 2, ',', ' ') . ' €' : '-'),
                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Total TTC')
                                    ->content(fn ($record) => $record ? number_format($record->total, 2, ',', ' ') . ' €' : '-'),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes internes')
                            ->rows(2),
                        Forms\Components\Textarea::make('terms')
                            ->label('Conditions générales')
                            ->rows(3)
                            ->default("Conditions de paiement: 30 jours\nValidité du devis: 30 jours"),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_number')
                    ->label('N° Devis')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quote_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Validité')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->valid_until->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total TTC')
                    ->money('EUR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'secondary' => 'draft',
                        'info' => 'sent',
                        'success' => 'accepted',
                        'danger' => 'rejected',
                        'warning' => 'expired',
                        'primary' => 'converted',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyé',
                        'accepted' => 'Accepté',
                        'rejected' => 'Refusé',
                        'expired' => 'Expiré',
                        'converted' => 'Converti',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Créé par')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'draft' => 'Brouillon',
                        'sent' => 'Envoyé',
                        'accepted' => 'Accepté',
                        'rejected' => 'Refusé',
                        'expired' => 'Expiré',
                        'converted' => 'Converti',
                    ]),
                Tables\Filters\SelectFilter::make('customer')
                    ->label('Client')
                    ->relationship('customer', 'name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('send')
                        ->label('Envoyer')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(fn (Quote $record) => $record->markAsSent())
                        ->visible(fn (Quote $record) => $record->status === 'draft'),
                    Tables\Actions\Action::make('accept')
                        ->label('Accepter')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Quote $record) => $record->accept())
                        ->visible(fn (Quote $record) => $record->status === 'sent'),
                    Tables\Actions\Action::make('reject')
                        ->label('Refuser')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Quote $record) => $record->reject())
                        ->visible(fn (Quote $record) => $record->status === 'sent'),
                    Tables\Actions\Action::make('convert')
                        ->label('Convertir en vente')
                        ->icon('heroicon-o-shopping-cart')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Convertir en vente')
                        ->modalDescription('Voulez-vous créer une vente à partir de ce devis ?')
                        ->action(fn (Quote $record) => $record->convertToSale())
                        ->visible(fn (Quote $record) => $record->status === 'accepted'),
                    Tables\Actions\Action::make('pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->url(fn (Quote $record) => route('quotes.pdf', $record))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Dupliquer')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (Quote $record) {
                            $newQuote = $record->replicate();
                            $newQuote->quote_number = null;
                            $newQuote->status = 'draft';
                            $newQuote->quote_date = now();
                            $newQuote->valid_until = now()->addDays(30);
                            $newQuote->sent_at = null;
                            $newQuote->accepted_at = null;
                            $newQuote->rejected_at = null;
                            $newQuote->converted_sale_id = null;
                            $newQuote->save();

                            foreach ($record->items as $item) {
                                $newQuote->items()->create($item->toArray());
                            }

                            return redirect()->to(QuoteResource::getUrl('edit', ['record' => $newQuote]));
                        }),
                    Tables\Actions\DeleteAction::make(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', Filament::getTenant()?->id);
    }
}

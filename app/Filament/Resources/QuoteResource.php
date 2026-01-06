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

    /**
     * Helper pour calculer le total d'une ligne
     */
    protected static function calculateLineTotal(Forms\Get $get, Forms\Set $set): void
    {
        // Cette méthode est appelée pour recalculer les totaux affichés
        // Les calculs réels sont faits dans les Placeholders
    }

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
                                                $set('vat_rate', $product->vat_rate_sale ?? 20);
                                                $set('vat_category', $product->vat_category ?? 'S');
                                            }
                                        }
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('description')
                                    ->label('Description')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qté')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        self::calculateLineTotal($get, $set);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('P.U. HT')
                                    ->numeric()
                                    ->required()
                                    ->prefix(fn () => Filament::getTenant()->currency ?? '€')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        self::calculateLineTotal($get, $set);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\Select::make('vat_rate')
                                    ->label('TVA')
                                    ->options(Product::getCommonVatRates())
                                    ->default(20.00)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        self::calculateLineTotal($get, $set);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('discount_percent')
                                    ->label('Rem. %')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        self::calculateLineTotal($get, $set);
                                    })
                                    ->columnSpan(1),
                                Forms\Components\Hidden::make('vat_category')
                                    ->default('S'),
                                Forms\Components\Placeholder::make('line_total_ht')
                                    ->label('Total HT')
                                    ->content(function (Forms\Get $get) {
                                        $qty = floatval($get('quantity') ?? 0);
                                        $price = floatval($get('unit_price') ?? 0);
                                        $discount = floatval($get('discount_percent') ?? 0);
                                        $subtotal = $qty * $price;
                                        $total = $subtotal - ($subtotal * $discount / 100);
                                        $currency = Filament::getTenant()->currency ?? '€';
                                        return number_format($total, 2, ',', ' ') . ' ' . $currency;
                                    })
                                    ->columnSpan(1),
                                Forms\Components\Placeholder::make('line_total_ttc')
                                    ->label('Total TTC')
                                    ->content(function (Forms\Get $get) {
                                        $qty = floatval($get('quantity') ?? 0);
                                        $price = floatval($get('unit_price') ?? 0);
                                        $discount = floatval($get('discount_percent') ?? 0);
                                        $vatRate = floatval($get('vat_rate') ?? 20);
                                        $subtotal = $qty * $price;
                                        $totalHt = $subtotal - ($subtotal * $discount / 100);
                                        $vat = $totalHt * ($vatRate / 100);
                                        $totalTtc = $totalHt + $vat;
                                        $currency = Filament::getTenant()->currency ?? '€';
                                        return number_format($totalTtc, 2, ',', ' ') . ' ' . $currency;
                                    })
                                    ->columnSpan(1),
                            ])
                            ->columns(10)
                            ->defaultItems(1)
                            ->addActionLabel('Ajouter un article')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['description']) && $state['description'] 
                                    ? $state['description'] . ' (x' . ($state['quantity'] ?? 1) . ')'
                                    : null
                            ),
                    ]),

                Forms\Components\Section::make('Totaux')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Remise globale')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix(fn () => Filament::getTenant()->currency ?? '€')
                                    ->helperText('Appliquée sur le total'),
                                Forms\Components\Placeholder::make('calculated_total_ht')
                                    ->label('Total HT')
                                    ->content(fn ($record) => $record 
                                        ? number_format($record->total_ht ?? $record->subtotal ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? '€')
                                        : '-'),
                                Forms\Components\Placeholder::make('calculated_total_vat')
                                    ->label('Total TVA')
                                    ->content(fn ($record) => $record 
                                        ? number_format($record->total_vat ?? $record->tax_amount ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? '€')
                                        : '-'),
                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Total TTC')
                                    ->content(fn ($record) => $record 
                                        ? number_format($record->total ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? '€')
                                        : '-'),
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
                    Tables\Actions\Action::make('copyPublicLink')
                        ->label('Copier le lien client')
                        ->icon('heroicon-o-link')
                        ->color('info')
                        ->action(function (Quote $record) {
                            if (!$record->public_token) {
                                $record->public_token = \Illuminate\Support\Str::uuid()->toString();
                                $record->save();
                            }
                            
                            $url = $record->getPublicUrl();
                            
                            return \Filament\Notifications\Notification::make()
                                ->title('Lien copié !')
                                ->body($url)
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(false)
                        ->modalHeading('Lien de partage')
                        ->modalContent(fn (Quote $record) => new \Illuminate\Support\HtmlString(
                            '<div style="padding: 16px; background: #f8fafc; border-radius: 8px; margin-top: 8px;">' .
                            '<p style="margin-bottom: 8px; font-weight: 600;">Lien à envoyer au client :</p>' .
                            '<input type="text" value="' . ($record->public_token ? $record->getPublicUrl() : 'Cliquez sur Confirmer pour générer le lien') . '" ' .
                            'readonly onclick="this.select(); document.execCommand(\'copy\');" ' .
                            'style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 4px; font-family: monospace; font-size: 13px;">' .
                            '</div>'
                        ))
                        ->visible(fn (Quote $record) => in_array($record->status, ['draft', 'sent'])),
                    Tables\Actions\Action::make('send')
                        ->label('Envoyer par email')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->form([
                            \Filament\Forms\Components\Textarea::make('message')
                                ->label('Message personnalisé (optionnel)')
                                ->placeholder('Ajoutez un message personnel qui sera affiché dans l\'email...')
                                ->rows(4)
                                ->maxLength(1000),
                        ])
                        ->action(function (Quote $record, array $data) {
                            if (!$record->customer || !$record->customer->email) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Email manquant')
                                    ->body('Le client n\'a pas d\'adresse email renseignée.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $record->markAsSent();

                            // Envoyer l'email
                            \Illuminate\Support\Facades\Mail::to($record->customer->email)
                                ->send(new \App\Mail\QuoteMail($record, $data['message'] ?? null));

                            \Filament\Notifications\Notification::make()
                                ->title('Devis envoyé')
                                ->body("Le devis a été envoyé à {$record->customer->email}")
                                ->success()
                                ->send();
                        })
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
                        ->modalDescription('Voulez-vous créer une vente à partir de ce devis accepté ? Cela créera une nouvelle facture et impactera le stock.')
                        ->action(function (Quote $record) {
                            $sale = $record->convertToSale();
                            
                            if ($sale) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Vente créée')
                                    ->body("La vente {$sale->invoice_number} a été créée avec succès.")
                                    ->success()
                                    ->send();
                                
                                return redirect()->to(\App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $sale]));
                            }
                        })
                        ->visible(fn (Quote $record) => $record->status === 'accepted' && !$record->converted_sale_id),
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
                            $newQuote->public_token = null;
                            $newQuote->status = 'draft';
                            $newQuote->quote_date = now();
                            $newQuote->valid_until = now()->addDays(30);
                            $newQuote->expires_at = null;
                            $newQuote->sent_at = null;
                            $newQuote->accepted_at = null;
                            $newQuote->rejected_at = null;
                            $newQuote->refusal_reason = null;
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

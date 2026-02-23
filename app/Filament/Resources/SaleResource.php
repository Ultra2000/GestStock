<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Ventes';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Ventes';
    protected static ?string $modelLabel = 'Vente';
    protected static ?string $pluralModelLabel = 'Ventes';

    /**
     * Optimisation: Eager loading des relations pour éviter N+1
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'warehouse', 'bankAccount']);
    }

    public static function form(Form $form): Form
    {
        $companyId = Filament::getTenant()?->id;

        return $form
            ->disabled(fn (?Sale $record) => $record?->status === 'completed')
            ->schema([
                Forms\Components\Section::make('Informations de la vente')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_number')
                            ->label('Numéro de facture')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('customer_id')
                            ->label('Client')
                            ->relationship('customer', 'name', fn ($query) => $query->where('company_id', $companyId))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Entrepôt source')
                            ->options(fn () => Warehouse::where('company_id', $companyId)
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->default(fn () => Warehouse::getDefault($companyId)?->id)
                            ->required()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('items', [])),
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'completed' => 'Terminée',
                                'cancelled' => 'Annulée',
                            ])
                            ->required()
                            ->default('pending')
                            ->live(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Mode de paiement')
                            ->options([
                                'cash' => 'Espèces',
                                'card' => 'Carte bancaire',
                                'transfer' => 'Virement SEPA',
                                'check' => 'Chèque',
                                'sepa_debit' => 'Prélèvement SEPA',
                                'paypal' => 'PayPal',
                            ])
                            ->required(),
                        Forms\Components\Select::make('bank_account_id')
                            ->label('Compte de dépôt')
                            ->relationship('bankAccount', 'name', fn ($query) => $query->where('company_id', $companyId))
                            ->searchable()
                            ->preload()
                            ->required(fn (Forms\Get $get) => $get('status') === 'completed')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'completed'),
                    ])->columns(3),

                Forms\Components\Section::make('Paramètres financiers')
                    ->schema([
                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Remise globale %')
                            ->numeric()->minValue(0)->maxValue(100)->default(0)
                            ->live(onBlur: true)
                            ->helperText('Appliquée sur le total TTC'),
                        Forms\Components\Placeholder::make('total_ht_display')
                            ->label('Total HT')
                            ->content(fn (?Sale $record) => $record ? number_format($record->total_ht ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'EUR') : '-'),
                        Forms\Components\Placeholder::make('total_vat_display')
                            ->label('Total TVA')
                            ->content(fn (?Sale $record) => $record ? number_format($record->total_vat ?? 0, 2, ',', ' ') . ' ' . (Filament::getTenant()->currency ?? 'EUR') : '-'),
                        Forms\Components\TextInput::make('total')
                            ->label('Total TTC')
                            ->disabled()
                            ->prefix(fn () => Filament::getTenant()->currency ?? 'EUR'),
                    ])->columns(4),

                Forms\Components\Section::make('Articles')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Articles')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Produit')
                                    ->options(function (Forms\Get $get) {
                                        $warehouseId = $get('../../warehouse_id');
                                        if (!$warehouseId) {
                                            // Fallback: tous les produits avec stock > 0
                                            return Product::whereHas('warehouses', fn ($q) => $q->where('quantity', '>', 0))
                                                ->pluck('name', 'id');
                                        }
                                        
                                        // Produits avec stock dans l'entrepôt sélectionné
                                        return \DB::table('product_warehouse')
                                            ->join('products', 'products.id', '=', 'product_warehouse.product_id')
                                            ->where('product_warehouse.warehouse_id', $warehouseId)
                                            ->where('product_warehouse.quantity', '>', 0)
                                            ->pluck('products.name', 'products.id');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                // Utiliser le prix de vente HT du produit
                                                $set('unit_price', $product->price);
                                                $set('vat_rate', $product->vat_rate_sale ?? 20);
                                                $set('vat_category', $product->vat_category ?? 'S');
                                                $set('quantity', 1);
                                                
                                                // Calculer le total
                                                $vatRate = $product->vat_rate_sale ?? 20;
                                                $totalHt = $product->price;
                                                $vat = round($totalHt * ($vatRate / 100), 2);
                                                $set('total_price', $totalHt + $vat);
                                                
                                                // Récupérer le stock disponible dans l'entrepôt
                                                $warehouseId = $get('../../warehouse_id');
                                                if ($warehouseId) {
                                                    $stock = \DB::table('product_warehouse')
                                                        ->where('product_id', $state)
                                                        ->where('warehouse_id', $warehouseId)
                                                        ->value('quantity') ?? 0;
                                                    $set('available_stock', $stock);
                                                }
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('available_stock')
                                    ->label('Dispo.')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qté')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->maxValue(function (Forms\Get $get) {
                                        $productId = $get('product_id');
                                        $warehouseId = $get('../../warehouse_id');
                                        if ($productId && $warehouseId) {
                                            return \DB::table('product_warehouse')
                                                ->where('product_id', $productId)
                                                ->where('warehouse_id', $warehouseId)
                                                ->value('quantity') ?? 1;
                                        }
                                        return 999999;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $quantity = $state;
                                        $unitPrice = $get('unit_price');
                                        $vatRate = $get('vat_rate') ?? 20;
                                        if ($quantity && $unitPrice) {
                                            $totalHt = $quantity * $unitPrice;
                                            $vat = round($totalHt * ($vatRate / 100), 2);
                                            $set('total_price', $totalHt + $vat);
                                        }
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('P.U. HT')
                                    ->required()
                                    ->numeric()
                                    ->suffix(fn () => Filament::getTenant()->currency ?? 'EUR')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $quantity = $get('quantity');
                                        $unitPrice = $state;
                                        $vatRate = $get('vat_rate') ?? 20;
                                        if ($quantity && $unitPrice) {
                                            $totalHt = $quantity * $unitPrice;
                                            $vat = round($totalHt * ($vatRate / 100), 2);
                                            $set('total_price', $totalHt + $vat);
                                        }
                                    }),
                                Forms\Components\Select::make('vat_rate')
                                    ->label('TVA')
                                    ->options(Product::getCommonVatRates())
                                    ->default(20.00)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $quantity = $get('quantity');
                                        $unitPrice = $get('unit_price');
                                        $vatRate = $state ?? 20;
                                        if ($quantity && $unitPrice) {
                                            $totalHt = $quantity * $unitPrice;
                                            $vat = round($totalHt * ($vatRate / 100), 2);
                                            $set('total_price', $totalHt + $vat);
                                        }
                                    }),
                                Forms\Components\Select::make('vat_category')
                                    ->label('Cat.')
                                    ->options(Product::getVatCategories())
                                    ->default('S')
                                    ->visible(false), // Caché mais transmis
                                Forms\Components\TextInput::make('total_price')
                                    ->label('Total TTC')
                                    ->required()
                                    ->numeric()
                                    ->suffix(fn () => Filament::getTenant()->currency ?? 'EUR')
                                    ->disabled(),
                            ])
                            ->columns(6)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->hidden(fn (Forms\Get $get) => !$get('warehouse_id')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Numéro de facture')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credit_note' => 'danger',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credit_note' => 'Avoir',
                        default => 'Facture',
                    }),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Entrepôt')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Terminée',
                        'cancelled' => 'Annulée',
                        default => 'En attente',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('ppf_status')
                    ->label('Statut Chorus Pro')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'DEPOSEE' => 'gray',
                        'MISE_A_DISPOSITION' => 'info',
                        'PRISE_EN_CHARGE' => 'warning',
                        'MISE_EN_PAIEMENT' => 'success',
                        'PAYEE' => 'success',
                        'SUSPENDUE' => 'warning',
                        'REJETEE' => 'danger',
                        'ERREUR' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'DEPOSEE' => '📥 Déposée',
                        'MISE_A_DISPOSITION' => '📤 Mise à disposition',
                        'PRISE_EN_CHARGE' => '✓ Prise en charge',
                        'MISE_EN_PAIEMENT' => '💳 Mise en paiement',
                        'PAYEE' => '💰 Payée',
                        'SUSPENDUE' => '⏸️ Suspendue',
                        'REJETEE' => '✗ Rejetée',
                        'ERREUR' => '⚠️ Erreur',
                        null => '-',
                        default => $state,
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ppf_id')
                    ->label('N° Flux PPF')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                Tables\Columns\TextColumn::make('ppf_synced_at')
                    ->label('Dernière synchro PPF')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Modifier')
                    ->hidden(fn (Sale $record) => $record->status === 'completed'),
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer')
                    ->hidden(fn (Sale $record) => $record->status === 'completed'),
                Tables\Actions\Action::make('invoice')
                    ->label('Facture')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Sale $record): string => route('sales.invoice', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('preview')
                    ->label('Prévisualiser')
                    ->icon('heroicon-o-eye')
                    ->color('secondary')
                    ->url(fn (Sale $record): string => route('sales.invoice.preview', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('send_email')
                    ->label('Envoyer email')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Destinataire')
                            ->email()
                            ->required(),
                        Forms\Components\Textarea::make('message')
                            ->label('Message (optionnel)')
                            ->rows(3),
                    ])
                    ->action(function (array $data, Sale $record) {
                        \Mail::to($data['email'])->send(new \App\Mail\InvoiceMail('sale', $record, $data['message'] ?? ''));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer la facture par email')
                    ->modalButton('Envoyer')
                    ->color('success'),
                Tables\Actions\Action::make('send_to_ppf')
                    ->label('Envoyer au PPF')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Sale $record, \App\Services\Integration\PpfService $ppfService) {
                        try {
                            $ppfService->sendInvoice($record);
                            \Filament\Notifications\Notification::make()
                                ->title('Facture envoyée au PPF')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erreur lors de l\'envoi')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Sale $record) => $record->status === 'completed' && !$record->ppf_status),
                Tables\Actions\Action::make('refresh_ppf_status')
                    ->label('Actualiser statut PPF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Sale $record, \App\Services\Integration\PpfService $ppfService) {
                        try {
                            $synced = $ppfService->syncInvoiceStatus($record);
                            if ($synced) {
                                $record->update(['ppf_synced_at' => now()]);
                                \Filament\Notifications\Notification::make()
                                    ->title('Statut mis à jour')
                                    ->body('Statut: ' . $record->fresh()->ppf_status)
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Facture non trouvée')
                                    ->body('La facture n\'a pas encore été traitée par Chorus Pro')
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erreur lors de la synchronisation')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Sale $record) => $record->ppf_id !== null),
                Tables\Actions\Action::make('credit_note')
                    ->label('Générer un avoir')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Générer un avoir')
                    ->modalDescription('Voulez-vous vraiment générer un avoir pour cette facture ? Cela créera une nouvelle facture négative et réintégrera le stock.')
                    ->action(function (Sale $record) {
                        // 1. Dupliquer la vente
                        $creditNote = $record->replicate(['invoice_number', 'security_hash', 'previous_hash', 'created_at', 'updated_at']);
                        $creditNote->type = 'credit_note';
                        $creditNote->parent_id = $record->id;
                        $creditNote->status = 'completed'; // L'avoir est validé immédiatement
                        $creditNote->notes = "Avoir annulant la facture n°{$record->invoice_number}";
                        $creditNote->total = -$record->total; // Montant négatif
                        $creditNote->save();

                        // 2. Dupliquer les articles avec quantités inversées (pour l'affichage)
                        // Note: SaleItem observer gère le stock.
                        // Pour un avoir, on veut que le stock augmente.
                        // Dans SaleItem observer:
                        // Si type = credit_note, multiplier = 1.
                        // Donc quantity * 1 = entrée en stock.
                        // On garde la quantité positive dans la base pour l'affichage, mais le total_price sera négatif ?
                        // Non, généralement un avoir a des quantités positives mais un total négatif, ou l'inverse.
                        // Pour simplifier et rester cohérent avec Factur-X, un avoir a souvent des lignes positives mais le code document 381 indique que c'est un avoir.
                        // Cependant, pour que le total soit négatif dans notre système actuel, il faut ruser.
                        // Option A: Quantité négative.
                        // Option B: Prix unitaire négatif.
                        // Option C: Quantité positive, Prix positif, mais le type 'credit_note' inverse le signe comptable.
                        
                        // Ici, on va garder les valeurs positives pour la quantité et le prix, 
                        // mais on va s'assurer que le total de la vente est stocké en négatif pour la compta.
                        // ATTENTION: SaleItem calcule total_price = quantity * unit_price.
                        // Si on veut un total négatif, il faut l'un des deux négatif.
                        // Pour la clarté, on met le prix unitaire en négatif sur l'avoir.
                        
                        foreach ($record->items as $item) {
                            $creditNote->items()->create([
                                'product_id' => $item->product_id,
                                'quantity' => $item->quantity, // On garde la quantité positive (ex: retour de 5 articles)
                                'unit_price' => -$item->unit_price, // Prix négatif pour inverser le montant
                                'vat_rate' => $item->vat_rate ?? 20,
                                'vat_category' => $item->vat_category ?? 'S',
                                'total_price' => -($item->quantity * $item->unit_price),
                            ]);
                        }
                        
                        // Redirection vers l'avoir créé
                        return redirect()->to(SaleResource::getUrl('edit', ['record' => $creditNote]));
                    })
                    ->visible(fn (Sale $record) => $record->status === 'completed' && $record->type === 'invoice' && !$record->creditNotes()->exists()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer la sélection')
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            if ($records->contains('status', 'completed')) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Action refusée')
                                    ->body('Impossible de supprimer des ventes terminées.')
                                    ->send();
                                
                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}

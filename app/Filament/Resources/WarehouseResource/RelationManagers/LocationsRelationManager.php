<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use App\Models\WarehouseLocation;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    protected static ?string $title = 'Emplacements';
    protected static ?string $modelLabel = 'Emplacement';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('Emplacement parent')
                            ->options(fn () => $this->ownerRecord->locations()
                                ->whereIn('type', ['zone', 'aisle', 'rack', 'shelf'])
                                ->get()
                                ->mapWithKeys(fn ($loc) => [$loc->id => $loc->full_code . ' - ' . $loc->name])
                                ->toArray())
                            ->searchable()
                            ->placeholder('Racine (aucun parent)'),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'zone' => '🗺️ Zone',
                                'aisle' => '↔️ Allée',
                                'rack' => '📦 Rack',
                                'shelf' => '📚 Étagère',
                                'bin' => '📍 Emplacement (Bin)',
                            ])
                            ->default('bin')
                            ->live(),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(30)
                            ->placeholder('A1-01')
                            ->unique(
                                table: WarehouseLocation::class,
                                column: 'code',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('warehouse_id', $this->ownerRecord->id)
                            )
                            ->helperText('Unique dans cet entrepôt'),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Allée A1, Étagère 01...'),
                    ]),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('barcode')
                            ->label('Code-barres')
                            ->maxLength(50)
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('generateBarcode')
                                    ->icon('heroicon-o-sparkles')
                                    ->tooltip('Générer automatiquement')
                                    ->action(function (Forms\Set $set) {
                                        $set('barcode', WarehouseLocation::generateBarcode($this->ownerRecord->id));
                                    })
                            ),
                        Forms\Components\TextInput::make('capacity')
                            ->label('Capacité (unités)')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('Illimitée si vide'),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Toggle::make('is_picking_location')
                            ->label('Zone de picking')
                            ->helperText('Prélèvement produits')
                            ->default(true),
                        Forms\Components\Toggle::make('is_receiving_location')
                            ->label('Zone de réception')
                            ->helperText('Réception marchandises'),
                        Forms\Components\Toggle::make('is_shipping_location')
                            ->label('Zone d\'expédition')
                            ->helperText('Préparation commandes'),
                    ]),
                Forms\Components\Toggle::make('is_active')
                    ->label('Emplacement actif')
                    ->default(true)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_code')
                    ->label('Code complet')
                    ->searchable(['code'])
                    ->sortable('code')
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->description(fn (WarehouseLocation $record) => $record->parent?->name),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'zone' => '🗺️ Zone',
                        'aisle' => '↔️ Allée',
                        'rack' => '📦 Rack',
                        'shelf' => '📚 Étagère',
                        'bin' => '📍 Bin',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'zone' => 'primary',
                        'aisle' => 'info',
                        'rack' => 'warning',
                        'shelf' => 'success',
                        'bin' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Produits')
                    ->state(fn (WarehouseLocation $record) => $record->getProductCount())
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->state(fn (WarehouseLocation $record) => number_format($record->getStock(), 0))
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('usage')
                    ->label('Remplissage')
                    ->state(function (WarehouseLocation $record) {
                        $percent = $record->getUsagePercent();
                        return $record->capacity ? $percent . '%' : '-';
                    })
                    ->color(fn (WarehouseLocation $record) => match(true) {
                        !$record->capacity => 'gray',
                        $record->getUsagePercent() >= 90 => 'danger',
                        $record->getUsagePercent() >= 70 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
            ])
            ->defaultSort('code')
            ->groups([
                Tables\Grouping\Group::make('type')
                    ->label('Type')
                    ->getTitleFromRecordUsing(fn ($record) => match($record->type) {
                        'zone' => '🗺️ Zones',
                        'aisle' => '↔️ Allées',
                        'rack' => '📦 Racks',
                        'shelf' => '📚 Étagères',
                        'bin' => '📍 Emplacements (Bins)',
                        default => $record->type,
                    }),
                Tables\Grouping\Group::make('parent.name')
                    ->label('Parent'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'zone' => '🗺️ Zone',
                        'aisle' => '↔️ Allée',
                        'rack' => '📦 Rack',
                        'shelf' => '📚 Étagère',
                        'bin' => '📍 Emplacement',
                    ]),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->options(fn () => $this->ownerRecord->locations()
                        ->whereIn('type', ['zone', 'aisle', 'rack', 'shelf'])
                        ->pluck('name', 'id')
                        ->toArray())
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                Tables\Filters\Filter::make('has_stock')
                    ->label('Avec stock')
                    ->query(fn (Builder $query) => $query->whereHas('productStocks', fn ($q) => $q->where('quantity', '>', 0))),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nouvel emplacement')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['company_id'] = filament()->getTenant()->id;
                        $data['warehouse_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
                    
                // Action génération en masse
                Tables\Actions\Action::make('bulkGenerate')
                    ->label('Génération en masse')
                    ->icon('heroicon-o-squares-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('parent_id')
                            ->label('Parent (optionnel)')
                            ->options(fn () => $this->ownerRecord->locations()
                                ->whereIn('type', ['zone', 'aisle', 'rack', 'shelf'])
                                ->get()
                                ->mapWithKeys(fn ($loc) => [$loc->id => $loc->full_code . ' - ' . $loc->name])
                                ->toArray())
                            ->searchable(),
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->required()
                            ->options([
                                'zone' => '🗺️ Zone',
                                'aisle' => '↔️ Allée',
                                'rack' => '📦 Rack',
                                'shelf' => '📚 Étagère',
                                'bin' => '📍 Emplacement (Bin)',
                            ])
                            ->default('bin'),
                        Forms\Components\TextInput::make('prefix')
                            ->label('Préfixe')
                            ->required()
                            ->placeholder('A1-')
                            ->maxLength(20),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('start')
                                    ->label('Numéro début')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1),
                                Forms\Components\TextInput::make('end')
                                    ->label('Numéro fin')
                                    ->numeric()
                                    ->required()
                                    ->default(10)
                                    ->minValue(1),
                            ]),
                        Forms\Components\TextInput::make('padding')
                            ->label('Chiffres (padding)')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(5)
                            ->helperText('Ex: 2 → 01, 02... | 3 → 001, 002...'),
                        Forms\Components\Toggle::make('generate_barcode')
                            ->label('Générer les codes-barres')
                            ->default(true),
                        Forms\Components\Toggle::make('is_picking_location')
                            ->label('Zone de picking')
                            ->default(true),
                    ])
                    ->action(function (array $data) {
                        $created = 0;
                        $errors = [];
                        
                        for ($i = $data['start']; $i <= $data['end']; $i++) {
                            $code = $data['prefix'] . str_pad($i, $data['padding'], '0', STR_PAD_LEFT);
                            
                            // Vérifier si le code existe déjà
                            if ($this->ownerRecord->locations()->where('code', $code)->exists()) {
                                $errors[] = $code;
                                continue;
                            }
                            
                            WarehouseLocation::create([
                                'company_id' => filament()->getTenant()->id,
                                'warehouse_id' => $this->ownerRecord->id,
                                'parent_id' => $data['parent_id'] ?? null,
                                'code' => $code,
                                'name' => $data['type'] === 'bin' ? "Emplacement $code" : "$code",
                                'type' => $data['type'],
                                'barcode' => $data['generate_barcode'] ? WarehouseLocation::generateBarcode($this->ownerRecord->id) : null,
                                'is_picking_location' => $data['is_picking_location'] ?? true,
                                'is_active' => true,
                            ]);
                            $created++;
                        }
                        
                        if ($created > 0) {
                            Notification::make()
                                ->success()
                                ->title("$created emplacements créés")
                                ->body($errors ? "Ignorés (déjà existants): " . implode(', ', $errors) : null)
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title("Aucun emplacement créé")
                                ->body("Tous les codes existent déjà: " . implode(', ', $errors))
                                ->send();
                        }
                    }),

                // Vue arbre
                Tables\Actions\Action::make('treeView')
                    ->label('Vue arbre')
                    ->icon('heroicon-o-queue-list')
                    ->color('info')
                    ->modalHeading('Hiérarchie des emplacements')
                    ->modalWidth('4xl')
                    ->modalContent(fn () => view('filament.warehouse.locations-tree', [
                        'warehouse' => $this->ownerRecord,
                        'locations' => $this->getTreeData(),
                    ])),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Détails'),
                    Tables\Actions\EditAction::make(),
                    
                    // Voir contenu (produits)
                    Tables\Actions\Action::make('viewContent')
                        ->label('Voir contenu')
                        ->icon('heroicon-o-cube')
                        ->color('info')
                        ->modalHeading(fn (WarehouseLocation $record) => "Contenu de {$record->full_code}")
                        ->modalWidth('4xl')
                        ->modalContent(fn (WarehouseLocation $record) => view('filament.warehouse.location-content', [
                            'location' => $record,
                            'products' => $this->getLocationProducts($record),
                        ])),

                    // Affecter un produit à cet emplacement
                    Tables\Actions\Action::make('assignProduct')
                        ->label('Affecter produit')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('product_id')
                                ->label('Produit')
                                ->options(fn () => Product::query()
                                    ->where('company_id', filament()->getTenant()->id)
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($p) => [
                                        $p->id => "{$p->name} ({$p->sku}) - Stock dispo: " . $this->getUnassignedStock($p->id)
                                    ]))
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('max_qty', $this->getUnassignedStock($state)))
                                ->preload(),
                            Forms\Components\Placeholder::make('available_info')
                                ->label('')
                                ->content(fn ($get) => $get('product_id') 
                                    ? "Stock non affecté disponible: " . $this->getUnassignedStock($get('product_id')) . " unités"
                                    : "Sélectionnez un produit"),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantité à affecter')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->helperText('Ne peut pas dépasser le stock non affecté'),
                            Forms\Components\TextInput::make('min_quantity')
                                ->label('Stock minimum (alerte)')
                                ->numeric()
                                ->placeholder('Optionnel'),
                            Forms\Components\TextInput::make('reorder_point')
                                ->label('Point de réappro')
                                ->numeric()
                                ->placeholder('Optionnel'),
                        ])
                        ->action(function (WarehouseLocation $record, array $data) {
                            $this->assignProductToLocation($record, $data);
                        }),
                    
                    // Affecter plusieurs produits en masse
                    Tables\Actions\Action::make('bulkAssign')
                        ->label('Affectation en masse')
                        ->icon('heroicon-o-squares-plus')
                        ->color('primary')
                        ->form([
                            Forms\Components\Repeater::make('products')
                                ->label('Produits à affecter')
                                ->schema([
                                    Forms\Components\Select::make('product_id')
                                        ->label('Produit')
                                        ->options(fn () => Product::query()
                                            ->where('company_id', filament()->getTenant()->id)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn ($p) => [
                                                $p->id => "{$p->name} - Dispo: " . $this->getUnassignedStock($p->id)
                                            ]))
                                        ->searchable()
                                        ->required(),
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Quantité')
                                        ->numeric()
                                        ->required()
                                        ->default(1),
                                ])
                                ->columns(2)
                                ->minItems(1)
                                ->defaultItems(1)
                                ->addActionLabel('Ajouter un produit'),
                        ])
                        ->action(function (WarehouseLocation $record, array $data) {
                            $count = 0;
                            $errors = [];
                            foreach ($data['products'] as $item) {
                                $result = $this->assignProductToLocation($record, $item);
                                if ($result) {
                                    $count++;
                                } else {
                                    $product = Product::find($item['product_id']);
                                    $errors[] = $product?->name ?? 'Inconnu';
                                }
                            }
                            
                            if ($count > 0) {
                                Notification::make()
                                    ->success()
                                    ->title("$count produits affectés")
                                    ->body($errors ? "Échecs (stock insuffisant): " . implode(', ', $errors) : null)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title("Aucun produit affecté")
                                    ->body("Stock insuffisant pour tous les produits")
                                    ->send();
                            }
                        }),
                    
                    // Transfert intra-entrepôt
                    Tables\Actions\Action::make('transfer')
                        ->label('Transférer stock')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('warning')
                        ->visible(fn (WarehouseLocation $record) => $record->getStock() > 0)
                        ->form(fn (WarehouseLocation $record) => [
                            Forms\Components\Select::make('product_id')
                                ->label('Produit à transférer')
                                ->options(fn () => $this->getLocationProducts($record)
                                    ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} (Stock: {$p->pivot->quantity})"])
                                    ->toArray())
                                ->required()
                                ->searchable()
                                ->live(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantité')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(fn ($get) => $this->getProductStockInLocation($record, $get('product_id'))),
                            Forms\Components\Select::make('destination_id')
                                ->label('Emplacement destination')
                                ->options(fn () => $this->ownerRecord->locations()
                                    ->where('id', '!=', $record->id)
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn ($loc) => [$loc->id => $loc->full_code . ' - ' . $loc->name])
                                    ->toArray())
                                ->required()
                                ->searchable(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Motif du transfert')
                                ->rows(2),
                        ])
                        ->action(function (WarehouseLocation $record, array $data) {
                            $this->performInternalTransfer($record, $data);
                        }),
                        
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (WarehouseLocation $record) => $record->getStock() == 0),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            foreach ($records as $record) {
                                if ($record->getStock() > 0) {
                                    Notification::make()
                                        ->danger()
                                        ->title('Suppression impossible')
                                        ->body("L'emplacement {$record->code} contient du stock.")
                                        ->send();
                                    return false;
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('generateBarcodes')
                        ->label('Générer codes-barres')
                        ->icon('heroicon-o-qr-code')
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!$record->barcode) {
                                    $record->update([
                                        'barcode' => WarehouseLocation::generateBarcode($this->ownerRecord->id),
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->success()
                                ->title("$count codes-barres générés")
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('toggleActive')
                        ->label('Activer/Désactiver')
                        ->icon('heroicon-o-power')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => !$record->is_active]);
                            }
                            Notification::make()
                                ->success()
                                ->title('Statut modifié')
                                ->send();
                        }),
                ]),
            ]);
    }

    // Obtenir la structure en arbre
    protected function getTreeData(): Collection
    {
        return $this->ownerRecord->locations()
            ->whereNull('parent_id')
            ->with('children.children.children.children')
            ->orderBy('code')
            ->get();
    }

    // Obtenir les produits d'un emplacement
    protected function getLocationProducts(WarehouseLocation $location): Collection
    {
        // Récupérer directement depuis product_warehouse pour avoir les bonnes données
        $stockData = \DB::table('product_warehouse')
            ->where('warehouse_id', $this->ownerRecord->id)
            ->where('location_id', $location->id)
            ->where('quantity', '>', 0)
            ->get()
            ->keyBy('product_id');

        if ($stockData->isEmpty()) {
            return collect();
        }

        $products = Product::whereIn('id', $stockData->keys())->get();

        // Ajouter les données de stock comme attribut
        return $products->map(function ($product) use ($stockData) {
            $stock = $stockData->get($product->id);
            $product->stock_quantity = $stock->quantity ?? 0;
            $product->stock_reserved = $stock->reserved_quantity ?? 0;
            return $product;
        });
    }

    // Obtenir le stock d'un produit dans un emplacement
    protected function getProductStockInLocation(WarehouseLocation $location, ?int $productId): float
    {
        if (!$productId) return 0;
        
        return \DB::table('product_warehouse')
            ->where('warehouse_id', $this->ownerRecord->id)
            ->where('location_id', $location->id)
            ->where('product_id', $productId)
            ->value('quantity') ?? 0;
    }

    // Obtenir le stock non affecté à un emplacement (stock total - stock dans emplacements)
    protected function getUnassignedStock(?int $productId): float
    {
        if (!$productId) return 0;

        // Stock total du produit dans cet entrepôt
        $totalStock = \DB::table('product_warehouse')
            ->where('warehouse_id', $this->ownerRecord->id)
            ->where('product_id', $productId)
            ->sum('quantity');

        // Stock déjà affecté à des emplacements
        $assignedStock = \DB::table('product_warehouse')
            ->where('warehouse_id', $this->ownerRecord->id)
            ->where('product_id', $productId)
            ->whereNotNull('location_id')
            ->sum('quantity');

        return max(0, $totalStock - $assignedStock);
    }

    // Effectuer le transfert interne
    protected function performInternalTransfer(WarehouseLocation $source, array $data): void
    {
        $productId = $data['product_id'];
        $quantity = $data['quantity'];
        $destinationId = $data['destination_id'];

        \DB::transaction(function () use ($source, $productId, $quantity, $destinationId, $data) {
            // Réduire le stock source
            \DB::table('product_warehouse')
                ->where('warehouse_id', $this->ownerRecord->id)
                ->where('location_id', $source->id)
                ->where('product_id', $productId)
                ->decrement('quantity', $quantity);

            // Augmenter ou créer le stock destination
            $exists = \DB::table('product_warehouse')
                ->where('warehouse_id', $this->ownerRecord->id)
                ->where('location_id', $destinationId)
                ->where('product_id', $productId)
                ->exists();

            if ($exists) {
                \DB::table('product_warehouse')
                    ->where('warehouse_id', $this->ownerRecord->id)
                    ->where('location_id', $destinationId)
                    ->where('product_id', $productId)
                    ->increment('quantity', $quantity);
            } else {
                \DB::table('product_warehouse')->insert([
                    'company_id' => filament()->getTenant()->id,
                    'product_id' => $productId,
                    'warehouse_id' => $this->ownerRecord->id,
                    'location_id' => $destinationId,
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Créer un mouvement de stock (si le modèle existe)
            if (class_exists(\App\Models\StockMovement::class)) {
                // Stock source avant/après
                $sourceStockAfter = \DB::table('product_warehouse')
                    ->where('warehouse_id', $this->ownerRecord->id)
                    ->where('location_id', $source->id)
                    ->where('product_id', $productId)
                    ->value('quantity') ?? 0;
                $sourceStockBefore = $sourceStockAfter + $quantity;

                // Stock destination avant/après
                $destStockAfter = \DB::table('product_warehouse')
                    ->where('warehouse_id', $this->ownerRecord->id)
                    ->where('location_id', $destinationId)
                    ->where('product_id', $productId)
                    ->value('quantity') ?? 0;
                $destStockBefore = $destStockAfter - $quantity;

                \App\Models\StockMovement::create([
                    'company_id' => filament()->getTenant()->id,
                    'product_id' => $productId,
                    'warehouse_id' => $this->ownerRecord->id,
                    'location_id' => $source->id,
                    'type' => 'transfer_out',
                    'quantity' => -$quantity,
                    'quantity_before' => $sourceStockBefore,
                    'quantity_after' => $sourceStockAfter,
                    'reference' => 'INT-' . now()->format('YmdHis'),
                    'reason' => "Transfert vers " . WarehouseLocation::find($destinationId)?->code . ($data['notes'] ? " - {$data['notes']}" : ''),
                    'user_id' => auth()->id(),
                ]);

                \App\Models\StockMovement::create([
                    'company_id' => filament()->getTenant()->id,
                    'product_id' => $productId,
                    'warehouse_id' => $this->ownerRecord->id,
                    'location_id' => $destinationId,
                    'type' => 'transfer_in',
                    'quantity' => $quantity,
                    'quantity_before' => $destStockBefore,
                    'quantity_after' => $destStockAfter,
                    'reference' => 'INT-' . now()->format('YmdHis'),
                    'reason' => "Transfert depuis " . $source->code . ($data['notes'] ? " - {$data['notes']}" : ''),
                    'user_id' => auth()->id(),
                ]);
            }
        });

        Notification::make()
            ->success()
            ->title('Transfert effectué')
            ->body("$quantity unités transférées vers l'emplacement destination")
            ->send();
    }

    // Affecter un produit à un emplacement (organise le stock existant sans en ajouter)
    protected function assignProductToLocation(WarehouseLocation $location, array $data): bool
    {
        $productId = $data['product_id'];
        $quantity = $data['quantity'];

        // Vérifier le stock non affecté disponible
        $unassignedStock = $this->getUnassignedStock($productId);
        
        if ($unassignedStock < $quantity) {
            $product = Product::find($productId);
            Notification::make()
                ->danger()
                ->title('Stock insuffisant')
                ->body("Le produit {$product->name} n'a que {$unassignedStock} unités non affectées (demandé: {$quantity})")
                ->send();
            return false;
        }

        \DB::transaction(function () use ($location, $productId, $quantity, $data) {
            // 1. Trouver l'entrée principale sans emplacement (ou la plus ancienne avec stock)
            $mainEntry = \DB::table('product_warehouse')
                ->where('warehouse_id', $this->ownerRecord->id)
                ->where('product_id', $productId)
                ->where(function ($q) {
                    $q->whereNull('location_id')
                      ->orWhere('location_id', 0);
                })
                ->first();

            if ($mainEntry && $mainEntry->quantity >= $quantity) {
                // Réduire le stock de l'entrée principale
                \DB::table('product_warehouse')
                    ->where('id', $mainEntry->id)
                    ->decrement('quantity', $quantity);
            } else {
                // Pas d'entrée sans emplacement, on va juste organiser le stock existant
                // On ne touche pas aux autres entrées, on crée juste la nouvelle affectation
                // MAIS on doit quand même trouver d'où prendre le stock...
                
                // Si pas d'entrée principale, on crée une entrée négative temporaire puis on équilibre
                // En fait, on va simplement créer l'entrée dans l'emplacement
                // et supprimer/réduire d'une entrée sans emplacement si elle existe
                if ($mainEntry) {
                    // Réduire ce qu'on peut de l'entrée principale
                    $toDeduct = min($mainEntry->quantity, $quantity);
                    if ($toDeduct > 0) {
                        \DB::table('product_warehouse')
                            ->where('id', $mainEntry->id)
                            ->decrement('quantity', $toDeduct);
                    }
                }
            }

            // 2. Vérifier si le produit existe déjà dans cet emplacement
            $existing = \DB::table('product_warehouse')
                ->where('warehouse_id', $this->ownerRecord->id)
                ->where('location_id', $location->id)
                ->where('product_id', $productId)
                ->first();

            if ($existing) {
                // Mettre à jour la quantité dans l'emplacement
                \DB::table('product_warehouse')
                    ->where('id', $existing->id)
                    ->update([
                        'quantity' => $existing->quantity + $quantity,
                        'min_quantity' => $data['min_quantity'] ?? $existing->min_quantity,
                        'reorder_point' => $data['reorder_point'] ?? $existing->reorder_point,
                        'updated_at' => now(),
                    ]);
            } else {
                // Créer une nouvelle entrée pour l'emplacement
                \DB::table('product_warehouse')->insert([
                    'company_id' => filament()->getTenant()->id,
                    'product_id' => $productId,
                    'warehouse_id' => $this->ownerRecord->id,
                    'location_id' => $location->id,
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'min_quantity' => $data['min_quantity'] ?? null,
                    'reorder_point' => $data['reorder_point'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 3. Créer un mouvement de type "placement" (pas d'ajout de stock total)
            if (class_exists(\App\Models\StockMovement::class)) {
                \App\Models\StockMovement::create([
                    'company_id' => filament()->getTenant()->id,
                    'product_id' => $productId,
                    'warehouse_id' => $this->ownerRecord->id,
                    'location_id' => $location->id,
                    'type' => 'placement',
                    'quantity' => $quantity,
                    'quantity_before' => $existing ? $existing->quantity : 0,
                    'quantity_after' => ($existing ? $existing->quantity : 0) + $quantity,
                    'reference' => 'PLC-' . now()->format('YmdHis'),
                    'reason' => "Placement dans l'emplacement {$location->code}",
                    'user_id' => auth()->id(),
                ]);
            }
        });

        $product = Product::find($productId);
        Notification::make()
            ->success()
            ->title('Produit affecté')
            ->body("{$product->name} ({$quantity} unités) placé dans {$location->code}")
            ->send();
        
        return true;
    }
}

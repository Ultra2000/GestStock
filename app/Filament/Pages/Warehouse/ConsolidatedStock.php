<?php

namespace App\Filament\Pages\Warehouse;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ConsolidatedStock extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup = 'Stocks & Achats';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'Stock consolidé';
    protected static ?string $title = 'Vue consolidée des stocks';
    protected static string $view = 'filament.pages.warehouse.consolidated-stock';

    public static function shouldRegisterNavigation(): bool
    {
        return \Filament\Facades\Filament::getTenant()?->isModuleEnabled('stock') ?? true;
    }

    public static function canAccess(): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant?->isModuleEnabled('stock')) return false;
        
        $user = auth()->user();
        if (!$user) return false;
        
        return $user->isAdmin() || $user->hasPermission('products.view') || $user->hasPermission('warehouses.view') || $user->hasPermission('products.stock');
    }

    public ?int $warehouseFilter = null;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unité')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stock total')
                    ->numeric(2)
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('total_reserved')
                    ->label('Réservé')
                    ->numeric(2)
                    ->color('warning')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('available_stock')
                    ->label('Disponible')
                    ->numeric(2)
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('warehouses_count')
                    ->label('Entrepôts')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Prix achat')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_value')
                    ->label('Valeur stock')
                    ->money(fn () => \Filament\Facades\Filament::getTenant()->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'ok' => 'Normal',
                        'low' => 'Bas',
                        'out' => 'Rupture',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'ok' => 'success',
                        'low' => 'warning',
                        'out' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Entrepôt')
                    ->options(fn () => Warehouse::query()
                        ->where('company_id', filament()->getTenant()->id)
                        ->where('is_active', true)
                        ->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            $this->warehouseFilter = (int) $data['value'];
                        } else {
                            $this->warehouseFilter = null;
                        }
                        return $query;
                    }),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock bas')
                    ->query(fn (Builder $query) => $query
                        ->whereRaw('COALESCE((SELECT SUM(quantity) FROM product_warehouse WHERE product_id = products.id), products.stock) <= products.min_stock')),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Rupture de stock')
                    ->query(fn (Builder $query) => $query
                        ->whereRaw('COALESCE((SELECT SUM(quantity) FROM product_warehouse WHERE product_id = products.id), products.stock) <= 0')),
            ])
            ->actions([
                Tables\Actions\Action::make('viewDetails')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Product $record) => 'Stock de ' . $record->name)
                    ->modalContent(fn (Product $record) => view('filament.pages.warehouse.product-stock-details', [
                        'product' => $record,
                        'stocks' => $record->getStockByWarehouse(),
                    ])),
                Tables\Actions\Action::make('transfer')
                    ->label('Transférer')
                    ->icon('heroicon-o-truck')
                    ->url(fn (Product $record) => route('filament.admin.resources.stock-transfers.create', [
                        'tenant' => filament()->getTenant(),
                    ])),
            ])
            ->bulkActions([])
            ->defaultSort('name')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    protected function getTableQuery(): Builder
    {
        $companyId = filament()->getTenant()->id;

        return Product::query()
            ->where('company_id', $companyId)
            ->select([
                'products.*',
                DB::raw('COALESCE((SELECT SUM(quantity) FROM product_warehouse WHERE product_id = products.id), products.stock) as total_stock'),
                DB::raw('COALESCE((SELECT SUM(reserved_quantity) FROM product_warehouse WHERE product_id = products.id), 0) as total_reserved'),
                DB::raw('COALESCE((SELECT SUM(quantity) FROM product_warehouse WHERE product_id = products.id), products.stock) - COALESCE((SELECT SUM(reserved_quantity) FROM product_warehouse WHERE product_id = products.id), 0) as available_stock'),
                DB::raw('(SELECT COUNT(DISTINCT warehouse_id) FROM product_warehouse WHERE product_id = products.id) as warehouses_count'),
                DB::raw('COALESCE((SELECT SUM(quantity) FROM product_warehouse WHERE product_id = products.id), products.stock) * COALESCE(products.purchase_price, 0) as total_value'),
                DB::raw('CASE 
                    WHEN COALESCE((SELECT SUM(quantity) FROM product_warehouse WHERE product_id = products.id), products.stock) <= 0 THEN "out"
                    WHEN COALESCE((SELECT SUM(quantity) FROM product_warehouse WHERE product_id = products.id), products.stock) <= products.min_stock THEN "low"
                    ELSE "ok"
                END as stock_status'),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\WarehouseOverview::class,
        ];
    }
}

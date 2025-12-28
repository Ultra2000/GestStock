<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class WarehouseStockSummary extends BaseWidget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Stock par entrepôt';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Warehouse::query()
                    ->where('company_id', filament()->getTenant()?->id)
                    ->where('is_active', true)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Entrepôt')
                    ->icon('heroicon-o-building-storefront')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'warehouse' => 'Entrepôt',
                        'store' => 'Magasin',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'warehouse',
                        'success' => 'store',
                    ]),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Produits')
                    ->counts('products')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('stock_value')
                    ->label('Valeur stock')
                    ->state(fn (Warehouse $record) => number_format($record->getTotalStockValue(), 0, ',', ' ') . ' ' . filament()->getTenant()->currency)
                    ->color('success'),
                Tables\Columns\TextColumn::make('low_stock')
                    ->label('Alertes')
                    ->state(fn (Warehouse $record) => $record->getLowStockProducts()->count())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('pending_transfers')
                    ->label('Transferts entrants')
                    ->state(fn (Warehouse $record) => $record->incomingTransfers()
                        ->whereIn('status', ['approved', 'in_transit'])
                        ->count())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Warehouse $record) => route('filament.admin.resources.warehouses.view', [
                        'tenant' => filament()->getTenant(),
                        'record' => $record,
                    ])),
            ])
            ->paginated(false);
    }
}

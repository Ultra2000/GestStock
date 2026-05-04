<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Filament\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Filament\Facades\Filament;

class ProductPurchaseHistory extends Page
{
    protected static string $resource = ProductResource::class;

    protected static string $view = 'filament.resources.product-resource.pages.product-purchase-history';

    public int $productId = 0;
    public string $period = 'this_year';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $supplierFilter = '';

    public function mount(int|string $record): void
    {
        $product = Product::withoutGlobalScope(SoftDeletingScope::class)->findOrFail($record);
        $this->productId = $product->id;
    }

    #[Computed]
    public function product(): Product
    {
        return Product::withoutGlobalScope(SoftDeletingScope::class)->findOrFail($this->productId);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Historique achats — ' . $this->product->name;
    }

    protected function getDateRange(): array
    {
        $now = Carbon::now();

        return match ($this->period) {
            'this_month'  => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month'  => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'last_quarter' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'this_year'   => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_year'   => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'custom'      => [
                $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : $now->copy()->startOfYear(),
                $this->dateTo   ? Carbon::parse($this->dateTo)->endOfDay()     : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };
    }

    protected function baseQuery()
    {
        [$from, $to] = $this->getDateRange();

        $query = PurchaseItem::query()
            ->where('product_id', $this->productId)
            ->whereHas('purchase', function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to]);
                if ($this->supplierFilter) {
                    $q->where('supplier_id', $this->supplierFilter);
                }
            })
            ->with(['purchase.supplier']);

        return $query;
    }

    #[Computed]
    public function kpis(): array
    {
        $items = $this->baseQuery()->get();

        $totalUnits   = $items->sum('quantity');
        $totalHt      = $items->sum('total_price_ht');
        $orderCount   = $items->pluck('purchase_id')->unique()->count();
        $avgDiscount  = $items->where('discount_percent', '>', 0)->avg('discount_percent') ?? 0;
        $totalDiscount = $items->sum('discount_amount');
        $currency     = Filament::getTenant()->currency ?? 'EUR';

        return [
            'total_units'    => $totalUnits,
            'total_ht'       => $totalHt,
            'order_count'    => $orderCount,
            'avg_discount'   => round($avgDiscount, 1),
            'total_discount' => $totalDiscount,
            'currency'       => $currency,
        ];
    }

    #[Computed]
    public function purchaseLines()
    {
        return $this->baseQuery()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->select('purchase_items.*')
            ->orderBy('purchases.created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function suppliers()
    {
        $companyId = Filament::getTenant()?->id;
        return Supplier::where('company_id', $companyId)->orderBy('name')->pluck('name', 'id');
    }

    protected function getViewData(): array
    {
        return [
            'product'       => $this->product,
            'kpis'          => $this->kpis,
            'purchaseLines' => $this->purchaseLines,
            'suppliers'     => $this->suppliers,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Retour aux produits')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }
}

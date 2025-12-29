<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use App\Models\Warehouse;

class ReportsCenter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Centre de Rapports';
    protected static ?string $title = 'Centre de Rapports PDF';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.reports-center';

    // Formulaire état des stocks
    public ?string $stock_warehouse_id = null;
    public bool $stock_low_only = false;

    // Formulaire bilan comptable
    public ?string $financial_start_date = null;
    public ?string $financial_end_date = null;

    // Formulaire journal ventes
    public ?string $sales_start_date = null;
    public ?string $sales_end_date = null;

    // Formulaire journal achats
    public ?string $purchases_start_date = null;
    public ?string $purchases_end_date = null;

    public function mount(): void
    {
        $this->financial_start_date = now()->startOfYear()->toDateString();
        $this->financial_end_date = now()->toDateString();
        $this->sales_start_date = now()->startOfMonth()->toDateString();
        $this->sales_end_date = now()->toDateString();
        $this->purchases_start_date = now()->startOfMonth()->toDateString();
        $this->purchases_end_date = now()->toDateString();
    }

    public function getCompanyId(): ?int
    {
        return Filament::getTenant()?->id;
    }

    public function downloadStockStatus(): void
    {
        $params = [
            'warehouse_id' => $this->stock_warehouse_id,
            'low_stock_only' => $this->stock_low_only ? '1' : '0',
        ];
        
        $url = route('reports.stock-status', ['companyId' => $this->getCompanyId()]) . '?' . http_build_query($params);
        
        $this->js("window.open('{$url}', '_blank')");
    }

    public function downloadInventoryCsv(): void
    {
        $url = route('reports.inventory-export', ['companyId' => $this->getCompanyId()]);
        $this->js("window.open('{$url}', '_blank')");
    }

    public function downloadFinancialReport(): void
    {
        $params = [
            'start_date' => $this->financial_start_date,
            'end_date' => $this->financial_end_date,
        ];
        
        $url = route('reports.financial', ['companyId' => $this->getCompanyId()]) . '?' . http_build_query($params);
        
        $this->js("window.open('{$url}', '_blank')");
    }

    public function downloadSalesJournal(): void
    {
        $params = [
            'start_date' => $this->sales_start_date,
            'end_date' => $this->sales_end_date,
        ];
        
        $url = route('reports.sales-journal', ['companyId' => $this->getCompanyId()]) . '?' . http_build_query($params);
        
        $this->js("window.open('{$url}', '_blank')");
    }

    public function downloadPurchasesJournal(): void
    {
        $params = [
            'start_date' => $this->purchases_start_date,
            'end_date' => $this->purchases_end_date,
        ];
        
        $url = route('reports.purchases-journal', ['companyId' => $this->getCompanyId()]) . '?' . http_build_query($params);
        
        $this->js("window.open('{$url}', '_blank')");
    }

    public function getWarehouses(): array
    {
        return Warehouse::where('company_id', $this->getCompanyId())
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function shouldRegisterNavigation(): bool
    {
        $company = Filament::getTenant();
        if (!$company) return true;
        
        return $company->isModuleEnabled('accounting');
    }
}

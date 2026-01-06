<?php

namespace App\Filament\Pages;

use App\Models\BankTransaction;
use App\Models\Sale;
use App\Models\Purchase;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;

class AccountingReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?string $title = 'Rapports & TVA';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.accounting-reports';

    public static function shouldRegisterNavigation(): bool
    {
        return \Filament\Facades\Filament::getTenant()?->isModuleEnabled('accounting') ?? false;
    }

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getTenant()?->isModuleEnabled('accounting') ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'period' => 'month',
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Période')
                    ->schema([
                        Select::make('period')
                            ->label('Période prédéfinie')
                            ->options([
                                'month' => 'Mois en cours',
                                'quarter' => 'Trimestre en cours',
                                'year' => 'Année en cours',
                                'custom' => 'Personnalisée',
                            ])
                            ->default('month')
                            ->live()
                            ->afterStateUpdated(fn ($state) => $this->updatePeriod($state)),
                        DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required()
                            ->visible(fn ($get) => $get('period') === 'custom'),
                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required()
                            ->visible(fn ($get) => $get('period') === 'custom'),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function updatePeriod(?string $period): void
    {
        if (!$period) {
            return;
        }

        switch ($period) {
            case 'month':
                $this->data['start_date'] = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->data['end_date'] = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'quarter':
                $this->data['start_date'] = Carbon::now()->startOfQuarter()->format('Y-m-d');
                $this->data['end_date'] = Carbon::now()->endOfQuarter()->format('Y-m-d');
                break;
            case 'year':
                $this->data['start_date'] = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->data['end_date'] = Carbon::now()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getReportData()
    {
        $companyId = Filament::getTenant()?->id;
        $startDate = $this->data['start_date'];
        $endDate = $this->data['end_date'] . ' 23:59:59';

        // 1. Recettes issues des Ventes (Chiffre d'Affaires)
        $salesByMethod = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('payment_method, SUM(total) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $salesCash = (float) ($salesByMethod['cash'] ?? 0);
        $salesBank = (float) (($salesByMethod['card'] ?? 0) + ($salesByMethod['transfer'] ?? 0) + ($salesByMethod['check'] ?? 0));
        $salesOther = (float) (($salesByMethod['sepa_debit'] ?? 0) + ($salesByMethod['paypal'] ?? 0));

        // 2. Dépenses issues des Achats (uniquement achats complétés)
        $purchasesTotal = Purchase::where('company_id', $companyId)
            ->whereIn('status', ['completed', 'received', 'paid'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        // TVA Collectée réelle (depuis les ventes)
        $salesData = Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total_ht), 0) as total_ht, COALESCE(SUM(total_vat), 0) as total_vat, COALESCE(SUM(total), 0) as total')
            ->first();

        // TVA Déductible réelle (depuis les achats)
        $purchasesData = Purchase::where('company_id', $companyId)
            ->whereIn('status', ['completed', 'received', 'paid'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total_ht), 0) as total_ht, COALESCE(SUM(total_vat), 0) as total_vat, COALESCE(SUM(total), 0) as total')
            ->first();

        $vatCollected = (float) ($salesData->total_vat ?? 0);
        $vatDeductible = (float) ($purchasesData->total_vat ?? 0);
        $vatToPay = $vatCollected - $vatDeductible;

        // Calcul du résultat
        $totalRecettes = $salesCash + $salesBank + $salesOther;
        $totalDepenses = (float) $purchasesTotal;
        $resultat = $totalRecettes - $totalDepenses;

        return [
            'income' => $salesBank,
            'cash_income' => $salesCash,
            'other_income' => $salesOther,
            'total_revenue' => $totalRecettes,
            'expenses' => $totalDepenses,
            'balance' => $resultat,
            // Données ventes
            'sales_ht' => (float) ($salesData->total_ht ?? 0),
            'sales_ttc' => (float) ($salesData->total ?? 0),
            'vat_collected' => $vatCollected,
            // Données achats
            'purchases_ht' => (float) ($purchasesData->total_ht ?? 0),
            'purchases_ttc' => (float) ($purchasesData->total ?? 0),
            'vat_deductible' => $vatDeductible,
            // Solde TVA
            'vat_to_pay' => $vatToPay,
            'vat_status' => $vatToPay >= 0 ? 'à_reverser' : 'crédit',
        ];
    }

    /**
     * Ventilation TVA Collectée par taux
     */
    public function getVatCollectedBreakdown(): array
    {
        $companyId = Filament::getTenant()?->id;
        $startDate = $this->data['start_date'];
        $endDate = $this->data['end_date'] . ' 23:59:59';

        return Sale::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('items')
            ->get()
            ->flatMap(fn ($sale) => $sale->getVatBreakdown())
            ->groupBy('rate')
            ->map(function ($items, $rate) {
                return [
                    'rate' => (float) $rate,
                    'category' => $items->first()['category'] ?? 'S',
                    'base' => $items->sum('base'),
                    'amount' => $items->sum('amount'),
                ];
            })
            ->sortByDesc('rate')
            ->values()
            ->toArray();
    }

    /**
     * Ventilation TVA Déductible par taux
     */
    public function getVatDeductibleBreakdown(): array
    {
        $companyId = Filament::getTenant()?->id;
        $startDate = $this->data['start_date'];
        $endDate = $this->data['end_date'] . ' 23:59:59';

        return Purchase::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('items')
            ->get()
            ->flatMap(fn ($purchase) => $purchase->getVatBreakdown())
            ->groupBy('rate')
            ->map(function ($items, $rate) {
                return [
                    'rate' => (float) $rate,
                    'base' => $items->sum('base'),
                    'amount' => $items->sum('amount'),
                ];
            })
            ->sortByDesc('rate')
            ->values()
            ->toArray();
    }

    public function getCurrency(): string
    {
        return Filament::getTenant()->currency ?? 'EUR';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Exporter PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $data = [
                        'company' => Filament::getTenant(),
                        'period' => [
                            'start' => Carbon::parse($this->data['start_date'])->translatedFormat('d F Y'),
                            'end' => Carbon::parse($this->data['end_date'])->translatedFormat('d F Y'),
                        ],
                        'report' => $this->getReportData(),
                        'collected' => $this->getVatCollectedBreakdown(),
                        'deductible' => $this->getVatDeductibleBreakdown(),
                        'currency' => $this->getCurrency(),
                    ];
                    
                    $pdf = Pdf::loadView('reports.vat-report', $data);
                    
                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'rapport-tva-' . $this->data['start_date'] . '-' . $this->data['end_date'] . '.pdf'
                    );
                }),
        ];
    }
}

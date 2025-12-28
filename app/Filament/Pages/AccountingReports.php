<?php

namespace App\Filament\Pages;

use App\Models\BankTransaction;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AccountingReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?string $title = 'Rapports & TVA';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.accounting-reports';

    public static function canAccess(): bool
    {
        return \Filament\Facades\Filament::getTenant()?->isModuleEnabled('accounting') ?? false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
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
                        DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->required(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function getReportData()
    {
        $startDate = $this->data['start_date'];
        $endDate = $this->data['end_date'];

        // Recettes (Crédits)
        $income = BankTransaction::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'credit')
            ->sum('amount');

        // Dépenses (Débits)
        $expenses = BankTransaction::whereBetween('date', [$startDate, $endDate])
            ->where('type', 'debit')
            ->sum('amount');

        // TVA Estimée (Simplifiée à 20% pour l'exemple, à affiner selon les catégories)
        // Dans un vrai système, on sommerait la TVA de chaque ligne si elle était stockée
        $estimatedVatCollected = $income * 0.20; // 20% sur les recettes
        $estimatedVatDeductible = $expenses * 0.20; // 20% sur les dépenses
        $vatToPay = $estimatedVatCollected - $estimatedVatDeductible;

        return [
            'income' => $income,
            'expenses' => $expenses,
            'balance' => $income - $expenses,
            'vat_collected' => $estimatedVatCollected,
            'vat_deductible' => $estimatedVatDeductible,
            'vat_to_pay' => $vatToPay,
        ];
    }
}

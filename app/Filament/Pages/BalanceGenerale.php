<?php

namespace App\Filament\Pages;

use App\Models\AccountingEntry;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BalanceGenerale extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static string $view = 'filament.pages.balance-generale';

    protected static ?string $navigationLabel = 'Balance Générale';

    protected static ?string $title = 'Balance Générale';

    protected static ?string $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 8;

    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfYear()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function getTableRecordKey($record): string
    {
        return $record->account_number;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getBalanceQuery())
            ->columns([
                TextColumn::make('account_number')
                    ->label('N° Compte')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($record) => $this->getAccountColor($record->account_number)),

                TextColumn::make('account_label')
                    ->label('Libellé')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('total_debit')
                    ->label('Total Débit')
                    ->money('EUR')
                    ->alignEnd()
                    ->color('success'),

                TextColumn::make('total_credit')
                    ->label('Total Crédit')
                    ->money('EUR')
                    ->alignEnd()
                    ->color('danger'),

                TextColumn::make('solde_debiteur')
                    ->label('Solde Débiteur')
                    ->money('EUR')
                    ->alignEnd()
                    ->getStateUsing(fn ($record) => $record->solde > 0 ? $record->solde : null)
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('solde_crediteur')
                    ->label('Solde Créditeur')
                    ->money('EUR')
                    ->alignEnd()
                    ->getStateUsing(fn ($record) => $record->solde < 0 ? abs($record->solde) : null)
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->filters([
                Filter::make('periode')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('Du')
                            ->default(now()->startOfYear()),
                        DatePicker::make('date_to')
                            ->label('Au')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q) => $q->having('min_date', '>=', $data['date_from']))
                            ->when($data['date_to'], fn ($q) => $q->having('max_date', '<=', $data['date_to']));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['date_from'] && $data['date_to']) {
                            return 'Période: ' . $data['date_from'] . ' au ' . $data['date_to'];
                        }
                        return null;
                    }),
            ])
            ->defaultSort('account_number')
            ->striped()
            ->paginated(false);
    }

    protected function getBalanceQuery(): Builder
    {
        $companyId = filament()->getTenant()?->id;

        // Sous-requête pour ajouter le libellé du compte
        return AccountingEntry::query()
            ->select([
                'account_number',
                DB::raw('MIN(entry_date) as min_date'),
                DB::raw('MAX(entry_date) as max_date'),
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('SUM(credit) as total_credit'),
                DB::raw('SUM(debit) - SUM(credit) as solde'),
                DB::raw("CASE 
                    WHEN account_number LIKE '401%' THEN 'Fournisseurs'
                    WHEN account_number LIKE '411%' THEN 'Clients'
                    WHEN account_number LIKE '44566%' THEN 'TVA Déductible'
                    WHEN account_number LIKE '44571%' THEN 'TVA Collectée'
                    WHEN account_number LIKE '44574%' THEN 'TVA en attente'
                    WHEN account_number LIKE '445%' THEN 'TVA'
                    WHEN account_number LIKE '511%' THEN 'Chèques à encaisser'
                    WHEN account_number LIKE '512%' THEN 'Banque'
                    WHEN account_number LIKE '530%' THEN 'Caisse'
                    WHEN account_number LIKE '607%' THEN 'Achats de marchandises'
                    WHEN account_number LIKE '609%' THEN 'RRR obtenus'
                    WHEN account_number LIKE '701%' THEN 'Ventes de produits finis'
                    WHEN account_number LIKE '706%' THEN 'Prestations de services'
                    WHEN account_number LIKE '707%' THEN 'Ventes de marchandises'
                    WHEN account_number LIKE '709%' THEN 'RRR accordés'
                    ELSE 'Compte ' || account_number
                END as account_label"),
            ])
            ->where('company_id', $companyId)
            ->groupBy('account_number');
    }

    protected function getAccountColor(string $accountNumber): string
    {
        $class = substr($accountNumber, 0, 1);
        
        return match ($class) {
            '1' => 'gray',      // Capitaux
            '2' => 'purple',    // Immobilisations
            '3' => 'orange',    // Stocks
            '4' => 'info',      // Tiers
            '5' => 'success',   // Financiers
            '6' => 'danger',    // Charges
            '7' => 'success',   // Produits
            default => 'gray',
        };
    }

    public function getBalanceTotals(): array
    {
        $companyId = filament()->getTenant()?->id;

        $totals = AccountingEntry::where('company_id', $companyId)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $totalDebit = $totals->total_debit ?? 0;
        $totalCredit = $totals->total_credit ?? 0;

        // Calcul des soldes débiteurs et créditeurs
        $soldes = AccountingEntry::where('company_id', $companyId)
            ->select('account_number')
            ->selectRaw('SUM(debit) - SUM(credit) as solde')
            ->groupBy('account_number')
            ->get();

        $totalSoldeDebiteur = $soldes->where('solde', '>', 0)->sum('solde');
        $totalSoldeCrediteur = abs($soldes->where('solde', '<', 0)->sum('solde'));

        return [
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'difference' => $totalDebit - $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'solde_debiteur' => $totalSoldeDebiteur,
            'solde_crediteur' => $totalSoldeCrediteur,
            'soldes_equilibres' => abs($totalSoldeDebiteur - $totalSoldeCrediteur) < 0.01,
        ];
    }

    public function getBalanceByClass(): array
    {
        $companyId = filament()->getTenant()?->id;

        $results = AccountingEntry::where('company_id', $companyId)
            ->selectRaw('SUBSTR(account_number, 1, 1) as classe')
            ->selectRaw('SUM(debit) as total_debit')
            ->selectRaw('SUM(credit) as total_credit')
            ->selectRaw('SUM(debit) - SUM(credit) as solde')
            ->groupBy('classe')
            ->orderBy('classe')
            ->get();

        $classes = [
            '1' => 'Capitaux',
            '2' => 'Immobilisations',
            '3' => 'Stocks',
            '4' => 'Tiers',
            '5' => 'Financiers',
            '6' => 'Charges',
            '7' => 'Produits',
            '8' => 'Comptes spéciaux',
        ];

        return $results->map(function ($item) use ($classes) {
            return [
                'classe' => $item->classe,
                'label' => $classes[$item->classe] ?? 'Classe ' . $item->classe,
                'total_debit' => $item->total_debit,
                'total_credit' => $item->total_credit,
                'solde' => $item->solde,
            ];
        })->toArray();
    }

    public function getAccountLabels(): array
    {
        return [
            '401' => 'Fournisseurs',
            '411' => 'Clients',
            '445' => 'TVA',
            '512' => 'Banque',
            '530' => 'Caisse',
            '607' => 'Achats de marchandises',
            '701' => 'Ventes de produits finis',
            '706' => 'Prestations de services',
            '707' => 'Ventes de marchandises',
        ];
    }
}

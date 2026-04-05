<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Facades\Filament;
use Carbon\Carbon;

class UnpaidInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Factures impayées';

    public function table(Table $table): Table
    {
        $companyId = Filament::getTenant()?->id;

        return $table
            ->query(
                Sale::query()
                    ->where('company_id', $companyId)
                    ->where('status', 'completed')
                    ->where('type', '!=', 'credit_note')
                    ->where(function ($q) {
                        $q->whereNull('payment_status')
                          ->orWhereIn('payment_status', ['pending', 'partial']);
                    })
                    ->orderByRaw('CASE WHEN due_date < ? THEN 0 ELSE 1 END', [now()])
                    ->orderBy('due_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('N° Facture')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Montant TTC')
                    ->money(Filament::getTenant()->currency ?? 'EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Payé')
                    ->money(Filament::getTenant()->currency ?? 'EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining')
                    ->label('Reste dû')
                    ->state(fn (Sale $record) => $record->total - ($record->amount_paid ?? 0))
                    ->money(Filament::getTenant()->currency ?? 'EUR')
                    ->color('danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (Sale $record) => $record->due_date && $record->due_date->isPast() ? 'danger' : 'warning')
                    ->description(fn (Sale $record) => $record->due_date && $record->due_date->isPast()
                        ? 'En retard de ' . $record->due_date->diffInDays(now()) . ' jour(s)'
                        : null),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'partial' => 'Partiel',
                        'pending' => 'En attente',
                        default => 'Non payé',
                    })
                    ->colors([
                        'warning' => 'partial',
                        'danger' => fn ($state) => $state === 'pending' || $state === null,
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Sale $record) => route('filament.admin.resources.sales.view', [
                        'tenant' => Filament::getTenant(),
                        'record' => $record,
                    ])),
            ])
            ->emptyStateHeading('Aucune facture impayée')
            ->emptyStateDescription('Toutes vos factures sont réglées.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    public static function canView(): bool
    {
        return Filament::getTenant() !== null;
    }
}

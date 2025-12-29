<?php

namespace App\Filament\Pages\Cashier;

use App\Models\CashSession;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class CashSessionsHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'filament.pages.cashier.cash-sessions-history';
    protected static ?string $navigationLabel = 'Historique sessions';
    protected static ?string $title = 'Historique des sessions de caisse';
    protected static ?string $navigationGroup = 'Point de Vente';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('pos')) {
            return false;
        }
        
        $user = auth()->user();
        if (!$user) return false;
        
        return $user->isAdmin() || $user->hasPermission('sales.view') || $user->hasPermission('sales.*');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CashSession::query()
                    ->where('company_id', Filament::getTenant()?->id)
                    ->with('user')
                    ->orderByDesc('opened_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Caissier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Ouverture')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Fermeture')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('En cours')
                    ->sortable(),
                Tables\Columns\TextColumn::make('opening_amount')
                    ->label('Fond de caisse')
                    ->money('EUR')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Total ventes')
                    ->money('EUR')
                    ->alignEnd()
                    ->color('success'),
                Tables\Columns\TextColumn::make('closing_amount')
                    ->label('Clôture')
                    ->money('EUR')
                    ->alignEnd()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('difference')
                    ->label('Écart')
                    ->formatStateUsing(function ($record) {
                        if (!$record->closed_at) return '-';
                        $expected = $record->opening_amount + $record->total_sales;
                        $diff = $record->closing_amount - $expected;
                        return number_format($diff, 2, ',', ' ') . ' €';
                    })
                    ->color(function ($record) {
                        if (!$record->closed_at) return 'gray';
                        $expected = $record->opening_amount + $record->total_sales;
                        $diff = $record->closing_amount - $expected;
                        return $diff == 0 ? 'success' : ($diff > 0 ? 'warning' : 'danger');
                    })
                    ->alignEnd(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Statut')
                    ->icon(fn ($record) => $record->closed_at ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                    ->color(fn ($record) => $record->closed_at ? 'success' : 'warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Caissier')
                    ->relationship('user', 'name'),
                Tables\Filters\Filter::make('open_sessions')
                    ->label('Sessions en cours')
                    ->query(fn (Builder $query) => $query->whereNull('closed_at'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Détails')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => 'Session du ' . $record->opened_at->format('d/m/Y'))
                    ->modalContent(fn ($record) => view('filament.pages.cashier.session-details', ['session' => $record])),
            ])
            ->emptyStateHeading('Aucune session de caisse')
            ->emptyStateDescription('Les sessions apparaîtront ici une fois ouvertes depuis le point de vente.');
    }
}

<?php

namespace App\Filament\Resources\AccountingEntryResource\Pages;

use App\Filament\Resources\AccountingEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAccountingEntries extends ListRecords
{
    protected static string $resource = AccountingEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_fec')
                ->label('Exporter FEC')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => route('filament.admin.pages.accounting-export', ['tenant' => \Filament\Facades\Filament::getTenant()]))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes')
                ->icon('heroicon-o-document-text'),
            
            'ventes' => Tab::make('Ventes')
                ->icon('heroicon-o-shopping-cart')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('journal_code', 'VTE'))
                ->badge(fn () => $this->getModel()::where('journal_code', 'VTE')->count()),
            
            'achats' => Tab::make('Achats')
                ->icon('heroicon-o-truck')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('journal_code', 'ACH'))
                ->badge(fn () => $this->getModel()::where('journal_code', 'ACH')->count()),
            
            'banque' => Tab::make('Banque')
                ->icon('heroicon-o-building-library')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('journal_code', 'BQ')),
            
            'caisse' => Tab::make('Caisse')
                ->icon('heroicon-o-banknotes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('journal_code', 'CAI')),
            
            'non_lettrees' => Tab::make('Non lettrées')
                ->icon('heroicon-o-link-slash')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('lettering'))
                ->badge(fn () => $this->getModel()::whereNull('lettering')->count())
                ->badgeColor('warning'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AccountingEntryResource\Widgets\AccountingBalanceWidget::class,
        ];
    }
}

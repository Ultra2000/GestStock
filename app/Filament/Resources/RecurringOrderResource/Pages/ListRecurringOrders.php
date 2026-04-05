<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRecurringOrders extends ListRecords
{
    protected static string $resource = RecurringOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'active' => Tab::make('Actifs')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active'))
                ->badge(fn () => RecurringOrderResource::getEloquentQuery()->where('status', 'active')->count())
                ->badgeColor('success'),
            'paused' => Tab::make('En pause')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paused'))
                ->badge(fn () => RecurringOrderResource::getEloquentQuery()->where('status', 'paused')->count())
                ->badgeColor('warning'),
            'due' => Tab::make('À exécuter')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')->where('next_order_date', '<=', now()))
                ->badge(fn () => RecurringOrderResource::getEloquentQuery()->where('status', 'active')->where('next_order_date', '<=', now())->count())
                ->badgeColor('danger'),
        ];
    }
}

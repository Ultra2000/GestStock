<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('clock_in')
                ->label('Pointer entrée')
                ->icon('heroicon-o-arrow-right-start-on-rectangle')
                ->color('success')
                ->action(fn () => $this->record->clockIn())
                ->visible(fn () => $this->record->status === 'active'),
            Actions\Action::make('clock_out')
                ->label('Pointer sortie')
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('warning')
                ->action(fn () => $this->record->clockOut())
                ->visible(fn () => $this->record->status === 'active'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations personnelles')
                    ->schema([
                        Infolists\Components\ImageEntry::make('photo')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->full_name) . '&background=7c3aed&color=fff&size=128'),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('employee_number')
                                    ->label('Matricule'),
                                Infolists\Components\TextEntry::make('full_name')
                                    ->label('Nom complet'),
                                Infolists\Components\TextEntry::make('birth_date')
                                    ->label('Date de naissance')
                                    ->date('d/m/Y'),
                            ]),
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope'),
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('Téléphone')
                                    ->icon('heroicon-o-phone'),
                                Infolists\Components\TextEntry::make('address')
                                    ->label('Adresse'),
                            ]),
                    ]),
                Infolists\Components\Section::make('Contrat')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('position')
                                    ->label('Poste'),
                                Infolists\Components\TextEntry::make('department')
                                    ->label('Service'),
                                Infolists\Components\TextEntry::make('contract_type_label')
                                    ->label('Type de contrat')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('status_label')
                                    ->label('Statut')
                                    ->badge()
                                    ->color(fn ($record) => $record->status_color),
                            ]),
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('hire_date')
                                    ->label("Date d'embauche")
                                    ->date('d/m/Y'),
                                Infolists\Components\TextEntry::make('seniority')
                                    ->label('Ancienneté'),
                                Infolists\Components\TextEntry::make('weekly_hours')
                                    ->label('Heures/semaine')
                                    ->suffix('h'),
                                Infolists\Components\TextEntry::make('commission_rate')
                                    ->label('Commission')
                                    ->suffix('%'),
                            ]),
                    ]),
            ]);
    }
}

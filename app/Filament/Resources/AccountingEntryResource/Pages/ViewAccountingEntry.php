<?php

namespace App\Filament\Resources\AccountingEntryResource\Pages;

use App\Filament\Resources\AccountingEntryResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;

class ViewAccountingEntry extends ViewRecord
{
    protected static string $resource = AccountingEntryResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informations de l\'écriture')
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('entry_date')
                            ->label('Date')
                            ->date('d/m/Y')
                            ->icon('heroicon-o-calendar'),
                        
                        TextEntry::make('piece_number')
                            ->label('N° pièce')
                            ->icon('heroicon-o-hashtag'),
                        
                        TextEntry::make('journal_code')
                            ->label('Journal')
                            ->badge()
                            ->color(fn (string $state) => match($state) {
                                'VTE' => 'success',
                                'ACH' => 'warning',
                                'BQ' => 'info',
                                'CAI' => 'primary',
                                'OD' => 'gray',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Compte comptable')
                    ->icon('heroicon-o-calculator')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('account_number')
                            ->label('Compte général')
                            ->weight('bold')
                            ->icon('heroicon-o-document-duplicate'),
                        
                        TextEntry::make('account_auxiliary')
                            ->label('Compte auxiliaire')
                            ->placeholder('Aucun')
                            ->icon('heroicon-o-user'),
                        
                        TextEntry::make('account_label')
                            ->label('Libellé du compte')
                            ->columnSpanFull(),
                    ]),

                Section::make('Montants')
                    ->icon('heroicon-o-currency-euro')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('debit')
                            ->label('Débit')
                            ->money('EUR')
                            ->color('danger')
                            ->weight(fn ($state) => $state > 0 ? 'bold' : 'normal'),
                        
                        TextEntry::make('credit')
                            ->label('Crédit')
                            ->money('EUR')
                            ->color('success')
                            ->weight(fn ($state) => $state > 0 ? 'bold' : 'normal'),
                        
                        TextEntry::make('vat_rate')
                            ->label('Taux TVA')
                            ->suffix(' %')
                            ->placeholder('N/A'),
                        
                        TextEntry::make('currency')
                            ->label('Devise')
                            ->default('EUR'),
                    ]),

                Section::make('Lettrage & Verrouillage')
                    ->icon('heroicon-o-link')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('lettering')
                            ->label('Code lettrage')
                            ->placeholder('Non lettré')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        
                        TextEntry::make('lettering_date')
                            ->label('Date lettrage')
                            ->date('d/m/Y')
                            ->placeholder('N/A'),
                        
                        IconEntry::make('is_locked')
                            ->label('Verrouillé')
                            ->boolean()
                            ->trueIcon('heroicon-o-lock-closed')
                            ->falseIcon('heroicon-o-lock-open')
                            ->trueColor('success')
                            ->falseColor('warning'),
                    ]),

                Section::make('Origine')
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('source_type')
                            ->label('Type de document')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'App\Models\Sale' => 'Vente',
                                'App\Models\Purchase' => 'Achat',
                                default => $state ?? 'Manuel',
                            }),
                        
                        TextEntry::make('source_id')
                            ->label('ID document')
                            ->placeholder('N/A'),
                        
                        TextEntry::make('reversal_of_id')
                            ->label('Extourne de')
                            ->placeholder('N/A')
                            ->url(fn ($state) => $state ? AccountingEntryResource::getUrl('view', ['record' => $state]) : null),
                        
                        TextEntry::make('label')
                            ->label('Libellé complet')
                            ->columnSpanFull(),
                    ]),

                Section::make('Traçabilité')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y H:i:s'),
                        
                        TextEntry::make('updated_at')
                            ->label('Modifié le')
                            ->dateTime('d/m/Y H:i:s'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}

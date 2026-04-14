<?php

namespace App\Filament\Superadmin\Pages;

use App\Models\AppSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GlobalSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Paramètres globaux';
    protected static ?string $title           = 'Paramètres globaux';
    protected static ?int    $navigationSort  = 20;

    protected static string $view = 'filament.superadmin.pages.global-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'trial_days' => AppSetting::get('trial_days', 180),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Période d\'essai')
                    ->description('Configurez la durée offerte aux nouveaux inscrits.')
                    ->icon('heroicon-o-gift')
                    ->schema([
                        TextInput::make('trial_days')
                            ->label('Durée de l\'essai gratuit (jours)')
                            ->helperText('180 jours = 6 mois. Applicable aux nouvelles inscriptions uniquement.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(3650)
                            ->required()
                            ->suffix('jours'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->form->validate();

        AppSetting::set('trial_days', (int) $this->data['trial_days']);

        Notification::make()
            ->title('Paramètres sauvegardés')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Enregistrer')
                ->icon('heroicon-o-check')
                ->action(fn () => $this->save()),
        ];
    }
}

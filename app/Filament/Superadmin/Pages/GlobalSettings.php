<?php

namespace App\Filament\Superadmin\Pages;

use App\Mail\AnnouncementBanner;
use App\Models\AppSetting;
use App\Models\Company;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
            'trial_days'      => AppSetting::get('trial_days', 180),
            'banner_message'  => AppSetting::get('banner_message', ''),
            'banner_days'     => AppSetting::get('banner_days', 7),
            'banner_color'    => AppSetting::get('banner_color', 'info'),
            'banner_published_at' => AppSetting::get('banner_published_at', ''),
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

                Section::make('Bandeau d\'annonce')
                    ->description('Affiche un bandeau en haut de l\'interface pour tous les utilisateurs. Vide = bandeau masqué.')
                    ->icon('heroicon-o-megaphone')
                    ->schema([
                        Textarea::make('banner_message')
                            ->label('Message du bandeau')
                            ->helperText('Laissez vide pour désactiver le bandeau.')
                            ->rows(2)
                            ->maxLength(300),
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            Select::make('banner_color')
                                ->label('Couleur')
                                ->options([
                                    'info'    => 'Bleu (info)',
                                    'success' => 'Vert (succès)',
                                    'warning' => 'Orange (avertissement)',
                                    'danger'  => 'Rouge (urgent)',
                                ])
                                ->default('info')
                                ->required(),
                            TextInput::make('banner_days')
                                ->label('Durée d\'affichage')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(365)
                                ->default(7)
                                ->required()
                                ->suffix('jours')
                                ->helperText('À partir de la date de publication.'),
                        ]),
                        TextInput::make('banner_published_at')
                            ->label('Date de publication (YYYY-MM-DD)')
                            ->helperText('Laissez vide pour utiliser aujourd\'hui au moment de la sauvegarde.')
                            ->placeholder(now()->toDateString()),
                        Toggle::make('send_email')
                            ->label('Envoyer aussi par email aux administrateurs')
                            ->helperText('Envoie le message aux admins de chaque entreprise au moment de la sauvegarde. Un seul envoi.')
                            ->default(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->form->validate();

        AppSetting::set('trial_days', (int) $this->data['trial_days']);

        // Bandeau
        $message = trim($this->data['banner_message'] ?? '');
        AppSetting::set('banner_message', $message);
        AppSetting::set('banner_color', $this->data['banner_color'] ?? 'info');
        AppSetting::set('banner_days', (int) ($this->data['banner_days'] ?? 7));

        $publishedAt = trim($this->data['banner_published_at'] ?? '');
        if (empty($publishedAt)) {
            $publishedAt = now()->toDateString();
        }
        AppSetting::set('banner_published_at', $publishedAt);

        // Envoi email aux admins si demandé
        if (!empty($this->data['send_email']) && !empty($message)) {
            $sent = 0;
            Company::with('users')->get()->each(function (Company $company) use ($message, &$sent) {
                $admins = $company->users->filter(fn ($u) => $u->isAdminOf($company));
                foreach ($admins as $admin) {
                    if (!$admin->email) continue;
                    try {
                        Mail::to($admin->email)->send(new AnnouncementBanner($message, $admin->name));
                        $sent++;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("AnnouncementBanner: failed to send to {$admin->email} — " . $e->getMessage());
                    }
                }
            });

            Notification::make()
                ->title('Paramètres sauvegardés')
                ->body("Email envoyé à {$sent} administrateur(s).")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Paramètres sauvegardés')
                ->success()
                ->send();
        }
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

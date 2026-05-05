<?php

namespace App\Filament\Superadmin\Pages;

use App\Mail\DirectMail;
use App\Models\Company;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendEmail extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Envoyer un email';
    protected static ?string $title           = 'Envoyer un email aux utilisateurs';
    protected static ?int    $navigationSort  = 21;

    protected static string $view = 'filament.superadmin.pages.send-email';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'target'     => 'admins',
            'subject'    => '',
            'body'       => '',
            'company_id' => null,
            'user_id'    => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Destinataires')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Select::make('target')
                            ->label('Envoyer à')
                            ->options([
                                'admins'  => 'Tous les admins',
                                'all'     => 'Tous les utilisateurs',
                                'company' => 'Une entreprise (tous ses utilisateurs)',
                                'user'    => 'Un utilisateur spécifique',
                            ])
                            ->default('admins')
                            ->required()
                            ->live(),

                        Select::make('company_id')
                            ->label('Entreprise')
                            ->options(Company::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get) => $get('target') === 'company')
                            ->required(fn (Get $get) => $get('target') === 'company'),

                        Select::make('user_id')
                            ->label('Utilisateur')
                            ->options(
                                User::whereNotNull('email')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->email})"])
                            )
                            ->searchable()
                            ->visible(fn (Get $get) => $get('target') === 'user')
                            ->required(fn (Get $get) => $get('target') === 'user'),
                    ]),

                Section::make('Message')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        TextInput::make('subject')
                            ->label('Objet')
                            ->placeholder('Ex : Maintenance planifiée le 10 mai')
                            ->maxLength(200)
                            ->required(),

                        Textarea::make('body')
                            ->label('Corps du message')
                            ->placeholder('Rédigez votre message ici…')
                            ->rows(8)
                            ->maxLength(5000)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $this->form->validate();

        $subject = trim($this->data['subject']);
        $body    = trim($this->data['body']);
        $target  = $this->data['target'];

        $recipients = $this->resolveRecipients($target);

        if ($recipients->isEmpty()) {
            Notification::make()
                ->title('Aucun destinataire trouvé')
                ->warning()
                ->send();
            return;
        }

        $sent   = 0;
        $failed = 0;

        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(new DirectMail($subject, $body, $user->name));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                \Illuminate\Support\Facades\Log::warning("DirectMail: failed to send to {$user->email} — " . $e->getMessage());
            }
        }

        $body_notif = "Envoyé à {$sent} utilisateur(s)";
        if ($failed > 0) {
            $body_notif .= ", {$failed} échec(s).";
        }

        Notification::make()
            ->title('Email envoyé')
            ->body($body_notif)
            ->success()
            ->send();

        // Réinitialiser le corps et l'objet après envoi
        $this->form->fill([
            'target'     => $this->data['target'],
            'company_id' => $this->data['company_id'] ?? null,
            'user_id'    => $this->data['user_id'] ?? null,
            'subject'    => '',
            'body'       => '',
        ]);
    }

    private function resolveRecipients(string $target): \Illuminate\Support\Collection
    {
        return match ($target) {
            'admins' => $this->getAdmins(),
            'all'    => User::whereNotNull('email')->get(),
            'company' => User::whereHas('companies', fn ($q) => $q->where('companies.id', $this->data['company_id']))
                             ->whereNotNull('email')
                             ->get(),
            'user'   => User::where('id', $this->data['user_id'])->whereNotNull('email')->get(),
            default  => collect(),
        };
    }

    private function getAdmins(): \Illuminate\Support\Collection
    {
        $adminUserIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.slug', 'admin')
            ->pluck('model_has_roles.user_id')
            ->unique();

        return User::whereIn('id', $adminUserIds)->whereNotNull('email')->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Envoyer')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirmer l\'envoi')
                ->modalDescription(fn () => $this->getSendSummary())
                ->action(fn () => $this->send()),
        ];
    }

    private function getSendSummary(): string
    {
        $target = $this->data['target'] ?? 'admins';
        $labels = [
            'admins'  => 'tous les admins',
            'all'     => 'tous les utilisateurs',
            'company' => 'tous les utilisateurs de l\'entreprise sélectionnée',
            'user'    => 'l\'utilisateur sélectionné',
        ];
        $subject = trim($this->data['subject'] ?? '');
        $label   = $labels[$target] ?? $target;
        return "Envoyer « {$subject} » à {$label} ?";
    }
}

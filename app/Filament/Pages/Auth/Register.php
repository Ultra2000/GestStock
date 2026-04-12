<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nom complet')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->same('passwordConfirmation')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state)),
                TextInput::make('passwordConfirmation')
                    ->label('Confirmation du mot de passe')
                    ->password()
                    ->required()
                    ->dehydrated(false),
            ]);
    }

    protected function handleRegistration(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin', // Rôle admin par défaut
        ]);

        $this->notifyAdmin($user);
        $this->notifyUser($user);

        return $user;
    }

    protected function notifyUser(User $user): void
    {
        try {
            Mail::raw(
                "Bonjour {$user->name},\n\n" .
                "Bienvenue sur FRECORP ERP ! Votre compte a bien été créé.\n\n" .
                "Vous pouvez dès maintenant vous connecter et créer votre entreprise :\n" .
                config('app.url') . "/admin\n\n" .
                "Si vous avez des questions, répondez simplement à cet email.\n\n" .
                "L'équipe FRECORP",
                function ($message) use ($user) {
                    $message->to($user->email, $user->name)
                            ->subject("Bienvenue sur FRECORP ERP !");
                }
            );
        } catch (\Exception $e) {
            Log::error('Échec de la notification utilisateur inscription : ' . $e->getMessage());
        }
    }

    protected function notifyAdmin(User $user): void
    {
        $adminEmail = config('app.admin_notification_email', config('mail.from.address'));

        if (!$adminEmail) {
            return;
        }

        try {
            Mail::raw(
                "Nouvel utilisateur inscrit sur FRECORP ERP :\n\n" .
                "Nom    : {$user->name}\n" .
                "Email  : {$user->email}\n" .
                "Date   : " . now()->format('d/m/Y à H:i') . "\n\n" .
                "Connectez-vous sur " . config('app.url') . "/admin pour gérer cet utilisateur.",
                function ($message) use ($adminEmail, $user) {
                    $message->to($adminEmail)
                            ->subject("[FRECORP] Nouvelle inscription : {$user->name}");
                }
            );
        } catch (\Exception $e) {
            Log::error('Échec de la notification admin inscription : ' . $e->getMessage());
        }
    }
}

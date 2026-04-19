<?php

namespace App\Console\Commands;

use App\Mail\TrialExpired;
use App\Mail\TrialExpiringSoon;
use App\Models\Company;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialReminders extends Command
{
    protected $signature   = 'notifications:send-trial-reminders';
    protected $description = 'Envoie les emails de rappel avant expiration du trial et à l\'expiration';

    public function handle(): void
    {
        $this->sendExpiringReminders(7);
        $this->sendExpiringReminders(3);
        $this->sendExpiredNotifications();
    }

    private function sendExpiringReminders(int $days): void
    {
        $companies = Company::where('subscription_status', 'trial')
            ->whereDate('trial_ends_at', now()->addDays($days)->toDateString())
            ->with('users')
            ->get();

        foreach ($companies as $company) {
            foreach ($company->users->filter(fn ($u) => $u->role === 'admin') as $admin) {
                Mail::to($admin->email)->queue(new TrialExpiringSoon($company, $days));

                Notification::make()
                    ->title("⏰ Plus que {$days} jour" . ($days > 1 ? 's' : '') . " d'évaluation")
                    ->body("La période d'essai de {$company->name} se termine dans {$days} jour" . ($days > 1 ? 's' : '') . ". Choisissez votre plan pour continuer.")
                    ->warning()
                    ->actions([
                        Action::make('subscribe')
                            ->label("S'abonner")
                            ->url(url('/admin/' . $company->slug . '/subscription-expired'))
                            ->button(),
                    ])
                    ->sendToDatabase($admin);
            }
            $this->line("Rappel {$days}j envoyé : {$company->name} ({$company->users->count()} admin(s))");
        }
    }

    private function sendExpiredNotifications(): void
    {
        $companies = Company::where('subscription_status', 'trial')
            ->whereDate('trial_ends_at', now()->subDay()->toDateString())
            ->with('users')
            ->get();

        foreach ($companies as $company) {
            foreach ($company->users->filter(fn ($u) => $u->role === 'admin') as $admin) {
                Mail::to($admin->email)->queue(new TrialExpired($company));

                Notification::make()
                    ->title('🔒 Période d\'évaluation terminée')
                    ->body("L'accès de {$company->name} est suspendu. Souscrivez un abonnement pour retrouver l'accès à vos données.")
                    ->danger()
                    ->actions([
                        Action::make('subscribe')
                            ->label('Réactiver mon accès')
                            ->url(url('/admin/' . $company->slug . '/subscription-expired'))
                            ->button(),
                    ])
                    ->sendToDatabase($admin);
            }
            $this->line("Expiration notifiée : {$company->name}");
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Mail\TrialExpired;
use App\Mail\TrialExpiringSoon;
use App\Models\Company;
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
            foreach ($company->users as $user) {
                Mail::to($user->email)->queue(new TrialExpiringSoon($company, $days));
            }
            $this->line("Rappel {$days}j envoyé : {$company->name} ({$company->users->count()} utilisateur(s))");
        }
    }

    private function sendExpiredNotifications(): void
    {
        // Entreprises dont le trial vient d'expirer aujourd'hui (statut encore 'trial' mais date passée)
        $companies = Company::where('subscription_status', 'trial')
            ->whereDate('trial_ends_at', now()->subDay()->toDateString())
            ->with('users')
            ->get();

        foreach ($companies as $company) {
            foreach ($company->users as $user) {
                Mail::to($user->email)->queue(new TrialExpired($company));
            }
            $this->line("Expiration notifiée : {$company->name}");
        }
    }
}

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
            ->with(['users' => fn ($q) => $q->where('role', 'admin')])
            ->get();

        foreach ($companies as $company) {
            foreach ($company->users as $admin) {
                Mail::to($admin->email)->queue(new TrialExpiringSoon($company, $days));
            }
            $this->line("Rappel {$days}j envoyé : {$company->name} ({$company->users->count()} admin(s))");
        }
    }

    private function sendExpiredNotifications(): void
    {
        $companies = Company::where('subscription_status', 'trial')
            ->whereDate('trial_ends_at', now()->subDay()->toDateString())
            ->with(['users' => fn ($q) => $q->where('role', 'admin')])
            ->get();

        foreach ($companies as $company) {
            foreach ($company->users as $admin) {
                Mail::to($admin->email)->queue(new TrialExpired($company));
            }
            $this->line("Expiration notifiée : {$company->name}");
        }
    }
}

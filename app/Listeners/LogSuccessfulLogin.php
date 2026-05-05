<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class LogSuccessfulLogin
{
    public function __construct(protected Request $request) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        // Déterminer le panel (superadmin ou admin)
        $panel = 'admin';
        if (str_contains($this->request->getPathInfo(), '/system')) {
            $panel = 'superadmin';
        }

        // Première entreprise de l'utilisateur pour affichage
        $companyName = null;
        try {
            $companyName = $user->companies()->first()?->name;
        } catch (\Throwable) {}

        LoginLog::create([
            'user_id'      => $user->id,
            'email'        => $user->email,
            'name'         => $user->name,
            'company_name' => $companyName,
            'ip_address'   => $this->request->ip(),
            'user_agent'   => $this->request->userAgent(),
            'panel'        => $panel,
            'logged_in_at' => now(),
        ]);
    }
}

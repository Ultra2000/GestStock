<?php

namespace App\Filament\Superadmin\Pages;

use App\Models\LoginLog;
use Filament\Pages\Page;

class LoginLogs extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-right-end-on-rectangle';
    protected static ?string $navigationLabel = 'Connexions';
    protected static ?string $title           = 'Historique des connexions';
    protected static ?int    $navigationSort  = 11;

    protected static string $view = 'filament.superadmin.pages.login-logs';

    public string $search     = '';
    public string $panelFilter = 'all';
    public int    $limit       = 100;

    public function getLogs(): \Illuminate\Support\Collection
    {
        $query = LoginLog::with('user')
            ->orderByDesc('logged_in_at');

        if ($this->panelFilter !== 'all') {
            $query->where('panel', $this->panelFilter);
        }

        if ($this->search !== '') {
            $q = $this->search;
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('company_name', 'like', "%{$q}%")
                        ->orWhere('ip_address', 'like', "%{$q}%");
            });
        }

        return $query->limit($this->limit)->get();
    }

    public function getStats(): array
    {
        return [
            'total'      => LoginLog::count(),
            'today'      => LoginLog::whereDate('logged_in_at', today())->count(),
            'this_week'  => LoginLog::where('logged_in_at', '>=', now()->startOfWeek())->count(),
            'unique'     => LoginLog::distinct('user_id')->count('user_id'),
        ];
    }
}

<?php

namespace App\Filament\Caisse\Pages;

use App\Models\CashSession;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CashSessionPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static string $view = 'filament.caisse.pages.cash-session';
    protected static ?string $navigationLabel = 'Session de caisse';
    protected static ?string $title = 'Gestion de caisse';
    protected static ?int $navigationSort = 2;

    public ?float $openingAmount = 0;
    public ?float $closingAmount = null;
    public ?string $notes = null;
    public ?CashSession $currentSession = null;

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        if ($tenant) {
            $this->currentSession = CashSession::getOpenSession($tenant->id, auth()->id());
        }
    }

    /**
     * Ouvre une nouvelle session de caisse
     */
    public function openSession(): void
    {
        $tenant = Filament::getTenant();
        if (!$tenant) {
            Notification::make()->title('Erreur')->body('Entreprise non trouvée')->danger()->send();
            return;
        }

        if ($this->currentSession) {
            Notification::make()->title('Session déjà ouverte')->warning()->send();
            return;
        }

        $this->currentSession = CashSession::openSession(
            $tenant->id,
            auth()->id(),
            $this->openingAmount ?? 0
        );

        Notification::make()
            ->title('Session ouverte')
            ->body('Fond de caisse : ' . number_format($this->openingAmount, 0, ',', ' ') . ' ' . Filament::getTenant()->currency)
            ->success()
            ->send();

        $this->openingAmount = 0;
    }

    /**
     * Ferme la session de caisse
     */
    public function closeSession(): void
    {
        if (!$this->currentSession) {
            Notification::make()->title('Aucune session ouverte')->warning()->send();
            return;
        }

        if ($this->closingAmount === null) {
            Notification::make()->title('Veuillez entrer le montant en caisse')->warning()->send();
            return;
        }

        $this->currentSession->closeSession($this->closingAmount, $this->notes);

        $difference = $this->currentSession->difference;
        $message = $difference == 0 
            ? 'Caisse équilibrée' 
            : ($difference > 0 ? 'Excédent de ' : 'Déficit de ') . number_format(abs($difference), 0, ',', ' ') . ' ' . Filament::getTenant()->currency;

        Notification::make()
            ->title('Session fermée')
            ->body($message)
            ->color($difference == 0 ? 'success' : ($difference > 0 ? 'warning' : 'danger'))
            ->send();

        $this->currentSession = null;
        $this->closingAmount = null;
        $this->notes = null;
    }

    /**
     * Récupère les statistiques de la session
     */
    public function getSessionStats(): array
    {
        if (!$this->currentSession) {
            return [];
        }

        $this->currentSession->recalculate();
        $this->currentSession->refresh();

        return [
            'total_sales' => $this->currentSession->total_sales,
            'total_cash' => $this->currentSession->total_cash,
            'total_card' => $this->currentSession->total_card,
            'total_mobile' => $this->currentSession->total_mobile,
            'total_other' => $this->currentSession->total_other,
            'total' => $this->currentSession->total,
            'opening_amount' => $this->currentSession->opening_amount,
            'expected_amount' => $this->currentSession->expected_amount,
            'opened_at' => $this->currentSession->opened_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Récupère l'historique des sessions
     */
    public function getSessionHistory(): array
    {
        $tenant = Filament::getTenant();
        if (!$tenant) return [];

        return CashSession::where('company_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->limit(10)
            ->get()
            ->toArray();
    }
}

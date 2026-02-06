<?php

namespace App\Filament\Pages\HR;

use App\Models\AttendanceQrToken;
use App\Models\Warehouse;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class AttendanceQrDisplay extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'QR Code Pointage';
    protected static ?string $navigationGroup = 'RH';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.h-r.attendance-qr-display';

    public ?int $warehouseId = null;
    public ?Warehouse $selectedWarehouse = null;
    public array $warehouses = [];
    public ?AttendanceQrToken $currentToken = null;
    public ?string $qrContent = null;
    public int $refreshInterval = 300; // 5 minutes
    public int $tokenValidity = 5; // minutes

    public function mount(): void
    {
        // Charger les warehouses avec QR activé
        $this->warehouses = Warehouse::where('company_id', Filament::getTenant()?->id)
            ->where('is_active', true)
            ->where('requires_qr_check', true)
            ->get()
            ->toArray();

        // Sélectionner le premier par défaut
        if (count($this->warehouses) > 0) {
            $this->warehouseId = $this->warehouses[0]['id'];
            $this->selectWarehouse();
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'QR Code de Pointage';
    }

    public function selectWarehouse(): void
    {
        if (!$this->warehouseId) {
            $this->selectedWarehouse = null;
            $this->currentToken = null;
            $this->qrContent = null;
            return;
        }

        $this->selectedWarehouse = Warehouse::find($this->warehouseId);
        
        if ($this->selectedWarehouse) {
            $this->generateNewToken();
        }
    }

    public function generateNewToken(): void
    {
        if (!$this->selectedWarehouse) {
            return;
        }

        $this->currentToken = AttendanceQrToken::generateForWarehouse(
            $this->selectedWarehouse,
            $this->tokenValidity
        );

        $this->qrContent = $this->currentToken->getQrContent();
        
        // Dispatcher l'événement pour mettre à jour le QR code côté JavaScript
        $this->dispatch('qr-content-updated', qrContent: $this->qrContent);
    }

    /**
     * Méthode appelée par le polling - ne régénère que si nécessaire
     */
    public function checkAndRefreshToken(): void
    {
        // Ne régénérer que si le token expire dans moins de 30 secondes
        if (!$this->currentToken || $this->getExpiresInSeconds() <= 30) {
            $this->generateNewToken();
        }
    }

    /**
     * Méthode pour forcer un nouveau QR (bouton manuel)
     */
    public function refreshToken(): void
    {
        $this->generateNewToken();
    }

    public function getExpiresInSeconds(): int
    {
        if (!$this->currentToken) {
            return 0;
        }

        return max(0, $this->currentToken->expires_at->diffInSeconds(now()));
    }

    public static function shouldRegisterNavigation(): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();
        if (!$tenant?->isModuleEnabled('hr')) {
            return false;
        }
        
        // Afficher uniquement pour les admins/managers
        return auth()->user()?->isAdmin() || auth()->user()?->isManager();
    }
}

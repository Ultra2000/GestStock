<?php

namespace App\Filament\Pages\HR;

use App\Models\Attendance;
use App\Models\AttendanceQrToken;
use App\Models\Employee;
use App\Models\Warehouse;
use App\Services\AttendanceService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class EmployeeClockIn extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationLabel = 'Pointage';
    protected static ?string $navigationGroup = 'Ressources Humaines';
    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.h-r.employee-clock-in';

    public ?Employee $employee = null;
    public ?Warehouse $selectedWarehouse = null;
    public array $warehouses = [];
    public ?int $warehouseId = null;
    
    // Status
    public string $currentStatus = 'loading';
    public ?string $clockInTime = null;
    public ?string $clockOutTime = null;
    public ?float $hoursWorked = null;
    
    // GPS
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?int $accuracy = null;
    public bool $gpsLoading = false;
    public ?string $gpsError = null;
    
    // QR
    public ?string $qrToken = null;
    public bool $qrScanning = false;
    public ?string $qrError = null;
    
    // Workflow
    public string $step = 'select_warehouse'; // select_warehouse, check_gps, scan_qr, confirm
    public bool $processing = false;
    public ?string $actionType = null; // clock_in, clock_out

    public function mount(): void
    {
        $user = auth()->user();
        
        // Trouver l'employé associé à l'utilisateur
        $this->employee = Employee::where('user_id', $user->id)
            ->where('company_id', Filament::getTenant()?->id)
            ->first();

        if (!$this->employee) {
            Notification::make()
                ->title('Aucun profil employé trouvé')
                ->body('Votre compte n\'est pas associé à un profil employé.')
                ->danger()
                ->persistent()
                ->send();
            return;
        }

        // Charger les warehouses de l'entreprise
        $this->warehouses = Warehouse::where('company_id', Filament::getTenant()?->id)
            ->where('is_active', true)
            ->get()
            ->toArray();

        // Pré-sélectionner le warehouse par défaut de l'employé
        if ($this->employee->warehouse_id) {
            $this->warehouseId = $this->employee->warehouse_id;
            $this->selectWarehouse();
        }

        $this->loadCurrentStatus();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Pointage';
    }

    public function loadCurrentStatus(): void
    {
        if (!$this->employee) {
            $this->currentStatus = 'no_employee';
            return;
        }

        $service = new AttendanceService();
        $status = $service->getEmployeeStatus($this->employee);

        $this->currentStatus = $status['status'];
        $this->clockInTime = $status['clock_in_time'] ?? null;
        $this->clockOutTime = $status['clock_out_time'] ?? null;
        $this->hoursWorked = $status['hours_worked'] ?? null;
    }

    public function selectWarehouse(): void
    {
        if (!$this->warehouseId) {
            return;
        }

        $this->selectedWarehouse = Warehouse::find($this->warehouseId);
        
        if ($this->selectedWarehouse) {
            $this->step = 'ready';
            $this->resetClockValidation();
        }
    }

    public function resetClockValidation(): void
    {
        $this->latitude = null;
        $this->longitude = null;
        $this->accuracy = null;
        $this->gpsError = null;
        $this->qrToken = null;
        $this->qrError = null;
    }

    public function startClockIn(): void
    {
        $this->actionType = 'clock_in';
        $this->startValidation();
    }

    public function startClockOut(): void
    {
        $this->actionType = 'clock_out';
        $this->startValidation();
    }

    public function startValidation(): void
    {
        if (!$this->selectedWarehouse) {
            Notification::make()
                ->title('Veuillez sélectionner un site')
                ->warning()
                ->send();
            return;
        }

        $this->resetClockValidation();

        // Déterminer les étapes nécessaires
        if ($this->selectedWarehouse->requires_gps_check) {
            $this->step = 'check_gps';
            $this->gpsLoading = true;
            // Le JS va appeler updateGpsPosition via dispatch
        } elseif ($this->selectedWarehouse->requires_qr_check) {
            $this->step = 'scan_qr';
            $this->qrScanning = true;
        } else {
            // Pas de validation requise, procéder directement
            $this->executeClockAction();
        }
    }

    #[On('gps-position-received')]
    public function updateGpsPosition(float $latitude, float $longitude, int $accuracy): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->accuracy = $accuracy;
        $this->gpsLoading = false;
        $this->gpsError = null;

        // Valider la position
        if ($this->selectedWarehouse && $this->selectedWarehouse->requires_gps_check) {
            $validation = $this->selectedWarehouse->validateGpsPosition($latitude, $longitude);
            
            if (!$validation['valid']) {
                $this->gpsError = "Vous êtes à {$validation['distance']}m du site (max: {$validation['max_distance']}m)";
                Notification::make()
                    ->title('Position hors zone')
                    ->body($this->gpsError)
                    ->danger()
                    ->send();
                return;
            }
        }

        // Passer à l'étape suivante
        if ($this->selectedWarehouse->requires_qr_check) {
            $this->step = 'scan_qr';
            $this->qrScanning = true;
        } else {
            $this->executeClockAction();
        }
    }

    #[On('gps-error')]
    public function handleGpsError(string $error): void
    {
        $this->gpsLoading = false;
        $this->gpsError = $error;
        
        Notification::make()
            ->title('Erreur de géolocalisation')
            ->body($error)
            ->danger()
            ->send();
    }

    #[On('qr-scanned')]
    public function handleQrScanned(string $data): void
    {
        $this->qrScanning = false;
        
        // Décoder le QR
        $decoded = json_decode($data, true);
        
        if (!$decoded || !isset($decoded['token'])) {
            $this->qrError = 'QR Code invalide';
            Notification::make()
                ->title('QR Code invalide')
                ->body('Le format du QR Code n\'est pas reconnu.')
                ->danger()
                ->send();
            return;
        }

        // Vérifier que le warehouse correspond
        if (isset($decoded['warehouse_id']) && $decoded['warehouse_id'] != $this->selectedWarehouse->id) {
            $this->qrError = 'Ce QR Code appartient à un autre site';
            Notification::make()
                ->title('Mauvais site')
                ->body('Ce QR Code appartient à un autre site.')
                ->danger()
                ->send();
            return;
        }

        $this->qrToken = $decoded['token'];
        $this->qrError = null;

        // Exécuter le pointage
        $this->executeClockAction();
    }

    #[On('qr-error')]
    public function handleQrError(string $error): void
    {
        $this->qrScanning = false;
        $this->qrError = $error;
        
        Notification::make()
            ->title('Erreur de scan')
            ->body($error)
            ->danger()
            ->send();
    }

    public function executeClockAction(): void
    {
        if ($this->processing) {
            return;
        }

        $this->processing = true;
        $this->step = 'processing';

        $service = new AttendanceService();

        $result = match($this->actionType) {
            'clock_in' => $service->clockIn(
                $this->employee,
                $this->selectedWarehouse,
                $this->latitude,
                $this->longitude,
                $this->accuracy,
                $this->qrToken,
                request()->ip(),
                request()->userAgent()
            ),
            'clock_out' => $service->clockOut(
                $this->employee,
                $this->selectedWarehouse,
                $this->latitude,
                $this->longitude,
                $this->accuracy,
                $this->qrToken,
                request()->ip(),
                request()->userAgent()
            ),
            default => ['success' => false, 'message' => 'Action inconnue'],
        };

        $this->processing = false;

        if ($result['success']) {
            Notification::make()
                ->title($this->actionType === 'clock_in' ? 'Entrée enregistrée' : 'Sortie enregistrée')
                ->body($result['message'])
                ->success()
                ->send();

            $this->loadCurrentStatus();
            $this->step = 'ready';
            $this->resetClockValidation();
        } else {
            Notification::make()
                ->title('Échec du pointage')
                ->body($result['message'])
                ->danger()
                ->send();

            $this->step = 'ready';
        }
    }

    public function cancelAction(): void
    {
        $this->step = 'ready';
        $this->actionType = null;
        $this->resetClockValidation();
        $this->gpsLoading = false;
        $this->qrScanning = false;
    }

    public function getStatusLabel(): string
    {
        return match($this->currentStatus) {
            'not_clocked_in' => 'Non pointé',
            'clocked_in' => 'En service depuis ' . $this->clockInTime,
            'completed' => 'Journée terminée',
            'no_employee' => 'Profil employé non trouvé',
            default => 'Chargement...',
        };
    }

    public function getStatusColor(): string
    {
        return match($this->currentStatus) {
            'not_clocked_in' => 'gray',
            'clocked_in' => 'success',
            'completed' => 'info',
            'no_employee' => 'danger',
            default => 'gray',
        };
    }
}

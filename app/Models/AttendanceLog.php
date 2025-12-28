<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'warehouse_id',
        'action',
        'status',
        'failure_reason',
        'latitude',
        'longitude',
        'gps_accuracy',
        'distance_from_site',
        'qr_token',
        'qr_valid',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'gps_accuracy' => 'integer',
        'distance_from_site' => 'decimal:2',
        'qr_valid' => 'boolean',
    ];

    // Relations
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // Créer un log de succès
    public static function logSuccess(
        int $companyId,
        int $employeeId,
        string $action,
        ?int $warehouseId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $gpsAccuracy = null,
        ?float $distance = null,
        ?string $qrToken = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'warehouse_id' => $warehouseId,
            'action' => $action,
            'status' => 'success',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'gps_accuracy' => $gpsAccuracy,
            'distance_from_site' => $distance,
            'qr_token' => $qrToken,
            'qr_valid' => $qrToken ? true : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    // Créer un log d'échec
    public static function logFailure(
        int $companyId,
        int $employeeId,
        string $action,
        string $reason,
        ?int $warehouseId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $gpsAccuracy = null,
        ?float $distance = null,
        ?string $qrToken = null,
        ?bool $qrValid = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return static::create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'warehouse_id' => $warehouseId,
            'action' => $action,
            'status' => 'failed',
            'failure_reason' => $reason,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'gps_accuracy' => $gpsAccuracy,
            'distance_from_site' => $distance,
            'qr_token' => $qrToken,
            'qr_valid' => $qrValid,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    // Libellés des raisons d'échec
    public function getFailureReasonLabelAttribute(): string
    {
        return match($this->failure_reason) {
            'gps_out_of_range' => 'Position hors zone autorisée',
            'gps_permission_denied' => 'Géolocalisation refusée',
            'gps_unavailable' => 'Géolocalisation indisponible',
            'qr_invalid' => 'QR Code invalide',
            'qr_expired' => 'QR Code expiré',
            'qr_already_used' => 'QR Code déjà utilisé',
            'qr_permission_denied' => 'Accès caméra refusé',
            'no_employee' => 'Employé non trouvé',
            'already_clocked_in' => 'Déjà pointé (entrée)',
            'not_clocked_in' => 'Pas encore pointé (entrée)',
            default => $this->failure_reason ?? 'Erreur inconnue',
        };
    }

    // Libellés des actions
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'clock_in' => 'Pointage entrée',
            'clock_out' => 'Pointage sortie',
            'break_start' => 'Début pause',
            'break_end' => 'Fin pause',
            default => $this->action,
        };
    }

    // Couleur du statut
    public function getStatusColorAttribute(): string
    {
        return $this->status === 'success' ? 'success' : 'danger';
    }
}

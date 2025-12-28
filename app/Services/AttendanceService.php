<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\AttendanceQrToken;
use App\Models\Employee;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Effectuer un pointage d'entrée
     */
    public function clockIn(
        Employee $employee,
        Warehouse $warehouse,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $gpsAccuracy = null,
        ?string $qrToken = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $companyId = $employee->company_id;
        $today = now()->toDateString();

        // Vérifier si déjà pointé aujourd'hui
        $existingAttendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if ($existingAttendance && $existingAttendance->clock_in) {
            $this->logFailure($companyId, $employee->id, 'clock_in', 'already_clocked_in', $warehouse->id, $latitude, $longitude, $gpsAccuracy, null, $qrToken, null, $ipAddress, $userAgent);
            return ['success' => false, 'error' => 'already_clocked_in', 'message' => 'Vous avez déjà pointé votre entrée aujourd\'hui.'];
        }

        // Valider GPS si requis
        $gpsValidation = $this->validateGps($warehouse, $latitude, $longitude);
        if (!$gpsValidation['valid']) {
            $this->logFailure($companyId, $employee->id, 'clock_in', $gpsValidation['reason'], $warehouse->id, $latitude, $longitude, $gpsAccuracy, $gpsValidation['distance'] ?? null, $qrToken, null, $ipAddress, $userAgent);
            return ['success' => false, 'error' => $gpsValidation['reason'], 'message' => $this->getErrorMessage($gpsValidation['reason']), 'distance' => $gpsValidation['distance'] ?? null];
        }

        // Valider QR si requis
        $qrValidation = $this->validateQr($warehouse, $qrToken, $employee->id);
        if (!$qrValidation['valid']) {
            $this->logFailure($companyId, $employee->id, 'clock_in', $qrValidation['reason'], $warehouse->id, $latitude, $longitude, $gpsAccuracy, $gpsValidation['distance'] ?? null, $qrToken, false, $ipAddress, $userAgent);
            return ['success' => false, 'error' => $qrValidation['reason'], 'message' => $this->getErrorMessage($qrValidation['reason'])];
        }

        // Créer ou mettre à jour l'attendance
        DB::beginTransaction();
        try {
            $attendance = Attendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => $today,
                ],
                [
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouse->id,
                    'clock_in' => now()->format('H:i:s'),
                    'clock_in_location' => $warehouse->name,
                    'clock_in_latitude' => $latitude,
                    'clock_in_longitude' => $longitude,
                    'clock_in_accuracy' => $gpsAccuracy,
                    'clock_in_qr_token' => $qrToken,
                    'clock_in_validation' => 'valid',
                    'status' => 'present',
                ]
            );

            // Log succès
            AttendanceLog::logSuccess(
                $companyId,
                $employee->id,
                'clock_in',
                $warehouse->id,
                $latitude,
                $longitude,
                $gpsAccuracy,
                $gpsValidation['distance'] ?? null,
                $qrToken,
                $ipAddress,
                $userAgent
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Pointage enregistré avec succès !',
                'attendance' => $attendance,
                'time' => now()->format('H:i'),
                'location' => $warehouse->name,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'error' => 'system_error', 'message' => 'Erreur système. Veuillez réessayer.'];
        }
    }

    /**
     * Effectuer un pointage de sortie
     */
    public function clockOut(
        Employee $employee,
        Warehouse $warehouse,
        ?float $latitude = null,
        ?float $longitude = null,
        ?int $gpsAccuracy = null,
        ?string $qrToken = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        $companyId = $employee->company_id;
        $today = now()->toDateString();

        // Vérifier si pointé aujourd'hui
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            $this->logFailure($companyId, $employee->id, 'clock_out', 'not_clocked_in', $warehouse->id, $latitude, $longitude, $gpsAccuracy, null, $qrToken, null, $ipAddress, $userAgent);
            return ['success' => false, 'error' => 'not_clocked_in', 'message' => 'Vous devez d\'abord pointer votre entrée.'];
        }

        if ($attendance->clock_out) {
            $this->logFailure($companyId, $employee->id, 'clock_out', 'already_clocked_out', $warehouse->id, $latitude, $longitude, $gpsAccuracy, null, $qrToken, null, $ipAddress, $userAgent);
            return ['success' => false, 'error' => 'already_clocked_out', 'message' => 'Vous avez déjà pointé votre sortie aujourd\'hui.'];
        }

        // Valider GPS si requis
        $gpsValidation = $this->validateGps($warehouse, $latitude, $longitude);
        if (!$gpsValidation['valid']) {
            $this->logFailure($companyId, $employee->id, 'clock_out', $gpsValidation['reason'], $warehouse->id, $latitude, $longitude, $gpsAccuracy, $gpsValidation['distance'] ?? null, $qrToken, null, $ipAddress, $userAgent);
            return ['success' => false, 'error' => $gpsValidation['reason'], 'message' => $this->getErrorMessage($gpsValidation['reason']), 'distance' => $gpsValidation['distance'] ?? null];
        }

        // Valider QR si requis
        $qrValidation = $this->validateQr($warehouse, $qrToken, $employee->id);
        if (!$qrValidation['valid']) {
            $this->logFailure($companyId, $employee->id, 'clock_out', $qrValidation['reason'], $warehouse->id, $latitude, $longitude, $gpsAccuracy, $gpsValidation['distance'] ?? null, $qrToken, false, $ipAddress, $userAgent);
            return ['success' => false, 'error' => $qrValidation['reason'], 'message' => $this->getErrorMessage($qrValidation['reason'])];
        }

        // Mettre à jour l'attendance
        DB::beginTransaction();
        try {
            $attendance->update([
                'clock_out' => now()->format('H:i:s'),
                'clock_out_location' => $warehouse->name,
                'clock_out_latitude' => $latitude,
                'clock_out_longitude' => $longitude,
                'clock_out_accuracy' => $gpsAccuracy,
                'clock_out_qr_token' => $qrToken,
                'clock_out_validation' => 'valid',
            ]);

            // Calculer les heures travaillées
            $attendance->calculateHoursWorked();

            // Log succès
            AttendanceLog::logSuccess(
                $companyId,
                $employee->id,
                'clock_out',
                $warehouse->id,
                $latitude,
                $longitude,
                $gpsAccuracy,
                $gpsValidation['distance'] ?? null,
                $qrToken,
                $ipAddress,
                $userAgent
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Pointage de sortie enregistré avec succès !',
                'attendance' => $attendance->fresh(),
                'time' => now()->format('H:i'),
                'location' => $warehouse->name,
                'hours_worked' => $attendance->hours_worked,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'error' => 'system_error', 'message' => 'Erreur système. Veuillez réessayer.'];
        }
    }

    /**
     * Valider la position GPS
     */
    protected function validateGps(Warehouse $warehouse, ?float $latitude, ?float $longitude): array
    {
        if (!$warehouse->requires_gps_check) {
            return ['valid' => true, 'required' => false];
        }

        if ($latitude === null || $longitude === null) {
            return ['valid' => false, 'reason' => 'gps_unavailable'];
        }

        return $warehouse->validateGpsPosition($latitude, $longitude);
    }

    /**
     * Valider le QR Code
     */
    protected function validateQr(Warehouse $warehouse, ?string $qrToken, int $employeeId): array
    {
        if (!$warehouse->requires_qr_check) {
            return ['valid' => true, 'required' => false];
        }

        if (!$qrToken) {
            return ['valid' => false, 'reason' => 'qr_required'];
        }

        return AttendanceQrToken::validateToken($qrToken, $warehouse->id, $employeeId);
    }

    /**
     * Log d'échec
     */
    protected function logFailure(
        int $companyId,
        int $employeeId,
        string $action,
        string $reason,
        ?int $warehouseId,
        ?float $latitude,
        ?float $longitude,
        ?int $gpsAccuracy,
        ?float $distance,
        ?string $qrToken,
        ?bool $qrValid,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        AttendanceLog::logFailure(
            $companyId,
            $employeeId,
            $action,
            $reason,
            $warehouseId,
            $latitude,
            $longitude,
            $gpsAccuracy,
            $distance,
            $qrToken,
            $qrValid,
            $ipAddress,
            $userAgent
        );
    }

    /**
     * Obtenir le message d'erreur traduit
     */
    protected function getErrorMessage(string $reason): string
    {
        return match($reason) {
            'gps_out_of_range' => 'Vous êtes trop loin du site. Veuillez vous rapprocher.',
            'gps_unavailable' => 'La géolocalisation n\'est pas disponible.',
            'gps_permission_denied' => 'L\'accès à la géolocalisation a été refusé.',
            'gps_not_configured' => 'La position GPS du site n\'est pas configurée.',
            'qr_invalid' => 'Le QR Code n\'est pas valide.',
            'qr_expired' => 'Le QR Code a expiré. Veuillez rescanner.',
            'qr_already_used' => 'Ce QR Code a déjà été utilisé.',
            'qr_required' => 'Veuillez scanner le QR Code du site.',
            'already_clocked_in' => 'Vous avez déjà pointé votre entrée aujourd\'hui.',
            'already_clocked_out' => 'Vous avez déjà pointé votre sortie aujourd\'hui.',
            'not_clocked_in' => 'Vous devez d\'abord pointer votre entrée.',
            default => 'Une erreur est survenue. Veuillez réessayer.',
        };
    }

    /**
     * Obtenir le statut de pointage actuel d'un employé
     */
    public function getEmployeeStatus(Employee $employee): array
    {
        $today = now()->toDateString();
        
        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        if (!$attendance) {
            return [
                'status' => 'not_clocked_in',
                'can_clock_in' => true,
                'can_clock_out' => false,
                'attendance' => null,
            ];
        }

        if ($attendance->clock_in && !$attendance->clock_out) {
            return [
                'status' => 'clocked_in',
                'can_clock_in' => false,
                'can_clock_out' => true,
                'attendance' => $attendance,
                'clock_in_time' => $attendance->clock_in?->format('H:i'),
            ];
        }

        if ($attendance->clock_in && $attendance->clock_out) {
            return [
                'status' => 'completed',
                'can_clock_in' => false,
                'can_clock_out' => false,
                'attendance' => $attendance,
                'clock_in_time' => $attendance->clock_in?->format('H:i'),
                'clock_out_time' => $attendance->clock_out?->format('H:i'),
                'hours_worked' => $attendance->hours_worked,
            ];
        }

        return [
            'status' => 'unknown',
            'can_clock_in' => true,
            'can_clock_out' => false,
            'attendance' => $attendance,
        ];
    }
}

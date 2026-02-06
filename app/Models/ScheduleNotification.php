<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleNotification extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'type',
        'week_start',
        'message',
        'read_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'read_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * Vérifie si la notification est lue
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Scope pour les notifications non lues
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope pour les notifications d'un employé
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Créer une notification de publication
     */
    public static function notifyPublished(int $companyId, int $employeeId, \Carbon\Carbon $weekStart): self
    {
        $weekEnd = $weekStart->copy()->addDays(6);
        
        return self::create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'type' => 'published',
            'week_start' => $weekStart,
            'message' => "Votre planning pour la semaine du {$weekStart->format('d/m/Y')} au {$weekEnd->format('d/m/Y')} a été publié.",
        ]);
    }

    /**
     * Créer une notification de modification
     */
    public static function notifyModified(int $companyId, int $employeeId, \Carbon\Carbon $date, ?string $details = null): self
    {
        $message = "Votre planning du {$date->format('d/m/Y')} a été modifié.";
        if ($details) {
            $message .= " " . $details;
        }

        return self::create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'type' => 'modified',
            'week_start' => $date->copy()->startOfWeek(),
            'message' => $message,
        ]);
    }

    /**
     * Créer une notification d'annulation
     */
    public static function notifyCancelled(int $companyId, int $employeeId, \Carbon\Carbon $date, ?string $reason = null): self
    {
        $message = "Votre créneau du {$date->format('d/m/Y')} a été annulé.";
        if ($reason) {
            $message .= " Raison: " . $reason;
        }

        return self::create([
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'type' => 'cancelled',
            'week_start' => $date->copy()->startOfWeek(),
            'message' => $message,
        ]);
    }

    /**
     * Notifier tous les employés concernés lors de la publication d'une semaine
     */
    public static function notifyWeekPublished(int $companyId, \Carbon\Carbon $weekStart): int
    {
        $employeeIds = Schedule::where('company_id', $companyId)
            ->whereBetween('date', [$weekStart, $weekStart->copy()->addDays(6)])
            ->where('is_published', true)
            ->distinct('employee_id')
            ->pluck('employee_id');

        $count = 0;
        foreach ($employeeIds as $employeeId) {
            // Éviter les doublons de notification
            $exists = self::where('company_id', $companyId)
                ->where('employee_id', $employeeId)
                ->where('type', 'published')
                ->where('week_start', $weekStart)
                ->exists();

            if (!$exists) {
                self::notifyPublished($companyId, $employeeId, $weekStart);
                $count++;
            }
        }

        return $count;
    }
}

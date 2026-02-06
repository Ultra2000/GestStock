<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleException extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'schedule_id',
        'employee_id',
        'exception_date',
        'start_time',
        'end_time',
        'break_duration',
        'shift_type',
        'reason',
        'type',
    ];

    protected $casts = [
        'exception_date' => 'date',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Vérifie si cette exception annule complètement le créneau
     */
    public function isCancelled(): bool
    {
        return $this->type === 'cancelled' || (empty($this->start_time) && empty($this->end_time));
    }

    /**
     * Obtenir les heures de travail de cette exception
     */
    public function getHoursAttribute(): float
    {
        if ($this->isCancelled() || !$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        
        $breakMinutes = 60;
        if ($this->break_duration) {
            $break = \Carbon\Carbon::parse($this->break_duration);
            $breakMinutes = $break->hour * 60 + $break->minute;
        }

        return max(0, ($start->diffInMinutes($end) - $breakMinutes) / 60);
    }

    /**
     * Créer une exception pour modifier une occurrence d'un planning récurrent
     */
    public static function createModification(
        int $companyId,
        int $scheduleId,
        int $employeeId,
        \Carbon\Carbon $date,
        ?string $startTime,
        ?string $endTime,
        ?string $breakDuration = null,
        ?string $reason = null
    ): self {
        return self::updateOrCreate(
            [
                'schedule_id' => $scheduleId,
                'exception_date' => $date,
            ],
            [
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'break_duration' => $breakDuration,
                'reason' => $reason,
                'type' => ($startTime && $endTime) ? 'modified' : 'cancelled',
            ]
        );
    }

    /**
     * Créer une annulation pour une occurrence
     */
    public static function cancelOccurrence(
        int $companyId,
        int $scheduleId,
        int $employeeId,
        \Carbon\Carbon $date,
        ?string $reason = null
    ): self {
        return self::updateOrCreate(
            [
                'schedule_id' => $scheduleId,
                'exception_date' => $date,
            ],
            [
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'start_time' => null,
                'end_time' => null,
                'reason' => $reason,
                'type' => 'cancelled',
            ]
        );
    }
}

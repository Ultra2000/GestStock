<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'break_duration',
        'shift_type',
        'location',
        'position',
        'color',
        'notes',
        'is_published',
    ];

    protected $casts = [
        'date' => 'date',
        'is_published' => 'boolean',
        'day_of_week' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // Validation de conflit de planning
        static::saving(function ($schedule) {
            if ($schedule->date && $schedule->employee_id) {
                $conflict = static::where('company_id', $schedule->company_id)
                    ->where('employee_id', $schedule->employee_id)
                    ->where('date', $schedule->date)
                    ->where('id', '!=', $schedule->id ?? 0)
                    ->exists();

                if ($conflict) {
                    throw new \Exception("Un planning existe déjà pour cet employé à cette date.");
                }
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getHoursAttribute(): float
    {
        if (!$this->start_time || !$this->end_time || $this->start_time === '-' || $this->end_time === '-') {
            return 0;
        }
        
        try {
            $start = \Carbon\Carbon::parse($this->start_time);
            $end = \Carbon\Carbon::parse($this->end_time);
            
            $breakMinutes = 60; // Par défaut 1h
            if ($this->break_duration && $this->break_duration !== '-') {
                try {
                    $breakTime = \Carbon\Carbon::parse($this->break_duration);
                    $breakMinutes = $breakTime->hour * 60 + $breakTime->minute;
                } catch (\Exception $e) {
                    $breakMinutes = 60;
                }
            }
            
            return max(0, ($start->diffInMinutes($end) - $breakMinutes) / 60);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function getShiftTypeLabelAttribute(): string
    {
        return match($this->shift_type) {
            'morning' => 'Matin',
            'afternoon' => 'Après-midi',
            'night' => 'Nuit',
            'full_day' => 'Journée complète',
            default => $this->shift_type ?? 'Standard',
        };
    }

    public function getShiftTypeColorAttribute(): string
    {
        return match($this->shift_type) {
            'morning' => 'info',
            'afternoon' => 'warning',
            'night' => 'gray',
            'full_day' => 'success',
            default => 'primary',
        };
    }

    public static function generateWeeklySchedule(int $companyId, \Carbon\Carbon $weekStart, array $employeeSchedules): void
    {
        foreach ($employeeSchedules as $employeeId => $days) {
            foreach ($days as $dayOffset => $schedule) {
                if (!$schedule) continue;

                static::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'employee_id' => $employeeId,
                        'date' => $weekStart->copy()->addDays($dayOffset),
                    ],
                    [
                        'start_time' => $schedule['start'],
                        'end_time' => $schedule['end'],
                        'break_duration' => $schedule['break'] ?? '01:00:00',
                        'shift_type' => $schedule['type'] ?? null,
                        'location' => $schedule['location'] ?? null,
                    ]
                );
            }
        }
    }

    public function publish(): void
    {
        $this->update(['is_published' => true]);
    }

    public static function publishWeek(int $companyId, \Carbon\Carbon $weekStart): void
    {
        static::where('company_id', $companyId)
            ->whereBetween('date', [$weekStart, $weekStart->copy()->addDays(6)])
            ->update(['is_published' => true]);
    }
}

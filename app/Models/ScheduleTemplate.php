<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'schedule_data',
        'is_default',
    ];

    protected $casts = [
        'schedule_data' => 'array',
        'is_default' => 'boolean',
    ];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'template_id');
    }

    /**
     * Appliquer ce template à un employé pour une semaine donnée
     */
    public function applyToEmployee(int $employeeId, \Carbon\Carbon $weekStart): array
    {
        $createdSchedules = [];

        foreach ($this->schedule_data as $dayOfWeek => $dayData) {
            if (empty($dayData) || empty($dayData['start_time']) || empty($dayData['end_time'])) {
                continue;
            }

            $date = $weekStart->copy()->startOfWeek()->addDays($dayOfWeek - 1);

            $schedule = Schedule::updateOrCreate(
                [
                    'company_id' => $this->company_id,
                    'employee_id' => $employeeId,
                    'date' => $date,
                ],
                [
                    'template_id' => $this->id,
                    'start_time' => $dayData['start_time'],
                    'end_time' => $dayData['end_time'],
                    'break_duration' => $dayData['break_duration'] ?? '01:00:00',
                    'shift_type' => $dayData['shift_type'] ?? null,
                    'is_published' => false,
                ]
            );

            $createdSchedules[] = $schedule;
        }

        return $createdSchedules;
    }

    /**
     * Créer un template à partir d'une semaine existante
     */
    public static function createFromWeek(int $companyId, int $employeeId, \Carbon\Carbon $weekStart, string $name): self
    {
        $schedules = Schedule::where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$weekStart, $weekStart->copy()->addDays(6)])
            ->get();

        $scheduleData = [];
        foreach ($schedules as $schedule) {
            $dayOfWeek = $schedule->date->dayOfWeekIso; // 1 = Monday, 7 = Sunday
            $scheduleData[$dayOfWeek] = [
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'break_duration' => $schedule->break_duration,
                'shift_type' => $schedule->shift_type,
            ];
        }

        return self::create([
            'company_id' => $companyId,
            'name' => $name,
            'schedule_data' => $scheduleData,
        ]);
    }

    /**
     * Obtenir un résumé du template
     */
    public function getSummaryAttribute(): string
    {
        $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        $summary = [];

        foreach ($this->schedule_data as $dayOfWeek => $dayData) {
            if (!empty($dayData['start_time']) && !empty($dayData['end_time'])) {
                $summary[] = $days[$dayOfWeek - 1] . ': ' . substr($dayData['start_time'], 0, 5) . '-' . substr($dayData['end_time'], 0, 5);
            }
        }

        return implode(' | ', $summary) ?: 'Aucun horaire défini';
    }

    /**
     * Calculer les heures totales du template
     */
    public function getTotalHoursAttribute(): float
    {
        $total = 0;

        foreach ($this->schedule_data as $dayData) {
            if (empty($dayData['start_time']) || empty($dayData['end_time'])) {
                continue;
            }

            $start = \Carbon\Carbon::parse($dayData['start_time']);
            $end = \Carbon\Carbon::parse($dayData['end_time']);
            $breakMinutes = 60;

            if (!empty($dayData['break_duration'])) {
                $break = \Carbon\Carbon::parse($dayData['break_duration']);
                $breakMinutes = $break->hour * 60 + $break->minute;
            }

            $total += max(0, ($start->diffInMinutes($end) - $breakMinutes) / 60);
        }

        return $total;
    }
}

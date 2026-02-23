<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'warehouse_id',
        'date',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'hours_worked',
        'overtime_hours',
        'status',
        'notes',
        'clock_in_location',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_accuracy',
        'clock_in_qr_token',
        'clock_out_location',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_accuracy',
        'clock_out_qr_token',
        'clock_in_validation',
        'clock_out_validation',
        'validation_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i',
        'clock_out' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
        'hours_worked' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'clock_in_latitude' => 'decimal:8',
        'clock_in_longitude' => 'decimal:8',
        'clock_out_latitude' => 'decimal:8',
        'clock_out_longitude' => 'decimal:8',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function calculateHoursWorked(): void
    {
        if (!$this->clock_in || !$this->clock_out || !$this->date) {
            return;
        }

        try {
            $dateStr = $this->date->format('Y-m-d');
            $clockInTime = $this->clock_in instanceof \Carbon\Carbon ? $this->clock_in->format('H:i:s') : $this->clock_in;
            $clockOutTime = $this->clock_out instanceof \Carbon\Carbon ? $this->clock_out->format('H:i:s') : $this->clock_out;
            $clockIn = \Carbon\Carbon::parse($dateStr . ' ' . $clockInTime);
            $clockOut = \Carbon\Carbon::parse($dateStr . ' ' . $clockOutTime);
            
            $totalMinutes = $clockIn->diffInMinutes($clockOut);

            // Soustraire la pause
            if ($this->break_start && $this->break_end) {
                try {
                    $breakStartTime = $this->break_start instanceof \Carbon\Carbon ? $this->break_start->format('H:i:s') : $this->break_start;
                    $breakEndTime = $this->break_end instanceof \Carbon\Carbon ? $this->break_end->format('H:i:s') : $this->break_end;
                    $breakStart = \Carbon\Carbon::parse($dateStr . ' ' . $breakStartTime);
                    $breakEnd = \Carbon\Carbon::parse($dateStr . ' ' . $breakEndTime);
                    $totalMinutes -= $breakStart->diffInMinutes($breakEnd);
                } catch (\Exception $e) {
                    // Ignorer si la pause n'est pas parseable
                }
            }

            $hoursWorked = max(0, $totalMinutes / 60);
            $standardHours = $this->employee?->weekly_hours ? $this->employee->weekly_hours / 5 : 8;
            
            $this->hours_worked = round($hoursWorked, 2);
            $this->overtime_hours = max(0, round($hoursWorked - $standardHours, 2));
            $this->save();
        } catch (\Exception $e) {
            // Ne pas sauvegarder si erreur de parsing
        }
    }

    public function startBreak(): void
    {
        if ($this->clock_in && !$this->break_start) {
            $this->update(['break_start' => now()->format('H:i:s')]);
        }
    }

    public function endBreak(): void
    {
        if ($this->break_start && !$this->break_end) {
            $this->update(['break_end' => now()->format('H:i:s')]);
        }
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'half_day' => 'info',
            'holiday' => 'primary',
            'sick' => 'danger',
            'remote' => 'info',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'present' => 'Présent',
            'absent' => 'Absent',
            'late' => 'En retard',
            'half_day' => 'Demi-journée',
            'holiday' => 'Congé',
            'sick' => 'Maladie',
            'remote' => 'Télétravail',
            default => $this->status,
        };
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->hours_worked) {
            return '-';
        }

        $hours = floor($this->hours_worked);
        $minutes = round(($this->hours_worked - $hours) * 60);

        return "{$hours}h{$minutes}";
    }
}

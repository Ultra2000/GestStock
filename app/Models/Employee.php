<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Employee extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'address',
        'city',
        'postal_code',
        'country',
        'social_security_number',
        'position',
        'department',
        'contract_type',
        'hire_date',
        'contract_end_date',
        'hourly_rate',
        'monthly_salary',
        'commission_rate',
        'weekly_hours',
        'status',
        'notes',
        'photo',
        'emergency_contact',
        'bank_details',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'contract_end_date' => 'date',
        'hourly_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'emergency_contact' => 'array',
        'bank_details' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (!$employee->employee_number) {
                $employee->employee_number = static::generateEmployeeNumber($employee->company_id);
            }
        });

        // Créer automatiquement un compte utilisateur désactivé après la création de l'employé
        static::created(function ($employee) {
            // Ne créer un utilisateur que si l'employé a un email et n'a pas déjà un compte
            if ($employee->email && !$employee->user_id) {
                // Vérifier si un utilisateur avec cet email existe déjà
                $existingUser = User::where('email', $employee->email)->first();
                
                if ($existingUser) {
                    // Associer l'utilisateur existant à l'employé
                    $employee->user_id = $existingUser->id;
                    $employee->saveQuietly();
                } else {
                    // Créer un nouveau compte utilisateur DÉSACTIVÉ
                    $user = User::create([
                        'name' => $employee->full_name,
                        'email' => $employee->email,
                        'password' => Hash::make(Str::random(16)), // Mot de passe temporaire aléatoire
                        'is_active' => false, // Compte désactivé par défaut
                    ]);

                    // Associer l'utilisateur à l'employé
                    $employee->user_id = $user->id;
                    $employee->saveQuietly();

                    // Associer l'utilisateur à l'entreprise de l'employé
                    if ($employee->company_id) {
                        $user->companies()->attach($employee->company_id);
                    }
                }
            }
        });
    }

    public static function generateEmployeeNumber($companyId): string
    {
        $prefix = 'EMP';
        $lastEmployee = static::where('company_id', $companyId)
            ->orderByDesc('id')
            ->first();

        $number = $lastEmployee ? intval(substr($lastEmployee->employee_number, -4)) + 1 : 1;
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    public function getSeniorityAttribute(): string
    {
        return $this->hire_date->diffForHumans(now(), true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function clockIn(): Attendance
    {
        $attendance = $this->attendances()->firstOrCreate(
            ['date' => today()],
            [
                'company_id' => $this->company_id,
                'clock_in' => now()->format('H:i:s'),
                'status' => 'present',
            ]
        );

        if (!$attendance->clock_in) {
            $attendance->update(['clock_in' => now()->format('H:i:s')]);
        }

        return $attendance;
    }

    public function clockOut(): ?Attendance
    {
        $attendance = $this->attendances()->where('date', today())->first();
        
        if ($attendance && $attendance->clock_in && !$attendance->clock_out) {
            try {
                $clockIn = \Carbon\Carbon::parse($attendance->clock_in);
                $clockOut = now();
                $hoursWorked = $clockIn->diffInMinutes($clockOut) / 60;

                $attendance->update([
                    'clock_out' => $clockOut->format('H:i:s'),
                    'hours_worked' => round($hoursWorked, 2),
                ]);
            } catch (\Exception $e) {
                $attendance->update([
                    'clock_out' => now()->format('H:i:s'),
                    'hours_worked' => 0,
                ]);
            }
        }

        return $attendance;
    }

    public function calculateCommissions(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        if (!$this->user_id || $this->commission_rate <= 0) {
            return 0;
        }

        $totalSales = Sale::where('user_id', $this->user_id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total');

        return $totalSales * ($this->commission_rate / 100);
    }

    public function getMonthlyHoursWorked(\Carbon\Carbon $month): float
    {
        return $this->attendances()
            ->whereMonth('date', $month->month)
            ->whereYear('date', $month->year)
            ->sum('hours_worked');
    }

    public function getContractTypeLabelAttribute(): string
    {
        return match($this->contract_type) {
            'cdi' => 'CDI',
            'cdd' => 'CDD',
            'interim' => 'Intérim',
            'stage' => 'Stage',
            'apprentissage' => 'Apprentissage',
            'freelance' => 'Freelance',
            default => $this->contract_type,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'success',
            'on_leave' => 'warning',
            'terminated' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Actif',
            'on_leave' => 'En congé',
            'terminated' => 'Terminé',
            default => $this->status,
        };
    }
}

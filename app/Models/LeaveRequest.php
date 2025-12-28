<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'approved_by',
        'type',
        'start_date',
        'end_date',
        'days_count',
        'status',
        'reason',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_count' => 'decimal:1',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (!$request->days_count) {
                $request->days_count = $request->calculateDays();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateDays(): float
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $days = 0;
        $current = $this->start_date->copy();

        while ($current->lte($this->end_date)) {
            if (!$current->isWeekend()) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
        ]);

        // Mettre à jour le statut de l'employé si le congé commence aujourd'hui ou est en cours
        if ($this->start_date->lte(now()) && $this->end_date->gte(now())) {
            $this->employee->update(['status' => 'on_leave']);
        }
    }

    public function reject(int $userId, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'rejection_reason' => $reason,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'paid' => 'Congé payé',
            'unpaid' => 'Congé sans solde',
            'sick' => 'Maladie',
            'maternity' => 'Maternité',
            'paternity' => 'Paternité',
            'other' => 'Autre',
            default => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'paid' => 'success',
            'unpaid' => 'warning',
            'sick' => 'danger',
            'maternity' => 'info',
            'paternity' => 'info',
            'other' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Refusé',
            'cancelled' => 'Annulé',
            default => $this->status,
        };
    }
}

<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'sale_id',
        'period_start',
        'period_end',
        'sale_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'sale_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public static function calculateForPeriod(Employee $employee, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): ?self
    {
        if (!$employee->user_id || $employee->commission_rate <= 0) {
            return null;
        }

        $sales = Sale::where('user_id', $employee->user_id)
            ->where('company_id', $employee->company_id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($sales->isEmpty()) {
            return null;
        }

        $totalSales = $sales->sum('total');
        $commissionAmount = $totalSales * ($employee->commission_rate / 100);

        return static::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'sale_amount' => $totalSales,
            'commission_rate' => $employee->commission_rate,
            'commission_amount' => $commissionAmount,
            'status' => 'pending',
        ]);
    }

    public static function generateMonthlyCommissions(int $companyId, \Carbon\Carbon $month): array
    {
        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->where('commission_rate', '>', 0)
            ->get();

        $commissions = [];
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        foreach ($employees as $employee) {
            $commission = static::calculateForPeriod($employee, $startDate, $endDate);
            if ($commission) {
                $commissions[] = $commission;
            }
        }

        return $commissions;
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'paid' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'paid' => 'Payée',
            'cancelled' => 'Annulée',
            default => $this->status,
        };
    }
}

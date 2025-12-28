<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Sale;
use App\Models\Commission;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CalculateCommissions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'hr:calculate-commissions 
                            {--month= : Calculate for a specific month (YYYY-MM)}
                            {--company= : Process only for a specific company ID}
                            {--employee= : Calculate for a specific employee ID}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate and create commission records for employees based on their sales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $month = $this->option('month') 
            ? Carbon::parse($this->option('month') . '-01') 
            : Carbon::now()->subMonth()->startOfMonth();
        
        $companyId = $this->option('company');
        $employeeId = $this->option('employee');

        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $this->info("Calculating commissions for: {$month->format('F Y')}");
        $this->info("Period: {$startDate->format('d/m/Y')} - {$endDate->format('d/m/Y')}");

        // Get employees with commission rates
        $query = Employee::where('status', 'active')
            ->where(function ($q) {
                $q->where('commission_rate', '>', 0)
                  ->orWhereNotNull('commission_rate');
            });

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($employeeId) {
            $query->where('id', $employeeId);
        }

        $employees = $query->get();

        if ($employees->isEmpty()) {
            $this->info('No employees with commission rates found.');
            return 0;
        }

        $this->info("Processing {$employees->count()} employee(s)...");
        $this->newLine();

        $totalCommissions = 0;
        $commissionsCreated = 0;

        foreach ($employees as $employee) {
            $this->line("Employee: {$employee->full_name} (Rate: {$employee->commission_rate}%)");

            // Get sales made by this employee in the period
            // Assuming there's a seller_id or employee_id field on sales
            $salesQuery = Sale::where('company_id', $employee->company_id)
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->where('status', 'completed')
                ->where('payment_status', 'paid');

            // Try to match by user_id if employee has a user
            if ($employee->user_id) {
                $salesQuery->where('user_id', $employee->user_id);
            }

            $sales = $salesQuery->get();

            if ($sales->isEmpty()) {
                $this->line("  No completed/paid sales found.");
                continue;
            }

            $this->line("  Found {$sales->count()} sale(s)");

            foreach ($sales as $sale) {
                // Check if commission already exists
                $existingCommission = Commission::where('employee_id', $employee->id)
                    ->where('sale_id', $sale->id)
                    ->exists();

                if ($existingCommission) {
                    $this->line("  - Sale #{$sale->reference}: Commission already exists, skipping.");
                    continue;
                }

                // Calculate commission
                $baseAmount = $sale->total_amount - ($sale->tax_amount ?? 0); // Commission on HT amount
                $commissionAmount = $baseAmount * ($employee->commission_rate / 100);

                // Create commission record
                $commission = Commission::create([
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'sale_id' => $sale->id,
                    'amount' => $commissionAmount,
                    'rate' => $employee->commission_rate,
                    'base_amount' => $baseAmount,
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'status' => 'pending',
                    'notes' => "Commission auto-calculée pour la vente #{$sale->reference}",
                ]);

                $totalCommissions += $commissionAmount;
                $commissionsCreated++;

                $this->line("  - Sale #{$sale->reference}: {$baseAmount} € HT → Commission: " . number_format($commissionAmount, 2) . " €");
            }

            // Calculate total for this employee
            $employeeTotal = $employee->calculateCommission($startDate, $endDate);
            $this->info("  Total commissions: " . number_format($employeeTotal, 2) . " €");
            $this->newLine();
        }

        $this->info("Summary:");
        $this->line("  - Commissions created: {$commissionsCreated}");
        $this->line("  - Total amount: " . number_format($totalCommissions, 2) . " €");

        return 0;
    }
}

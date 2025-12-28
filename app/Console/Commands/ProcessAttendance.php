<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ProcessAttendance extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'hr:process-attendance 
                            {--date= : Process attendance for a specific date (YYYY-MM-DD)}
                            {--company= : Process only for a specific company ID}';

    /**
     * The console command description.
     */
    protected $description = 'Process daily attendance records and calculate work hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::yesterday();
        $companyId = $this->option('company');

        $this->info("Processing attendance for: {$date->format('d/m/Y')}");

        // Find all attendances for the date that are still clocked in
        $query = Attendance::whereDate('date', $date)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $openAttendances = $query->get();

        if ($openAttendances->isEmpty()) {
            $this->info('No open attendance records to process.');
        } else {
            $this->info("Found {$openAttendances->count()} open attendance record(s).");

            foreach ($openAttendances as $attendance) {
                // Auto clock out at end of day (configurable)
                $endOfDay = $date->copy()->setTime(18, 0, 0);
                
                $attendance->update([
                    'clock_out' => $endOfDay,
                    'notes' => ($attendance->notes ? $attendance->notes . "\n" : '') . 
                              "Auto clock-out à {$endOfDay->format('H:i')} (fin de journée)",
                ]);

                $this->line("  Auto clock-out: {$attendance->employee->full_name} at {$endOfDay->format('H:i')}");
            }
        }

        // Check for employees who didn't clock in (optional: create absent records)
        $this->newLine();
        $this->info("Checking for absent employees...");

        $employeeQuery = Employee::where('status', 'active');
        
        if ($companyId) {
            $employeeQuery->where('company_id', $companyId);
        }

        $activeEmployees = $employeeQuery->get();
        $absentCount = 0;

        foreach ($activeEmployees as $employee) {
            // Check if employee has a scheduled day
            $dayOfWeek = $date->dayOfWeekIso; // 1 = Monday, 7 = Sunday
            
            $hasSchedule = $employee->schedules()
                ->whereDate('date', $date)
                ->orWhere(function ($q) use ($dayOfWeek) {
                    $q->whereNull('date')
                      ->where('day_of_week', $dayOfWeek);
                })
                ->exists();

            if (!$hasSchedule) {
                continue; // Not scheduled to work
            }

            // Check if has attendance record
            $hasAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->exists();

            if (!$hasAttendance) {
                // Create absent record
                Attendance::create([
                    'company_id' => $employee->company_id,
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'status' => 'absent',
                    'notes' => 'Absence non justifiée',
                ]);

                $absentCount++;
                $this->line("  Absent: {$employee->full_name}");
            }
        }

        $this->info("Processed {$absentCount} absent record(s).");

        return 0;
    }
}

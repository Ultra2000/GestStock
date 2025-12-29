<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Schedule;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SchedulePlanning extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'RH';

    protected static ?string $navigationLabel = 'Planning';

    protected static ?string $title = 'Planning des équipes';

    protected static string $view = 'filament.pages.schedule-planning';

    protected static ?int $navigationSort = 1;

    public $weekStart;
    public $selectedEmployee;
    public $employees = [];
    public $schedules = [];
    public $weekDays = [];

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('hr') ?? true;
    }

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('hr')) {
            return false;
        }
        
        $user = auth()->user();
        if (!$user) return false;
        
        return $user->isAdmin() || $user->hasPermission('schedule.view') || $user->hasPermission('schedule.manage');
    }

    public function mount(): void
    {
        $this->weekStart = now()->startOfWeek()->format('Y-m-d');
        $this->loadData();
    }

    public function loadData(): void
    {
        $companyId = Filament::getTenant()?->id;
        $startDate = Carbon::parse($this->weekStart);
        $endDate = $startDate->copy()->addDays(6);

        $this->employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get();

        $this->weekDays = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $this->weekDays[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->locale('fr')->isoFormat('ddd'),
                'dayNum' => $date->format('d'),
                'month' => $date->locale('fr')->isoFormat('MMM'),
                'isToday' => $date->isToday(),
                'isWeekend' => $date->isWeekend(),
            ];
        }

        $this->schedules = Schedule::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn ($s) => $s->employee_id . '-' . $s->date->format('Y-m-d'))
            ->map(fn ($group) => $group->first())
            ->toArray();
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->format('Y-m-d');
        $this->loadData();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->format('Y-m-d');
        $this->loadData();
    }

    public function goToToday(): void
    {
        $this->weekStart = now()->startOfWeek()->format('Y-m-d');
        $this->loadData();
    }

    public function getSchedule($employeeId, $date): ?array
    {
        $key = $employeeId . '-' . $date;
        return $this->schedules[$key] ?? null;
    }

    public function saveSchedule($employeeId, $date, $startTime, $endTime, $shiftType = null): void
    {
        $companyId = Filament::getTenant()?->id;

        if (empty($startTime) || empty($endTime)) {
            Schedule::where('company_id', $companyId)
                ->where('employee_id', $employeeId)
                ->where('date', $date)
                ->delete();
        } else {
            Schedule::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'employee_id' => $employeeId,
                    'date' => $date,
                ],
                [
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'shift_type' => $shiftType,
                    'break_duration' => '01:00:00',
                ]
            );
        }

        $this->loadData();

        Notification::make()
            ->title('Planning mis à jour')
            ->success()
            ->send();
    }

    public function publishWeek(): void
    {
        $companyId = Filament::getTenant()?->id;
        $startDate = Carbon::parse($this->weekStart);

        Schedule::publishWeek($companyId, $startDate);

        Notification::make()
            ->title('Planning publié')
            ->body('Le planning de la semaine a été publié aux employés.')
            ->success()
            ->send();

        $this->loadData();
    }

    public function duplicatePreviousWeek(): void
    {
        $companyId = Filament::getTenant()?->id;
        $currentWeekStart = Carbon::parse($this->weekStart);
        $previousWeekStart = $currentWeekStart->copy()->subWeek();

        $previousSchedules = Schedule::where('company_id', $companyId)
            ->whereBetween('date', [$previousWeekStart, $previousWeekStart->copy()->addDays(6)])
            ->get();

        foreach ($previousSchedules as $schedule) {
            $newDate = Carbon::parse($schedule->date)->addWeek();
            
            Schedule::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'employee_id' => $schedule->employee_id,
                    'date' => $newDate,
                ],
                [
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'shift_type' => $schedule->shift_type,
                    'break_duration' => $schedule->break_duration,
                    'location' => $schedule->location,
                    'is_published' => false,
                ]
            );
        }

        $this->loadData();

        Notification::make()
            ->title('Planning dupliqué')
            ->body('Le planning de la semaine précédente a été copié.')
            ->success()
            ->send();
    }
}

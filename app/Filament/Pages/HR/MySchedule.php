<?php

namespace App\Filament\Pages\HR;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ScheduleNotification;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MySchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'RH';

    protected static ?string $navigationLabel = 'Mon Planning';

    protected static ?string $title = 'Mon Planning';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.h-r.my-schedule';

    public $weekStart;
    public $weekDays = [];
    public $schedules = [];
    public $notifications = [];
    public $employee = null;
    public $weeklyStats = [];

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getTenant()?->isModuleEnabled('hr') ?? false;
    }

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();
        if (!$tenant?->isModuleEnabled('hr')) {
            return false;
        }
        
        $user = auth()->user();
        if (!$user) return false;
        
        // Tous les utilisateurs avec un profil employé peuvent voir leur planning
        return true;
    }

    public function mount(): void
    {
        $this->weekStart = now()->startOfWeek()->format('Y-m-d');
        $this->loadEmployee();
        $this->loadData();
        $this->loadNotifications();
    }

    protected function loadEmployee(): void
    {
        $companyId = Filament::getTenant()?->id;
        $user = auth()->user();

        // Trouver l'employé lié à cet utilisateur
        $this->employee = Employee::where('company_id', $companyId)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();
    }

    public function loadData(): void
    {
        if (!$this->employee) {
            $this->schedules = [];
            $this->weekDays = [];
            return;
        }

        $startDate = Carbon::parse($this->weekStart);
        $endDate = $startDate->copy()->addDays(6);

        $this->weekDays = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $this->weekDays[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->locale('fr')->isoFormat('dddd'),
                'dayShort' => $date->locale('fr')->isoFormat('ddd'),
                'dayNum' => $date->format('d'),
                'month' => $date->locale('fr')->isoFormat('MMMM'),
                'isToday' => $date->isToday(),
                'isWeekend' => $date->isWeekend(),
                'isPast' => $date->isPast() && !$date->isToday(),
            ];
        }

        $this->schedules = Schedule::where('company_id', Filament::getTenant()?->id)
            ->where('employee_id', $this->employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_published', true) // Seuls les plannings publiés
            ->get()
            ->keyBy(fn ($s) => $s->date->format('Y-m-d'))
            ->toArray();

        // Calculer les statistiques de la semaine
        $this->calculateWeeklyStats();
    }

    protected function calculateWeeklyStats(): void
    {
        $totalHours = 0;
        $workedDays = 0;

        foreach ($this->schedules as $schedule) {
            if (!empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                $start = Carbon::parse($schedule['start_time']);
                $end = Carbon::parse($schedule['end_time']);
                $breakMinutes = 60;

                if (!empty($schedule['break_duration'])) {
                    try {
                        $break = Carbon::parse($schedule['break_duration']);
                        $breakMinutes = $break->hour * 60 + $break->minute;
                    } catch (\Exception $e) {
                        $breakMinutes = 60;
                    }
                }

                $hours = max(0, ($start->diffInMinutes($end) - $breakMinutes) / 60);
                $totalHours += $hours;
                $workedDays++;
            }
        }

        $this->weeklyStats = [
            'totalHours' => round($totalHours, 1),
            'workedDays' => $workedDays,
            'contractHours' => $this->employee->weekly_hours ?? 35,
        ];
    }

    protected function loadNotifications(): void
    {
        if (!$this->employee) {
            $this->notifications = [];
            return;
        }

        $this->notifications = ScheduleNotification::where('company_id', Filament::getTenant()?->id)
            ->where('employee_id', $this->employee->id)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
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

    public function getScheduleForDate(string $date): ?array
    {
        return $this->schedules[$date] ?? null;
    }

    public function markNotificationAsRead(int $notificationId): void
    {
        $notification = ScheduleNotification::find($notificationId);
        if ($notification && $notification->employee_id === $this->employee?->id) {
            $notification->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllNotificationsAsRead(): void
    {
        if (!$this->employee) return;

        ScheduleNotification::where('company_id', Filament::getTenant()?->id)
            ->where('employee_id', $this->employee->id)
            ->unread()
            ->update(['read_at' => now()]);

        $this->loadNotifications();
    }
}

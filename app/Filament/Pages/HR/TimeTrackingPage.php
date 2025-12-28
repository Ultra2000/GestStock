<?php

namespace App\Filament\Pages\HR;

use App\Models\Employee;
use App\Models\Attendance;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Livewire\Attributes\On;

class TimeTrackingPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Ressources Humaines';

    protected static ?string $navigationLabel = 'Pointage';

    protected static ?string $title = 'Pointage';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.hr.time-tracking';

    public array $todayAttendances = [];
    public array $employees = [];
    public ?int $selectedEmployee = null;
    public string $currentTime = '';

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $companyId = Filament::getTenant()?->id;
        $today = Carbon::today();

        // Load all active employees
        $this->employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->full_name,
                'position' => $e->position,
                'photo' => $e->photo,
            ])
            ->toArray();

        // Load today's attendances
        $attendances = Attendance::where('company_id', $companyId)
            ->whereDate('date', $today)
            ->with('employee')
            ->get();

        $this->todayAttendances = [];
        foreach ($attendances as $att) {
            $this->todayAttendances[$att->employee_id] = [
                'id' => $att->id,
                'clock_in' => $att->clock_in?->format('H:i'),
                'clock_out' => $att->clock_out?->format('H:i'),
                'break_start' => $att->break_start?->format('H:i'),
                'break_end' => $att->break_end?->format('H:i'),
                'status' => $att->status,
                'hours_worked' => $att->hours_worked,
            ];
        }

        $this->currentTime = Carbon::now()->format('H:i:s');
    }

    public function clockIn(?int $employeeId = null): void
    {
        $employeeId = $employeeId ?? $this->selectedEmployee;
        
        if (!$employeeId) {
            Notification::make()
                ->title('Veuillez sélectionner un employé')
                ->warning()
                ->send();
            return;
        }

        $employee = Employee::find($employeeId);
        
        if (!$employee) {
            Notification::make()
                ->title('Employé introuvable')
                ->danger()
                ->send();
            return;
        }

        try {
            $employee->clockIn();
            
            Notification::make()
                ->title('Pointage entrée enregistré')
                ->body("{$employee->full_name} a pointé à " . Carbon::now()->format('H:i'))
                ->success()
                ->send();

            $this->loadData();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function clockOut(?int $employeeId = null): void
    {
        $employeeId = $employeeId ?? $this->selectedEmployee;
        
        if (!$employeeId) {
            Notification::make()
                ->title('Veuillez sélectionner un employé')
                ->warning()
                ->send();
            return;
        }

        $employee = Employee::find($employeeId);
        
        if (!$employee) {
            Notification::make()
                ->title('Employé introuvable')
                ->danger()
                ->send();
            return;
        }

        try {
            $employee->clockOut();
            
            Notification::make()
                ->title('Pointage sortie enregistré')
                ->body("{$employee->full_name} a pointé à " . Carbon::now()->format('H:i'))
                ->success()
                ->send();

            $this->loadData();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function startBreak(?int $employeeId = null): void
    {
        $employeeId = $employeeId ?? $this->selectedEmployee;
        
        if (!$employeeId) {
            return;
        }

        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($attendance && !$attendance->break_start) {
            $attendance->update(['break_start' => Carbon::now()]);
            
            Notification::make()
                ->title('Pause démarrée')
                ->success()
                ->send();

            $this->loadData();
        }
    }

    public function endBreak(?int $employeeId = null): void
    {
        $employeeId = $employeeId ?? $this->selectedEmployee;
        
        if (!$employeeId) {
            return;
        }

        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($attendance && $attendance->break_start && !$attendance->break_end) {
            $attendance->update(['break_end' => Carbon::now()]);
            
            Notification::make()
                ->title('Pause terminée')
                ->success()
                ->send();

            $this->loadData();
        }
    }

    public function getEmployeeStatus(int $employeeId): string
    {
        if (!isset($this->todayAttendances[$employeeId])) {
            return 'absent';
        }

        $att = $this->todayAttendances[$employeeId];

        if ($att['clock_out']) {
            return 'left';
        }

        if ($att['break_start'] && !$att['break_end']) {
            return 'break';
        }

        if ($att['clock_in']) {
            return 'present';
        }

        return 'absent';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->loadData()),

            Action::make('manual_entry')
                ->label('Saisie manuelle')
                ->icon('heroicon-o-pencil-square')
                ->form([
                    Select::make('employee_id')
                        ->label('Employé')
                        ->options(fn () => collect($this->employees)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    \Filament\Forms\Components\DatePicker::make('date')
                        ->label('Date')
                        ->default(now())
                        ->required(),
                    \Filament\Forms\Components\TimePicker::make('clock_in')
                        ->label('Heure d\'entrée')
                        ->required(),
                    \Filament\Forms\Components\TimePicker::make('clock_out')
                        ->label('Heure de sortie'),
                    Textarea::make('notes')
                        ->label('Notes'),
                ])
                ->action(function (array $data) {
                    $companyId = Filament::getTenant()?->id;

                    Attendance::updateOrCreate(
                        [
                            'company_id' => $companyId,
                            'employee_id' => $data['employee_id'],
                            'date' => $data['date'],
                        ],
                        [
                            'clock_in' => $data['clock_in'],
                            'clock_out' => $data['clock_out'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'status' => 'present',
                        ]
                    );

                    Notification::make()
                        ->title('Pointage enregistré')
                        ->success()
                        ->send();

                    $this->loadData();
                }),
        ];
    }
}

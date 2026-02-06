<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ScheduleTemplate;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
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
    
    // Propriétés pour le modal d'édition
    public ?int $editingScheduleId = null;
    public ?int $editingEmployeeId = null;
    public ?string $editingDate = null;
    public ?string $editStartTime = null;
    public ?string $editEndTime = null;
    public ?string $editBreakDuration = null;
    public ?string $editShiftType = null;
    public ?string $editNotes = null;
    public bool $showEditModal = false;
    
    // Pour l'application des templates
    public bool $showTemplateModal = false;
    public ?int $selectedTemplateId = null;
    public array $selectedEmployeesForTemplate = [];
    public array $templates = [];

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
        $this->loadTemplates();
    }

    protected function loadTemplates(): void
    {
        $companyId = Filament::getTenant()?->id;
        $this->templates = ScheduleTemplate::where('company_id', $companyId)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get()
            ->toArray();
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

    /**
     * Ouvre le modal d'édition pour un créneau existant ou nouveau
     */
    public function openEditModal(int $employeeId, string $date, ?int $scheduleId = null): void
    {
        $this->editingEmployeeId = $employeeId;
        $this->editingDate = $date;
        $this->editingScheduleId = $scheduleId;
        
        if ($scheduleId) {
            $schedule = Schedule::find($scheduleId);
            if ($schedule) {
                $this->editStartTime = substr($schedule->start_time, 0, 5);
                $this->editEndTime = substr($schedule->end_time, 0, 5);
                $this->editBreakDuration = $schedule->break_duration ? substr($schedule->break_duration, 0, 5) : '01:00';
                $this->editShiftType = $schedule->shift_type;
                $this->editNotes = $schedule->notes;
            }
        } else {
            // Valeurs par défaut pour un nouveau créneau
            $this->editStartTime = '09:00';
            $this->editEndTime = '17:00';
            $this->editBreakDuration = '01:00';
            $this->editShiftType = null;
            $this->editNotes = null;
        }
        
        $this->showEditModal = true;
    }

    /**
     * Ferme le modal d'édition
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetEditForm();
    }

    /**
     * Réinitialise le formulaire d'édition
     */
    protected function resetEditForm(): void
    {
        $this->editingScheduleId = null;
        $this->editingEmployeeId = null;
        $this->editingDate = null;
        $this->editStartTime = null;
        $this->editEndTime = null;
        $this->editBreakDuration = null;
        $this->editShiftType = null;
        $this->editNotes = null;
    }

    /**
     * Sauvegarde le créneau depuis le modal
     */
    public function saveScheduleFromModal(): void
    {
        $companyId = Filament::getTenant()?->id;

        if (empty($this->editStartTime) || empty($this->editEndTime)) {
            Notification::make()
                ->title('Erreur')
                ->body('Les heures de début et de fin sont obligatoires.')
                ->danger()
                ->send();
            return;
        }

        Schedule::updateOrCreate(
            [
                'company_id' => $companyId,
                'employee_id' => $this->editingEmployeeId,
                'date' => $this->editingDate,
            ],
            [
                'start_time' => $this->editStartTime,
                'end_time' => $this->editEndTime,
                'break_duration' => $this->editBreakDuration ?? '01:00:00',
                'shift_type' => $this->editShiftType,
                'notes' => $this->editNotes,
            ]
        );

        $this->loadData();
        $this->closeEditModal();

        Notification::make()
            ->title('Planning mis à jour')
            ->success()
            ->send();
    }

    /**
     * Supprime un créneau
     */
    public function deleteSchedule(): void
    {
        if ($this->editingScheduleId) {
            Schedule::destroy($this->editingScheduleId);
            $this->loadData();
            $this->closeEditModal();

            Notification::make()
                ->title('Créneau supprimé')
                ->success()
                ->send();
        }
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

        if ($previousSchedules->isEmpty()) {
            Notification::make()
                ->title('Aucun planning')
                ->body('Aucun planning trouvé la semaine précédente.')
                ->warning()
                ->send();
            return;
        }

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
                    'notes' => $schedule->notes,
                    'is_published' => false,
                ]
            );
        }

        $this->loadData();

        Notification::make()
            ->title('Planning dupliqué')
            ->body($previousSchedules->count() . ' créneaux copiés de la semaine précédente.')
            ->success()
            ->send();
    }

    /**
     * Retourne le nom de l'employé pour l'affichage dans le modal
     */
    public function getEditingEmployeeName(): string
    {
        if (!$this->editingEmployeeId) return '';
        
        $employee = $this->employees->firstWhere('id', $this->editingEmployeeId);
        return $employee ? $employee->first_name . ' ' . $employee->last_name : '';
    }

    /**
     * Retourne la date formatée pour l'affichage dans le modal
     */
    public function getEditingDateFormatted(): string
    {
        if (!$this->editingDate) return '';
        
        return Carbon::parse($this->editingDate)->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }

    /**
     * Ouvre le modal pour appliquer un template
     */
    public function openTemplateModal(): void
    {
        $this->selectedTemplateId = null;
        $this->selectedEmployeesForTemplate = [];
        $this->showTemplateModal = true;
    }

    /**
     * Ferme le modal de template
     */
    public function closeTemplateModal(): void
    {
        $this->showTemplateModal = false;
        $this->selectedTemplateId = null;
        $this->selectedEmployeesForTemplate = [];
    }

    /**
     * Applique un template aux employés sélectionnés
     */
    public function applyTemplate(): void
    {
        if (!$this->selectedTemplateId) {
            Notification::make()
                ->title('Erreur')
                ->body('Veuillez sélectionner un template.')
                ->danger()
                ->send();
            return;
        }

        if (empty($this->selectedEmployeesForTemplate)) {
            Notification::make()
                ->title('Erreur')
                ->body('Veuillez sélectionner au moins un employé.')
                ->danger()
                ->send();
            return;
        }

        $template = ScheduleTemplate::find($this->selectedTemplateId);
        if (!$template) {
            Notification::make()
                ->title('Erreur')
                ->body('Template introuvable.')
                ->danger()
                ->send();
            return;
        }

        $weekStart = Carbon::parse($this->weekStart);
        $count = 0;

        foreach ($this->selectedEmployeesForTemplate as $employeeId) {
            $schedules = $template->applyToEmployee($employeeId, $weekStart);
            $count += count($schedules);
        }

        $this->loadData();
        $this->closeTemplateModal();

        Notification::make()
            ->title('Template appliqué')
            ->body("{$count} créneaux créés pour " . count($this->selectedEmployeesForTemplate) . " employé(s).")
            ->success()
            ->send();
    }

    /**
     * Sauvegarder la semaine actuelle comme template
     */
    public function saveAsTemplate(int $employeeId, string $templateName): void
    {
        $companyId = Filament::getTenant()?->id;
        $weekStart = Carbon::parse($this->weekStart);

        $template = ScheduleTemplate::createFromWeek($companyId, $employeeId, $weekStart, $templateName);
        
        $this->loadTemplates();

        Notification::make()
            ->title('Template créé')
            ->body("Le template '{$templateName}' a été créé à partir du planning de la semaine.")
            ->success()
            ->send();
    }
}

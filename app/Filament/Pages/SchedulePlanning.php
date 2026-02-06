<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ScheduleTemplate;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class SchedulePlanning extends Page implements HasForms, HasActions
{
    use InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'RH';

    protected static ?string $navigationLabel = 'Planning';

    protected static ?string $title = 'Planning des équipes';

    protected static string $view = 'filament.pages.schedule-planning';

    protected static ?int $navigationSort = 1;

    public $weekStart;
    public $employees = [];
    public $schedules = [];
    public $weekDays = [];
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
            ->pluck('name', 'id')
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
     * Action pour éditer/créer un créneau - Modal Filament natif
     */
    public function editScheduleAction(): Action
    {
        return Action::make('editSchedule')
            ->label('Modifier le créneau')
            ->modalHeading(fn (array $arguments) => 
                isset($arguments['scheduleId']) ? 'Modifier le créneau' : 'Nouveau créneau'
            )
            ->modalDescription(fn (array $arguments) => 
                $this->getEmployeeName($arguments['employeeId'] ?? null) . ' - ' . 
                $this->formatDate($arguments['date'] ?? null)
            )
            ->form([
                TextInput::make('start_time')
                    ->label('Heure de début')
                    ->type('time')
                    ->required()
                    ->default('09:00'),
                    
                TextInput::make('end_time')
                    ->label('Heure de fin')
                    ->type('time')
                    ->required()
                    ->default('17:00'),
                    
                Select::make('break_duration')
                    ->label('Durée de pause')
                    ->options([
                        '00:00' => 'Pas de pause',
                        '00:30' => '30 minutes',
                        '00:45' => '45 minutes',
                        '01:00' => '1 heure',
                        '01:30' => '1h30',
                        '02:00' => '2 heures',
                    ])
                    ->default('01:00'),
                    
                Select::make('shift_type')
                    ->label('Type de shift')
                    ->options([
                        'morning' => 'Matin',
                        'afternoon' => 'Après-midi',
                        'evening' => 'Soir',
                        'night' => 'Nuit',
                        'full_day' => 'Journée complète',
                    ])
                    ->placeholder('-- Aucun --'),
                    
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2)
                    ->placeholder('Notes optionnelles...'),
            ])
            ->fillForm(function (array $arguments): array {
                if (isset($arguments['scheduleId'])) {
                    $schedule = Schedule::find($arguments['scheduleId']);
                    if ($schedule) {
                        return [
                            'start_time' => substr($schedule->start_time ?? '09:00', 0, 5),
                            'end_time' => substr($schedule->end_time ?? '17:00', 0, 5),
                            'break_duration' => $schedule->break_duration ? substr($schedule->break_duration, 0, 5) : '01:00',
                            'shift_type' => $schedule->shift_type,
                            'notes' => $schedule->notes,
                        ];
                    }
                }
                return [
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'break_duration' => '01:00',
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $companyId = Filament::getTenant()?->id;
                
                Schedule::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'employee_id' => $arguments['employeeId'],
                        'date' => $arguments['date'],
                    ],
                    [
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'break_duration' => $data['break_duration'] . ':00',
                        'shift_type' => $data['shift_type'],
                        'notes' => $data['notes'],
                    ]
                );

                $this->loadData();

                Notification::make()
                    ->title('Planning mis à jour')
                    ->success()
                    ->send();
            })
            ->extraModalFooterActions(fn (Action $action, array $arguments): array => 
                isset($arguments['scheduleId']) ? [
                    Action::make('delete')
                        ->label('Supprimer')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Supprimer ce créneau ?')
                        ->modalDescription('Cette action est irréversible.')
                        ->action(function () use ($arguments) {
                            if (isset($arguments['scheduleId'])) {
                                Schedule::destroy($arguments['scheduleId']);
                                $this->loadData();
                                
                                Notification::make()
                                    ->title('Créneau supprimé')
                                    ->success()
                                    ->send();
                            }
                        }),
                ] : []
            )
            ->modalSubmitActionLabel('Enregistrer')
            ->modalCancelActionLabel('Annuler');
    }

    /**
     * Action pour appliquer un template
     */
    public function applyTemplateAction(): Action
    {
        return Action::make('applyTemplate')
            ->label('Appliquer un template')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->modalHeading('Appliquer un template')
            ->modalDescription('Sélectionnez un template et les employés auxquels l\'appliquer pour la semaine en cours.')
            ->form([
                Select::make('template_id')
                    ->label('Template')
                    ->options($this->templates)
                    ->required()
                    ->placeholder('-- Choisir un template --'),
                    
                CheckboxList::make('employee_ids')
                    ->label('Employés')
                    ->options(fn () => $this->employees->pluck('full_name', 'id')->toArray())
                    ->required()
                    ->columns(2)
                    ->gridDirection('row'),
            ])
            ->action(function (array $data): void {
                $template = ScheduleTemplate::find($data['template_id']);
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

                foreach ($data['employee_ids'] as $employeeId) {
                    $schedules = $template->applyToEmployee($employeeId, $weekStart);
                    $count += count($schedules);
                }

                $this->loadData();

                Notification::make()
                    ->title('Template appliqué')
                    ->body("{$count} créneaux créés pour " . count($data['employee_ids']) . " employé(s).")
                    ->success()
                    ->send();
            })
            ->modalSubmitActionLabel('Appliquer')
            ->modalCancelActionLabel('Annuler')
            ->visible(fn () => count($this->templates) > 0);
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

    protected function getEmployeeName(?int $employeeId): string
    {
        if (!$employeeId) return '';
        $employee = $this->employees->firstWhere('id', $employeeId);
        return $employee ? $employee->first_name . ' ' . $employee->last_name : '';
    }

    protected function formatDate(?string $date): string
    {
        if (!$date) return '';
        return Carbon::parse($date)->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }
}

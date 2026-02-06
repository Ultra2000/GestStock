<?php

namespace App\Filament\Pages\HR;

use App\Models\Employee;
use App\Models\Schedule;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class ScheduleCalendar extends FullCalendarWidget
{
    public Model | string | null $model = Schedule::class;

    // Ce widget est utilisé via ScheduleCalendarPage
    // Ne pas l'enregistrer directement dans la navigation
    protected static bool $shouldRegisterNavigation = false;

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

    public function config(): array
    {
        return [
            'firstDay' => 1, // Monday
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            ],
            'initialView' => 'timeGridWeek',
            'navLinks' => true,
            'editable' => true,
            'selectable' => true,
            'dayMaxEvents' => true,
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '22:00:00',
            'slotDuration' => '00:30:00',
            'allDaySlot' => false,
            'nowIndicator' => true,
            'locale' => 'fr',
            'buttonText' => [
                'today' => "Aujourd'hui",
                'month' => 'Mois',
                'week' => 'Semaine',
                'day' => 'Jour',
                'list' => 'Liste',
            ],
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],
            'slotLabelFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],
        ];
    }

    public function fetchEvents(array $info): array
    {
        $companyId = Filament::getTenant()?->id;

        // Vérifier que les dates sont valides
        $startDate = $info['start'] ?? null;
        $endDate = $info['end'] ?? null;
        
        if (!$startDate || !$endDate || $startDate === '-' || $endDate === '-') {
            // Dates par défaut: mois courant
            $startDate = now()->startOfMonth()->format('Y-m-d');
            $endDate = now()->endOfMonth()->format('Y-m-d');
        }

        return Schedule::query()
            ->where('company_id', $companyId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        // Handle recurring schedules by day of week
                        $q->whereNull('date')
                          ->whereNotNull('day_of_week');
                    });
            })
            ->with('employee')
            ->get()
            ->flatMap(function (Schedule $schedule) use ($startDate, $endDate) {
                // If it's a specific date schedule
                if ($schedule->date) {
                    return [[
                        'id' => $schedule->id,
                        'title' => $schedule->employee?->full_name ?? 'Sans employé',
                        'start' => $schedule->date->format('Y-m-d') . 'T' . ($schedule->start_time ?? '09:00'),
                        'end' => $schedule->date->format('Y-m-d') . 'T' . ($schedule->end_time ?? '17:00'),
                        'backgroundColor' => $schedule->color ?? $this->getEmployeeColor($schedule->employee_id),
                        'borderColor' => $schedule->color ?? $this->getEmployeeColor($schedule->employee_id),
                        'extendedProps' => [
                            'employee_id' => $schedule->employee_id,
                            'notes' => $schedule->notes,
                            'position' => $schedule->position,
                            'is_published' => $schedule->is_published,
                        ],
                    ]];
                }

                // If it's a recurring schedule by day of week, generate events for the date range
                $events = [];
                try {
                    $start = \Carbon\Carbon::parse($startDate);
                    $end = \Carbon\Carbon::parse($endDate);
                } catch (\Exception $e) {
                    return [];
                }

                while ($start->lte($end)) {
                    if ($start->dayOfWeekIso === $schedule->day_of_week) {
                        $events[] = [
                            'id' => $schedule->id . '_' . $start->format('Y-m-d'),
                            'title' => $schedule->employee?->full_name ?? 'Sans employé',
                            'start' => $start->format('Y-m-d') . 'T' . ($schedule->start_time ?? '09:00'),
                            'end' => $start->format('Y-m-d') . 'T' . ($schedule->end_time ?? '17:00'),
                            'backgroundColor' => $schedule->color ?? $this->getEmployeeColor($schedule->employee_id),
                            'borderColor' => $schedule->color ?? $this->getEmployeeColor($schedule->employee_id),
                            'extendedProps' => [
                                'employee_id' => $schedule->employee_id,
                                'notes' => $schedule->notes,
                                'position' => $schedule->position,
                                'is_recurring' => true,
                                'schedule_id' => $schedule->id,
                            ],
                        ];
                    }
                    $start->addDay();
                }

                return $events;
            })
            ->toArray();
    }

    protected function getEmployeeColor(int $employeeId): string
    {
        $colors = [
            '#3b82f6', // blue
            '#10b981', // green
            '#f59e0b', // amber
            '#ef4444', // red
            '#8b5cf6', // violet
            '#ec4899', // pink
            '#06b6d4', // cyan
            '#84cc16', // lime
            '#f97316', // orange
            '#6366f1', // indigo
        ];

        return $colors[$employeeId % count($colors)];
    }

    public function getFormSchema(): array
    {
        $companyId = Filament::getTenant()?->id;

        return [
            Grid::make(2)
                ->schema([
                    Select::make('employee_id')
                        ->label('Employé')
                        ->options(
                            Employee::where('company_id', $companyId)
                                ->where('status', 'active')
                                ->get()
                                ->pluck('full_name', 'id')
                        )
                        ->required()
                        ->searchable()
                        ->columnSpan(2),

                    DateTimePicker::make('start')
                        ->label('Début')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(15),

                    DateTimePicker::make('end')
                        ->label('Fin')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(15)
                        ->after('start'),

                    TextInput::make('position')
                        ->label('Poste / Station')
                        ->placeholder('Ex: Caisse 1, Rayon fruits...')
                        ->maxLength(100),

                    ColorPicker::make('color')
                        ->label('Couleur'),

                    Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpan(2),

                    Toggle::make('is_published')
                        ->label('Publié')
                        ->helperText('Visible par l\'employé')
                        ->default(false)
                        ->columnSpan(2),
                ]),
        ];
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau créneau')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['company_id'] = Filament::getTenant()?->id;
                    
                    $startDate = $this->parseDate($data['start'] ?? null);
                    $endDate = $this->parseDate($data['end'] ?? null);
                    
                    $data['date'] = $startDate->format('Y-m-d');
                    $data['start_time'] = $startDate->format('H:i');
                    $data['end_time'] = $endDate->format('H:i');
                    return $data;
                }),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $startDate = $this->parseDate($data['start'] ?? null);
                    $endDate = $this->parseDate($data['end'] ?? null);
                    
                    $data['date'] = $startDate->format('Y-m-d');
                    $data['start_time'] = $startDate->format('H:i');
                    $data['end_time'] = $endDate->format('H:i');
                    return $data;
                }),
            Actions\DeleteAction::make(),
        ];
    }
    
    /**
     * Parse une date de manière sécurisée, retourne une date par défaut si invalide
     */
    protected function parseDate(?string $dateString): \Carbon\Carbon
    {
        if (empty($dateString) || $dateString === '-' || !strtotime($dateString)) {
            return now();
        }
        
        try {
            return \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            return now();
        }
    }

    protected function viewAction(): Actions\ViewAction
    {
        return Actions\ViewAction::make();
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        $startDate = $this->parseDate($event['start'] ?? null);
        $endDate = $this->parseDate($event['end'] ?? null);
        
        // Handle recurring event instance - create a new specific date schedule
        if (str_contains($event['id'], '_')) {
            $parts = explode('_', $event['id']);
            $originalScheduleId = $parts[0];
            $originalSchedule = Schedule::find($originalScheduleId);

            if ($originalSchedule) {
                // Create a new schedule for this specific date
                Schedule::create([
                    'company_id' => $originalSchedule->company_id,
                    'employee_id' => $originalSchedule->employee_id,
                    'date' => $startDate->format('Y-m-d'),
                    'start_time' => $startDate->format('H:i'),
                    'end_time' => $endDate->format('H:i'),
                    'position' => $originalSchedule->position,
                    'notes' => $originalSchedule->notes,
                    'color' => $originalSchedule->color,
                    'is_published' => $originalSchedule->is_published,
                ]);

                Notification::make()
                    ->title('Créneau déplacé')
                    ->body('Un nouveau créneau a été créé pour cette date.')
                    ->success()
                    ->send();

                return true;
            }
        }

        // Handle regular event
        $schedule = Schedule::find($event['id']);
        
        if ($schedule) {
            $schedule->update([
                'date' => $startDate->format('Y-m-d'),
                'start_time' => $startDate->format('H:i'),
                'end_time' => $endDate->format('H:i'),
            ]);

            Notification::make()
                ->title('Créneau mis à jour')
                ->success()
                ->send();

            return true;
        }

        return false;
    }

    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $startDelta, array $endDelta): bool
    {
        $startDate = $this->parseDate($event['start'] ?? null);
        $endDate = $this->parseDate($event['end'] ?? null);
        
        // Handle recurring event instance
        if (str_contains($event['id'], '_')) {
            $parts = explode('_', $event['id']);
            $originalScheduleId = $parts[0];
            $originalSchedule = Schedule::find($originalScheduleId);

            if ($originalSchedule) {
                // Create a new schedule for this specific date with new times
                Schedule::create([
                    'company_id' => $originalSchedule->company_id,
                    'employee_id' => $originalSchedule->employee_id,
                    'date' => $startDate->format('Y-m-d'),
                    'start_time' => $startDate->format('H:i'),
                    'end_time' => $endDate->format('H:i'),
                    'position' => $originalSchedule->position,
                    'notes' => $originalSchedule->notes,
                    'color' => $originalSchedule->color,
                    'is_published' => $originalSchedule->is_published,
                ]);

                Notification::make()
                    ->title('Créneau redimensionné')
                    ->success()
                    ->send();

                return true;
            }
        }

        $schedule = Schedule::find($event['id']);
        
        if ($schedule) {
            $schedule->update([
                'start_time' => $startDate->format('H:i'),
                'end_time' => $endDate->format('H:i'),
            ]);

            Notification::make()
                ->title('Durée mise à jour')
                ->success()
                ->send();

            return true;
        }

        return false;
    }

    public function onDateSelect(string $start, ?string $end, bool $allDay, ?array $view, ?array $resource): void
    {
        $startDate = $this->parseDate($start);
        $endDate = $end ? $this->parseDate($end) : $startDate->copy()->addHours(8);
        
        // This is called when a user selects a time range
        $this->mountAction('create', [
            'start' => $startDate->toIso8601String(),
            'end' => $endDate->toIso8601String(),
        ]);
    }

    public function onEventClick(array $event): void
    {
        // Handle click on recurring event instance
        if (str_contains($event['id'], '_')) {
            $parts = explode('_', $event['id']);
            $scheduleId = $parts[0];
            
            // Load the original schedule for editing
            $this->record = Schedule::find($scheduleId);
        } else {
            $this->record = Schedule::find($event['id']);
        }

        if ($this->record) {
            $this->mountAction('view');
        }
    }

    public function resolveEventRecord(array $data): Model
    {
        $id = $data['id'] ?? null;
        
        // Handle recurring event instance ID
        if ($id && str_contains($id, '_')) {
            $parts = explode('_', $id);
            $id = $parts[0];
        }

        return Schedule::find($id) ?? new Schedule();
    }
}

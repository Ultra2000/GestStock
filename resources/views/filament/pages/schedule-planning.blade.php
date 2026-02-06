<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header avec navigation --}}
        <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
            <div class="flex items-center gap-3">
                <button wire:click="previousWeek" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <x-heroicon-o-chevron-left class="w-5 h-5"/>
                </button>
                
                @php
                    $weekStartDate = $weekStart && $weekStart !== '-' ? \Carbon\Carbon::parse($weekStart) : now()->startOfWeek();
                @endphp
                <h2 class="text-lg font-semibold">
                    Semaine du {{ $weekStartDate->format('d/m/Y') }}
                    au {{ $weekStartDate->copy()->addDays(6)->format('d/m/Y') }}
                </h2>
                
                <button wire:click="nextWeek" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                    <x-heroicon-o-chevron-right class="w-5 h-5"/>
                </button>

                <button wire:click="goToToday" class="px-3 py-1 text-sm bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                    Aujourd'hui
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button wire:click="duplicatePreviousWeek" class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-2">
                    <x-heroicon-o-document-duplicate class="w-4 h-4"/>
                    Dupliquer semaine précédente
                </button>
                <button wire:click="publishWeek" class="px-4 py-2 text-sm bg-success-500 text-white rounded-lg hover:bg-success-600 transition flex items-center gap-2">
                    <x-heroicon-o-paper-airplane class="w-4 h-4"/>
                    Publier la semaine
                </button>
            </div>
        </div>

        {{-- Grille du planning --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300 w-48 sticky left-0 bg-gray-50 dark:bg-gray-700 z-10">
                                Employé
                            </th>
                            @foreach($weekDays as $day)
                                <th class="px-2 py-3 text-center text-sm min-w-[120px] {{ $day['isToday'] ? 'bg-primary-50 dark:bg-primary-900/20' : '' }} {{ $day['isWeekend'] ? 'bg-gray-100 dark:bg-gray-600' : '' }}">
                                    <div class="font-semibold {{ $day['isToday'] ? 'text-primary-600 dark:text-primary-400' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $day['day'] }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $day['dayNum'] }} {{ $day['month'] }}
                                    </div>
                                </th>
                            @endforeach
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700 dark:text-gray-300 w-24">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($employees as $employee)
                            @php
                                $weeklyHours = 0;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 sticky left-0 bg-white dark:bg-gray-800 z-10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-semibold text-sm">
                                            {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white text-sm">
                                                {{ $employee->first_name }} {{ $employee->last_name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $employee->position }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                @foreach($weekDays as $day)
                                    @php
                                        $schedule = $this->getSchedule($employee->id, $day['date']);
                                        $hours = 0;
                                        $startTime = null;
                                        $endTime = null;
                                        $scheduleId = null;
                                        
                                        if ($schedule && !empty($schedule['start_time']) && !empty($schedule['end_time']) && $schedule['start_time'] !== '-' && $schedule['end_time'] !== '-') {
                                            try {
                                                $start = \Carbon\Carbon::parse($schedule['start_time']);
                                                $end = \Carbon\Carbon::parse($schedule['end_time']);
                                                $startTime = $start->format('H:i');
                                                $endTime = $end->format('H:i');
                                                $scheduleId = $schedule['id'] ?? null;
                                                $breakMinutes = 60; // Par défaut 1h
                                                if (!empty($schedule['break_duration']) && $schedule['break_duration'] !== '-') {
                                                    try {
                                                        $breakTime = \Carbon\Carbon::parse($schedule['break_duration']);
                                                        $breakMinutes = $breakTime->hour * 60 + $breakTime->minute;
                                                    } catch (\Exception $e) {
                                                        $breakMinutes = 60;
                                                    }
                                                }
                                                $hours = max(0, ($start->diffInMinutes($end) - $breakMinutes) / 60);
                                                $weeklyHours += $hours;
                                            } catch (\Exception $e) {
                                                $schedule = null;
                                            }
                                        } else {
                                            $schedule = null;
                                        }
                                    @endphp
                                    <td class="px-2 py-2 text-center {{ $day['isToday'] ? 'bg-primary-50 dark:bg-primary-900/10' : '' }} {{ $day['isWeekend'] ? 'bg-gray-50 dark:bg-gray-700/50' : '' }}">
                                        @if($schedule && $startTime && $endTime)
                                            <button 
                                                wire:click="openEditModal({{ $employee->id }}, '{{ $day['date'] }}', {{ $scheduleId ?? 'null' }})"
                                                class="w-full bg-primary-100 dark:bg-primary-900/30 rounded-lg p-2 text-xs cursor-pointer hover:bg-primary-200 dark:hover:bg-primary-900/50 transition text-left">
                                                <div class="font-semibold text-primary-700 dark:text-primary-300">
                                                    {{ $startTime }} - {{ $endTime }}
                                                </div>
                                                <div class="text-primary-600 dark:text-primary-400">
                                                    {{ number_format($hours, 1) }}h
                                                </div>
                                                @if(!empty($schedule['shift_type']))
                                                    <div class="text-xs text-primary-500 mt-1">
                                                        {{ ucfirst($schedule['shift_type']) }}
                                                    </div>
                                                @endif
                                            </button>
                                        @else
                                            <button 
                                                wire:click="openEditModal({{ $employee->id }}, '{{ $day['date'] }}')"
                                                class="w-full h-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition flex items-center justify-center text-gray-400 hover:text-primary-600">
                                                <x-heroicon-o-plus class="w-5 h-5"/>
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center">
                                    <div class="font-semibold {{ $weeklyHours >= ($employee->weekly_hours ?? 35) ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">
                                        {{ number_format($weeklyHours, 1) }}h
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        / {{ $employee->weekly_hours ?? 35 }}h
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($weekDays) + 2 }}" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-user-group class="w-12 h-12 mx-auto mb-2 text-gray-400"/>
                                    Aucun employé actif. Créez d'abord des employés dans la section RH.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Légende --}}
        <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-primary-100 dark:bg-primary-900/30 rounded"></div>
                <span>Planifié</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-primary-50 dark:bg-primary-900/10 rounded border-2 border-primary-300"></div>
                <span>Aujourd'hui</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-gray-100 dark:bg-gray-600 rounded"></div>
                <span>Week-end</span>
            </div>
        </div>
    </div>

    {{-- Modal d'édition --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            {{-- Overlay --}}
            <div wire:click="closeEditModal" class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity"></div>

            {{-- Modal panel --}}
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                {{ $editingScheduleId ? 'Modifier le créneau' : 'Nouveau créneau' }}
                            </h3>
                            
                            @if($editingEmployeeId)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $this->getEditingEmployeeName() }} - {{ $this->getEditingDateFormatted() }}
                                </p>
                            @endif

                            <div class="mt-6 space-y-4">
                                {{-- Heure de début --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Heure de début *
                                    </label>
                                    <input type="time" wire:model="editStartTime"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                                </div>

                                {{-- Heure de fin --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Heure de fin *
                                    </label>
                                    <input type="time" wire:model="editEndTime"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                                </div>

                                {{-- Durée de pause --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Durée de pause
                                    </label>
                                    <select wire:model="editBreakDuration"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                                        <option value="00:00">Pas de pause</option>
                                        <option value="00:30">30 minutes</option>
                                        <option value="00:45">45 minutes</option>
                                        <option value="01:00">1 heure</option>
                                        <option value="01:30">1h30</option>
                                        <option value="02:00">2 heures</option>
                                    </select>
                                </div>

                                {{-- Type de shift --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Type de shift
                                    </label>
                                    <select wire:model="editShiftType"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500">
                                        <option value="">-- Aucun --</option>
                                        <option value="morning">Matin</option>
                                        <option value="afternoon">Après-midi</option>
                                        <option value="evening">Soir</option>
                                        <option value="night">Nuit</option>
                                        <option value="full_day">Journée complète</option>
                                    </select>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Notes
                                    </label>
                                    <textarea wire:model="editNotes" rows="2"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                                        placeholder="Notes optionnelles..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button wire:click="saveScheduleFromModal" type="button"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:w-auto sm:text-sm">
                        Enregistrer
                    </button>
                    
                    @if($editingScheduleId)
                        <button wire:click="deleteSchedule" type="button"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-danger-600 text-base font-medium text-white hover:bg-danger-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-500 sm:w-auto sm:text-sm">
                            Supprimer
                        </button>
                    @endif
                    
                    <button wire:click="closeEditModal" type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>

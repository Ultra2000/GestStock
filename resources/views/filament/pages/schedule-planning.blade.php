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
                                        
                                        if ($schedule && !empty($schedule['start_time']) && !empty($schedule['end_time']) && $schedule['start_time'] !== '-' && $schedule['end_time'] !== '-') {
                                            try {
                                                $start = \Carbon\Carbon::parse($schedule['start_time']);
                                                $end = \Carbon\Carbon::parse($schedule['end_time']);
                                                $startTime = $start->format('H:i');
                                                $endTime = $end->format('H:i');
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
                                            <div class="bg-primary-100 dark:bg-primary-900/30 rounded-lg p-2 text-xs cursor-pointer hover:bg-primary-200 dark:hover:bg-primary-900/50 transition"
                                                 x-data="{ open: false }"
                                                 @click="open = true">
                                                <div class="font-semibold text-primary-700 dark:text-primary-300">
                                                    {{ $startTime }}
                                                    -
                                                    {{ $endTime }}
                                                </div>
                                                <div class="text-primary-600 dark:text-primary-400">
                                                    {{ number_format($hours, 1) }}h
                                                </div>
                                            </div>
                                        @else
                                            <button 
                                                wire:click="saveSchedule({{ $employee->id }}, '{{ $day['date'] }}', '09:00', '17:00')"
                                                class="w-full h-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition flex items-center justify-center text-gray-400 hover:text-primary-600">
                                                <x-heroicon-o-plus class="w-5 h-5"/>
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center">
                                    <div class="font-semibold {{ $weeklyHours >= $employee->weekly_hours ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">
                                        {{ number_format($weeklyHours, 1) }}h
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        / {{ $employee->weekly_hours }}h
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
</x-filament-panels::page>

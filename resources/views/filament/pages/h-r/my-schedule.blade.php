<x-filament-panels::page>
    <div class="space-y-6">
        @if(!$employee)
            {{-- Message si pas de profil employé --}}
            <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 rounded-xl p-6 text-center">
                <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-3 text-warning-500"/>
                <h3 class="text-lg font-semibold text-warning-800 dark:text-warning-200 mb-2">
                    Profil employé non trouvé
                </h3>
                <p class="text-warning-600 dark:text-warning-400">
                    Votre compte n'est pas encore associé à un profil employé. 
                    Contactez votre responsable RH pour configurer votre accès au planning.
                </p>
            </div>
        @else
            {{-- Notifications --}}
            @if(count($notifications) > 0)
                <div class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-primary-800 dark:text-primary-200 flex items-center gap-2">
                            <x-heroicon-o-bell class="w-5 h-5"/>
                            Notifications
                            <span class="bg-primary-500 text-white text-xs px-2 py-0.5 rounded-full">{{ count($notifications) }}</span>
                        </h3>
                        <button wire:click="markAllNotificationsAsRead" class="text-sm text-primary-600 hover:text-primary-800 dark:text-primary-400">
                            Tout marquer comme lu
                        </button>
                    </div>
                    <div class="space-y-2">
                        @foreach($notifications as $notification)
                            <div class="flex items-start justify-between bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm">
                                <div class="flex-1">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $notification['message'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ \Carbon\Carbon::parse($notification['created_at'])->locale('fr')->diffForHumans() }}
                                    </p>
                                </div>
                                <button wire:click="markNotificationAsRead({{ $notification['id'] }})" 
                                        class="text-gray-400 hover:text-gray-600 ml-2">
                                    <x-heroicon-o-x-mark class="w-4 h-4"/>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Header avec navigation et stats --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Navigation de la semaine --}}
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button wire:click="previousWeek" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                <x-heroicon-o-chevron-left class="w-5 h-5"/>
                            </button>
                            
                            @php
                                $weekStartDate = $weekStart ? \Carbon\Carbon::parse($weekStart) : now()->startOfWeek();
                            @endphp
                            <h2 class="text-lg font-semibold">
                                Semaine du {{ $weekStartDate->format('d') }} au {{ $weekStartDate->copy()->addDays(6)->format('d F Y') }}
                            </h2>
                            
                            <button wire:click="nextWeek" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                <x-heroicon-o-chevron-right class="w-5 h-5"/>
                            </button>
                        </div>

                        <button wire:click="goToToday" class="px-3 py-1 text-sm bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition">
                            Aujourd'hui
                        </button>
                    </div>
                </div>

                {{-- Statistiques --}}
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Cette semaine</p>
                            <p class="text-2xl font-bold {{ $weeklyStats['totalHours'] >= $weeklyStats['contractHours'] ? 'text-success-600' : 'text-primary-600' }}">
                                {{ $weeklyStats['totalHours'] }}h
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Contrat</p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                {{ $weeklyStats['contractHours'] }}h
                            </p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            @php
                                $percentage = min(100, ($weeklyStats['totalHours'] / max(1, $weeklyStats['contractHours'])) * 100);
                            @endphp
                            <div class="h-2 rounded-full {{ $percentage >= 100 ? 'bg-success-500' : 'bg-primary-500' }}" 
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $weeklyStats['workedDays'] }} jour(s) travaillé(s)</p>
                    </div>
                </div>
            </div>

            {{-- Planning de la semaine --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="grid grid-cols-7 divide-x divide-gray-200 dark:divide-gray-700">
                    @foreach($weekDays as $day)
                        @php
                            $schedule = $this->getScheduleForDate($day['date']);
                            $hasSchedule = $schedule && !empty($schedule['start_time']) && !empty($schedule['end_time']);
                        @endphp
                        <div class="{{ $day['isToday'] ? 'bg-primary-50 dark:bg-primary-900/20' : '' }} {{ $day['isWeekend'] ? 'bg-gray-50 dark:bg-gray-700/30' : '' }}">
                            {{-- En-tête du jour --}}
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700 text-center">
                                <div class="text-xs uppercase tracking-wide {{ $day['isToday'] ? 'text-primary-600 font-semibold' : 'text-gray-500' }}">
                                    {{ $day['dayShort'] }}
                                </div>
                                <div class="text-lg font-semibold {{ $day['isToday'] ? 'text-primary-600' : '' }} {{ $day['isPast'] ? 'text-gray-400' : '' }}">
                                    {{ $day['dayNum'] }}
                                </div>
                            </div>
                            
                            {{-- Contenu du jour --}}
                            <div class="p-3 min-h-[120px]">
                                @if($hasSchedule)
                                    <div class="bg-primary-100 dark:bg-primary-900/40 rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold text-primary-700 dark:text-primary-300">
                                            {{ substr($schedule['start_time'], 0, 5) }}
                                        </div>
                                        <div class="text-xs text-primary-500 my-1">à</div>
                                        <div class="text-lg font-bold text-primary-700 dark:text-primary-300">
                                            {{ substr($schedule['end_time'], 0, 5) }}
                                        </div>
                                        
                                        @if(!empty($schedule['break_duration']) && $schedule['break_duration'] !== '00:00:00')
                                            <div class="text-xs text-primary-500 mt-2">
                                                Pause: {{ substr($schedule['break_duration'], 0, 5) }}
                                            </div>
                                        @endif

                                        @if(!empty($schedule['shift_type']))
                                            <div class="mt-2">
                                                <span class="inline-block px-2 py-0.5 text-xs rounded-full 
                                                    {{ $schedule['shift_type'] === 'morning' ? 'bg-blue-100 text-blue-700' : '' }}
                                                    {{ $schedule['shift_type'] === 'afternoon' ? 'bg-orange-100 text-orange-700' : '' }}
                                                    {{ $schedule['shift_type'] === 'evening' ? 'bg-purple-100 text-purple-700' : '' }}
                                                    {{ $schedule['shift_type'] === 'night' ? 'bg-gray-100 text-gray-700' : '' }}
                                                    {{ $schedule['shift_type'] === 'full_day' ? 'bg-green-100 text-green-700' : '' }}">
                                                    {{ ucfirst($schedule['shift_type']) }}
                                                </span>
                                            </div>
                                        @endif

                                        @if(!empty($schedule['notes']))
                                            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 italic">
                                                {{ Str::limit($schedule['notes'], 50) }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex items-center justify-center h-full text-gray-400">
                                        <div class="text-center">
                                            <x-heroicon-o-minus class="w-6 h-6 mx-auto"/>
                                            <span class="text-xs">Repos</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Informations employé --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-semibold text-lg">
                        {{ substr($employee->first_name ?? '', 0, 1) }}{{ substr($employee->last_name ?? '', 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            {{ $employee->first_name }} {{ $employee->last_name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $employee->position ?? 'Employé' }} • Contrat {{ $employee->weekly_hours ?? 35 }}h/semaine
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

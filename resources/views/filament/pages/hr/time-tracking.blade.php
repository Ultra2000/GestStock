<div>
    <div class="mb-6">
        <!-- Current Time Display -->
        <div class="text-center mb-6">
            <div class="text-6xl font-bold text-primary-600 dark:text-primary-400" 
                 x-data="{ time: '{{ $currentTime }}' }"
                 x-init="setInterval(() => { 
                     const now = new Date();
                     time = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                 }, 1000)">
                <span x-text="time"></span>
            </div>
            <div class="text-lg text-gray-500 dark:text-gray-400 mt-2">
                {{ \Carbon\Carbon::now()->translatedFormat('l d F Y') }}
            </div>
        </div>

        <!-- Quick Clock In/Out for Self -->
        @php
            $currentUserEmployee = collect($employees)->first(fn($e) => $e['id'] == auth()->user()?->employee?->id);
        @endphp
        @if($currentUserEmployee)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Mon pointage</h3>
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-gray-600 dark:text-gray-300">{{ $currentUserEmployee['name'] }}</span>
                    @if(isset($todayAttendances[$currentUserEmployee['id']]))
                        <span class="ml-2 px-2 py-1 rounded-full text-xs 
                            @if($this->getEmployeeStatus($currentUserEmployee['id']) === 'present') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($this->getEmployeeStatus($currentUserEmployee['id']) === 'break') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($this->getEmployeeStatus($currentUserEmployee['id']) === 'left') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                            @if($this->getEmployeeStatus($currentUserEmployee['id']) === 'present') Présent
                            @elseif($this->getEmployeeStatus($currentUserEmployee['id']) === 'break') En pause
                            @elseif($this->getEmployeeStatus($currentUserEmployee['id']) === 'left') Parti
                            @else Absent @endif
                        </span>
                    @endif
                </div>
                <div class="flex gap-2">
                    @if(!isset($todayAttendances[$currentUserEmployee['id']]) || !$todayAttendances[$currentUserEmployee['id']]['clock_in'])
                        <x-filament::button wire:click="clockIn({{ $currentUserEmployee['id'] }})" color="success" size="lg">
                            <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5 mr-2"/>
                            Pointer mon entrée
                        </x-filament::button>
                    @elseif(!$todayAttendances[$currentUserEmployee['id']]['clock_out'])
                        <x-filament::button wire:click="clockOut({{ $currentUserEmployee['id'] }})" color="danger" size="lg">
                            <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5 mr-2"/>
                            Pointer ma sortie
                        </x-filament::button>
                    @else
                        <span class="text-gray-500">Journée terminée</span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Summary Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @php
                $present = collect($employees)->filter(fn($e) => $this->getEmployeeStatus($e['id']) === 'present')->count();
                $onBreak = collect($employees)->filter(fn($e) => $this->getEmployeeStatus($e['id']) === 'break')->count();
                $left = collect($employees)->filter(fn($e) => $this->getEmployeeStatus($e['id']) === 'left')->count();
                $absent = count($employees) - $present - $onBreak - $left;
            @endphp
            <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $present }}</div>
                <div class="text-sm text-green-700 dark:text-green-300">Présents</div>
            </div>
            <div class="bg-yellow-50 dark:bg-yellow-900/30 rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $onBreak }}</div>
                <div class="text-sm text-yellow-700 dark:text-yellow-300">En pause</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-gray-600 dark:text-gray-400">{{ $left }}</div>
                <div class="text-sm text-gray-700 dark:text-gray-300">Partis</div>
            </div>
            <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-4 text-center">
                <div class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $absent }}</div>
                <div class="text-sm text-red-700 dark:text-red-300">Absents</div>
            </div>
        </div>

        <!-- Employee Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($employees as $employee)
                @php
                    $status = $this->getEmployeeStatus($employee['id']);
                    $attendance = $todayAttendances[$employee['id']] ?? null;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden
                    @if($status === 'present') ring-2 ring-green-500 @endif
                    @if($status === 'break') ring-2 ring-yellow-500 @endif
                    @if($status === 'left') ring-2 ring-gray-400 @endif">
                    
                    <!-- Employee Header -->
                    <div class="p-4 border-b dark:border-gray-700 flex items-center gap-3">
                        <div class="relative">
                            @if($employee['photo'])
                                <img src="{{ Storage::url($employee['photo']) }}" 
                                     alt="{{ $employee['name'] }}" 
                                     class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                    <span class="text-primary-700 dark:text-primary-300 font-bold text-lg">
                                        {{ strtoupper(substr($employee['name'], 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                            <!-- Status indicator -->
                            <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white dark:border-gray-800
                                @if($status === 'present') bg-green-500
                                @elseif($status === 'break') bg-yellow-500
                                @elseif($status === 'left') bg-gray-400
                                @else bg-red-500 @endif">
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-gray-900 dark:text-white truncate">
                                {{ $employee['name'] }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ $employee['position'] ?? 'Non défini' }}
                            </div>
                        </div>
                    </div>

                    <!-- Time Info -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50">
                        @if($attendance)
                            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Entrée:</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">
                                        {{ $attendance['clock_in'] ?? '-' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Sortie:</span>
                                    <span class="font-medium text-red-600 dark:text-red-400">
                                        {{ $attendance['clock_out'] ?? '-' }}
                                    </span>
                                </div>
                                @if($attendance['break_start'])
                                <div class="col-span-2">
                                    <span class="text-gray-500 dark:text-gray-400">Pause:</span>
                                    <span class="font-medium text-yellow-600 dark:text-yellow-400">
                                        {{ $attendance['break_start'] }} - {{ $attendance['break_end'] ?? '...' }}
                                    </span>
                                </div>
                                @endif
                                @if($attendance['hours_worked'])
                                <div class="col-span-2">
                                    <span class="text-gray-500 dark:text-gray-400">Total:</span>
                                    <span class="font-bold text-primary-600 dark:text-primary-400">
                                        {{ number_format($attendance['hours_worked'], 2) }}h
                                    </span>
                                </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center text-gray-500 dark:text-gray-400 mb-3 py-2">
                                Pas encore pointé
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex gap-2">
                            @if(!$attendance || !$attendance['clock_in'])
                                <x-filament::button wire:click="clockIn({{ $employee['id'] }})" 
                                    color="success" size="sm" class="flex-1">
                                    <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4 mr-1"/>
                                    Entrée
                                </x-filament::button>
                            @elseif(!$attendance['clock_out'])
                                @if(!$attendance['break_start'])
                                    <x-filament::button wire:click="startBreak({{ $employee['id'] }})" 
                                        color="warning" size="sm" class="flex-1">
                                        <x-heroicon-o-pause class="w-4 h-4 mr-1"/>
                                        Pause
                                    </x-filament::button>
                                @elseif(!$attendance['break_end'])
                                    <x-filament::button wire:click="endBreak({{ $employee['id'] }})" 
                                        color="info" size="sm" class="flex-1">
                                        <x-heroicon-o-play class="w-4 h-4 mr-1"/>
                                        Fin pause
                                    </x-filament::button>
                                @endif
                                <x-filament::button wire:click="clockOut({{ $employee['id'] }})" 
                                    color="danger" size="sm" class="flex-1">
                                    <x-heroicon-o-arrow-left-on-rectangle class="w-4 h-4 mr-1"/>
                                    Sortie
                                </x-filament::button>
                            @else
                                <div class="text-center text-gray-500 dark:text-gray-400 text-sm w-full py-1">
                                    ✓ Journée terminée
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(count($employees) === 0)
            <div class="text-center py-12">
                <x-heroicon-o-users class="w-16 h-16 mx-auto text-gray-400"/>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">Aucun employé</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                    Ajoutez des employés pour commencer à utiliser le pointage.
                </p>
            </div>
        @endif
    </div>
</div>

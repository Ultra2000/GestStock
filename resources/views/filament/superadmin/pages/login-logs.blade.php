<x-filament-panels::page>

    {{-- Stats --}}
    @php $stats = $this->getStats(); @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <p class="text-2xl font-bold text-primary-600">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Total connexions</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['today'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Aujourd'hui</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['this_week'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Cette semaine</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 text-center">
            <p class="text-2xl font-bold text-amber-600">{{ $stats['unique'] }}</p>
            <p class="text-xs text-gray-500 mt-1">Utilisateurs distincts</p>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="flex flex-wrap gap-3 mb-4 items-center">
        <input
            wire:model.live.debounce.300ms="search"
            type="text"
            placeholder="Rechercher (nom, email, IP…)"
            class="flex-1 min-w-[200px] rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
        />

        <select
            wire:model.live="panelFilter"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
        >
            <option value="all">Tous les panels</option>
            <option value="admin">Admin</option>
            <option value="superadmin">Superadmin</option>
        </select>

        <select
            wire:model.live="limit"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
        >
            <option value="50">50 entrées</option>
            <option value="100">100 entrées</option>
            <option value="250">250 entrées</option>
            <option value="500">500 entrées</option>
        </select>
    </div>

    {{-- Table --}}
    @php $logs = $this->getLogs(); @endphp

    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        @if($logs->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <x-heroicon-o-arrow-right-end-on-rectangle class="w-12 h-12 mx-auto mb-3 opacity-40" />
                <p class="text-sm">Aucune connexion enregistrée.</p>
                <p class="text-xs mt-1">Les connexions seront tracées à partir de maintenant.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & heure</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Utilisateur</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Entreprise</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Adresse IP</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Panel</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Navigateur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($logs as $log)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-mono text-xs text-gray-700 dark:text-gray-300">
                                    {{ $log->logged_in_at->format('d/m/Y') }}
                                </span>
                                <span class="font-mono text-xs font-semibold text-primary-600 ml-1">
                                    {{ $log->logged_in_at->format('H:i:s') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $log->name }}</p>
                                <p class="text-xs text-gray-400">{{ $log->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                                {{ $log->company_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($log->panel === 'superadmin')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        Superadmin
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                        Admin
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400 max-w-xs truncate" title="{{ $log->user_agent }}">
                                {{ $log->user_agent ? \Illuminate\Support\Str::limit($log->user_agent, 60) : '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400 text-right">
                {{ $logs->count() }} entrée(s) affichée(s)
            </div>
        @endif
    </div>

</x-filament-panels::page>

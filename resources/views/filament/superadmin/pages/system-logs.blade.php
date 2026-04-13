<x-filament-panels::page>
    @php
        $logs  = $this->getParsedLogs();
        $stats = $this->getLogStats();
        $info  = $this->getLogFileInfo();

        $levelColors = [
            'error'     => ['bg' => 'bg-red-50',     'border' => 'border-red-400',   'badge' => 'bg-red-100 text-red-700',     'dot' => 'bg-red-500',    'label' => 'ERREUR'],
            'warning'   => ['bg' => 'bg-amber-50',   'border' => 'border-amber-400', 'badge' => 'bg-amber-100 text-amber-700', 'dot' => 'bg-amber-500',  'label' => 'AVERT.'],
            'info'      => ['bg' => 'bg-blue-50',    'border' => 'border-blue-300',  'badge' => 'bg-blue-100 text-blue-700',   'dot' => 'bg-blue-500',   'label' => 'INFO'],
            'debug'     => ['bg' => 'bg-gray-50',    'border' => 'border-gray-300',  'badge' => 'bg-gray-100 text-gray-600',   'dot' => 'bg-gray-400',   'label' => 'DEBUG'],
            'critical'  => ['bg' => 'bg-purple-50',  'border' => 'border-purple-500','badge' => 'bg-purple-100 text-purple-700','dot' => 'bg-purple-600', 'label' => 'CRITIQUE'],
            'emergency' => ['bg' => 'bg-red-100',    'border' => 'border-red-600',   'badge' => 'bg-red-200 text-red-800',     'dot' => 'bg-red-700',    'label' => 'URGENCE'],
        ];

        $defaultColor = ['bg' => 'bg-gray-50', 'border' => 'border-gray-300', 'badge' => 'bg-gray-100 text-gray-600', 'dot' => 'bg-gray-400', 'label' => strtoupper($this->levelFilter)];
    @endphp

    {{-- Stats cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach(['error' => ['label' => 'Erreurs', 'icon' => '🔴'], 'warning' => ['label' => 'Avertissements', 'icon' => '🟡'], 'info' => ['label' => 'Infos', 'icon' => '🔵'], 'debug' => ['label' => 'Debug', 'icon' => '⚪']] as $lvl => $meta)
        <button
            wire:click="$set('levelFilter', '{{ $lvl }}')"
            class="text-left rounded-xl border-2 p-4 transition-all hover:shadow-md focus:outline-none
                {{ $this->levelFilter === $lvl ? 'border-gray-800 shadow-md' : 'border-gray-200' }}
                {{ $lvl === 'error' ? 'bg-red-50' : ($lvl === 'warning' ? 'bg-amber-50' : ($lvl === 'info' ? 'bg-blue-50' : 'bg-gray-50')) }}"
        >
            <div class="text-2xl mb-1">{{ $meta['icon'] }}</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($stats[$lvl] ?? 0) }}</div>
            <div class="text-sm text-gray-500 mt-1">{{ $meta['label'] }}</div>
        </button>
        @endforeach
    </div>

    {{-- Filters bar --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-4 flex flex-wrap gap-3 items-center">
        {{-- Level buttons --}}
        <div class="flex gap-2 flex-wrap">
            @foreach(['all' => 'Tous', 'error' => 'Erreurs', 'warning' => 'Avertissements', 'info' => 'Infos', 'debug' => 'Debug'] as $lvl => $label)
            <button
                wire:click="$set('levelFilter', '{{ $lvl }}')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium border transition-all
                    {{ $this->levelFilter === $lvl
                        ? 'bg-gray-800 text-white border-gray-800'
                        : 'bg-white text-gray-600 border-gray-300 hover:border-gray-400' }}"
            >{{ $label }}</button>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="flex-1 min-w-48">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Rechercher dans les logs..."
                class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:ring-2 focus:ring-gray-400 focus:border-transparent dark:bg-gray-800 dark:border-gray-600 dark:text-white"
            />
        </div>

        {{-- Limit --}}
        <select
            wire:model.live="limit"
            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
        >
            <option value="50">50 entrées</option>
            <option value="100">100 entrées</option>
            <option value="200">200 entrées</option>
            <option value="500">500 entrées</option>
        </select>

        {{-- File info --}}
        @if($info['exists'])
        <span class="text-xs text-gray-400 ml-auto">
            Fichier : {{ number_format($info['size'] / 1024, 1) }} Ko
            — Modifié il y a {{ \Carbon\Carbon::createFromTimestamp($info['modified'])->diffForHumans() }}
        </span>
        @else
        <span class="text-xs text-gray-400 ml-auto">Aucun fichier de log trouvé</span>
        @endif
    </div>

    {{-- Log entries --}}
    @if(count($logs) === 0)
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
        <div class="text-4xl mb-3">✅</div>
        <div class="text-lg font-semibold text-gray-700 dark:text-gray-300">Aucune entrée trouvée</div>
        <div class="text-sm text-gray-400 mt-1">
            @if($this->levelFilter !== 'all') Aucun log de niveau « {{ $this->levelFilter }} » dans les 500 Ko récents.
            @else Aucun log dans les 500 Ko récents.
            @endif
        </div>
    </div>
    @else
    <div class="space-y-2" x-data="{ open: null }">
        @foreach($logs as $entry)
        @php
            $c = $levelColors[$entry['level']] ?? $defaultColor;
        @endphp

        <div
            class="rounded-xl border-l-4 {{ $c['bg'] }} {{ $c['border'] }} shadow-sm overflow-hidden"
            x-data="{ expanded: false }"
        >
            {{-- Entry header (always visible) --}}
            <div
                class="flex items-start gap-3 p-4 cursor-pointer select-none"
                @click="expanded = !expanded"
            >
                {{-- Level dot --}}
                <div class="mt-1 flex-shrink-0">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-bold {{ $c['badge'] }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $c['dot'] }} inline-block"></span>
                        {{ $c['label'] }}
                    </span>
                </div>

                {{-- Message --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 leading-snug break-words">
                        {{ Str::limit($entry['message'], 200) }}
                    </p>
                    <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-1.5 text-xs text-gray-500">
                        <span>🕐 {{ \Carbon\Carbon::parse($entry['datetime'])->format('d/m/Y H:i:s') }}</span>
                        <span class="text-gray-400">{{ \Carbon\Carbon::parse($entry['datetime'])->diffForHumans() }}</span>
                        @if($entry['url'])
                        <span>🌐 <span class="font-mono">{{ Str::limit($entry['url'], 80) }}</span></span>
                        @endif
                        @if($entry['user_id'])
                        <span>👤 Utilisateur #{{ $entry['user_id'] }}</span>
                        @endif
                    </div>
                </div>

                {{-- Expand icon --}}
                <div class="flex-shrink-0 text-gray-400" x-show="!expanded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                <div class="flex-shrink-0 text-gray-400" x-show="expanded" x-cloak>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                </div>
            </div>

            {{-- Expanded details --}}
            <div x-show="expanded" x-cloak x-transition class="border-t border-gray-200 dark:border-gray-700">
                {{-- Full message if truncated --}}
                @if(strlen($entry['message']) > 200)
                <div class="px-4 pt-3">
                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Message complet</div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 break-words bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-100 dark:border-gray-700 font-mono leading-relaxed">{{ $entry['message'] }}</p>
                </div>
                @endif

                {{-- Context info --}}
                @if(!empty($entry['context']))
                <div class="px-4 pt-3">
                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Contexte</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($entry['context'] as $key => $value)
                        @if(!in_array($key, ['exception', 'trace', 'file', 'line']) && !is_array($value))
                        <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-100 dark:border-gray-700 text-xs">
                            <span class="text-gray-400 font-medium">{{ $key }}</span>
                            <span class="ml-2 text-gray-700 dark:text-gray-300 font-mono break-all">{{ $value }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Exception details from context --}}
                @if(!empty($entry['context']['exception']) || !empty($entry['context']['file']))
                <div class="px-4 pt-3">
                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Localisation</div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-gray-100 dark:border-gray-700 text-xs font-mono text-gray-600 dark:text-gray-400">
                        @if(!empty($entry['context']['file']))
                        📄 {{ str_replace(base_path(), '', $entry['context']['file']) }}
                        @if(!empty($entry['context']['line'])) <span class="text-gray-400">ligne {{ $entry['context']['line'] }}</span>@endif
                        @endif
                    </div>
                </div>
                @endif

                {{-- Stack trace --}}
                @if($entry['stack'])
                <div class="px-4 pt-3 pb-4">
                    <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Stack trace</div>
                    <div class="bg-gray-900 rounded-lg p-3 overflow-x-auto max-h-64 overflow-y-auto">
                        <pre class="text-xs text-green-300 font-mono whitespace-pre-wrap leading-relaxed">{{ trim($entry['stack']) }}</pre>
                    </div>
                </div>
                @endif

                @if(!$entry['stack'] && empty($entry['context']))
                <div class="px-4 pb-4 pt-2">
                    <p class="text-xs text-gray-400 italic">Aucun détail supplémentaire disponible pour cette entrée.</p>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 text-center text-xs text-gray-400">
        {{ count($logs) }} entrée(s) affichée(s) — triées de la plus récente à la plus ancienne — 500 Ko max du fichier log
    </div>
    @endif

    {{-- Auto-refresh with Livewire poll --}}
    <div wire:poll.30s></div>

</x-filament-panels::page>

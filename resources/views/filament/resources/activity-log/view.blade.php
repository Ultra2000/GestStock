<div class="space-y-4">
    <!-- En-tête -->
    <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                    Détails de l'activité
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Le {{ $record->created_at->format('d/m/Y à H:i:s') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Informations générales -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Utilisateur</label>
            <p class="text-sm text-gray-900 dark:text-white mt-1">
                {{ $record->causer?->name ?? 'Système' }}
            </p>
        </div>

        <div>
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Action</label>
            <p class="text-sm text-gray-900 dark:text-white mt-1">
                @if($record->event === 'created')
                    <span class="text-green-600 dark:text-green-400">Création</span>
                @elseif($record->event === 'updated')
                    <span class="text-blue-600 dark:text-blue-400">Modification</span>
                @elseif($record->event === 'deleted')
                    <span class="text-red-600 dark:text-red-400">Suppression</span>
                @else
                    {{ ucfirst($record->event) }}
                @endif
            </p>
        </div>

        <div>
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Entité concernée</label>
            <p class="text-sm text-gray-900 dark:text-white mt-1">
                {{ class_basename($record->subject_type ?? '-') }} #{{ $record->subject_id }}
            </p>
        </div>

        <div>
            <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Description</label>
            <p class="text-sm text-gray-900 dark:text-white mt-1">
                {{ $record->description }}
            </p>
        </div>
    </div>

    <!-- Modifications détaillées -->
    @if($record->event === 'updated' && !empty($record->properties['attributes']) && !empty($record->properties['old']))
    <div class="mt-4">
        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2 block">Modifications effectuées</label>
        
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 space-y-3">
            @foreach($record->properties['attributes'] as $key => $newValue)
                @if(isset($record->properties['old'][$key]) && $record->properties['old'][$key] != $newValue)
                <div class="border-l-4 border-blue-500 pl-3">
                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        {{ ucfirst(str_replace('_', ' ', $key)) }}
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Avant :</span>
                            <span class="text-red-600 dark:text-red-400 font-mono">
                                {{ is_array($record->properties['old'][$key]) ? json_encode($record->properties['old'][$key]) : $record->properties['old'][$key] }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Après :</span>
                            <span class="text-green-600 dark:text-green-400 font-mono">
                                {{ is_array($newValue) ? json_encode($newValue) : $newValue }}
                            </span>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @elseif($record->event === 'created' && !empty($record->properties['attributes']))
    <div class="mt-4">
        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2 block">Données créées</label>
        
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <pre class="text-xs text-gray-800 dark:text-gray-200 overflow-x-auto">{{ json_encode($record->properties['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @elseif($record->event === 'deleted' && !empty($record->properties['old']))
    <div class="mt-4">
        <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2 block">Données supprimées</label>
        
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
            <pre class="text-xs text-gray-800 dark:text-gray-200 overflow-x-auto">{{ json_encode($record->properties['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
    @endif

    <!-- Métadonnées techniques -->
    <details class="mt-4">
        <summary class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase cursor-pointer">
            Informations techniques
        </summary>
        <div class="mt-2 bg-gray-50 dark:bg-gray-900 rounded-lg p-3 text-xs space-y-1">
            <div><strong>ID :</strong> {{ $record->id }}</div>
            <div><strong>Log Name :</strong> {{ $record->log_name }}</div>
            @if($record->batch_uuid)
            <div><strong>Batch UUID :</strong> {{ $record->batch_uuid }}</div>
            @endif
            <div><strong>Created at :</strong> {{ $record->created_at->toIso8601String() }}</div>
        </div>
    </details>
</div>

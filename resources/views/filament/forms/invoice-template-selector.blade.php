<div x-data="{ selected: $wire.$entangle('{{ $getStatePath() }}') }" x-init="if (!selected) { selected = 'corporate' }" class="space-y-4">
    @php
        $templates = [
            'minimal' => [
                'name' => 'Minimal',
                'desc' => 'Design épuré et moderne. Lignes fines, typographie légère, beaucoup d\'espace blanc.',
                'colors' => ['#f8fafc', '#4f46e5', '#0f172a'],
                'icon' => 'heroicon-o-minus',
            ],
            'corporate' => [
                'name' => 'Corporate',
                'desc' => 'Professionnel avec en-tête sombre, cartes structurées et conditions générales. Idéal pour les entreprises.',
                'colors' => ['#1e293b', '#3b82f6', '#f8fafc'],
                'icon' => 'heroicon-o-building-office',
            ],
            'creative' => [
                'name' => 'Créatif',
                'desc' => 'Coloré avec accents violet/indigo, articles en cartes et totaux dans un encart vibrant.',
                'colors' => ['#4f46e5', '#c026d3', '#f5f3ff'],
                'icon' => 'heroicon-o-paint-brush',
            ],
            'dark' => [
                'name' => 'Sombre',
                'desc' => 'Thème sombre avec typographie monospace, style tech/développeur. Idéal pour les startups.',
                'colors' => ['#0f172a', '#6366f1', '#cbd5e1'],
                'icon' => 'heroicon-o-moon',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($templates as $key => $tpl)
            <button
                type="button"
                x-on:click="selected = '{{ $key }}'"
                :class="{
                    'ring-2 ring-primary-500 border-primary-500': selected === '{{ $key }}',
                    'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600': selected !== '{{ $key }}'
                }"
                class="relative flex flex-col rounded-xl border-2 bg-white dark:bg-gray-900 p-3 text-left transition-all duration-200 cursor-pointer group"
            >
                {{-- Check mark --}}
                <div
                    x-show="selected === '{{ $key }}'"
                    x-transition
                    class="absolute -top-2 -right-2 w-6 h-6 bg-primary-500 text-white rounded-full flex items-center justify-center shadow-lg z-10"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>

                {{-- Template Preview Thumb --}}
                <div class="w-full aspect-[210/297] rounded-lg mb-3 overflow-hidden border border-gray-100 dark:border-gray-800 relative"
                     style="background-color: {{ $key === 'dark' ? '#0f172a' : '#ffffff' }}">
                    
                    @if($key === 'minimal')
                    {{-- Minimal preview --}}
                    <div style="padding: 8px; font-family: sans-serif;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                            <div style="width: 8px; height: 8px; background: #4f46e5; border-radius: 2px;"></div>
                            <div style="font-size: 5px; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px;">Facture</div>
                        </div>
                        <div style="border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; margin: 4px 0; padding: 3px 0;">
                            <div style="display: flex; gap: 4px;">
                                <div style="flex:1; height: 3px; background: #f1f5f9; border-radius: 1px;"></div>
                                <div style="flex:1; height: 3px; background: #f1f5f9; border-radius: 1px;"></div>
                            </div>
                        </div>
                        <div style="margin: 4px 0;">
                            <div style="height: 2px; background: #e2e8f0; margin-bottom: 2px;"></div>
                            <div style="height: 2px; background: #f1f5f9; margin-bottom: 2px;"></div>
                            <div style="height: 2px; background: #e2e8f0; margin-bottom: 2px;"></div>
                        </div>
                        <div style="text-align: right; margin-top: 6px;">
                            <div style="display: inline-block; border-top: 2px solid #0f172a; padding-top: 2px;">
                                <div style="height: 3px; width: 30px; background: #0f172a;"></div>
                            </div>
                        </div>
                    </div>
                    @elseif($key === 'corporate')
                    {{-- Corporate preview --}}
                    <div>
                        <div style="background: #1e293b; padding: 6px 8px; margin-bottom: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="height: 4px; width: 24px; background: #fff; border-radius: 1px;"></div>
                                <div style="height: 6px; width: 16px; background: rgba(255,255,255,0.3); border-radius: 1px;"></div>
                            </div>
                        </div>
                        <div style="padding: 4px 8px;">
                            <div style="display: flex; gap: 4px; margin-bottom: 4px;">
                                <div style="flex:1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 2px; height: 14px; border-top: 2px solid #3b82f6;"></div>
                                <div style="flex:1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 2px; height: 14px; border-top: 2px solid #3b82f6;"></div>
                            </div>
                            <div style="background: #1e293b; height: 3px; margin-bottom: 2px; border-radius: 1px;"></div>
                            <div style="height: 2px; background: #f1f5f9; margin-bottom: 1px;"></div>
                            <div style="height: 2px; background: #f8fafc; margin-bottom: 1px;"></div>
                            <div style="height: 2px; background: #f1f5f9; margin-bottom: 3px;"></div>
                            <div style="text-align: right;">
                                <div style="display: inline-block; background: #1e293b; width: 28px; height: 8px; border-radius: 2px;"></div>
                            </div>
                        </div>
                    </div>
                    @elseif($key === 'creative')
                    {{-- Creative preview --}}
                    <div style="padding: 8px; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: -6px; right: -6px; width: 16px; height: 16px; background: #f5f3ff; border-radius: 50%;"></div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <div style="height: 4px; width: 24px; background: #312e81; border-radius: 1px;"></div>
                            <div style="background: #4f46e5; color: white; font-size: 3px; padding: 1px 4px; border-radius: 6px;">FACTURE</div>
                        </div>
                        <div style="margin: 4px 0;">
                            <div style="background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 3px; height: 8px; margin-bottom: 2px;"></div>
                            <div style="background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 3px; height: 8px; margin-bottom: 2px;"></div>
                            <div style="background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 3px; height: 8px; margin-bottom: 3px;"></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="display: inline-block; background: #4f46e5; width: 28px; height: 12px; border-radius: 4px;"></div>
                        </div>
                    </div>
                    @else
                    {{-- Dark preview --}}
                    <div style="padding: 6px; border: 2px solid rgba(99,102,241,0.25);">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #1e293b; padding-bottom: 4px; margin-bottom: 4px;">
                            <div style="color: #818cf8; font-size: 4px; font-weight: bold; font-style: italic;">&lt;/&gt;</div>
                            <div style="color: #fff; font-size: 5px; font-weight: bold;">INVOICE</div>
                        </div>
                        <div style="margin: 3px 0;">
                            <div style="display: flex; gap: 3px; margin-bottom: 3px;">
                                <div style="flex:1;">
                                    <div style="color: #6366f1; font-size: 3px;">// DEST</div>
                                    <div style="height: 2px; background: #334155; margin-top: 1px;"></div>
                                </div>
                                <div style="flex:1; text-align: right;">
                                    <div style="color: #6366f1; font-size: 3px;">// TERMS</div>
                                    <div style="height: 2px; background: #334155; margin-top: 1px;"></div>
                                </div>
                            </div>
                        </div>
                        <div style="border: 1px solid #1e293b; margin: 3px 0;">
                            <div style="background: rgba(30,41,59,0.5); height: 3px;"></div>
                            <div style="height: 2px; border-bottom: 1px solid #1e293b;"></div>
                            <div style="height: 2px; border-bottom: 1px solid #1e293b;"></div>
                            <div style="height: 2px;"></div>
                        </div>
                        <div style="text-align: right; margin-top: 4px;">
                            <div style="display: inline-block; background: #6366f1; width: 26px; height: 6px;"></div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Label --}}
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors">{{ $tpl['name'] }}</span>
                    {{-- Color dots --}}
                    <div class="flex gap-1 ml-auto">
                        @foreach($tpl['colors'] as $color)
                            <div class="w-3 h-3 rounded-full border border-gray-200 dark:border-gray-600" style="background-color: {{ $color }};"></div>
                        @endforeach
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ $tpl['desc'] }}</p>
            </button>
        @endforeach
    </div>
</div>

<x-filament-panels::page>
    @php
        $companyId = \Filament\Facades\Filament::getTenant()?->id;
    @endphp
    <div x-data="cashRegister({{ $companyId ?? 'null' }})" x-init="init()" class="min-h-screen">
        {{-- Session fermée - Ouverture de caisse --}}
        <template x-if="!sessionOpen">
            <div class="flex items-center justify-center min-h-[70vh]">
                <div class="relative">
                    {{-- Cercles décoratifs --}}
                    <div class="absolute -top-20 -left-20 w-40 h-40 rounded-full blur-3xl" style="background: linear-gradient(to bottom right, rgba(139, 92, 246, 0.2), rgba(168, 85, 247, 0.2));"></div>
                    <div class="absolute -bottom-20 -right-20 w-40 h-40 rounded-full blur-3xl" style="background: linear-gradient(to bottom right, rgba(217, 70, 239, 0.2), rgba(236, 72, 153, 0.2));"></div>
                    
                    <div class="relative rounded-3xl shadow-2xl p-10 max-w-md border" style="background-color: white; border-color: #f3f4f6;">
                        <div class="text-center">
                            {{-- Icône animée --}}
                            <div class="mx-auto w-24 h-24 rounded-2xl flex items-center justify-center mb-6 animate-pulse" style="background: linear-gradient(to bottom right, #8b5cf6, #9333ea); box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            
                            <h2 class="text-2xl font-bold mb-2" style="color: #111827;">Ouvrir la Caisse</h2>
                            <p class="mb-8" style="color: #6b7280;">Entrez le montant de départ pour commencer la journée</p>
                            
                            <div class="relative mb-6">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-xl font-semibold" style="color: #9ca3af;">€</span>
                                </div>
                                <input type="number" 
                                       x-model="openingAmount" 
                                       step="0.01"
                                       class="w-full pl-10 pr-4 py-4 text-2xl font-bold text-center rounded-2xl transition-all"
                                       style="background-color: #f9fafb; border: 2px solid #e5e7eb; color: #111827;"
                                       placeholder="0.00">
                            </div>
                            
                            <button @click="openSession()" 
                                    class="w-full py-4 px-6 font-bold text-lg rounded-2xl transition-all duration-300 transform hover:scale-[1.02]"
                                    style="background: linear-gradient(to right, #7c3aed, #9333ea); color: white; box-shadow: 0 10px 15px -3px rgba(139, 92, 246, 0.3);">
                                <span class="flex items-center justify-center gap-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Démarrer la Session
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Session ouverte - Interface POS --}}
        <template x-if="sessionOpen">
            <div class="space-y-6">
                {{-- Header avec stats --}}
                <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 rounded-3xl p-6 shadow-xl shadow-purple-500/20" style="background: linear-gradient(to right, #7c3aed, #9333ea, #c026d3);">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background: rgba(255,255,255,0.2);">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white">Caisse Enregistreuse</h1>
                                <div class="flex items-center gap-2" style="color: rgba(255,255,255,0.8);">
                                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                    Session active
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-6">
                            {{-- Stats rapides --}}
                            <div class="hidden md:flex items-center gap-6">
                                <div class="text-center px-4 py-2 rounded-xl" style="background: rgba(255,255,255,0.1);">
                                    <div class="text-2xl font-bold text-white" x-text="formatPrice(sessionStats.total_sales)">0€</div>
                                    <div class="text-xs" style="color: rgba(255,255,255,0.7);">Ventes</div>
                                </div>
                                <div class="text-center px-4 py-2 rounded-xl" style="background: rgba(255,255,255,0.1);">
                                    <div class="text-2xl font-bold text-white" x-text="sessionStats.sales_count">0</div>
                                    <div class="text-xs" style="color: rgba(255,255,255,0.7);">Tickets</div>
                                </div>
                                <div class="text-center px-4 py-2 rounded-xl" style="background: rgba(255,255,255,0.1);">
                                    <div class="text-2xl font-bold text-white" x-text="formatPrice(sessionStats.cash_in_drawer)">0€</div>
                                    <div class="text-xs" style="color: rgba(255,255,255,0.7);">En caisse</div>
                                </div>
                            </div>

                            {{-- Bouton plein écran --}}
                            <button @click="toggleFullscreen()" 
                                    class="p-3 rounded-xl transition-all text-white" style="background: rgba(255,255,255,0.2);"
                                    :title="isFullscreen ? 'Quitter plein écran' : 'Mode plein écran'">
                                <svg x-show="!isFullscreen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                                <svg x-show="isFullscreen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
                                </svg>
                            </button>

                            {{-- Bouton son --}}
                            <button @click="toggleSound()" 
                                    class="p-3 rounded-xl transition-all text-white" style="background: rgba(255,255,255,0.2);"
                                    :title="soundEnabled ? 'Désactiver les sons' : 'Activer les sons'">
                                <svg x-show="soundEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                </svg>
                                <svg x-show="!soundEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                                </svg>
                            </button>

                            {{-- Bouton Rapport --}}
                            <button @click="openReportModal()" 
                                    class="p-3 rounded-xl transition-all text-white" style="background: rgba(255,255,255,0.2);"
                                    title="Rapport de caisse">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </button>
                            
                            <button @click="showCloseModal = true" 
                                    class="px-6 py-3 rounded-xl font-semibold transition-all flex items-center gap-2 text-white" style="background: rgba(255,255,255,0.2);">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Grille principale --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    {{-- Colonne gauche - Recherche et produits --}}
                    <div class="xl:col-span-2 space-y-6">
                        {{-- Barre de recherche --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 border border-gray-100 dark:border-gray-700">
                            <div class="flex gap-3">
                                <div class="flex-1 relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input type="text" 
                                           x-model="searchQuery" 
                                           @input.debounce.300ms="searchProducts()"
                                           @keydown.enter="handleBarcodeEnter()"
                                           class="w-full pl-12 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-violet-500 text-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                                           placeholder="Rechercher un produit ou scanner un code-barres...">
                                </div>
                                <button @click="toggleScanner()" 
                                        :class="scannerActive ? 'bg-red-500 hover:bg-red-600' : 'bg-violet-500 hover:bg-violet-600'"
                                        class="px-5 py-3 text-white rounded-xl transition-all flex items-center gap-2 font-medium">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    <span x-text="scannerActive ? 'Stop' : 'Scanner'"></span>
                                </button>
                            </div>
                            
                            {{-- Scanner vidéo --}}
                            <div x-show="scannerActive" x-transition class="mt-4">
                                <div class="relative rounded-xl overflow-hidden bg-black aspect-video max-w-md mx-auto">
                                    <video id="scanner-video" class="w-full h-full object-cover"></video>
                                    <div class="absolute inset-0 border-4 border-violet-500/50 rounded-xl pointer-events-none">
                                        <div class="absolute top-1/2 left-4 right-4 h-0.5 bg-red-500 animate-pulse"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Grille de produits --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    Produits
                                </h3>
                                <span class="text-sm text-gray-500 dark:text-gray-400" x-text="products.length + ' produit(s)'"></span>
                            </div>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 max-h-[50vh] overflow-y-auto pr-2">
                                <template x-for="product in products" :key="product.id">
                                    <button @click="addToCart(product)" 
                                            class="group relative bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl p-4 text-left hover:shadow-lg hover:scale-[1.02] transition-all duration-200 border border-gray-200 dark:border-gray-600 hover:border-violet-300 dark:hover:border-violet-500">
                                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span class="w-8 h-8 bg-violet-500 text-white rounded-full flex items-center justify-center shadow-lg">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center mb-3 shadow-md">
                                            <span class="text-white font-bold text-lg" x-text="product.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm truncate" x-text="product.name"></h4>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-violet-600 dark:text-violet-400 font-bold" x-text="formatPrice(product.selling_price)"></span>
                                            <span class="text-xs px-2 py-1 rounded-full" 
                                                  :class="product.quantity > 10 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : product.quantity > 0 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                                  x-text="product.quantity + ' en stock'"></span>
                                        </div>
                                    </button>
                                </template>
                                
                                <template x-if="products.length === 0">
                                    <div class="col-span-full py-12 text-center">
                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400">Aucun produit trouvé</p>
                                        <p class="text-sm text-gray-400 dark:text-gray-500">Tapez pour rechercher ou scannez un code-barres</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Colonne droite - Panier --}}
                    <div class="space-y-6">
                        {{-- Panier --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 flex flex-col h-[calc(100vh-380px)] min-h-[400px]">
                            {{-- Header panier --}}
                            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <span class="w-8 h-8 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </span>
                                        Panier
                                    </h3>
                                    <span class="px-3 py-1 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-full text-sm font-semibold" x-text="cart.length + ' article(s)'"></span>
                                </div>
                            </div>
                            
                            {{-- Liste des articles --}}
                            <div class="flex-1 overflow-y-auto p-4 space-y-3">
                                <template x-for="(item, index) in cart" :key="index">
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 animate-fade-in">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-medium text-gray-900 dark:text-white truncate" x-text="item.name"></h4>
                                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="formatPrice(item.price) + ' × ' + item.quantity"></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-violet-600 dark:text-violet-400" x-text="formatPrice(item.price * item.quantity)"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between mt-3">
                                            <div class="flex items-center gap-1">
                                                <button @click="decrementItem(index)" class="w-8 h-8 bg-white dark:bg-gray-600 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-500 transition-colors shadow-sm text-gray-700 dark:text-gray-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                    </svg>
                                                </button>
                                                <span class="w-10 text-center font-semibold text-gray-900 dark:text-white" x-text="item.quantity"></span>
                                                <button @click="incrementItem(index)" class="w-8 h-8 bg-white dark:bg-gray-600 rounded-lg flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-500 transition-colors shadow-sm text-gray-700 dark:text-gray-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <button @click="removeItem(index)" class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 p-2 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                
                                <template x-if="cart.length === 0">
                                    <div class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-500 py-8">
                                        <svg class="w-20 h-20 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <p class="font-medium text-gray-500 dark:text-gray-400">Panier vide</p>
                                        <p class="text-sm text-gray-400 dark:text-gray-500">Ajoutez des produits pour commencer</p>
                                    </div>
                                </template>
                            </div>
                            
                            {{-- Footer panier - Total et paiement --}}
                            <div class="border-t border-gray-100 dark:border-gray-700 p-4 space-y-4 bg-gray-50 dark:bg-gray-700/30 rounded-b-2xl">
                                {{-- Méthode de paiement --}}
                                <div>
                                    <label class="text-sm text-gray-500 dark:text-gray-400 mb-2 block">Mode de paiement</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <button @click="paymentMethod = 'cash'" 
                                                :class="paymentMethod === 'cash' ? 'bg-violet-500 text-white border-violet-500' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-500'"
                                                class="p-2 rounded-xl border-2 transition-all text-center">
                                            <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                            <span class="text-xs font-medium">Cash</span>
                                        </button>
                                        <button @click="paymentMethod = 'card'" 
                                                :class="paymentMethod === 'card' ? 'bg-violet-500 text-white border-violet-500' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-500'"
                                                class="p-2 rounded-xl border-2 transition-all text-center">
                                            <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                            </svg>
                                            <span class="text-xs font-medium">Carte</span>
                                        </button>
                                        <button @click="paymentMethod = 'mobile'" 
                                                :class="paymentMethod === 'mobile' ? 'bg-violet-500 text-white border-violet-500' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-500'"
                                                class="p-2 rounded-xl border-2 transition-all text-center">
                                            <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            <span class="text-xs font-medium">Mobile</span>
                                        </button>
                                        <button @click="paymentMethod = 'mixed'" 
                                                :class="paymentMethod === 'mixed' ? 'bg-violet-500 text-white border-violet-500' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-500'"
                                                class="p-2 rounded-xl border-2 transition-all text-center">
                                            <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                            </svg>
                                            <span class="text-xs font-medium">Mixte</span>
                                        </button>
                                    </div>
                                </div>
                                
                                {{-- Total --}}
                                <div class="bg-gradient-to-r from-violet-600 to-purple-600 rounded-xl p-4 text-white">
                                    <div class="flex items-center justify-between">
                                        <span class="text-lg font-medium text-white/80">Total</span>
                                        <span class="text-3xl font-bold" x-text="formatPrice(cartTotal)"></span>
                                    </div>
                                </div>
                                
                                {{-- Boutons d'action --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <button @click="clearCart()" 
                                            :disabled="cart.length === 0"
                                            class="py-3 px-4 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                        Annuler
                                    </button>
                                    <button @click="processSale()" 
                                            :disabled="cart.length === 0 || processing"
                                            class="py-3 px-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-green-500/30">
                                        <svg x-show="!processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg x-show="processing" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span x-text="processing ? 'Traitement...' : 'Encaisser'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Modal fermeture de caisse --}}
        <div x-show="showCloseModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
             @click.self="showCloseModal = false">
            <div x-show="showCloseModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
                
                <div class="bg-gradient-to-r from-violet-600 to-purple-600 p-6 text-white">
                    <h3 class="text-xl font-bold">Fermeture de Caisse</h3>
                    <p class="text-white/80 text-sm">Vérifiez le montant en caisse avant de fermer</p>
                </div>
                
                <div class="p-6 space-y-6">
                    {{-- Résumé de la session --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Montant d'ouverture</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatPrice(sessionStats.opening_amount)"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total ventes</p>
                            <p class="text-xl font-bold text-green-600" x-text="formatPrice(sessionStats.total_sales)"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ventes cash</p>
                            <p class="text-xl font-bold text-violet-600" x-text="formatPrice(sessionStats.cash_sales)"></p>
                        </div>
                        <div class="bg-violet-50 dark:bg-violet-900/30 rounded-xl p-4 text-center">
                            <p class="text-sm text-violet-600 dark:text-violet-400">Attendu en caisse</p>
                            <p class="text-xl font-bold text-violet-600" x-text="formatPrice(sessionStats.cash_in_drawer)"></p>
                        </div>
                    </div>
                    
                    {{-- Montant compté --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Montant compté (€)</label>
                        <input type="number" 
                               x-model="closingAmount" 
                               step="0.01"
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border-2 border-gray-200 dark:border-gray-600 rounded-xl focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 text-xl font-bold text-center text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500"
                               placeholder="0.00">
                        
                        {{-- Différence --}}
                        <div x-show="closingAmount" class="mt-3 p-3 rounded-xl" :class="getDifferenceClass()">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">Différence</span>
                                <span class="font-bold" x-text="formatDifference()"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 pt-0 flex gap-3">
                    <button @click="showCloseModal = false" 
                            class="flex-1 py-3 px-4 rounded-xl font-semibold transition-all"
                            style="background-color: #e5e7eb; color: #374151;">
                        Annuler
                    </button>
                    <button @click="closeSession()" 
                            class="flex-1 py-3 px-4 rounded-xl font-semibold transition-all"
                            style="background: linear-gradient(to right, #ef4444, #e11d48); color: white; box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);">
                        Confirmer la fermeture
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal succès vente --}}
        <div x-show="showSuccessModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
             @click.self="showSuccessModal = false">
            <div x-show="showSuccessModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center">
                
                <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-green-500/30 animate-bounce">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Vente Enregistrée!</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">Transaction effectuée avec succès</p>
                
                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 mb-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Montant encaissé</p>
                    <p class="text-3xl font-bold text-green-600" x-text="formatPrice(lastSaleAmount)"></p>
                </div>
                
                <button @click="showSuccessModal = false" 
                        class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl font-semibold hover:from-violet-700 hover:to-purple-700 transition-all">
                    Continuer
                </button>
            </div>
        </div>

        {{-- Modal Rapport de Caisse --}}
        <div x-show="showReportModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
             @click.self="showReportModal = false"
             @keydown.escape.window="showReportModal = false">
            <div x-show="showReportModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                
                {{-- Header du modal --}}
                <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold">📊 Rapport de Caisse</h2>
                                <p class="text-white/80 text-sm">Session en cours</p>
                            </div>
                        </div>
                        <button @click="showReportModal = false" class="p-2 hover:bg-white/20 rounded-lg transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Contenu du rapport --}}
                <div class="flex-1 overflow-y-auto p-6">
                    {{-- Chargement --}}
                    <template x-if="reportLoading">
                        <div class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-violet-600"></div>
                        </div>
                    </template>

                    <template x-if="!reportLoading && reportData">
                        <div class="space-y-6">
                            {{-- Résumé principal --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-4 border border-green-200 dark:border-green-800">
                                    <div class="text-sm text-green-600 dark:text-green-400 font-medium">Total Ventes</div>
                                    <div class="text-2xl font-bold text-green-700 dark:text-green-300" x-text="formatPrice(reportData.summary?.total_sales)"></div>
                                </div>
                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-4 border border-blue-200 dark:border-blue-800">
                                    <div class="text-sm text-blue-600 dark:text-blue-400 font-medium">Nb. Ventes</div>
                                    <div class="text-2xl font-bold text-blue-700 dark:text-blue-300" x-text="reportData.summary?.sales_count || 0"></div>
                                </div>
                                <div class="bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-2xl p-4 border border-purple-200 dark:border-purple-800">
                                    <div class="text-sm text-purple-600 dark:text-purple-400 font-medium">Panier Moyen</div>
                                    <div class="text-2xl font-bold text-purple-700 dark:text-purple-300" x-text="formatPrice(reportData.summary?.average_sale)"></div>
                                </div>
                                <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-2xl p-4 border border-orange-200 dark:border-orange-800">
                                    <div class="text-sm text-orange-600 dark:text-orange-400 font-medium">En Caisse</div>
                                    <div class="text-2xl font-bold text-orange-700 dark:text-orange-300" x-text="formatPrice(reportData.summary?.cash_in_drawer)"></div>
                                </div>
                            </div>

                            {{-- Détail par mode de paiement --}}
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-5">
                                <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    Détail par Mode de Paiement
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    {{-- Espèces --}}
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">💵</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Espèces</span>
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatPrice(reportData.payment_stats?.cash?.total)"></div>
                                        <div class="text-sm text-gray-500" x-text="(reportData.payment_stats?.cash?.count || 0) + ' ventes'"></div>
                                        <div class="mt-2 bg-green-100 dark:bg-green-900/30 h-2 rounded-full overflow-hidden">
                                            <div class="bg-green-500 h-full rounded-full" :style="'width:' + (reportData.payment_stats?.cash?.percentage || 0) + '%'"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1" x-text="(reportData.payment_stats?.cash?.percentage || 0).toFixed(1) + '%'"></div>
                                    </div>
                                    {{-- Carte --}}
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">💳</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Carte</span>
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatPrice(reportData.payment_stats?.card?.total)"></div>
                                        <div class="text-sm text-gray-500" x-text="(reportData.payment_stats?.card?.count || 0) + ' ventes'"></div>
                                        <div class="mt-2 bg-blue-100 dark:bg-blue-900/30 h-2 rounded-full overflow-hidden">
                                            <div class="bg-blue-500 h-full rounded-full" :style="'width:' + (reportData.payment_stats?.card?.percentage || 0) + '%'"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1" x-text="(reportData.payment_stats?.card?.percentage || 0).toFixed(1) + '%'"></div>
                                    </div>
                                    {{-- Mobile --}}
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">📱</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Mobile</span>
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatPrice(reportData.payment_stats?.mobile?.total)"></div>
                                        <div class="text-sm text-gray-500" x-text="(reportData.payment_stats?.mobile?.count || 0) + ' ventes'"></div>
                                        <div class="mt-2 bg-purple-100 dark:bg-purple-900/30 h-2 rounded-full overflow-hidden">
                                            <div class="bg-purple-500 h-full rounded-full" :style="'width:' + (reportData.payment_stats?.mobile?.percentage || 0) + '%'"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1" x-text="(reportData.payment_stats?.mobile?.percentage || 0).toFixed(1) + '%'"></div>
                                    </div>
                                    {{-- Mixte --}}
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">🔀</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Mixte</span>
                                        </div>
                                        <div class="text-xl font-bold text-gray-900 dark:text-white" x-text="formatPrice(reportData.payment_stats?.mixed?.total)"></div>
                                        <div class="text-sm text-gray-500" x-text="(reportData.payment_stats?.mixed?.count || 0) + ' ventes'"></div>
                                        <div class="mt-2 bg-orange-100 dark:bg-orange-900/30 h-2 rounded-full overflow-hidden">
                                            <div class="bg-orange-500 h-full rounded-full" :style="'width:' + (reportData.payment_stats?.mixed?.percentage || 0) + '%'"></div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1" x-text="(reportData.payment_stats?.mixed?.percentage || 0).toFixed(1) + '%'"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Top Produits --}}
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-5">
                                <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    Top 10 Produits
                                </h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-left text-sm text-gray-500 dark:text-gray-400">
                                                <th class="pb-3 font-medium">#</th>
                                                <th class="pb-3 font-medium">Produit</th>
                                                <th class="pb-3 font-medium text-center">Qté</th>
                                                <th class="pb-3 font-medium text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                            <template x-for="(product, index) in (reportData.top_products || [])" :key="index">
                                                <tr class="text-gray-700 dark:text-gray-300">
                                                    <td class="py-2">
                                                        <span class="w-6 h-6 flex items-center justify-center bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-full text-xs font-bold" x-text="index + 1"></span>
                                                    </td>
                                                    <td class="py-2 font-medium" x-text="product.name"></td>
                                                    <td class="py-2 text-center" x-text="product.quantity"></td>
                                                    <td class="py-2 text-right font-semibold" x-text="formatPrice(product.total)"></td>
                                                </tr>
                                            </template>
                                            <template x-if="!reportData.top_products || reportData.top_products.length === 0">
                                                <tr>
                                                    <td colspan="4" class="py-6 text-center text-gray-500 dark:text-gray-400">Aucune vente enregistrée</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Liste des ventes --}}
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl p-5">
                                <h3 class="font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    Détail des Ventes
                                </h3>
                                <div class="overflow-x-auto max-h-60">
                                    <table class="w-full text-sm">
                                        <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700">
                                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                                <th class="pb-2 font-medium">Heure</th>
                                                <th class="pb-2 font-medium">N° Ticket</th>
                                                <th class="pb-2 font-medium text-center">Articles</th>
                                                <th class="pb-2 font-medium">Paiement</th>
                                                <th class="pb-2 font-medium text-right">Montant</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                            <template x-for="sale in (reportData.sales || [])" :key="sale.id">
                                                <tr class="text-gray-700 dark:text-gray-300">
                                                    <td class="py-2" x-text="formatDate(sale.created_at).split(' ')[1]"></td>
                                                    <td class="py-2 font-mono text-xs" x-text="'#' + sale.id"></td>
                                                    <td class="py-2 text-center" x-text="sale.items_count"></td>
                                                    <td class="py-2">
                                                        <span class="px-2 py-1 rounded-full text-xs font-medium"
                                                              :class="{
                                                                  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': sale.payment_method === 'cash',
                                                                  'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': sale.payment_method === 'card',
                                                                  'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': sale.payment_method === 'mobile',
                                                                  'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': sale.payment_method === 'mixed'
                                                              }"
                                                              x-text="sale.payment_method === 'cash' ? '💵 Espèces' : sale.payment_method === 'card' ? '💳 Carte' : sale.payment_method === 'mobile' ? '📱 Mobile' : '🔀 Mixte'">
                                                        </span>
                                                    </td>
                                                    <td class="py-2 text-right font-semibold" x-text="formatPrice(sale.total)"></td>
                                                </tr>
                                            </template>
                                            <template x-if="!reportData.sales || reportData.sales.length === 0">
                                                <tr>
                                                    <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400">Aucune vente</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer avec boutons d'export --}}
                <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <span x-show="reportData?.session">Session ouverte le <span x-text="formatDate(reportData?.session?.opened_at)"></span></span>
                        </div>
                        <div class="flex gap-3">
                            <button @click="downloadExcel()" 
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium flex items-center gap-2 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export Excel
                            </button>
                            <button @click="downloadPdf()" 
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium flex items-center gap-2 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }

        /* Mode plein écran / kiosque */
        :fullscreen {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
        }
        
        :fullscreen .fi-topbar,
        :fullscreen .fi-sidebar,
        :fullscreen .fi-sidebar-nav,
        :fullscreen header.fi-header,
        :fullscreen nav[aria-label="Breadcrumbs"] {
            display: none !important;
        }
        
        :fullscreen .fi-main {
            padding: 0 !important;
            max-width: 100% !important;
        }
        
        :fullscreen .fi-page {
            padding: 1rem !important;
        }

        /* Styles dark mode plein écran */
        .dark:fullscreen {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        /* Animation pour les boutons son/fullscreen */
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        
        .sound-pulse::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: rgba(255,255,255,0.3);
            animation: pulse-ring 0.5s ease-out;
        }
    </style>

    @push('scripts')
    <script src="https://unpkg.com/@aspect-build/aspect-ratio"></script>
    <script src="https://unpkg.com/@aspect-build/aspect-ratio"></script>
    @endpush

    <script>
        function cashRegister(companyId) {
            return {
                // Company ID pour les requêtes API
                companyId: companyId,
                
                // État de la session
                sessionOpen: false,
                openingAmount: '',
                closingAmount: '',
                sessionStats: {
                    opening_amount: 0,
                    total_sales: 0,
                    sales_count: 0,
                    cash_sales: 0,
                    card_sales: 0,
                    mobile_sales: 0,
                    cash_in_drawer: 0
                },
                
                // Headers communs pour les requêtes
                getHeaders() {
                    const headers = {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    };
                    if (this.companyId) {
                        headers['X-Company-Id'] = this.companyId;
                    }
                    return headers;
                },
                
                // Recherche et produits
                searchQuery: '',
                products: [],
                
                // Panier
                cart: [],
                paymentMethod: 'cash',
                processing: false,
                
                // Scanner
                scannerActive: false,
                codeReader: null,
                
                // Modals
                showCloseModal: false,
                showSuccessModal: false,
                showReportModal: false,
                lastSaleAmount: 0,

                // Données du rapport
                reportData: null,
                reportLoading: false,
                sessionHistory: [],

                // Mode plein écran & sons
                isFullscreen: false,
                soundEnabled: true,
                audioContext: null,
                
                // Initialisation
                async init() {
                    await this.checkSession();
                    await this.loadProducts();
                    this.initAudio();
                    this.checkFullscreen();
                    
                    // Écouter les changements de plein écran
                    document.addEventListener('fullscreenchange', () => this.checkFullscreen());
                    document.addEventListener('webkitfullscreenchange', () => this.checkFullscreen());
                    
                    // Charger les préférences sauvegardées
                    this.soundEnabled = localStorage.getItem('pos_sound') !== 'false';
                },

                // Initialiser le contexte audio
                initAudio() {
                    try {
                        this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    } catch (e) {
                        console.warn('Audio non disponible');
                    }
                },

                // Jouer un son de bip (scan)
                playBeep(frequency = 800, duration = 100, type = 'sine') {
                    if (!this.soundEnabled || !this.audioContext) return;
                    
                    try {
                        const oscillator = this.audioContext.createOscillator();
                        const gainNode = this.audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(this.audioContext.destination);
                        
                        oscillator.frequency.value = frequency;
                        oscillator.type = type;
                        
                        gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + duration / 1000);
                        
                        oscillator.start(this.audioContext.currentTime);
                        oscillator.stop(this.audioContext.currentTime + duration / 1000);
                    } catch (e) {
                        console.warn('Erreur audio:', e);
                    }
                },

                // Son de succès (double bip aigu)
                playSuccess() {
                    this.playBeep(880, 100);
                    setTimeout(() => this.playBeep(1100, 150), 120);
                },

                // Son d'erreur (bip grave)
                playError() {
                    this.playBeep(300, 300, 'square');
                },

                // Son d'ajout au panier
                playAddToCart() {
                    this.playBeep(600, 80);
                },

                // Son de scan
                playScan() {
                    this.playBeep(1000, 50);
                    setTimeout(() => this.playBeep(1200, 50), 60);
                },

                // Toggle son
                toggleSound() {
                    this.soundEnabled = !this.soundEnabled;
                    localStorage.setItem('pos_sound', this.soundEnabled);
                    if (this.soundEnabled) {
                        this.playBeep(800, 100);
                    }
                },

                // Vérifier l'état plein écran
                checkFullscreen() {
                    this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement);
                },

                // Toggle plein écran
                async toggleFullscreen() {
                    try {
                        if (!this.isFullscreen) {
                            const elem = document.documentElement;
                            if (elem.requestFullscreen) {
                                await elem.requestFullscreen();
                            } else if (elem.webkitRequestFullscreen) {
                                await elem.webkitRequestFullscreen();
                            }
                        } else {
                            if (document.exitFullscreen) {
                                await document.exitFullscreen();
                            } else if (document.webkitExitFullscreen) {
                                await document.webkitExitFullscreen();
                            }
                        }
                    } catch (e) {
                        console.warn('Plein écran non disponible:', e);
                    }
                },
                
                // Vérifier si une session est ouverte
                async checkSession() {
                    try {
                        const response = await fetch('/api/pos/session/check', {
                            headers: this.getHeaders()
                        });
                        const data = await response.json();
                        this.sessionOpen = data.open;
                        if (data.open && data.session) {
                            this.sessionStats = data.session;
                        }
                    } catch (error) {
                        console.error('Erreur vérification session:', error);
                    }
                },
                
                // Ouvrir la session
                async openSession() {
                    try {
                        const response = await fetch('/api/pos/session/open', {
                            method: 'POST',
                            headers: this.getHeaders(),
                            body: JSON.stringify({
                                opening_amount: parseFloat(this.openingAmount) || 0
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.playSuccess();
                            this.sessionOpen = true;
                            this.sessionStats = data.session;
                        } else {
                            this.playError();
                        }
                    } catch (error) {
                        this.playError();
                        console.error('Erreur ouverture session:', error);
                    }
                },
                
                // Fermer la session
                async closeSession() {
                    try {
                        const response = await fetch('/api/pos/session/close', {
                            method: 'POST',
                            headers: this.getHeaders(),
                            body: JSON.stringify({
                                closing_amount: parseFloat(this.closingAmount) || 0
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.playSuccess();
                            this.sessionOpen = false;
                            this.showCloseModal = false;
                            this.openingAmount = '';
                            this.closingAmount = '';
                            this.cart = [];
                        } else {
                            this.playError();
                        }
                    } catch (error) {
                        this.playError();
                        console.error('Erreur fermeture session:', error);
                    }
                },
                
                // Charger les produits
                async loadProducts() {
                    try {
                        const response = await fetch('/api/pos/products', {
                            headers: this.getHeaders()
                        });
                        this.products = await response.json();
                    } catch (error) {
                        console.error('Erreur chargement produits:', error);
                    }
                },
                
                // Rechercher des produits
                async searchProducts() {
                    if (this.searchQuery.length < 1) {
                        await this.loadProducts();
                        return;
                    }
                    try {
                        const response = await fetch(`/api/pos/products/search?q=${encodeURIComponent(this.searchQuery)}`, {
                            headers: this.getHeaders()
                        });
                        this.products = await response.json();
                    } catch (error) {
                        console.error('Erreur recherche:', error);
                    }
                },
                
                // Gérer l'entrée code-barres
                async handleBarcodeEnter() {
                    if (this.searchQuery.length > 5) {
                        try {
                            const response = await fetch(`/api/pos/products/barcode/${encodeURIComponent(this.searchQuery)}`, {
                                headers: this.getHeaders()
                            });
                            const product = await response.json();
                            if (product && product.id) {
                                this.playScan();
                                this.addToCart(product);
                                this.searchQuery = '';
                            } else {
                                this.playError();
                            }
                        } catch (error) {
                            this.playError();
                            console.error('Erreur code-barres:', error);
                        }
                    }
                },
                
                // Toggle scanner
                async toggleScanner() {
                    if (this.scannerActive) {
                        this.stopScanner();
                    } else {
                        await this.startScanner();
                    }
                },
                
                // Démarrer le scanner
                async startScanner() {
                    try {
                        const { BrowserMultiFormatReader } = await import('https://unpkg.com/@aspect-build/aspect-ratio/dist/esm/index.js');
                        this.codeReader = new BrowserMultiFormatReader();
                        const videoElement = document.getElementById('scanner-video');
                        
                        this.codeReader.decodeFromVideoDevice(null, videoElement, async (result, error) => {
                            if (result) {
                                const barcode = result.getText();
                                this.searchQuery = barcode;
                                await this.handleBarcodeEnter();
                            }
                        });
                        
                        this.scannerActive = true;
                    } catch (error) {
                        console.error('Erreur scanner:', error);
                        // Fallback si ZXing n'est pas disponible
                        alert('Scanner non disponible. Utilisez la saisie manuelle.');
                    }
                },
                
                // Arrêter le scanner
                stopScanner() {
                    if (this.codeReader) {
                        this.codeReader.reset();
                    }
                    this.scannerActive = false;
                },
                
                // Ajouter au panier
                addToCart(product) {
                    const existingIndex = this.cart.findIndex(item => item.id === product.id);
                    if (existingIndex >= 0) {
                        if (this.cart[existingIndex].quantity < product.quantity) {
                            this.cart[existingIndex].quantity++;
                            this.playAddToCart();
                        } else {
                            this.playError();
                        }
                    } else {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            price: parseFloat(product.selling_price),
                            quantity: 1,
                            max_quantity: product.quantity
                        });
                        this.playAddToCart();
                    }
                },
                
                // Incrémenter quantité
                incrementItem(index) {
                    if (this.cart[index].quantity < this.cart[index].max_quantity) {
                        this.cart[index].quantity++;
                    }
                },
                
                // Décrémenter quantité
                decrementItem(index) {
                    if (this.cart[index].quantity > 1) {
                        this.cart[index].quantity--;
                    } else {
                        this.removeItem(index);
                    }
                },
                
                // Supprimer du panier
                removeItem(index) {
                    this.cart.splice(index, 1);
                },
                
                // Vider le panier
                clearCart() {
                    this.cart = [];
                },
                
                // Total du panier
                get cartTotal() {
                    return this.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
                },
                
                // Enregistrer la vente
                async processSale() {
                    if (this.cart.length === 0 || this.processing) return;
                    
                    this.processing = true;
                    try {
                        const response = await fetch('/api/pos/sale', {
                            method: 'POST',
                            headers: this.getHeaders(),
                            body: JSON.stringify({
                                items: this.cart.map(item => ({
                                    product_id: item.id,
                                    quantity: item.quantity,
                                    price: item.price
                                })),
                                payment_method: this.paymentMethod,
                                total: this.cartTotal
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.playSuccess();
                            this.lastSaleAmount = this.cartTotal;
                            this.showSuccessModal = true;
                            this.cart = [];
                            this.sessionStats = data.session;
                            await this.loadProducts(); // Rafraîchir les stocks
                        } else {
                            this.playError();
                        }
                    } catch (error) {
                        this.playError();
                        console.error('Erreur enregistrement vente:', error);
                    } finally {
                        this.processing = false;
                    }
                },
                
                // Formater le prix
                formatPrice(amount) {
                    return new Intl.NumberFormat('fr-FR', {
                        style: 'currency',
                        currency: 'EUR'
                    }).format(amount || 0);
                },
                
                // Calculer la différence
                getDifference() {
                    return parseFloat(this.closingAmount || 0) - this.sessionStats.cash_in_drawer;
                },
                
                // Classe CSS pour la différence
                getDifferenceClass() {
                    const diff = this.getDifference();
                    if (Math.abs(diff) < 0.01) return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400';
                    return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400';
                },
                
                // Formater la différence
                formatDifference() {
                    const diff = this.getDifference();
                    const formatted = this.formatPrice(Math.abs(diff));
                    if (Math.abs(diff) < 0.01) return '✓ Correct';
                    return diff > 0 ? `+${formatted} (excédent)` : `-${formatted} (manquant)`;
                },

                // === RAPPORT DE CAISSE ===
                
                // Ouvrir le modal du rapport
                async openReportModal() {
                    this.showReportModal = true;
                    await this.loadReport();
                },

                // Charger les données du rapport
                async loadReport() {
                    this.reportLoading = true;
                    try {
                        const response = await fetch('/api/pos/report', {
                            headers: this.getHeaders()
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.reportData = data;
                        }
                    } catch (error) {
                        console.error('Erreur chargement rapport:', error);
                    } finally {
                        this.reportLoading = false;
                    }
                },

                // Charger l'historique des sessions
                async loadSessionHistory() {
                    try {
                        const response = await fetch('/api/pos/report/history', {
                            headers: this.getHeaders()
                        });
                        this.sessionHistory = await response.json();
                    } catch (error) {
                        console.error('Erreur chargement historique:', error);
                    }
                },

                // Télécharger le PDF
                downloadPdf(sessionId = null) {
                    const id = sessionId || (this.reportData?.session?.id);
                    if (id) {
                        window.open(`/api/pos/report/${id}/pdf`, '_blank');
                    }
                },

                // Télécharger Excel/CSV
                downloadExcel(sessionId = null) {
                    const id = sessionId || (this.reportData?.session?.id);
                    if (id) {
                        window.open(`/api/pos/report/${id}/excel`, '_blank');
                    }
                },

                // Formater une date
                formatDate(dateString) {
                    if (!dateString) return '-';
                    return new Date(dateString).toLocaleString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },

                // Formater durée
                formatDuration(minutes) {
                    if (!minutes) return '-';
                    const hours = Math.floor(minutes / 60);
                    const mins = minutes % 60;
                    return hours > 0 ? `${hours}h ${mins}min` : `${mins}min`;
                }
            };
        }
    </script>
</x-filament-panels::page>

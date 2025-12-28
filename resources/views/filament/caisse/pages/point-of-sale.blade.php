<x-filament-panels::page>
    <div 
        x-data="pointOfSale()" 
        x-init="init()"
        class="h-[calc(100vh-180px)] flex flex-col"
        @keydown.f2.window="focusSearch()"
        @keydown.f3.window="focusBarcode()"
        @keydown.f12.window="submitSale()"
        @keydown.escape.window="clearCart()"
    >
        {{-- Barre d'état session --}}
        <template x-if="!hasSession">
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="font-medium text-amber-800">Aucune session de caisse ouverte</span>
                </div>
                <a href="{{ route('filament.caisse.pages.cash-session-page', ['tenant' => \Filament\Facades\Filament::getTenant()]) }}" 
                   class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
                    Ouvrir une session
                </a>
            </div>
        </template>

        {{-- Interface principale --}}
        <div class="flex-1 grid grid-cols-12 gap-4 min-h-0">
            {{-- Panneau gauche: Produits --}}
            <div class="col-span-7 flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden">
                {{-- Barre de recherche --}}
                <div class="p-4 border-b bg-gray-50">
                    <div class="flex gap-3">
                        <div class="flex-1 relative">
                            <input 
                                x-ref="searchInput"
                                x-model="searchQuery"
                                @input.debounce.300ms="searchProducts()"
                                type="text" 
                                placeholder="🔍 Rechercher un produit (F2)..."
                                class="w-full pl-4 pr-10 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-lg"
                            >
                            <button @click="searchQuery = ''; products = []" x-show="searchQuery" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                ✕
                            </button>
                        </div>
                        <div class="relative">
                            <input 
                                x-ref="barcodeInput"
                                @keydown.enter.prevent="scanBarcode($event.target.value); $event.target.value = ''"
                                type="text"
                                placeholder="Code-barres (F3)"
                                class="w-48 px-4 py-3 border border-emerald-300 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-emerald-50 text-lg"
                            >
                        </div>
                        <button @click="openCameraScanner()" class="px-4 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Scanner
                        </button>
                    </div>
                </div>

                {{-- Grille de produits --}}
                <div class="flex-1 overflow-y-auto p-4">
                    <template x-if="loading">
                        <div class="flex items-center justify-center h-full">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
                        </div>
                    </template>
                    
                    <template x-if="!loading && products.length === 0 && searchQuery">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-lg">Aucun produit trouvé</p>
                        </div>
                    </template>

                    <template x-if="!loading && products.length === 0 && !searchQuery">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <p class="text-lg">Recherchez un produit ou scannez un code-barres</p>
                            <p class="text-sm mt-2">Raccourcis: F2 (recherche), F3 (code-barres), F12 (encaisser)</p>
                        </div>
                    </template>

                    <div class="grid grid-cols-3 gap-3" x-show="products.length > 0">
                        <template x-for="product in products" :key="product.id">
                            <button 
                                @click="addToCart(product)"
                                class="p-4 bg-gradient-to-br from-gray-50 to-white border-2 rounded-xl hover:border-emerald-400 hover:shadow-md transition-all text-left group"
                                :class="{'border-red-300 bg-red-50': product.stock <= (product.min_stock || 0)}"
                            >
                                <div class="font-semibold text-gray-800 truncate group-hover:text-emerald-700" x-text="product.name"></div>
                                <div class="text-xs text-gray-500 mt-1" x-text="product.code || 'Sans code'"></div>
                                <div class="flex justify-between items-end mt-3">
                                    <div class="text-lg font-bold text-emerald-600" x-text="formatPrice(product.price)"></div>
                                    <div class="text-xs px-2 py-1 rounded-full" 
                                         :class="product.stock <= (product.min_stock || 0) ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'"
                                         x-text="'Stock: ' + product.stock">
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Panneau droit: Panier --}}
            <div class="col-span-5 flex flex-col bg-white rounded-xl shadow-sm border overflow-hidden">
                {{-- En-tête panier --}}
                <div class="p-4 border-b bg-gradient-to-r from-emerald-600 to-emerald-700 text-white">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Panier
                        </h2>
                        <button @click="clearCart()" x-show="cart.length > 0" class="text-white/80 hover:text-white text-sm">
                            Vider (Esc)
                        </button>
                    </div>
                    <div class="text-emerald-100 text-sm mt-1" x-text="cart.length + ' article(s)'"></div>
                </div>

                {{-- Liste articles --}}
                <div class="flex-1 overflow-y-auto">
                    <template x-if="cart.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 p-8">
                            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <p>Panier vide</p>
                        </div>
                    </template>

                    <ul class="divide-y">
                        <template x-for="(item, index) in cart" :key="item.id">
                            <li class="p-4 hover:bg-gray-50">
                                <div class="flex justify-between">
                                    <div class="flex-1">
                                        <div class="font-medium" x-text="item.name"></div>
                                        <div class="text-sm text-gray-500" x-text="formatPrice(item.unit_price) + ' × ' + item.quantity"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold text-emerald-600" x-text="formatPrice(item.total)"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <button @click="decrementQty(index)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center">−</button>
                                    <input type="number" x-model.number="item.quantity" @change="updateItemTotal(index)" min="1" class="w-16 text-center border rounded py-1">
                                    <button @click="incrementQty(index)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center">+</button>
                                    <button @click="removeFromCart(index)" class="ml-auto text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Résumé et paiement --}}
                <div class="border-t bg-gray-50 p-4 space-y-3">
                    {{-- Sous-total, remise, TVA --}}
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Sous-total</span>
                            <span x-text="formatPrice(subtotal)"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Remise (%)</span>
                            <input type="number" x-model.number="discount" min="0" max="100" step="0.5" class="w-20 text-right border rounded px-2 py-1 text-sm">
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">TVA (%)</span>
                            <input type="number" x-model.number="tax" min="0" max="100" step="0.5" class="w-20 text-right border rounded px-2 py-1 text-sm">
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="flex justify-between items-center py-3 border-t border-b">
                        <span class="text-xl font-bold">TOTAL</span>
                        <span class="text-2xl font-bold text-emerald-600" x-text="formatPrice(grandTotal)"></span>
                    </div>

                    {{-- Mode de paiement --}}
                    <div class="grid grid-cols-4 gap-2">
                        <button @click="paymentMethod = 'cash'" 
                                class="py-2 px-3 rounded-lg border-2 transition text-sm font-medium"
                                :class="paymentMethod === 'cash' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'">
                            💵 Espèces
                        </button>
                        <button @click="paymentMethod = 'card'" 
                                class="py-2 px-3 rounded-lg border-2 transition text-sm font-medium"
                                :class="paymentMethod === 'card' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'">
                            💳 Carte
                        </button>
                        <button @click="paymentMethod = 'mobile'" 
                                class="py-2 px-3 rounded-lg border-2 transition text-sm font-medium"
                                :class="paymentMethod === 'mobile' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'">
                            📱 Mobile
                        </button>
                        <button @click="paymentMethod = 'mixed'" 
                                class="py-2 px-3 rounded-lg border-2 transition text-sm font-medium"
                                :class="paymentMethod === 'mixed' ? 'border-emerald-500 bg-emerald-50 text-emerald-700' : 'border-gray-200 hover:border-gray-300'">
                            🔀 Mixte
                        </button>
                    </div>

                    {{-- Montant reçu (espèces) --}}
                    <template x-if="paymentMethod === 'cash'">
                        <div class="flex gap-3 items-center">
                            <div class="flex-1">
                                <label class="text-xs text-gray-500">Montant reçu</label>
                                <input type="number" x-model.number="receivedAmount" min="0" class="w-full border rounded px-3 py-2 text-lg">
                            </div>
                            <div class="flex-1">
                                <label class="text-xs text-gray-500">Monnaie à rendre</label>
                                <div class="text-2xl font-bold" :class="change >= 0 ? 'text-emerald-600' : 'text-red-600'" x-text="formatPrice(Math.max(0, change))"></div>
                            </div>
                        </div>
                    </template>

                    {{-- Bouton encaisser --}}
                    <button 
                        @click="submitSale()"
                        :disabled="cart.length === 0 || saving || !hasSession"
                        class="w-full py-4 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white text-xl font-bold rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3"
                    >
                        <template x-if="!saving">
                            <span>
                                <svg class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                ENCAISSER (F12)
                            </span>
                        </template>
                        <template x-if="saving">
                            <span>
                                <svg class="animate-spin w-6 h-6 inline mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Traitement...
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Scanner Caméra --}}
        <template x-if="showCameraModal">
            <div class="fixed inset-0 bg-black/70 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                    <div class="bg-emerald-600 text-white p-4 flex justify-between items-center">
                        <h3 class="text-lg font-bold">Scanner code-barres</h3>
                        <button @click="closeCameraScanner()" class="hover:bg-emerald-700 rounded-full p-1">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-4">
                        <div class="aspect-video bg-black rounded-lg overflow-hidden relative">
                            <video x-ref="cameraVideo" class="w-full h-full object-cover" playsinline></video>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div class="w-64 h-32 border-2 border-emerald-400 rounded-lg"></div>
                            </div>
                        </div>
                        <div class="mt-4 text-center text-sm text-gray-500" x-text="lastScannedCode ? 'Dernier code: ' + lastScannedCode : 'Placez le code-barres dans le cadre'"></div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Notification succès --}}
        <template x-if="showSuccess">
            <div class="fixed top-4 right-4 bg-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg z-50 animate-bounce">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <div class="font-bold">Vente enregistrée !</div>
                        <div class="text-sm" x-text="'Ticket #' + lastSaleId"></div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function pointOfSale() {
            return {
                // État
                searchQuery: '',
                products: [],
                cart: [],
                loading: false,
                saving: false,
                hasSession: false,
                
                // Paiement
                discount: 0,
                tax: 0,
                paymentMethod: 'cash',
                receivedAmount: 0,
                
                // Scanner
                showCameraModal: false,
                lastScannedCode: '',
                codeReader: null,
                
                // Feedback
                showSuccess: false,
                lastSaleId: null,

                init() {
                    this.checkSession();
                    this.focusSearch();
                },

                async checkSession() {
                    const response = await this.$wire.hasOpenSession();
                    this.hasSession = response;
                },

                formatPrice(value) {
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(value) + ' FCFA';
                },

                focusSearch() {
                    this.$refs.searchInput?.focus();
                },

                focusBarcode() {
                    this.$refs.barcodeInput?.focus();
                },

                async searchProducts() {
                    if (!this.searchQuery.trim()) {
                        this.products = [];
                        return;
                    }
                    this.loading = true;
                    try {
                        this.products = await this.$wire.searchProducts(this.searchQuery);
                    } finally {
                        this.loading = false;
                    }
                },

                async scanBarcode(code) {
                    if (!code.trim()) return;
                    const product = await this.$wire.getProductByBarcode(code.trim());
                    if (product) {
                        this.addToCart(product);
                        this.playBeep();
                    } else {
                        alert('Produit non trouvé: ' + code);
                    }
                },

                addToCart(product) {
                    const existing = this.cart.find(i => i.id === product.id);
                    if (existing) {
                        if (existing.quantity < product.stock) {
                            existing.quantity++;
                            existing.total = existing.quantity * existing.unit_price;
                        } else {
                            alert('Stock insuffisant');
                        }
                    } else {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            unit_price: parseFloat(product.price),
                            quantity: 1,
                            total: parseFloat(product.price),
                            stock: product.stock
                        });
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                incrementQty(index) {
                    const item = this.cart[index];
                    if (item.quantity < item.stock) {
                        item.quantity++;
                        this.updateItemTotal(index);
                    }
                },

                decrementQty(index) {
                    const item = this.cart[index];
                    if (item.quantity > 1) {
                        item.quantity--;
                        this.updateItemTotal(index);
                    }
                },

                updateItemTotal(index) {
                    const item = this.cart[index];
                    item.total = item.quantity * item.unit_price;
                },

                clearCart() {
                    this.cart = [];
                    this.discount = 0;
                    this.tax = 0;
                    this.receivedAmount = 0;
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + item.total, 0);
                },

                get grandTotal() {
                    const discountAmt = this.subtotal * (this.discount / 100);
                    const afterDiscount = this.subtotal - discountAmt;
                    const taxAmt = afterDiscount * (this.tax / 100);
                    return Math.round(afterDiscount + taxAmt);
                },

                get change() {
                    return this.receivedAmount - this.grandTotal;
                },

                async submitSale() {
                    if (this.cart.length === 0 || !this.hasSession) return;
                    
                    this.saving = true;
                    try {
                        const result = await this.$wire.recordSale({
                            items: this.cart.map(i => ({
                                product_id: i.id,
                                quantity: i.quantity,
                                unit_price: i.unit_price
                            })),
                            discount_percent: this.discount,
                            tax_percent: this.tax,
                            payment_method: this.paymentMethod,
                            payment_details: this.paymentMethod === 'mixed' ? { cash: this.receivedAmount } : null
                        });

                        if (result.success) {
                            this.lastSaleId = result.invoice_number;
                            this.showSuccess = true;
                            setTimeout(() => this.showSuccess = false, 3000);
                            this.clearCart();
                            this.playBeep();
                        } else {
                            alert(result.message || 'Erreur lors de la vente');
                        }
                    } finally {
                        this.saving = false;
                    }
                },

                // Scanner caméra
                async openCameraScanner() {
                    this.showCameraModal = true;
                    await this.$nextTick();
                    this.startCamera();
                },

                closeCameraScanner() {
                    this.stopCamera();
                    this.showCameraModal = false;
                },

                async startCamera() {
                    if (!window.ZXingBrowser) {
                        await this.loadZxing();
                    }
                    
                    const video = this.$refs.cameraVideo;
                    this.codeReader = new window.ZXingBrowser.BrowserMultiFormatReader();
                    
                    try {
                        const devices = await window.ZXingBrowser.BrowserMultiFormatReader.listVideoInputDevices();
                        const backCamera = devices.find(d => /back|rear|environment/i.test(d.label)) || devices[0];
                        
                        if (backCamera) {
                            this.codeReader.decodeFromVideoDevice(backCamera.deviceId, video, (result, err) => {
                                if (result) {
                                    this.lastScannedCode = result.getText();
                                    this.scanBarcode(this.lastScannedCode);
                                    this.closeCameraScanner();
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Camera error:', e);
                    }
                },

                stopCamera() {
                    if (this.codeReader) {
                        this.codeReader.reset();
                    }
                    const video = this.$refs.cameraVideo;
                    if (video?.srcObject) {
                        video.srcObject.getTracks().forEach(t => t.stop());
                    }
                },

                loadZxing() {
                    return new Promise((resolve, reject) => {
                        if (window.ZXingBrowser) return resolve();
                        const script = document.createElement('script');
                        script.src = 'https://unpkg.com/@aspect1/browser@0.1.4/umd/index.min.js';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                },

                playBeep() {
                    if (navigator.vibrate) navigator.vibrate(50);
                    // Audio beep could be added here
                }
            };
        }
    </script>
</x-filament-panels::page>

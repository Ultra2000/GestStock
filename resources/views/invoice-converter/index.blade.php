<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convertisseur Factur-X gratuit — Convertir PDF en facture électronique | FRECORP</title>
    <meta name="description" content="Convertissez gratuitement vos factures PDF, images ou Excel en Factur-X conforme EN16931. Extraction automatique par IA. 5 conversions/mois gratuites.">
    <meta name="keywords" content="facturation électronique, factur-x, convertisseur facture, PDF facture, EN16931, CII, facture conforme 2026">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/convertir-facture') }}">

    <!-- Open Graph -->
    <meta property="og:title" content="Convertisseur Factur-X gratuit — FRECORP">
    <meta property="og:description" content="Convertissez vos factures PDF en Factur-X EN16931 conforme. Gratuit, rapide, par IA.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/convertir-facture') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Tailwind CDN (pour page publique standalone) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwindcss.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eef2ff', 100: '#e0e7ff', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' }
                    }
                }
            }
        }
    </script>
    <style>
        .drop-zone.drag-over { border-color: #6366f1; background: #eef2ff; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse-border { 0%, 100% { border-color: #6366f1; } 50% { border-color: #a5b4fc; } }
        .processing { animation: pulse-border 1.5s ease-in-out infinite; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">F</span>
                </div>
                <span class="font-bold text-gray-900 text-lg">FRECORP</span>
            </a>
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline text-sm text-gray-500">
                    {{ $remaining }}/{{ $limit }} conversions restantes ce mois
                </span>
                <a href="/admin/login" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-lg transition-colors">
                    Se connecter
                </a>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="bg-gradient-to-br from-brand-600 via-brand-700 to-purple-800 text-white py-16 sm:py-20">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight mb-4">
                Convertissez vos factures en <span class="text-brand-100">Factur-X</span>
            </h1>
            <p class="text-lg sm:text-xl text-brand-100 max-w-2xl mx-auto mb-2">
                PDF, image ou Excel → facture électronique conforme EN16931
            </p>
            <p class="text-sm text-brand-200">
                Extraction automatique par intelligence artificielle • {{ $limit }} conversions/mois gratuites
            </p>
        </div>
    </section>

    {{-- Main content --}}
    <main class="max-w-4xl mx-auto px-4 -mt-8 pb-16">

        {{-- Upload card --}}
        <div id="upload-section" class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8 mb-8">
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-1">1. Importez votre facture</h2>
                <p class="text-sm text-gray-500">Glissez un fichier ou cliquez pour parcourir</p>
            </div>

            {{-- Drop zone --}}
            <div id="drop-zone" class="drop-zone border-2 border-dashed border-gray-300 rounded-xl p-12 text-center cursor-pointer hover:border-brand-400 transition-all">
                <svg class="mx-auto w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <p class="text-gray-600 mb-1">Glissez votre fichier ici</p>
                <p class="text-sm text-gray-400">ou <span class="text-brand-600 font-semibold hover:underline">choisissez un fichier</span></p>
                <p class="mt-3 text-xs text-gray-400">PDF · JPEG · PNG · WebP · Excel · CSV — Max 10 Mo</p>
                <input type="file" id="file-input" accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.csv" class="hidden" />
            </div>

            {{-- File info --}}
            <div id="file-info" class="hidden mt-4 flex items-center justify-between bg-gray-50 rounded-xl p-4 fade-in">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-brand-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <p id="file-name" class="text-sm font-medium text-gray-900"></p>
                        <p id="file-size" class="text-xs text-gray-500"></p>
                    </div>
                </div>
                <button id="remove-file" class="text-gray-400 hover:text-red-500 transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Extract button --}}
            <div id="extract-btn-wrapper" class="hidden mt-6 text-center fade-in">
                <button id="extract-btn" class="inline-flex items-center px-8 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg shadow-brand-200 transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg id="extract-icon" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <svg id="extract-spinner" class="hidden w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span id="extract-text">Extraire les données par IA</span>
                </button>
            </div>

            {{-- Error message --}}
            <div id="error-msg" class="hidden mt-4 bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 fade-in"></div>

            {{-- Upgrade CTA --}}
            <div id="upgrade-cta" class="hidden mt-4 bg-brand-50 border border-brand-200 rounded-xl p-5 text-center fade-in">
                <p class="text-sm text-brand-800 mb-3">Limite atteinte ce mois-ci. Inscrivez-vous pour des conversions illimitées !</p>
                <a href="/admin/register" class="inline-flex items-center px-5 py-2 bg-brand-600 text-white font-semibold rounded-lg hover:bg-brand-700 transition-colors text-sm">
                    Créer un compte gratuit →
                </a>
            </div>
        </div>

        {{-- Preview section (hidden initially) --}}
        <div id="preview-section" class="hidden space-y-6 fade-in">
            {{-- AI result badge --}}
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">2. Vérifiez et corrigez</h2>
                <div class="flex items-center gap-2">
                    <span id="ai-badge" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"></span>
                    <span id="time-badge" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700"></span>
                </div>
            </div>

            {{-- Seller / Buyer --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Émetteur</h3>
                    <div class="space-y-2">
                        <input type="text" id="s-name" placeholder="Raison sociale" class="w-full rounded-lg border-gray-300 text-sm" />
                        <input type="text" id="s-address" placeholder="Adresse" class="w-full rounded-lg border-gray-300 text-sm" />
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="s-zip" placeholder="Code postal" class="rounded-lg border-gray-300 text-sm" />
                            <input type="text" id="s-city" placeholder="Ville" class="rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="s-siret" placeholder="SIRET" class="rounded-lg border-gray-300 text-sm" />
                            <input type="text" id="s-vat" placeholder="N° TVA" class="rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Destinataire</h3>
                    <div class="space-y-2">
                        <input type="text" id="b-name" placeholder="Raison sociale" class="w-full rounded-lg border-gray-300 text-sm" />
                        <input type="text" id="b-address" placeholder="Adresse" class="w-full rounded-lg border-gray-300 text-sm" />
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="b-zip" placeholder="Code postal" class="rounded-lg border-gray-300 text-sm" />
                            <input type="text" id="b-city" placeholder="Ville" class="rounded-lg border-gray-300 text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="b-siret" placeholder="SIRET" class="rounded-lg border-gray-300 text-sm" />
                            <input type="text" id="b-vat" placeholder="N° TVA" class="rounded-lg border-gray-300 text-sm" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Invoice meta --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Facture</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <input type="text" id="inv-number" placeholder="N° facture" class="rounded-lg border-gray-300 text-sm" />
                    <input type="date" id="inv-date" class="rounded-lg border-gray-300 text-sm" />
                    <input type="date" id="inv-due" class="rounded-lg border-gray-300 text-sm" placeholder="Échéance" />
                    <select id="inv-currency" class="rounded-lg border-gray-300 text-sm">
                        <option value="EUR">EUR (€)</option>
                        <option value="USD">USD ($)</option>
                        <option value="GBP">GBP (£)</option>
                    </select>
                </div>
            </div>

            {{-- Lines --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Lignes</h3>
                    <button id="add-line-btn" class="text-xs font-medium text-brand-600 hover:text-brand-700">+ Ajouter</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="lines-table">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase">
                                <th class="text-left pb-2 pr-2" style="min-width:200px">Description</th>
                                <th class="text-center pb-2 px-2" style="width:70px">Qté</th>
                                <th class="text-right pb-2 px-2" style="width:100px">P.U. HT</th>
                                <th class="text-center pb-2 px-2" style="width:70px">TVA</th>
                                <th class="text-right pb-2 px-2" style="width:100px">Total HT</th>
                                <th class="pb-2" style="width:30px"></th>
                            </tr>
                        </thead>
                        <tbody id="lines-body"></tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <div class="w-64 space-y-1.5 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Total HT</span><span id="total-ht" class="font-semibold">0,00 €</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Total TVA</span><span id="total-vat">0,00 €</span></div>
                        <div class="flex justify-between pt-2 border-t border-gray-200 text-base"><span class="font-bold">Total TTC</span><span id="total-ttc" class="font-bold text-brand-600">0,00 €</span></div>
                    </div>
                </div>
            </div>

            {{-- Generate button --}}
            <div class="flex items-center justify-between">
                <button id="back-btn" class="text-sm text-gray-500 hover:text-gray-700">← Recommencer</button>
                <button id="generate-btn" class="inline-flex items-center px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-lg transition-all text-base disabled:opacity-50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Générer le Factur-X
                </button>
            </div>
        </div>

        {{-- Download section --}}
        <div id="download-section" class="hidden fade-in">
            <div class="bg-white rounded-2xl shadow-lg border border-green-200 p-10 text-center">
                <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Factur-X prêt !</h2>
                <p class="text-gray-500 mb-8">Votre facture est conforme au standard Factur-X EN16931.</p>
                <div class="flex items-center justify-center gap-4 mb-6">
                    <a id="dl-pdf-btn" href="#" class="inline-flex items-center px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-lg transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Télécharger le PDF
                    </a>
                    <a id="dl-xml-btn" href="#" class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-bold rounded-xl transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                        Télécharger le XML
                    </a>
                </div>
                <button id="new-conversion-btn" class="text-sm text-brand-600 hover:underline">← Convertir une autre facture</button>

                {{-- CTA --}}
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <p class="text-sm text-gray-500 mb-3">Besoin de plus ? Gérez vos factures, stocks, paie et comptabilité</p>
                    <a href="/admin/register" class="inline-flex items-center px-5 py-2.5 bg-brand-50 text-brand-700 font-semibold rounded-lg hover:bg-brand-100 transition-colors text-sm border border-brand-200">
                        Essayer FRECORP gratuitement →
                    </a>
                </div>
            </div>
        </div>

        {{-- Features section (SEO) --}}
        <section class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Extraction par IA</h3>
                <p class="text-sm text-gray-500">Notre intelligence artificielle lit vos factures et extrait automatiquement toutes les données : émetteur, client, lignes, TVA.</p>
            </div>
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Conforme EN16931</h3>
                <p class="text-sm text-gray-500">Factur-X généré au profil EN16931, le format standard pour la facturation électronique obligatoire en France dès 2026.</p>
            </div>
            <div class="bg-white rounded-xl p-6 border border-gray-200">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Sécurisé</h3>
                <p class="text-sm text-gray-500">Vos fichiers sont traités en temps réel et n'ont jamais stockés. La suppression est automatique après traitement.</p>
            </div>
        </section>

        {{-- FAQ (SEO) --}}
        <section class="mt-12 bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Questions fréquentes</h2>
            <div class="space-y-4 text-sm">
                <details class="group">
                    <summary class="font-medium text-gray-900 cursor-pointer hover:text-brand-600">Qu'est-ce que le format Factur-X ?</summary>
                    <p class="mt-2 text-gray-600 pl-4">Factur-X est le standard franco-allemand de facturation électronique. C'est un PDF hybride contenant un fichier XML structuré (CII CrossIndustryInvoice). Il sera obligatoire pour toutes les entreprises françaises à partir de 2026.</p>
                </details>
                <details class="group">
                    <summary class="font-medium text-gray-900 cursor-pointer hover:text-brand-600">Combien de conversions gratuites puis-je faire ?</summary>
                    <p class="mt-2 text-gray-600 pl-4">Vous pouvez convertir {{ $limit }} factures par mois gratuitement sans créer de compte. En vous inscrivant à FRECORP, vous obtenez des conversions illimitées et un ERP complet.</p>
                </details>
                <details class="group">
                    <summary class="font-medium text-gray-900 cursor-pointer hover:text-brand-600">Quels formats de fichiers sont acceptés ?</summary>
                    <p class="mt-2 text-gray-600 pl-4">PDF (texte ou scanné), images (JPEG, PNG, WebP), et tableurs (Excel XLSX, CSV). La taille maximum est de 10 Mo.</p>
                </details>
                <details class="group">
                    <summary class="font-medium text-gray-900 cursor-pointer hover:text-brand-600">Mes données sont-elles en sécurité ?</summary>
                    <p class="mt-2 text-gray-600 pl-4">Oui. Les fichiers sont traités en temps réel sur nos serveurs en France et supprimés automatiquement après 24h. Aucune donnée n'est conservée ou partagée.</p>
                </details>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="bg-white border-t border-gray-200 py-6">
        <div class="max-w-5xl mx-auto px-4 flex items-center justify-between text-sm text-gray-500">
            <p>&copy; {{ date('Y') }} FRECORP — ERP français pour TPE/PME</p>
            <a href="https://app.frecorp.fr" class="text-brand-600 hover:underline">app.frecorp.fr</a>
        </div>
    </footer>

    <script>
    (function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let selectedFile = null;
        let conversionId = null;
        let extractedData = null;

        // DOM refs
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const fileInfo = document.getElementById('file-info');
        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');
        const removeFile = document.getElementById('remove-file');
        const extractBtnWrapper = document.getElementById('extract-btn-wrapper');
        const extractBtn = document.getElementById('extract-btn');
        const extractIcon = document.getElementById('extract-icon');
        const extractSpinner = document.getElementById('extract-spinner');
        const extractText = document.getElementById('extract-text');
        const errorMsg = document.getElementById('error-msg');
        const upgradeCta = document.getElementById('upgrade-cta');
        const uploadSection = document.getElementById('upload-section');
        const previewSection = document.getElementById('preview-section');
        const downloadSection = document.getElementById('download-section');

        // Drop zone events
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            if (e.dataTransfer.files.length) selectFile(e.dataTransfer.files[0]);
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) selectFile(e.target.files[0]);
        });

        removeFile.addEventListener('click', clearFile);

        function selectFile(file) {
            if (file.size > 10 * 1024 * 1024) {
                showError('Le fichier ne doit pas dépasser 10 Mo.');
                return;
            }
            selectedFile = file;
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(1) + ' Ko';
            fileInfo.classList.remove('hidden');
            extractBtnWrapper.classList.remove('hidden');
            errorMsg.classList.add('hidden');
            upgradeCta.classList.add('hidden');
        }

        function clearFile() {
            selectedFile = null;
            fileInput.value = '';
            fileInfo.classList.add('hidden');
            extractBtnWrapper.classList.add('hidden');
        }

        function showError(msg) {
            errorMsg.textContent = msg;
            errorMsg.classList.remove('hidden');
        }

        // Extract
        extractBtn.addEventListener('click', async () => {
            if (!selectedFile) return;
            extractBtn.disabled = true;
            extractIcon.classList.add('hidden');
            extractSpinner.classList.remove('hidden');
            extractText.textContent = 'Extraction en cours…';
            errorMsg.classList.add('hidden');

            const formData = new FormData();
            formData.append('file', selectedFile);

            try {
                const resp = await fetch('{{ route("invoice-converter.upload") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    body: formData
                });
                const json = await resp.json();

                if (!resp.ok || !json.success) {
                    showError(json.message || 'Erreur inconnue');
                    if (json.upgrade) upgradeCta.classList.remove('hidden');
                    return;
                }

                conversionId = json.conversion_id;
                extractedData = json.data;

                // Fill preview
                fillPreview(json.data);
                document.getElementById('ai-badge').textContent = 'IA: ' + json.ai_provider;
                document.getElementById('time-badge').textContent = (json.processing_time_ms / 1000).toFixed(1) + 's';

                uploadSection.classList.add('hidden');
                previewSection.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });

            } catch (err) {
                showError('Erreur réseau : ' + err.message);
            } finally {
                extractBtn.disabled = false;
                extractIcon.classList.remove('hidden');
                extractSpinner.classList.add('hidden');
                extractText.textContent = 'Extraire les données par IA';
            }
        });

        function fillPreview(data) {
            const s = data.seller || {};
            const b = data.buyer || {};
            const inv = data.invoice || {};
            const totals = data.totals || {};

            document.getElementById('s-name').value = s.name || '';
            document.getElementById('s-address').value = s.address || '';
            document.getElementById('s-zip').value = s.zip_code || '';
            document.getElementById('s-city').value = s.city || '';
            document.getElementById('s-siret').value = s.siret || '';
            document.getElementById('s-vat').value = s.vat_number || '';

            document.getElementById('b-name').value = b.name || '';
            document.getElementById('b-address').value = b.address || '';
            document.getElementById('b-zip').value = b.zip_code || '';
            document.getElementById('b-city').value = b.city || '';
            document.getElementById('b-siret').value = b.siret || '';
            document.getElementById('b-vat').value = b.vat_number || '';

            document.getElementById('inv-number').value = inv.number || '';
            document.getElementById('inv-date').value = inv.date || '';
            document.getElementById('inv-due').value = inv.due_date || '';
            document.getElementById('inv-currency').value = inv.currency || 'EUR';

            renderLines(data.lines || []);
            updateTotals();
        }

        function renderLines(lines) {
            const tbody = document.getElementById('lines-body');
            tbody.innerHTML = '';
            lines.forEach((line, i) => addLineRow(line, i));
        }

        function addLineRow(line = {}, index = null) {
            const tbody = document.getElementById('lines-body');
            if (index === null) index = tbody.rows.length;
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-100';
            tr.innerHTML = `
                <td class="py-1.5 pr-2"><input type="text" data-field="description" value="${escHtml(line.description || '')}" class="w-full rounded border-gray-300 text-sm py-1.5" /></td>
                <td class="py-1.5 px-2"><input type="number" data-field="quantity" value="${line.quantity || 1}" step="0.01" min="0" class="w-full rounded border-gray-300 text-sm text-center py-1.5 calc-trigger" /></td>
                <td class="py-1.5 px-2"><input type="number" data-field="unit_price_ht" value="${line.unit_price_ht || 0}" step="0.01" min="0" class="w-full rounded border-gray-300 text-sm text-right py-1.5 calc-trigger" /></td>
                <td class="py-1.5 px-2">
                    <select data-field="vat_rate" class="w-full rounded border-gray-300 text-sm text-center py-1.5 calc-trigger">
                        <option value="20" ${(line.vat_rate == 20 || !line.vat_rate) ? 'selected' : ''}>20%</option>
                        <option value="10" ${line.vat_rate == 10 ? 'selected' : ''}>10%</option>
                        <option value="5.5" ${line.vat_rate == 5.5 ? 'selected' : ''}>5,5%</option>
                        <option value="2.1" ${line.vat_rate == 2.1 ? 'selected' : ''}>2,1%</option>
                        <option value="0" ${line.vat_rate == 0 ? 'selected' : ''}>0%</option>
                    </select>
                </td>
                <td class="py-1.5 px-2 text-right font-medium line-total">0,00 €</td>
                <td class="py-1.5 pl-1"><button class="remove-line text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button></td>
            `;
            tbody.appendChild(tr);

            tr.querySelectorAll('.calc-trigger').forEach(el => el.addEventListener('change', updateTotals));
            tr.querySelector('.remove-line').addEventListener('click', () => { tr.remove(); updateTotals(); });

            updateTotals();
        }

        document.getElementById('add-line-btn').addEventListener('click', () => addLineRow());
        document.getElementById('back-btn').addEventListener('click', () => {
            previewSection.classList.add('hidden');
            uploadSection.classList.remove('hidden');
            clearFile();
        });

        document.getElementById('new-conversion-btn').addEventListener('click', () => {
            downloadSection.classList.add('hidden');
            uploadSection.classList.remove('hidden');
            clearFile();
        });

        function updateTotals() {
            let totalHt = 0, totalVat = 0;
            document.querySelectorAll('#lines-body tr').forEach(tr => {
                const qty = parseFloat(tr.querySelector('[data-field="quantity"]')?.value || 0);
                const pu = parseFloat(tr.querySelector('[data-field="unit_price_ht"]')?.value || 0);
                const vat = parseFloat(tr.querySelector('[data-field="vat_rate"]')?.value || 20);
                const lineHt = qty * pu;
                totalHt += lineHt;
                totalVat += lineHt * vat / 100;
                const totalTd = tr.querySelector('.line-total');
                if (totalTd) totalTd.textContent = fmt(lineHt);
            });
            document.getElementById('total-ht').textContent = fmt(totalHt);
            document.getElementById('total-vat').textContent = fmt(totalVat);
            document.getElementById('total-ttc').textContent = fmt(totalHt + totalVat);
        }

        function fmt(n) { return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €'; }
        function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

        function gatherFormData() {
            const lines = [];
            document.querySelectorAll('#lines-body tr').forEach(tr => {
                lines.push({
                    description: tr.querySelector('[data-field="description"]')?.value || '',
                    quantity: parseFloat(tr.querySelector('[data-field="quantity"]')?.value || 1),
                    unit_price_ht: parseFloat(tr.querySelector('[data-field="unit_price_ht"]')?.value || 0),
                    vat_rate: parseFloat(tr.querySelector('[data-field="vat_rate"]')?.value || 20),
                    total_ht: parseFloat(tr.querySelector('[data-field="quantity"]')?.value || 1) * parseFloat(tr.querySelector('[data-field="unit_price_ht"]')?.value || 0),
                });
            });

            return {
                seller: {
                    name: document.getElementById('s-name').value,
                    address: document.getElementById('s-address').value,
                    zip_code: document.getElementById('s-zip').value,
                    city: document.getElementById('s-city').value,
                    country_code: 'FR',
                    siret: document.getElementById('s-siret').value || null,
                    vat_number: document.getElementById('s-vat').value || null,
                },
                buyer: {
                    name: document.getElementById('b-name').value,
                    address: document.getElementById('b-address').value,
                    zip_code: document.getElementById('b-zip').value,
                    city: document.getElementById('b-city').value,
                    country_code: 'FR',
                    siret: document.getElementById('b-siret').value || null,
                    vat_number: document.getElementById('b-vat').value || null,
                },
                invoice: {
                    number: document.getElementById('inv-number').value,
                    date: document.getElementById('inv-date').value,
                    due_date: document.getElementById('inv-due').value || null,
                    currency: document.getElementById('inv-currency').value,
                },
                lines: lines,
                totals: {
                    total_ht: parseFloat(document.getElementById('total-ht').textContent) || 0,
                    total_vat: parseFloat(document.getElementById('total-vat').textContent) || 0,
                    total_ttc: parseFloat(document.getElementById('total-ttc').textContent) || 0,
                },
                vat_breakdown: extractedData?.vat_breakdown || [],
            };
        }

        // Generate
        document.getElementById('generate-btn').addEventListener('click', async () => {
            const btn = document.getElementById('generate-btn');
            btn.disabled = true;
            btn.textContent = 'Génération…';

            try {
                const data = gatherFormData();
                const resp = await fetch('{{ route("invoice-converter.generate") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ conversion_id: conversionId, data: data }),
                });
                const json = await resp.json();

                if (!resp.ok || !json.success) {
                    alert(json.message || 'Erreur');
                    return;
                }

                document.getElementById('dl-pdf-btn').href = json.download_pdf_url;
                document.getElementById('dl-xml-btn').href = json.download_xml_url;

                previewSection.classList.add('hidden');
                downloadSection.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });

            } catch (err) {
                alert('Erreur réseau : ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg> Générer le Factur-X';
            }
        });
    })();
    </script>
</body>
</html>

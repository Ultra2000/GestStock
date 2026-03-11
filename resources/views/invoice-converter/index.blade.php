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

    <!-- External Resources -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .glass-card-hover:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(99, 102, 241, 0.3);
        }
        .floating-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: 0;
            opacity: 0.5;
            pointer-events: none;
        }
        .orb-1 { width: 500px; height: 500px; background: #4338ca; top: -150px; left: -150px; }
        .orb-2 { width: 400px; height: 400px; background: #7c3aed; bottom: 10%; right: -100px; }
        .orb-3 { width: 300px; height: 300px; background: #06b6d4; top: 50%; left: 30%; }
        .gradient-text {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            box-shadow: 0 10px 40px -10px rgba(99, 102, 241, 0.5);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 50px -10px rgba(99, 102, 241, 0.6);
        }
        .nav-modern {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.85) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(99, 102, 241, 0.15);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }
        .drop-zone.drag-over { border-color: #6366f1; background: rgba(99, 102, 241, 0.1); }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse-border { 0%, 100% { border-color: #6366f1; } 50% { border-color: #a5b4fc; } }
        .processing { animation: pulse-border 1.5s ease-in-out infinite; }
        .dark-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            transition: all 0.2s;
        }
        .dark-input:focus {
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            outline: none;
        }
        .dark-input::placeholder { color: #64748b; }
        .btn-login {
            border: 1px solid rgba(148, 163, 184, 0.2);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(99, 102, 241, 0.1);
        }
    </style>
</head>
<body class="bg-[#0f172a] text-slate-200 overflow-x-hidden min-h-screen font-sans">

    <!-- Floating Orbs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="floating-orb orb-1"></div>
        <div class="floating-orb orb-2"></div>
        <div class="floating-orb orb-3"></div>
    </div>

    {{-- Header --}}
    <nav class="fixed top-0 w-full z-50 nav-modern">
        <div class="max-w-5xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="https://frecorp.fr" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 via-purple-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/40">
                    <i class="fas fa-boxes-stacked text-white text-lg"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-black tracking-tight bg-gradient-to-r from-white via-indigo-200 to-indigo-400 bg-clip-text text-transparent">FRECORP</span>
                    <span class="text-[9px] font-medium text-indigo-400/60 tracking-widest uppercase -mt-1 hidden sm:block">Convertisseur</span>
                </div>
            </a>
            <div class="flex items-center gap-4">
                <a href="https://frecorp.fr" class="hidden sm:inline text-sm text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-home mr-1.5 text-xs opacity-70"></i>Accueil
                </a>
                <span class="hidden sm:inline text-sm text-slate-500">
                    <i class="fas fa-bolt mr-1 text-xs text-indigo-400"></i>
                    {{ $remaining }}/{{ $limit }} conversions
                </span>
                <a href="/admin/login" class="btn-login flex items-center gap-2 text-slate-300 font-medium text-sm px-4 py-2 rounded-lg">
                    <i class="fas fa-arrow-right-to-bracket text-xs"></i>
                    <span>Connexion</span>
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="relative pt-28 pb-14 sm:pt-32 sm:pb-16 px-4">
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <div class="inline-flex items-center px-4 py-1.5 bg-cyan-500/20 border border-cyan-400/30 rounded-full text-sm font-medium text-cyan-300 mb-6">
                <i class="fas fa-wand-magic-sparkles mr-2 text-cyan-400"></i>Propulsé par l'Intelligence Artificielle
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight mb-4">
                <span class="text-white">Convertissez vos factures en </span>
                <span class="gradient-text">Factur-X</span>
            </h1>
            <p class="text-lg text-slate-400 max-w-2xl mx-auto mb-2">
                PDF, image ou Excel → facture électronique conforme EN16931
            </p>
            <p class="text-sm text-slate-500">
                <i class="fas fa-shield-halved mr-1 text-emerald-400"></i>
                Extraction automatique par IA • {{ $limit }} conversions/mois gratuites
            </p>
        </div>
    </section>

    {{-- Main content --}}
    <main class="max-w-4xl mx-auto px-4 -mt-4 pb-16 relative z-10">

        {{-- Upload card --}}
        <div id="upload-section" class="glass-card rounded-2xl p-8 mb-8">
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-white mb-1">1. Importez votre facture</h2>
                <p class="text-sm text-slate-400">Glissez un fichier ou cliquez pour parcourir</p>
            </div>

            {{-- Drop zone --}}
            <div id="drop-zone" class="drop-zone border-2 border-dashed border-slate-600 rounded-xl p-12 text-center cursor-pointer hover:border-indigo-400/50 transition-all">
                <div class="w-16 h-16 mx-auto mb-4 bg-indigo-500/20 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-cloud-arrow-up text-3xl text-indigo-400"></i>
                </div>
                <p class="text-slate-300 mb-1">Glissez votre fichier ici</p>
                <p class="text-sm text-slate-500">ou <span class="text-indigo-400 font-semibold hover:underline cursor-pointer">choisissez un fichier</span></p>
                <p class="mt-3 text-xs text-slate-600">PDF · JPEG · PNG · WebP · Excel · CSV — Max 10 Mo</p>
                <input type="file" id="file-input" accept=".pdf,.jpg,.jpeg,.png,.webp,.xlsx,.xls,.csv" class="hidden" />
            </div>

            {{-- File info --}}
            <div id="file-info" class="hidden mt-4 flex items-center justify-between bg-slate-800/50 border border-slate-700/50 rounded-xl p-4 fade-in">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-lines text-indigo-400"></i>
                    </div>
                    <div>
                        <p id="file-name" class="text-sm font-medium text-white"></p>
                        <p id="file-size" class="text-xs text-slate-500"></p>
                    </div>
                </div>
                <button id="remove-file" class="text-slate-500 hover:text-red-400 transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Extract button --}}
            <div id="extract-btn-wrapper" class="hidden mt-6 text-center fade-in">
                <button id="extract-btn" class="btn-primary inline-flex items-center px-8 py-3 text-white font-bold rounded-xl text-base disabled:opacity-50 disabled:cursor-not-allowed">
                    <i id="extract-icon" class="fas fa-bolt mr-2"></i>
                    <svg id="extract-spinner" class="hidden w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span id="extract-text">Extraire les données par IA</span>
                </button>
            </div>

            {{-- Error message --}}
            <div id="error-msg" class="hidden mt-4 bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-sm text-red-300 fade-in"></div>

            {{-- Upgrade CTA --}}
            <div id="upgrade-cta" class="hidden mt-4 bg-indigo-500/10 border border-indigo-500/30 rounded-xl p-5 text-center fade-in">
                <p class="text-sm text-indigo-200 mb-3">Limite atteinte ce mois-ci. Inscrivez-vous pour des conversions illimitées !</p>
                <a href="/admin/register" class="btn-primary inline-flex items-center px-5 py-2 text-white font-semibold rounded-lg text-sm">
                    <i class="fas fa-rocket mr-2 text-xs"></i>Créer un compte gratuit
                </a>
            </div>
        </div>

        {{-- Preview section (hidden initially) --}}
        <div id="preview-section" class="hidden space-y-6 fade-in">
            {{-- AI result badge --}}
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-white">2. Vérifiez et corrigez</h2>
                <div class="flex items-center gap-2">
                    <span id="ai-badge" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-500/20 border border-purple-400/30 text-purple-300"></span>
                    <span id="time-badge" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-700/50 border border-slate-600/50 text-slate-300"></span>
                </div>
            </div>

            {{-- Seller / Buyer --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="glass-card rounded-xl p-5">
                    <h3 class="text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">
                        <i class="fas fa-building mr-1.5"></i>Émetteur
                    </h3>
                    <div class="space-y-2">
                        <input type="text" id="s-name" placeholder="Raison sociale" class="w-full rounded-lg dark-input text-sm px-3 py-2" />
                        <input type="text" id="s-address" placeholder="Adresse" class="w-full rounded-lg dark-input text-sm px-3 py-2" />
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="s-zip" placeholder="Code postal" class="rounded-lg dark-input text-sm px-3 py-2" />
                            <input type="text" id="s-city" placeholder="Ville" class="rounded-lg dark-input text-sm px-3 py-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="s-siret" placeholder="SIRET" class="rounded-lg dark-input text-sm px-3 py-2" />
                            <input type="text" id="s-vat" placeholder="N° TVA" class="rounded-lg dark-input text-sm px-3 py-2" />
                        </div>
                    </div>
                </div>
                <div class="glass-card rounded-xl p-5">
                    <h3 class="text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">
                        <i class="fas fa-user-tie mr-1.5"></i>Destinataire
                    </h3>
                    <div class="space-y-2">
                        <input type="text" id="b-name" placeholder="Raison sociale" class="w-full rounded-lg dark-input text-sm px-3 py-2" />
                        <input type="text" id="b-address" placeholder="Adresse" class="w-full rounded-lg dark-input text-sm px-3 py-2" />
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="b-zip" placeholder="Code postal" class="rounded-lg dark-input text-sm px-3 py-2" />
                            <input type="text" id="b-city" placeholder="Ville" class="rounded-lg dark-input text-sm px-3 py-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" id="b-siret" placeholder="SIRET" class="rounded-lg dark-input text-sm px-3 py-2" />
                            <input type="text" id="b-vat" placeholder="N° TVA" class="rounded-lg dark-input text-sm px-3 py-2" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Invoice meta --}}
            <div class="glass-card rounded-xl p-5">
                <h3 class="text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">
                    <i class="fas fa-file-invoice mr-1.5"></i>Facture
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <input type="text" id="inv-number" placeholder="N° facture" class="rounded-lg dark-input text-sm px-3 py-2" />
                    <input type="date" id="inv-date" class="rounded-lg dark-input text-sm px-3 py-2" />
                    <input type="date" id="inv-due" class="rounded-lg dark-input text-sm px-3 py-2" placeholder="Échéance" />
                    <select id="inv-currency" class="rounded-lg dark-input text-sm px-3 py-2">
                        <option value="EUR">EUR (€)</option>
                        <option value="USD">USD ($)</option>
                        <option value="GBP">GBP (£)</option>
                    </select>
                </div>
            </div>

            {{-- Lines --}}
            <div class="glass-card rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">
                        <i class="fas fa-list mr-1.5"></i>Lignes
                    </h3>
                    <button id="add-line-btn" class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                        <i class="fas fa-plus mr-1"></i>Ajouter
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="lines-table">
                        <thead>
                            <tr class="text-xs text-slate-500 uppercase">
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
                        <div class="flex justify-between"><span class="text-slate-500">Total HT</span><span id="total-ht" class="font-semibold text-slate-200">0,00 €</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Total TVA</span><span id="total-vat" class="text-slate-300">0,00 €</span></div>
                        <div class="flex justify-between pt-2 border-t border-slate-700/50 text-base"><span class="font-bold text-white">Total TTC</span><span id="total-ttc" class="font-bold text-indigo-400">0,00 €</span></div>
                    </div>
                </div>
            </div>

            {{-- Generate button --}}
            <div class="flex items-center justify-between">
                <button id="back-btn" class="text-sm text-slate-500 hover:text-slate-300 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i>Recommencer
                </button>
                <button id="generate-btn" class="inline-flex items-center px-8 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/20 transition-all text-base disabled:opacity-50">
                    <i class="fas fa-check-circle mr-2"></i>
                    Générer le Factur-X
                </button>
            </div>
        </div>

        {{-- Download section --}}
        <div id="download-section" class="hidden fade-in">
            <div class="glass-card rounded-2xl p-10 text-center" style="border-color: rgba(16, 185, 129, 0.3);">
                <div class="w-20 h-20 mx-auto bg-emerald-500/20 rounded-full flex items-center justify-center mb-5">
                    <i class="fas fa-check text-4xl text-emerald-400"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-white mb-2">Factur-X prêt !</h2>
                <p class="text-slate-400 mb-8">Votre facture est conforme au standard Factur-X EN16931.</p>
                <div class="flex items-center justify-center gap-4 mb-6">
                    <a id="dl-pdf-btn" href="#" class="btn-primary inline-flex items-center px-6 py-3 text-white font-bold rounded-xl">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Télécharger le PDF
                    </a>
                    <a id="dl-xml-btn" href="#" class="inline-flex items-center px-6 py-3 bg-slate-700 hover:bg-slate-600 text-white font-bold rounded-xl transition-all border border-slate-600">
                        <i class="fas fa-code mr-2"></i>
                        Télécharger le XML
                    </a>
                </div>
                <button id="new-conversion-btn" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i>Convertir une autre facture
                </button>

                {{-- CTA --}}
                <div class="mt-8 pt-6 border-t border-slate-700/50">
                    <p class="text-sm text-slate-500 mb-3">Besoin de plus ? Gérez vos factures, stocks, paie et comptabilité</p>
                    <a href="/admin/register" class="inline-flex items-center px-5 py-2.5 bg-indigo-500/10 text-indigo-300 font-semibold rounded-lg hover:bg-indigo-500/20 transition-colors text-sm border border-indigo-500/30">
                        <i class="fas fa-rocket mr-2 text-xs"></i>Essayer FRECORP gratuitement
                    </a>
                </div>
            </div>
        </div>

        {{-- Features section (SEO) --}}
        <section class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="glass-card glass-card-hover rounded-xl p-6">
                <div class="w-10 h-10 bg-cyan-500/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-bolt text-cyan-400"></i>
                </div>
                <h3 class="font-bold text-white mb-1">Extraction par IA</h3>
                <p class="text-sm text-slate-400">Notre intelligence artificielle lit vos factures et extrait automatiquement toutes les données : émetteur, client, lignes, TVA.</p>
            </div>
            <div class="glass-card glass-card-hover rounded-xl p-6">
                <div class="w-10 h-10 bg-emerald-500/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                </div>
                <h3 class="font-bold text-white mb-1">Conforme EN16931</h3>
                <p class="text-sm text-slate-400">Factur-X généré au profil EN16931, le format standard pour la facturation électronique obligatoire en France dès 2026.</p>
            </div>
            <div class="glass-card glass-card-hover rounded-xl p-6">
                <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center mb-3">
                    <i class="fas fa-shield-halved text-purple-400"></i>
                </div>
                <h3 class="font-bold text-white mb-1">Sécurisé</h3>
                <p class="text-sm text-slate-400">Vos fichiers sont traités en temps réel et ne sont jamais stockés. La suppression est automatique après traitement.</p>
            </div>
        </section>

        {{-- FAQ (SEO) --}}
        <section class="mt-12 glass-card rounded-xl p-6">
            <h2 class="text-lg font-bold text-white mb-4">
                <i class="fas fa-circle-question mr-2 text-indigo-400"></i>Questions fréquentes
            </h2>
            <div class="space-y-3 text-sm">
                <details class="group">
                    <summary class="font-medium text-slate-300 cursor-pointer hover:text-indigo-400 transition-colors py-2 flex items-center justify-between">
                        Qu'est-ce que le format Factur-X ?
                        <i class="fas fa-chevron-down text-xs text-slate-600 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-1 text-slate-500 pl-4 pb-2">Factur-X est le standard franco-allemand de facturation électronique. C'est un PDF hybride contenant un fichier XML structuré (CII CrossIndustryInvoice). Il sera obligatoire pour toutes les entreprises françaises à partir de 2026.</p>
                </details>
                <details class="group border-t border-slate-700/50">
                    <summary class="font-medium text-slate-300 cursor-pointer hover:text-indigo-400 transition-colors py-2 flex items-center justify-between">
                        Combien de conversions gratuites puis-je faire ?
                        <i class="fas fa-chevron-down text-xs text-slate-600 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-1 text-slate-500 pl-4 pb-2">Vous pouvez convertir {{ $limit }} factures par mois gratuitement sans créer de compte. En vous inscrivant à FRECORP, vous obtenez des conversions illimitées et un ERP complet.</p>
                </details>
                <details class="group border-t border-slate-700/50">
                    <summary class="font-medium text-slate-300 cursor-pointer hover:text-indigo-400 transition-colors py-2 flex items-center justify-between">
                        Quels formats de fichiers sont acceptés ?
                        <i class="fas fa-chevron-down text-xs text-slate-600 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-1 text-slate-500 pl-4 pb-2">PDF (texte ou scanné), images (JPEG, PNG, WebP), et tableurs (Excel XLSX, CSV). La taille maximum est de 10 Mo.</p>
                </details>
                <details class="group border-t border-slate-700/50">
                    <summary class="font-medium text-slate-300 cursor-pointer hover:text-indigo-400 transition-colors py-2 flex items-center justify-between">
                        Mes données sont-elles en sécurité ?
                        <i class="fas fa-chevron-down text-xs text-slate-600 group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <p class="mt-1 text-slate-500 pl-4 pb-2">Oui. Les fichiers sont traités en temps réel sur nos serveurs en France et supprimés automatiquement après 24h. Aucune donnée n'est conservée ou partagée.</p>
                </details>
            </div>
        </section>
    </main>

    {{-- Footer --}}
    <footer class="border-t border-slate-800/50 py-6 relative z-10">
        <div class="max-w-5xl mx-auto px-4 flex items-center justify-between text-sm text-slate-500">
            <p>&copy; {{ date('Y') }} FRECORP — ERP français pour TPE/PME</p>
            <a href="https://frecorp.fr" class="text-indigo-400 hover:text-indigo-300 transition-colors">frecorp.fr</a>
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
            tr.className = 'border-t border-slate-700/30';
            tr.innerHTML = `
                <td class="py-1.5 pr-2"><input type="text" data-field="description" value="${escHtml(line.description || '')}" class="w-full rounded dark-input text-sm py-1.5 px-2" /></td>
                <td class="py-1.5 px-2"><input type="number" data-field="quantity" value="${line.quantity || 1}" step="0.01" min="0" class="w-full rounded dark-input text-sm text-center py-1.5 px-2 calc-trigger" /></td>
                <td class="py-1.5 px-2"><input type="number" data-field="unit_price_ht" value="${line.unit_price_ht || 0}" step="0.01" min="0" class="w-full rounded dark-input text-sm text-right py-1.5 px-2 calc-trigger" /></td>
                <td class="py-1.5 px-2">
                    <select data-field="vat_rate" class="w-full rounded dark-input text-sm text-center py-1.5 px-2 calc-trigger">
                        <option value="20" ${(line.vat_rate == 20 || !line.vat_rate) ? 'selected' : ''}>20%</option>
                        <option value="10" ${line.vat_rate == 10 ? 'selected' : ''}>10%</option>
                        <option value="5.5" ${line.vat_rate == 5.5 ? 'selected' : ''}>5,5%</option>
                        <option value="2.1" ${line.vat_rate == 2.1 ? 'selected' : ''}>2,1%</option>
                        <option value="0" ${line.vat_rate == 0 ? 'selected' : ''}>0%</option>
                    </select>
                </td>
                <td class="py-1.5 px-2 text-right font-medium text-slate-300 line-total">0,00 €</td>
                <td class="py-1.5 pl-1"><button class="remove-line text-slate-600 hover:text-red-400 transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button></td>
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
                btn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Générer le Factur-X';
            }
        });
    })();
    </script>
</body>
</html>

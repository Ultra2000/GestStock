<x-filament-panels::page>
    <div class="flex gap-6" x-data="{ activeSection: @entangle('activeSection') }">
        {{-- Sidebar navigation --}}
        <div class="w-64 shrink-0 hidden lg:block">
            <nav class="sticky top-4 space-y-1 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Sommaire</p>
                @php $videoCounts = $this->videoCounts; @endphp
                @foreach($this->getSections() as $key => $section)
                    <button
                        wire:click="setSection('{{ $key }}')"
                        class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                            {{ $activeSection === $key 
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-400' 
                                : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5' }}"
                    >
                        <x-dynamic-component :component="$section['icon']" class="h-4 w-4" />
                        <span class="flex-1 text-left">{{ $section['label'] }}</span>
                        @if(isset($videoCounts[$key]) && $videoCounts[$key] > 0)
                            <span class="inline-flex items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-medium min-w-[20px] h-5 px-1.5">
                                <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                                {{ $videoCounts[$key] }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Mobile section selector --}}
        <div class="lg:hidden mb-4 w-full">
            <select wire:model.live="activeSection" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                @foreach($this->getSections() as $key => $section)
                    <option value="{{ $key }}">{{ $section['label'] }}</option>
                @endforeach
            </select>
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            <div class="prose prose-sm max-w-none dark:prose-invert
                prose-headings:text-gray-900 dark:prose-headings:text-white
                prose-h2:text-2xl prose-h2:font-bold prose-h2:border-b prose-h2:border-gray-200 dark:prose-h2:border-gray-700 prose-h2:pb-3 prose-h2:mb-6
                prose-h3:text-xl prose-h3:font-semibold prose-h3:mt-8
                prose-h4:text-lg prose-h4:font-medium
                prose-table:text-sm
                prose-th:bg-gray-50 dark:prose-th:bg-gray-800 prose-th:px-4 prose-th:py-2
                prose-td:px-4 prose-td:py-2
                prose-code:text-primary-600 dark:prose-code:text-primary-400 prose-code:bg-gray-100 dark:prose-code:bg-gray-800 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-xs
                prose-a:text-primary-600 dark:prose-a:text-primary-400
            ">

                {{-- SECTION: PREMIERS PAS --}}
                @if($activeSection === 'getting-started')
                <div>
                    <h2>Premiers pas</h2>

                    <div class="not-prose mb-6 rounded-xl bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 p-4">
                        <div class="flex gap-3">
                            <x-heroicon-s-light-bulb class="h-6 w-6 text-primary-500 shrink-0 mt-0.5" />
                            <div>
                                <p class="font-semibold text-primary-900 dark:text-primary-300">Bienvenue dans FRECORP ERP</p>
                                <p class="text-sm text-primary-700 dark:text-primary-400 mt-1">Ce guide couvre l'ensemble des fonctionnalites de l'application. Utilisez le sommaire a gauche pour naviguer entre les sections.</p>
                            </div>
                        </div>
                    </div>

                    <h3>1.1 Creer son entreprise</h3>
                    <p>Lors de votre premiere connexion, vous devez creer votre entreprise :</p>
                    <ol>
                        <li>Cliquez sur <strong>Enregistrer une entreprise</strong></li>
                        <li>Saisissez votre <strong>numero SIREN</strong> (9 chiffres) puis cliquez sur <strong>Rechercher</strong>
                            <ul>
                                <li>Le systeme interroge l'API gouvernementale et remplit automatiquement le nom et l'adresse</li>
                            </ul>
                        </li>
                        <li>Completez les informations : <strong>Email</strong>, <strong>Telephone</strong></li>
                        <li>Validez &mdash; le systeme cree automatiquement :
                            <ul>
                                <li>L'entreprise avec un slug unique</li>
                                <li>Les roles par defaut (Admin, Manager, Vendeur, Caissier, Comptable)</li>
                                <li>Les permissions associees</li>
                                <li>Votre compte est assigne au role <strong>Admin</strong></li>
                            </ul>
                        </li>
                    </ol>

                    <h3>1.2 Configurer le profil entreprise</h3>
                    <p>Acces : <strong>Profil de l'entreprise</strong> (icone engrenage en bas a gauche)</p>

                    <table>
                        <thead><tr><th>Champ</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Nom de l'entreprise</td><td>Raison sociale</td></tr>
                            <tr><td>Email / Telephone</td><td>Contact principal</td></tr>
                            <tr><td>Adresse</td><td>Siege social</td></tr>
                            <tr><td>Site web</td><td>URL publique</td></tr>
                            <tr><td>N&deg; TVA Intra</td><td>Numero de TVA intracommunautaire</td></tr>
                            <tr><td>SIREN / SIRET</td><td>Obligatoire pour la facturation electronique</td></tr>
                            <tr><td>Logo</td><td>Image affichee sur les factures et devis (upload)</td></tr>
                            <tr><td>Texte de pied de page</td><td>Mentions legales sur les documents</td></tr>
                            <tr><td>Devise</td><td>XOF, XAF, USD, EUR, GBP, CHF, CAD, AUD, JPY, CNY, INR, BRL, MXN</td></tr>
                        </tbody>
                    </table>

                    <h3>1.3 Activer / Desactiver les modules</h3>
                    <p>Dans la section <strong>Modules</strong> du profil, activez uniquement ce dont vous avez besoin :</p>

                    <table>
                        <thead><tr><th>Module</th><th>Fonctionnalites debloquees</th></tr></thead>
                        <tbody>
                            <tr><td>Point de Vente (Caisse)</td><td>Interface caissier, sessions de caisse, historique POS</td></tr>
                            <tr><td>Gestion de Stock</td><td>Entrepots, transferts, inventaires, stock consolide</td></tr>
                            <tr><td>Ressources Humaines</td><td>Employes, planning, pointage, conges, commissions</td></tr>
                            <tr><td>Comptabilite</td><td>Grand Livre, ecritures, TVA, export FEC, reglements</td></tr>
                            <tr><td>Banque</td><td>Comptes bancaires, transactions, rapprochement</td></tr>
                        </tbody>
                    </table>

                    <div class="not-prose mt-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-300"><strong>Note :</strong> Les menus de navigation s'adaptent automatiquement aux modules actives.</p>
                    </div>
                </div>
                @endif

                {{-- SECTION: TABLEAU DE BORD --}}
                @if($activeSection === 'dashboard')
                <div>
                    <h2>Tableau de bord</h2>
                    <p>Le tableau de bord affiche une vue d'ensemble de votre activite avec les widgets suivants :</p>

                    <table>
                        <thead><tr><th>Widget</th><th>Contenu</th></tr></thead>
                        <tbody>
                            <tr><td>Statistiques cles</td><td>Chiffre d'affaires, nombre de ventes, panier moyen</td></tr>
                            <tr><td>Actions rapides</td><td>Boutons d'acces direct aux fonctions frequentes</td></tr>
                            <tr><td>Graphique des ventes</td><td>Evolution du CA sur la periode</td></tr>
                            <tr><td>Statistiques commandes</td><td>Volume et montants des commandes</td></tr>
                            <tr><td>Graphique des devis</td><td>Taux de conversion des devis</td></tr>
                            <tr><td>Alertes de stock</td><td>Produits en stock bas ou en rupture</td></tr>
                            <tr><td>Vue entrepots</td><td>Resume par entrepot</td></tr>
                            <tr><td>Resume stock</td><td>Vue consolidee des stocks</td></tr>
                            <tr><td>Stats RH</td><td>Effectifs, presences (si module RH active)</td></tr>
                            <tr><td>Graphique presences</td><td>Taux de presence (si module RH active)</td></tr>
                            <tr><td>Resume URSSAF</td><td>Charges sociales a prevoir (si module RH active)</td></tr>
                            <tr><td>Resume TVA</td><td>TVA collectee vs deductible (si module comptabilite active)</td></tr>
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- SECTION: VENTES --}}
                @if($activeSection === 'sales')
                <div>
                    <h2>Module Ventes</h2>

                    <h3>3.1 Clients</h3>
                    <p><strong>Menu :</strong> Ventes &rarr; Clients</p>

                    <h4>Creer un client</h4>
                    <ol>
                        <li>Cliquez <strong>Nouveau Client</strong></li>
                        <li>Remplissez les champs :
                            <ul>
                                <li><strong>Nom / Raison Sociale</strong> (obligatoire)</li>
                                <li><strong>SIREN</strong> (9 chiffres), <strong>SIRET</strong> (14 chiffres)</li>
                                <li><strong>N&deg; TVA Intra</strong></li>
                                <li><strong>Email</strong>, <strong>Telephone</strong></li>
                                <li><strong>Adresse</strong>, <strong>Code postal</strong>, <strong>Ville</strong>, <strong>Pays</strong> (defaut : France)</li>
                                <li><strong>Code pays ISO</strong> (defaut : FR) &mdash; utilise pour la facturation electronique</li>
                            </ul>
                        </li>
                    </ol>

                    <div class="not-prose mt-4 mb-6 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                        <p class="text-sm text-amber-800 dark:text-amber-300"><strong>Astuce :</strong> Vous pouvez creer un client directement depuis un formulaire de vente ou de devis grace au bouton &laquo;&nbsp;+&nbsp;&raquo; a cote du selecteur client.</p>
                    </div>

                    <h3>3.2 Devis</h3>
                    <p><strong>Menu :</strong> Ventes &rarr; Devis</p>

                    <h4>Cycle de vie d'un devis</h4>
                    <div class="not-prose mb-4">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 font-medium">Brouillon</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-3 py-1 font-medium">Envoye</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 px-3 py-1 font-medium">Accepte</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-300 px-3 py-1 font-medium">Converti en vente</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm mt-2 ml-[calc(theme(spacing.3)*2+theme(spacing.2)+100px)]">
                            <span class="rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 px-3 py-1 font-medium">Refuse / Expire</span>
                        </div>
                    </div>

                    <h4>Creer un devis</h4>
                    <ol>
                        <li>Cliquez <strong>Nouveau Devis</strong></li>
                        <li><strong>En-tete :</strong> numero auto, date du jour, validite +30 jours, client</li>
                        <li><strong>Lignes d'articles :</strong> ajoutez des produits &mdash; le prix, la description et le taux de TVA se remplissent automatiquement. Modifiez la quantite, le prix unitaire HT, le taux de TVA, la remise % si besoin.</li>
                        <li><strong>Totaux HT, TVA et TTC</strong> se calculent en temps reel</li>
                        <li><strong>Notes internes</strong> (non visibles par le client) et <strong>Conditions generales</strong></li>
                    </ol>

                    <h4>Actions sur un devis</h4>
                    <table>
                        <thead><tr><th>Action</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Envoyer par email</td><td>Envoie le PDF au client avec message personnalisable. Passe en &laquo;&nbsp;Envoye&nbsp;&raquo;</td></tr>
                            <tr><td>Copier le lien public</td><td>Genere un lien URL unique consultable sans connexion</td></tr>
                            <tr><td>Accepter</td><td>Passe le devis en &laquo;&nbsp;Accepte&nbsp;&raquo; (depuis &laquo;&nbsp;Envoye&nbsp;&raquo;)</td></tr>
                            <tr><td>Refuser</td><td>Passe le devis en &laquo;&nbsp;Refuse&nbsp;&raquo; (depuis &laquo;&nbsp;Envoye&nbsp;&raquo;)</td></tr>
                            <tr><td>Convertir en vente</td><td>Cree une vente avec tous les articles du devis (depuis &laquo;&nbsp;Accepte&nbsp;&raquo;)</td></tr>
                            <tr><td>Telecharger PDF</td><td>Ouvre le PDF du devis</td></tr>
                            <tr><td>Dupliquer</td><td>Cree une copie avec nouvelles dates et statut &laquo;&nbsp;Brouillon&nbsp;&raquo;</td></tr>
                        </tbody>
                    </table>

                    <h3>3.3 Ventes / Factures</h3>
                    <p><strong>Menu :</strong> Ventes &rarr; Ventes</p>

                    <h4>Creer une vente</h4>
                    <ol>
                        <li>Cliquez <strong>Nouvelle Vente</strong></li>
                        <li><strong>En-tete :</strong> N&deg; facture auto (FAC-YYYY-NNNNN), Client, Entrepot source, Mode de paiement, Compte bancaire</li>
                        <li><strong>Remise globale</strong> : pourcentage applique sur le total</li>
                        <li><strong>Lignes d'articles</strong> (visibles apres selection de l'entrepot) :
                            <ul>
                                <li>Produit filtre par stock disponible (le stock est affiche)</li>
                                <li>Quantite (ne peut pas depasser le stock disponible)</li>
                                <li>Prix unitaire HT, Taux TVA, Categorie TVA</li>
                            </ul>
                        </li>
                    </ol>

                    <h4>Validation de la vente</h4>
                    <p>Quand vous passez le statut a <strong>Validee</strong> :</p>
                    <ul>
                        <li>Le <strong>stock est deduit</strong> de l'entrepot source</li>
                        <li>Les <strong>ecritures comptables</strong> sont generees automatiquement :
                            <ul>
                                <li>DEBIT 411xxx (Client) : montant TTC</li>
                                <li>CREDIT 707xxx (Ventes) : montant HT par taux TVA</li>
                                <li>CREDIT 4457xx (TVA collectee) : TVA par taux</li>
                            </ul>
                        </li>
                    </ul>

                    <h4>Actions sur une vente validee</h4>
                    <table>
                        <thead><tr><th>Action</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Facture PDF</td><td>Telecharge le PDF de la facture</td></tr>
                            <tr><td>Envoyer par email</td><td>Envoie la facture PDF par email</td></tr>
                            <tr><td>Envoyer au PPF</td><td>Transmet la facture a Chorus Pro (facturation electronique)</td></tr>
                            <tr><td>Actualiser statut PPF</td><td>Synchronise le statut depuis Chorus Pro</td></tr>
                            <tr><td>Generer un avoir</td><td>Cree une facture d'avoir negative, restaure le stock, contre-passe les ecritures</td></tr>
                        </tbody>
                    </table>

                    <div class="not-prose mt-4 mb-6 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                        <p class="text-sm text-red-800 dark:text-red-300"><strong>Important :</strong> Une vente validee ne peut plus etre modifiee ni supprimee (immutabilite comptable). Utilisez un <strong>avoir</strong> pour corriger.</p>
                    </div>

                    <h3>3.4 Commandes recurrentes</h3>
                    <p><strong>Menu :</strong> Ventes &rarr; Commandes</p>
                    <p>Automatisez les ventes repetitives (abonnements, livraisons regulieres).</p>

                    <h4>Creer une commande recurrente</h4>
                    <ol>
                        <li><strong>Informations :</strong> Reference auto, Nom, Client</li>
                        <li><strong>Planification :</strong> Frequence (Quotidienne, Hebdomadaire, Bimensuelle, Mensuelle, Trimestrielle, Annuelle), Dates de debut/fin, Nombre max d'executions</li>
                        <li><strong>Articles :</strong> meme systeme que les ventes</li>
                        <li><strong>Options :</strong> Generation automatique, Envoi automatique de facture</li>
                    </ol>

                    <table>
                        <thead><tr><th>Action</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Executer maintenant</td><td>Genere immediatement une vente</td></tr>
                            <tr><td>Mettre en pause / Reprendre</td><td>Suspend ou reactive les executions automatiques</td></tr>
                            <tr><td>Annuler</td><td>Arrete definitivement la commande</td></tr>
                            <tr><td>Dupliquer</td><td>Copie la commande avec compteurs remis a zero</td></tr>
                        </tbody>
                    </table>

                    <h3>3.5 Bons de livraison</h3>
                    <p><strong>Menu :</strong> Ventes &rarr; Bons de livraison</p>

                    <h4>Cycle de vie</h4>
                    <div class="not-prose mb-4">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 font-medium">En attente</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 px-3 py-1 font-medium">En preparation</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-3 py-1 font-medium">Pret</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 px-3 py-1 font-medium">Expedie</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 px-3 py-1 font-medium">Livre</span>
                        </div>
                    </div>

                    <ol>
                        <li><strong>Lier a une vente</strong> : selectionnez la vente &rarr; le client et l'adresse se remplissent</li>
                        <li><strong>Transporteur</strong> et <strong>N&deg; de suivi</strong></li>
                        <li><strong>Articles</strong> : produit, quantite commandee, quantite livree</li>
                    </ol>
                </div>
                @endif

                {{-- SECTION: STOCKS & ACHATS --}}
                @if($activeSection === 'stock')
                <div>
                    <h2>Stocks & Achats</h2>

                    <h3>4.1 Produits</h3>
                    <p><strong>Menu :</strong> Stocks & Achats &rarr; Produits</p>

                    <h4>Creer un produit</h4>
                    <ol>
                        <li><strong>Informations generales :</strong> Nom (obligatoire), Code (auto), Type de code-barres (Code 128), Description</li>
                        <li><strong>Prix & TVA :</strong>
                            <ul>
                                <li>Activez <strong>Prix saisis en TTC</strong> pour basculer entre saisie HT et TTC</li>
                                <li><strong>Achat :</strong> Prix d'achat HT ou TTC, Taux TVA achat</li>
                                <li><strong>Vente :</strong> Prix de vente HT ou TTC, Taux TVA vente</li>
                                <li><strong>Categorie TVA</strong> (Chorus Pro) : S (Standard), Z (Zero), E (Exonere), AE (Autoliquidation)</li>
                                <li><strong>Indicateurs de marge :</strong> marge brute, taux de marge %, taux de marque % &mdash; en vert (positif) ou rouge (negatif)</li>
                            </ul>
                        </li>
                        <li><strong>Stock :</strong> Stock initial, Unite (piece, kg, litre, metre, boite, carton, palette, lot), Stock minimum (seuil d'alerte)</li>
                        <li><strong>Fournisseur :</strong> selectionnez ou creez a la volee</li>
                    </ol>

                    <h4>Actions sur les produits</h4>
                    <table>
                        <thead><tr><th>Action</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Regenerer le code</td><td>Admin uniquement, irreversible &mdash; genere un nouveau code produit</td></tr>
                            <tr><td>Imprimer etiquettes</td><td>Definir la quantite, nombre de colonnes (2/3/4), afficher ou masquer le prix</td></tr>
                        </tbody>
                    </table>

                    <div class="not-prose mt-4 mb-6 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                        <p class="text-sm text-amber-800 dark:text-amber-300"><strong>Impression en masse :</strong> Selectionnez plusieurs produits dans le tableau &rarr; Action &laquo;&nbsp;Imprimer etiquettes&nbsp;&raquo; &rarr; definissez la quantite par produit.</p>
                    </div>

                    <h3>4.2 Fournisseurs</h3>
                    <p><strong>Menu :</strong> Stocks & Achats &rarr; Fournisseurs</p>
                    <p>Gestion simple : Nom, Email, Telephone, Adresse, Ville, Pays, Notes.</p>

                    <h3>4.3 Achats</h3>
                    <p><strong>Menu :</strong> Stocks & Achats &rarr; Achats</p>
                    <p>Fonctionne en miroir des ventes :</p>
                    <ol>
                        <li><strong>En-tete :</strong> N&deg; facture (auto), Fournisseur, Entrepot de reception, Mode de paiement</li>
                        <li><strong>Articles :</strong> Produit (remplit automatiquement le prix d'achat et la TVA), Quantite, Prix unitaire HT, Taux TVA</li>
                        <li><strong>Remise globale</strong> et <strong>Totaux</strong> (HT, TVA deductible, TTC) calcules automatiquement</li>
                    </ol>

                    <h4>Validation</h4>
                    <p>Quand l'achat passe en <strong>Valide</strong> :</p>
                    <ul>
                        <li>Le <strong>stock est ajoute</strong> dans l'entrepot de reception</li>
                        <li>Les <strong>ecritures comptables</strong> sont generees :
                            <ul>
                                <li>CREDIT 401xxx (Fournisseur) : montant TTC</li>
                                <li>DEBIT 607xxx (Achats) : montant HT</li>
                                <li>DEBIT 4456xx (TVA deductible) : TVA</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>4.4 Entrepots</h3>
                    <p><strong>Menu :</strong> Stocks & Achats &rarr; Entrepots</p>

                    <h4>Creer un entrepot</h4>
                    <ol>
                        <li><strong>General :</strong> Code (unique), Nom, Type (Entrepot, Magasin, Depot fournisseur, Depot client), Responsable</li>
                        <li><strong>Adresse :</strong> Adresse, Ville, Code postal, Pays</li>
                        <li><strong>Geolocalisation & Pointage :</strong>
                            <ul>
                                <li>Latitude / Longitude : cliquez &laquo;&nbsp;Obtenir ma position&nbsp;&raquo; pour remplir automatiquement</li>
                                <li>Rayon GPS (10-1 000 m) : zone autorisee pour le pointage des employes</li>
                                <li>Apercu OpenStreetMap de la position</li>
                                <li>Activer <strong>Verification GPS requise</strong> et/ou <strong>Verification QR code requise</strong></li>
                            </ul>
                        </li>
                        <li><strong>Options :</strong> Entrepot par defaut, Point de vente (POS), Autoriser stock negatif, Actif</li>
                    </ol>

                    <h3>4.5 Transferts de stock</h3>
                    <p><strong>Menu :</strong> Stocks & Achats &rarr; Transferts</p>

                    <h4>Cycle de vie</h4>
                    <div class="not-prose mb-4">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-3 py-1 font-medium">Brouillon</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 px-3 py-1 font-medium">En attente</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-3 py-1 font-medium">Approuve</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 px-3 py-1 font-medium">En transit</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 px-3 py-1 font-medium">Termine</span>
                        </div>
                    </div>

                    <h4>Creer un transfert</h4>
                    <ol>
                        <li><strong>Reference</strong> auto-generee</li>
                        <li><strong>Entrepot source</strong> et <strong>Entrepot destination</strong> (ne peut pas etre le meme)</li>
                        <li><strong>Date de transfert</strong> et <strong>Date d'arrivee prevue</strong></li>
                        <li><strong>Articles :</strong> Produit (filtre par stock disponible), Quantite demandee, Cout unitaire (auto), N&deg; de lot et Date d'expiration (optionnels)</li>
                        <li><strong>Expedition :</strong> Transporteur, N&deg; de suivi</li>
                    </ol>

                    <table>
                        <thead><tr><th>Action</th><th>Effet</th></tr></thead>
                        <tbody>
                            <tr><td>Approuver</td><td>Valide la demande de transfert</td></tr>
                            <tr><td>Expedier</td><td>Deduit le stock de l'entrepot source, passe en &laquo;&nbsp;En transit&nbsp;&raquo;</td></tr>
                            <tr><td>Recevoir</td><td>Page dediee de reception : saisie des quantites recues (receptions partielles possibles)</td></tr>
                            <tr><td>Annuler</td><td>Annule avec saisie du motif obligatoire</td></tr>
                        </tbody>
                    </table>

                    <h3>4.6 Inventaires</h3>
                    <p><strong>Menu :</strong> Stocks & Achats &rarr; Inventaires</p>

                    <h4>Types d'inventaire</h4>
                    <table>
                        <thead><tr><th>Type</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Complet</td><td>Tous les produits de l'entrepot</td></tr>
                            <tr><td>Partiel</td><td>Selection de produits</td></tr>
                            <tr><td>Tournant (cycle)</td><td>Par rotation sur une categorie</td></tr>
                        </tbody>
                    </table>

                    <h4>Processus</h4>
                    <ol>
                        <li><strong>Creer</strong> l'inventaire : choisir l'entrepot, le type, la date</li>
                        <li><strong>Demarrer</strong> : passe en &laquo;&nbsp;En cours&nbsp;&raquo; &rarr; acces a la page de comptage</li>
                        <li><strong>Compter</strong> : page dediee ou vous saisissez les quantites physiques pour chaque produit</li>
                        <li><strong>Valider</strong> : applique les ajustements de stock (irreversible)</li>
                    </ol>

                    <h3>4.7 Stock consolide</h3>
                    <p><strong>Menu :</strong> Stocks & Achats &rarr; Stock consolide</p>
                    <p>Vue transversale de tout le stock, tous entrepots confondus :</p>

                    <table>
                        <thead><tr><th>Colonne</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Code</td><td>Code produit (copiable)</td></tr>
                            <tr><td>Stock total</td><td>Somme de tous les entrepots</td></tr>
                            <tr><td>Quantite reservee</td><td>Stock engage (commandes en cours)</td></tr>
                            <tr><td>Stock disponible</td><td>Total &minus; Reserve</td></tr>
                            <tr><td>Valeur du stock</td><td>Stock &times; Prix d'achat</td></tr>
                            <tr><td>Statut</td><td>Normal (vert) / Bas (orange) / Rupture (rouge)</td></tr>
                        </tbody>
                    </table>

                    <p><strong>Actions :</strong> Voir details (ventilation par entrepot), Transferer (lien vers creation de transfert)</p>
                </div>
                @endif

                {{-- SECTION: CAISSE (POS) --}}
                @if($activeSection === 'pos')
                <div>
                    <h2>Caisse (Point de Vente)</h2>

                    <div class="not-prose mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-300">Accessible depuis le panneau <strong>Caisse</strong> (menu lateral separe).</p>
                    </div>

                    <h3>5.1 Sessions de caisse</h3>

                    <h4>Ouvrir une session</h4>
                    <ol>
                        <li>Saisissez le <strong>montant de caisse d'ouverture</strong> (fond de caisse)</li>
                        <li>Validez &rarr; la session est active</li>
                    </ol>

                    <h4>Pendant la session</h4>
                    <p>Statistiques en temps reel : total des ventes, ventilation par mode de paiement (Especes, Carte, Mobile, Autre), montant d'ouverture et montant attendu.</p>

                    <h4>Fermer une session</h4>
                    <ol>
                        <li>Saisissez le <strong>montant de caisse de fermeture</strong> (comptage physique)</li>
                        <li>Le systeme calcule la <strong>difference</strong> :
                            <ul>
                                <li><span class="text-green-600 dark:text-green-400 font-semibold">Equilibree</span> : le compte est bon</li>
                                <li><span class="text-blue-600 dark:text-blue-400 font-semibold">Excedent</span> : plus d'argent que prevu</li>
                                <li><span class="text-red-600 dark:text-red-400 font-semibold">Deficit</span> : moins d'argent que prevu</li>
                            </ul>
                        </li>
                    </ol>

                    <h3>5.2 Interface de vente</h3>

                    <div class="not-prose mb-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                        <p class="text-sm text-amber-800 dark:text-amber-300"><strong>Prerequis :</strong> Une session de caisse doit etre ouverte.</p>
                    </div>

                    <ol>
                        <li><strong>Chercher un produit</strong> par nom, code ou code-barres (lecteur de code-barres supporte)</li>
                        <li>Le produit s'ajoute au panier avec la quantite 1</li>
                        <li>Modifiez la <strong>quantite</strong> si besoin (le stock disponible est verifie en temps reel)</li>
                        <li>Selectionnez un <strong>client</strong> ou laissez &laquo;&nbsp;Client comptoir&nbsp;&raquo;</li>
                        <li>Choisissez le <strong>mode de paiement</strong> : Especes, Carte, Mobile, Autre</li>
                        <li><strong>Validez la vente</strong> &rarr; le stock est deduit, la session de caisse est mise a jour</li>
                    </ol>

                    <h3>5.3 Historique des ventes</h3>
                    <ul>
                        <li>Affiche les <strong>50 dernieres ventes du jour</strong> avec le detail des articles</li>
                        <li><strong>Annuler une vente</strong> : restaure automatiquement le stock et met a jour la session de caisse</li>
                        <li><strong>Voir les details</strong> : visualisation complete de la vente</li>
                    </ul>
                </div>
                @endif

                {{-- SECTION: COMPTABILITE --}}
                @if($activeSection === 'accounting')
                <div>
                    <h2>Comptabilite</h2>

                    <h3>6.1 Parametres comptables</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Parametres Comptables</p>

                    <h4>Regime fiscal</h4>
                    <table>
                        <thead><tr><th>Parametre</th><th>Options</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Franchise en base de TVA</td><td>Oui / Non</td><td>Si active (Art. 293 B du CGI), aucune TVA n'est calculee</td></tr>
                            <tr><td>Regime de TVA</td><td>Sur les debits / Sur les encaissements</td><td>Debits = TVA exigible a la facturation. Encaissements = TVA exigible au paiement</td></tr>
                        </tbody>
                    </table>

                    <div class="not-prose mt-4 mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-300"><strong>Regime encaissements :</strong> La TVA est d'abord comptabilisee dans un compte d'attente (44574x), puis basculee vers le compte de TVA collectee (4457x) a chaque paiement recu, au prorata du montant encaisse.</p>
                    </div>

                    <h4>Plan Comptable General (PCG)</h4>
                    <table>
                        <thead><tr><th>Compte</th><th>N&deg; par defaut</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Clients</td><td>411000</td><td>Creances clients</td></tr>
                            <tr><td>Fournisseurs</td><td>401000</td><td>Dettes fournisseurs</td></tr>
                            <tr><td>Banque</td><td>512000</td><td>Banque</td></tr>
                            <tr><td>Caisse</td><td>530000</td><td>Caisse</td></tr>
                            <tr><td>Ventes</td><td>707000</td><td>Ventes de marchandises</td></tr>
                            <tr><td>Achats</td><td>607000</td><td>Achats de marchandises</td></tr>
                            <tr><td>Remises accordees</td><td>709000</td><td>RRR accordes</td></tr>
                            <tr><td>Remises obtenues</td><td>609000</td><td>RRR obtenus</td></tr>
                            <tr><td>TVA collectee</td><td>445710</td><td>TVA collectee (ventes)</td></tr>
                            <tr><td>TVA deductible</td><td>445660</td><td>TVA deductible (achats)</td></tr>
                        </tbody>
                    </table>

                    <h4>Codes journaux</h4>
                    <table>
                        <thead><tr><th>Code</th><th>Libelle</th></tr></thead>
                        <tbody>
                            <tr><td>VTE</td><td>Journal des Ventes</td></tr>
                            <tr><td>ACH</td><td>Journal des Achats</td></tr>
                            <tr><td>BQ</td><td>Journal de Banque</td></tr>
                            <tr><td>CAI</td><td>Journal de Caisse</td></tr>
                            <tr><td>OD</td><td>Operations Diverses</td></tr>
                        </tbody>
                    </table>

                    <h3>6.2 Categories comptables</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Categories comptables</p>
                    <p>Categories pour classer vos operations : Nom, Type (Produit / Charge), Couleur, Hierarchie (categorie parente possible).</p>

                    <h3>6.3 Regles d'imputation automatique</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Regles d'imputation</p>
                    <p>Classement automatique des transactions bancaires par motif :</p>

                    <table>
                        <thead><tr><th>Champ</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Nom</td><td>Nom de la regle</td></tr>
                            <tr><td>Type de condition</td><td>Contient, Commence par, Finit par, Exact</td></tr>
                            <tr><td>Valeur</td><td>Texte a chercher dans le libelle (ex: AMAZON, LOYER)</td></tr>
                            <tr><td>Categorie</td><td>Categorie comptable a appliquer</td></tr>
                            <tr><td>Priorite</td><td>En cas de conflit, la priorite la plus haute gagne</td></tr>
                            <tr><td>Actif</td><td>Activer/desactiver la regle</td></tr>
                        </tbody>
                    </table>

                    <h3>6.4 Grand Livre</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Grand Livre</p>
                    <p>Registre central de toutes les ecritures comptables. Les ecritures sont en <strong>lecture seule</strong> (conformite FEC). Seul le <strong>lettrage</strong> est modifiable.</p>

                    <h4>Colonnes</h4>
                    <p>Date, Journal (colore par type), N&deg; Piece, Compte, Libelle, Debit / Credit (avec totaux en pied), Lettrage, Verrouillee.</p>

                    <h4>Actions</h4>
                    <table>
                        <thead><tr><th>Action</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Lettrer (en masse)</td><td>Selectionnez plusieurs ecritures &rarr; verification Debits = Credits &rarr; code de lettrage attribue</td></tr>
                            <tr><td>Reclasser</td><td>Cree une ecriture OD pour deplacer un montant d'un compte vers un autre (avec choix de date et motif)</td></tr>
                        </tbody>
                    </table>

                    <h3>6.5 Ecritures OD</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Ecritures OD</p>
                    <p>Pour les corrections, reclassements et regularisations manuelles.</p>
                    <ol>
                        <li><strong>En-tete :</strong> Date, Journal (OD, AN, EX), N&deg; de piece (auto), Motif</li>
                        <li><strong>Lignes (minimum 2) :</strong> N&deg; Compte (valide PCG : 6-10 chiffres), Auxiliaire, Libelle, Debit et Credit</li>
                        <li><strong>Validation :</strong> total debits = total credits (tolerance 0,01)</li>
                    </ol>

                    <h3>6.6 Balance Generale</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Balance Generale</p>
                    <p>Vue synthetique de tous les comptes avec Total Debit, Total Credit, Solde Debiteur, Solde Crediteur. Regroupement par classe PCG. Verification d'equilibre automatique.</p>

                    <h3>6.7 Rapports & TVA</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Rapports & TVA</p>
                    <p>Rapport de TVA pour la preparation de la declaration CA3 :</p>
                    <ol>
                        <li>Choisissez la <strong>periode</strong> : Mois, Trimestre, Annee, ou Personnalisee</li>
                        <li>Le rapport affiche : CA ventile par mode de paiement, Ventes/Achats HT/TTC, TVA par taux (20%, 10%, 5,5%, 2,1%), TVA nette, Resultat</li>
                        <li><strong>Export PDF</strong> : telechargez le rapport TVA complet</li>
                    </ol>

                    <h3>6.8 Journal d'Audit</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Journal d'Audit</p>
                    <p>Controle de sante automatique de votre comptabilite :</p>

                    <table>
                        <thead><tr><th>Pilier</th><th>Ce qui est verifie</th></tr></thead>
                        <tbody>
                            <tr><td>Integrite Ventes</td><td>Total des ventes = Total classe 7</td></tr>
                            <tr><td>Integrite Achats</td><td>Total des achats = Total classe 6</td></tr>
                            <tr><td>Sequences</td><td>Continuite des numeros FEC, detection des trous</td></tr>
                            <tr><td>Coherence TVA</td><td>TVA theorique vs TVA comptabilisee</td></tr>
                            <tr><td>Anomalies</td><td>Ventes/achats sans ecritures, pieces desequilibrees</td></tr>
                            <tr><td>Lettrage</td><td>% de comptes client/fournisseur rapproches</td></tr>
                        </tbody>
                    </table>

                    <p><strong>Score de sante</strong> sur 100 points. <strong>Telecharger le certificat d'integrite</strong> : PDF attestant la coherence de votre comptabilite.</p>

                    <h3>6.9 Export FEC</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Export Comptable</p>
                    <ol>
                        <li>Selectionnez la <strong>periode</strong></li>
                        <li>Choisissez le <strong>format</strong> : FEC (separateur pipe, virgule decimale) ou CSV (point-virgule, BOM UTF-8 pour Excel)</li>
                        <li><strong>Previsualiser</strong> : affiche les 100 premieres lignes</li>
                        <li><strong>Exporter</strong> : telecharge le fichier</li>
                    </ol>

                    <h3>6.10 Reglements</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Reglements</p>
                    <ol>
                        <li><strong>Type :</strong> Client (vente) ou Fournisseur (achat)</li>
                        <li><strong>Document :</strong> selectionnez la vente ou l'achat concerne (documents valides avec solde restant)</li>
                        <li><strong>Montant :</strong> calcule automatiquement comme le reste a payer</li>
                        <li><strong>Mode de paiement :</strong> Especes (530000), Carte (512000), Virement (512000), Cheque (511200), Mobile (512000)</li>
                        <li><strong>Date, Reference, Notes</strong></li>
                    </ol>

                    <p>Le lettrage automatique rapproche l'ecriture de paiement avec l'ecriture d'origine. En regime encaissements, la TVA est basculee au prorata du paiement.</p>

                    <h3>6.11 Centre de Rapports PDF</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Centre de Rapports</p>
                    <table>
                        <thead><tr><th>Rapport</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Etat du stock</td><td>Liste des produits avec quantites et valeurs</td></tr>
                            <tr><td>Export inventaire CSV</td><td>Export tabulaire du stock</td></tr>
                            <tr><td>Bilan comptable</td><td>Synthese financiere : CA, achats, marge, TVA, resultat, evolution mensuelle</td></tr>
                            <tr><td>Journal des ventes</td><td>Detail de toutes les ventes validees</td></tr>
                            <tr><td>Journal des achats</td><td>Detail de tous les achats valides</td></tr>
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- SECTION: BANQUE --}}
                @if($activeSection === 'banking')
                <div>
                    <h2>Banque</h2>

                    <h3>7.1 Comptes bancaires</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Comptes bancaires</p>
                    <table>
                        <thead><tr><th>Champ</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Nom</td><td>Nom du compte (ex: &laquo;&nbsp;Compte courant pro&nbsp;&raquo;)</td></tr>
                            <tr><td>Banque</td><td>Nom de l'etablissement</td></tr>
                            <tr><td>N&deg; de compte / IBAN</td><td>Identifiant bancaire</td></tr>
                            <tr><td>Devise</td><td>EUR par defaut</td></tr>
                            <tr><td>Solde initial</td><td>Solde a l'ouverture</td></tr>
                            <tr><td>Solde actuel</td><td>Calcule automatiquement</td></tr>
                            <tr><td>Actif</td><td>Activer/desactiver</td></tr>
                        </tbody>
                    </table>

                    <h3>7.2 Transactions bancaires</h3>
                    <p><strong>Menu :</strong> Comptabilite &rarr; Transactions</p>
                    <table>
                        <thead><tr><th>Champ</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Compte bancaire</td><td>Selectionnez le compte</td></tr>
                            <tr><td>Date</td><td>Date de l'operation</td></tr>
                            <tr><td>Montant</td><td>Positif</td></tr>
                            <tr><td>Type</td><td>Credit (entree) ou Debit (sortie)</td></tr>
                            <tr><td>Libelle</td><td>Description de l'operation</td></tr>
                            <tr><td>Reference</td><td>Reference bancaire</td></tr>
                            <tr><td>Categorie comptable</td><td>Classification (manuelle ou auto)</td></tr>
                            <tr><td>Statut</td><td>En attente / Rapproche</td></tr>
                        </tbody>
                    </table>

                    <div class="not-prose mt-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                        <p class="text-sm text-amber-800 dark:text-amber-300"><strong>Categorisation automatique :</strong> Selectionnez des transactions &rarr; Action &laquo;&nbsp;Appliquer les regles automatiques&nbsp;&raquo; &rarr; le systeme applique les regles d'imputation pour classer automatiquement les transactions.</p>
                    </div>
                </div>
                @endif

                {{-- SECTION: RH --}}
                @if($activeSection === 'hr')
                <div>
                    <h2>Ressources Humaines</h2>

                    <h3>8.1 Employes</h3>
                    <p><strong>Menu :</strong> RH &rarr; Employes</p>

                    <h4>Creer un employe</h4>
                    <p><strong>Onglet Informations personnelles :</strong> Photo, Matricule (auto), Prenom, Nom, Email, Telephone, Date de naissance, Adresse, N&deg; de securite sociale.</p>

                    <p><strong>Onglet Contrat & Poste :</strong></p>
                    <ul>
                        <li>Poste, Departement, Entrepot/site de rattachement</li>
                        <li><strong>Type de contrat :</strong> CDI, CDD, Interim, Stage, Apprentissage, Freelance</li>
                        <li>Date d'embauche, Date de fin (si CDD)</li>
                        <li>Heures hebdomadaires, Taux horaire, Salaire mensuel</li>
                        <li>Taux de commission (%)</li>
                        <li><strong>Statut :</strong> Actif, En conge, Termine</li>
                    </ul>

                    <p><strong>Acces systeme :</strong> Activez &laquo;&nbsp;Creer un compte utilisateur&nbsp;&raquo; pour donner a l'employe un acces a l'ERP avec un role defini et un mot de passe.</p>

                    <p><strong>Onglet Contact urgence & Banque :</strong> Contact d'urgence, coordonnees bancaires (IBAN, BIC) pour les virements de salaire.</p>

                    <h4>Actions sur un employe</h4>
                    <table>
                        <thead><tr><th>Action</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Pointer l'entree</td><td>Enregistre une arrivee</td></tr>
                            <tr><td>Pointer la sortie</td><td>Enregistre un depart</td></tr>
                            <tr><td>Activer le compte</td><td>Active l'acces utilisateur lie</td></tr>
                            <tr><td>Desactiver le compte</td><td>Bloque l'acces utilisateur</td></tr>
                        </tbody>
                    </table>

                    <h3>8.2 Planning</h3>
                    <p><strong>Menu :</strong> RH &rarr; Planning</p>
                    <p>Interface principale de gestion des horaires de l'equipe : grille <strong>Employes &times; Jours</strong> avec navigation semaine par semaine. Cliquez sur une case pour creer ou modifier un horaire.</p>

                    <h4>Modifier un horaire</h4>
                    <p>Heure de debut / fin (par tranches de 15 min), Duree de pause (0 a 2h), Type de vacation (Matin, Apres-midi, Soir, Nuit, Journee complete), Notes.</p>

                    <h4>Actions en masse</h4>
                    <table>
                        <thead><tr><th>Action</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Appliquer un modele</td><td>Selectionnez un modele &rarr; cochez les employes &rarr; horaires crees pour la semaine</td></tr>
                            <tr><td>Publier la semaine</td><td>Rend les horaires visibles par les employes</td></tr>
                            <tr><td>Dupliquer la semaine precedente</td><td>Copie les horaires de la semaine d'avant (non publies)</td></tr>
                        </tbody>
                    </table>

                    <h3>8.3 Modeles de planning</h3>
                    <p><strong>Menu :</strong> RH &rarr; Templates Planning</p>
                    <p>Modeles reutilisables d'horaires hebdomadaires : Nom, Description, et pour chaque jour (Lundi &rarr; Dimanche) : Heure de debut/fin, Duree de pause, Type de vacation. Total heures/semaine calcule automatiquement.</p>

                    <h3>8.4 Calendrier</h3>
                    <p><strong>Menu :</strong> RH &rarr; Calendrier</p>
                    <p>Vue calendrier interactive : Mois, Semaine, Jour, Liste. Plage 06h00-22h00, creneaux de 30 min.</p>
                    <ul>
                        <li><strong>Glisser-deposer</strong> pour deplacer un horaire</li>
                        <li><strong>Redimensionner</strong> pour modifier la duree</li>
                        <li><strong>Cliquer</strong> sur un creneau vide pour creer un horaire</li>
                        <li>Evenements <strong>colores par employe</strong></li>
                    </ul>

                    <h3>8.5 Mon Planning (espace employe)</h3>
                    <p><strong>Menu :</strong> RH &rarr; Mon Planning</p>
                    <p>Vue personnelle de l'employe connecte. Navigation semaine par semaine, affiche uniquement les horaires <strong>publies</strong>. Statistiques : total heures planifiees, nombre de jours travailles, comparaison avec heures contractuelles.</p>

                    <h3>8.6 Pointage</h3>
                    <p><strong>Menu :</strong> RH &rarr; Pointage</p>

                    <h4>Pointage manager</h4>
                    <p>Horloge en temps reel, liste de tous les employes actifs avec leur statut (Absent / Present / En pause / Parti). Actions : Pointer l'entree, Pointer la sortie, Debut de pause, Fin de pause. Saisie manuelle pour corrections.</p>

                    <h4>Pointage employe (self-service)</h4>
                    <p>Interface mobile avec verification anti-fraude en 4 etapes :</p>
                    <ol>
                        <li><strong>Selection du site</strong> : choisir l'entrepot/magasin</li>
                        <li><strong>Verification GPS</strong> (si active) : le navigateur verifie que l'employe est dans le rayon autorise</li>
                        <li><strong>Scan QR Code</strong> (si active) : scanner le QR code affiche a l'entree du site</li>
                        <li><strong>Confirmation</strong> : pointage enregistre avec heure, IP et distance au site</li>
                    </ol>

                    <div class="not-prose mt-4 mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-300"><strong>Anti-fraude :</strong> Le QR code change toutes les 5 minutes et le GPS verifie la proximite physique du site.</p>
                    </div>

                    <h3>8.7 QR Code de pointage</h3>
                    <p><strong>Menu :</strong> RH &rarr; QR Code Pointage (admins et managers)</p>
                    <p>Page a afficher sur un ecran a l'entree du site. Selectionnez l'entrepot, un QR code est genere avec un token rotatif. Le QR se rafraichit automatiquement toutes les 30 secondes.</p>

                    <h3>8.8 Conges</h3>
                    <p><strong>Menu :</strong> RH &rarr; Conges</p>

                    <h4>Types de conges</h4>
                    <p>Conges payes, Conge sans solde, Maladie, Maternite, Paternite, Autre.</p>

                    <h4>Demander un conge</h4>
                    <ol>
                        <li>Selectionnez l'<strong>employe</strong></li>
                        <li>Choisissez le <strong>type</strong> de conge</li>
                        <li><strong>Date de debut</strong> et <strong>Date de fin</strong></li>
                        <li>Nombre de <strong>jours ouvres</strong> calcule automatiquement (weekends exclus)</li>
                        <li>Saisissez le <strong>motif</strong></li>
                    </ol>

                    <h4>Cycle de vie</h4>
                    <div class="not-prose mb-4">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 px-3 py-1 font-medium">En attente</span>
                            <x-heroicon-s-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 px-3 py-1 font-medium">Approuve</span>
                            <span class="text-gray-400 mx-1">ou</span>
                            <span class="rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 px-3 py-1 font-medium">Refuse</span>
                        </div>
                    </div>

                    <h3>8.9 Commissions</h3>
                    <p>Accessible via le detail employe. Suivi des commissions commerciales : Employe, Vente liee, Periode, Montant de la vente, Taux de commission (%), Montant calcule automatiquement.</p>
                    <p>Cycle : En attente &rarr; Approuvee &rarr; Payee (ou Annulee).</p>
                </div>
                @endif

                {{-- SECTION: ADMINISTRATION --}}
                @if($activeSection === 'admin')
                <div>
                    <h2>Administration</h2>

                    <h3>9.1 Utilisateurs</h3>
                    <p><strong>Menu :</strong> Administration &rarr; Utilisateurs</p>
                    <p>Gestion des comptes d'acces : Nom, Email, Mot de passe (hashe automatiquement), Roles (badges colores).</p>

                    <div class="not-prose mt-4 mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                        <p class="text-sm text-blue-800 dark:text-blue-300"><strong>Multi-entreprise :</strong> Un meme utilisateur peut avoir des roles differents dans plusieurs entreprises.</p>
                    </div>

                    <h3>9.2 Roles & Permissions</h3>
                    <p><strong>Menu :</strong> Administration &rarr; Roles</p>

                    <h4>Roles par defaut</h4>
                    <table>
                        <thead><tr><th>Role</th><th>Acces type</th></tr></thead>
                        <tbody>
                            <tr><td><span class="font-semibold text-red-600 dark:text-red-400">Admin</span></td><td>Acces complet (ne peut pas etre supprime)</td></tr>
                            <tr><td><span class="font-semibold text-orange-600 dark:text-orange-400">Manager</span></td><td>Gestion operationnelle</td></tr>
                            <tr><td><span class="font-semibold text-blue-600 dark:text-blue-400">Vendeur</span></td><td>Ventes et clients</td></tr>
                            <tr><td><span class="font-semibold text-cyan-600 dark:text-cyan-400">Caissier</span></td><td>Caisse uniquement</td></tr>
                            <tr><td><span class="font-semibold text-green-600 dark:text-green-400">Comptable</span></td><td>Comptabilite et rapports</td></tr>
                        </tbody>
                    </table>

                    <h4>Creer un role personnalise</h4>
                    <ol>
                        <li><strong>Nom</strong> : le slug est genere automatiquement</li>
                        <li><strong>Description</strong></li>
                        <li><strong>Role par defaut</strong> : attribue automatiquement aux nouvelles invitations</li>
                        <li><strong>Permissions</strong> : cochez les actions autorisees par module</li>
                    </ol>

                    <h3>9.3 Invitations</h3>
                    <p><strong>Menu :</strong> Administration &rarr; Invitations</p>
                    <p>Invitez de nouveaux utilisateurs a rejoindre votre entreprise :</p>
                    <ol>
                        <li>Saisissez l'<strong>email</strong> du nouveau collaborateur</li>
                        <li>Choisissez le <strong>role</strong> a attribuer</li>
                        <li>Un email d'invitation est envoye avec un lien unique (valide 7 jours)</li>
                    </ol>

                    <table>
                        <thead><tr><th>Action</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Renvoyer</td><td>Renvoie l'email et prolonge la validite de 7 jours</td></tr>
                            <tr><td>Copier le lien</td><td>Copie le lien d'invitation dans le presse-papiers</td></tr>
                            <tr><td>Supprimer</td><td>Annule l'invitation</td></tr>
                        </tbody>
                    </table>

                    <h3>9.4 Historique des modifications</h3>
                    <p><strong>Menu :</strong> Administration &rarr; Historique des modifications</p>
                    <p>Journal d'audit <strong>immutable</strong> de toutes les actions effectuees dans le systeme : Date, Module (badge colore), Description, Type, Utilisateur, Evenement (Creation, Modification, Suppression).</p>

                    <div class="not-prose mt-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                        <p class="text-sm text-red-800 dark:text-red-300"><strong>Important :</strong> Ce journal est en lecture seule. Aucune entree ne peut etre modifiee ou supprimee (conformite audit).</p>
                    </div>
                </div>
                @endif

                {{-- SECTION: FACTURATION ELECTRONIQUE --}}
                @if($activeSection === 'einvoicing')
                <div>
                    <h2>Facturation electronique (PPF / Chorus Pro)</h2>

                    <h3>10.1 Assistant de configuration</h3>
                    <p><strong>Menu :</strong> Administration &rarr; Assistant facturation</p>
                    <p>Wizard en 5 etapes :</p>
                    <ol>
                        <li><strong>Introduction</strong> : informations sur l'obligation de facturation electronique (2026)</li>
                        <li><strong>Creation de compte</strong> : guide pour creer un compte Chorus Pro</li>
                        <li><strong>Identifiants</strong> : saisie du SIRET, login (TECH_xxx@cpro.fr) et mot de passe</li>
                        <li><strong>Test de connexion</strong> : verification que les identifiants fonctionnent</li>
                        <li><strong>Confirmation</strong> : activation de l'integration</li>
                    </ol>

                    <h3>10.2 Parametres PPF</h3>
                    <p><strong>Menu :</strong> Administration &rarr; Facturation electronique</p>
                    <table>
                        <thead><tr><th>Champ</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Actif</td><td>Activer/desactiver l'integration</td></tr>
                            <tr><td>SIRET fournisseur</td><td>Votre SIRET pour Chorus Pro</td></tr>
                            <tr><td>Login</td><td>Identifiant technique (format TECH_xxx@cpro.fr)</td></tr>
                            <tr><td>Mot de passe</td><td>Mot de passe Chorus Pro</td></tr>
                        </tbody>
                    </table>
                    <p><strong>Action :</strong> &laquo;&nbsp;Tester la connexion&nbsp;&raquo; &mdash; verifie l'authentification avec Chorus Pro.</p>

                    <h3>10.3 Envoyer une facture au PPF</h3>
                    <p>Depuis une vente validee (menu Ventes &rarr; Ventes) :</p>
                    <ol>
                        <li>Ouvrez une vente au statut <strong>Validee</strong></li>
                        <li>Cliquez sur <strong>Envoyer au PPF</strong></li>
                        <li>La facture est transmise a Chorus Pro au format Factur-X</li>
                    </ol>

                    <h4>Suivi du statut PPF</h4>
                    <table>
                        <thead><tr><th>Statut</th><th>Signification</th></tr></thead>
                        <tbody>
                            <tr><td><span class="text-blue-600 dark:text-blue-400 font-medium">Deposee</span></td><td>Facture deposee sur la plateforme</td></tr>
                            <tr><td><span class="text-indigo-600 dark:text-indigo-400 font-medium">Mise a disposition</span></td><td>Facture disponible pour le client</td></tr>
                            <tr><td><span class="text-cyan-600 dark:text-cyan-400 font-medium">Prise en charge</span></td><td>Facture recue par le client</td></tr>
                            <tr><td><span class="text-yellow-600 dark:text-yellow-400 font-medium">Mise en paiement</span></td><td>Paiement en cours</td></tr>
                            <tr><td><span class="text-green-600 dark:text-green-400 font-medium">Payee</span></td><td>Paiement effectue</td></tr>
                            <tr><td><span class="text-orange-600 dark:text-orange-400 font-medium">Suspendue</span></td><td>Facture en attente</td></tr>
                            <tr><td><span class="text-red-600 dark:text-red-400 font-medium">Rejetee</span></td><td>Facture refusee</td></tr>
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- SECTION: ANNEXES --}}
                @if($activeSection === 'appendix')
                <div>
                    <h2>Annexes & Glossaire</h2>

                    <h3>Raccourcis et astuces</h3>
                    <table>
                        <thead><tr><th>Astuce</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td>Creer a la volee</td><td>Le bouton &laquo;&nbsp;+&nbsp;&raquo; a cote des selecteurs Client/Fournisseur permet de creer sans quitter le formulaire</td></tr>
                            <tr><td>Copier</td><td>Cliquez sur les numeros de facture, references et codes pour les copier</td></tr>
                            <tr><td>Filtres combines</td><td>Tous les tableaux supportent la combinaison de plusieurs filtres</td></tr>
                            <tr><td>Tri</td><td>Cliquez sur les en-tetes de colonnes pour trier</td></tr>
                            <tr><td>Pagination</td><td>Changez le nombre de lignes par page en bas de chaque tableau</td></tr>
                            <tr><td>Lecteur code-barres</td><td>La caisse POS supporte les lecteurs USB/Bluetooth</td></tr>
                        </tbody>
                    </table>

                    <h3>Devises supportees</h3>
                    <table>
                        <thead><tr><th>Code</th><th>Devise</th></tr></thead>
                        <tbody>
                            <tr><td>XOF</td><td>Franc CFA (UEMOA)</td></tr>
                            <tr><td>XAF</td><td>Franc CFA (CEMAC)</td></tr>
                            <tr><td>EUR</td><td>Euro</td></tr>
                            <tr><td>USD</td><td>Dollar americain</td></tr>
                            <tr><td>GBP</td><td>Livre sterling</td></tr>
                            <tr><td>CHF</td><td>Franc suisse</td></tr>
                            <tr><td>CAD</td><td>Dollar canadien</td></tr>
                            <tr><td>AUD</td><td>Dollar australien</td></tr>
                            <tr><td>JPY</td><td>Yen japonais</td></tr>
                            <tr><td>CNY</td><td>Yuan chinois</td></tr>
                            <tr><td>INR</td><td>Roupie indienne</td></tr>
                            <tr><td>BRL</td><td>Real bresilien</td></tr>
                            <tr><td>MXN</td><td>Peso mexicain</td></tr>
                        </tbody>
                    </table>

                    <h3>Taux de TVA supportes</h3>
                    <table>
                        <thead><tr><th>Taux</th><th>Compte collectee</th><th>Compte deductible</th><th>Usage</th></tr></thead>
                        <tbody>
                            <tr><td>20,00%</td><td>445710</td><td>445660</td><td>Taux normal</td></tr>
                            <tr><td>10,00%</td><td>445712</td><td>445662</td><td>Taux intermediaire</td></tr>
                            <tr><td>5,50%</td><td>445711</td><td>445661</td><td>Taux reduit</td></tr>
                            <tr><td>2,10%</td><td>445713</td><td>445663</td><td>Taux super-reduit</td></tr>
                        </tbody>
                    </table>

                    <h3>Glossaire</h3>
                    <table>
                        <thead><tr><th>Terme</th><th>Definition</th></tr></thead>
                        <tbody>
                            <tr><td><strong>FEC</strong></td><td>Fichier des Ecritures Comptables &mdash; export obligatoire pour le fisc</td></tr>
                            <tr><td><strong>PCG</strong></td><td>Plan Comptable General &mdash; nomenclature francaise des comptes</td></tr>
                            <tr><td><strong>OD</strong></td><td>Operations Diverses &mdash; ecritures manuelles de correction</td></tr>
                            <tr><td><strong>TVA</strong></td><td>Taxe sur la Valeur Ajoutee</td></tr>
                            <tr><td><strong>HT</strong></td><td>Hors Taxes</td></tr>
                            <tr><td><strong>TTC</strong></td><td>Toutes Taxes Comprises</td></tr>
                            <tr><td><strong>PPF</strong></td><td>Portail Public de Facturation (Chorus Pro)</td></tr>
                            <tr><td><strong>Factur-X</strong></td><td>Format de facture electronique hybride (PDF + XML)</td></tr>
                            <tr><td><strong>Lettrage</strong></td><td>Rapprochement entre une facture et son paiement</td></tr>
                            <tr><td><strong>Contre-passation</strong></td><td>Ecriture inverse pour annuler une ecriture existante</td></tr>
                            <tr><td><strong>Avoir</strong></td><td>Facture negative annulant tout ou partie d'une vente</td></tr>
                            <tr><td><strong>SIREN</strong></td><td>Identifiant entreprise (9 chiffres)</td></tr>
                            <tr><td><strong>SIRET</strong></td><td>Identifiant etablissement (14 chiffres)</td></tr>
                            <tr><td><strong>IBAN</strong></td><td>International Bank Account Number</td></tr>
                            <tr><td><strong>URSSAF</strong></td><td>Organisme de recouvrement des cotisations sociales</td></tr>
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- TUTORIAL VIDEOS SECTION (shown for every section that has videos) --}}
                @php $sectionVideos = $this->getVideosForSection(); @endphp
                @if($sectionVideos->count() > 0)
                <div class="not-prose mt-10 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white m-0">Videos tutoriels</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 m-0">{{ $sectionVideos->count() }} video{{ $sectionVideos->count() > 1 ? 's' : '' }} disponible{{ $sectionVideos->count() > 1 ? 's' : '' }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($sectionVideos as $video)
                        <div x-data="{ open: false }" class="rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                            {{-- Video thumbnail / player --}}
                            <div class="relative">
                                <template x-if="!open">
                                    <button @click="open = true" class="relative w-full aspect-video bg-gray-100 dark:bg-gray-900 group cursor-pointer">
                                        @if($video->thumbnail_url)
                                            <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}" class="w-full h-full object-cover">
                                        @elseif($video->video_type === 'youtube' && preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $video->video_url, $m))
                                            <img src="https://img.youtube.com/vi/{{ $m[1] }}/hqdefault.jpg" alt="{{ $video->title }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="flex items-center justify-center w-full h-full">
                                                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        {{-- Play overlay --}}
                                        <div class="absolute inset-0 bg-black/20 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                                            <div class="w-16 h-16 rounded-full bg-white/90 dark:bg-gray-800/90 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                                <svg class="w-7 h-7 text-red-600 dark:text-red-400 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/>
                                                </svg>
                                            </div>
                                        </div>
                                        {{-- Duration badge --}}
                                        @if($video->formatted_duration)
                                        <div class="absolute bottom-2 right-2 bg-black/80 text-white text-xs font-medium px-2 py-1 rounded">
                                            {{ $video->formatted_duration }}
                                        </div>
                                        @endif
                                    </button>
                                </template>
                                <template x-if="open">
                                    <div class="w-full aspect-video">
                                        @if(in_array($video->video_type, ['youtube', 'vimeo']))
                                            <iframe
                                                src="{{ $video->embed_url }}?autoplay=1"
                                                class="w-full h-full"
                                                frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen
                                            ></iframe>
                                        @else
                                            <video controls autoplay class="w-full h-full">
                                                <source src="{{ $video->video_url }}" type="video/mp4">
                                                Votre navigateur ne supporte pas la lecture video.
                                            </video>
                                        @endif
                                    </div>
                                </template>
                            </div>
                            {{-- Info --}}
                            <div class="p-4">
                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm m-0">{{ $video->title }}</h4>
                                @if($video->description)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 m-0 line-clamp-2">{{ $video->description }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</x-filament-panels::page>

# 📘 FRECORP ERP — Guide d'Utilisation Complet

> **Version :** 2.x | **Date :** Février 2026  
> **URL :** https://test-erp.frecorp.fr

---

## Table des matières

1. [Premiers pas](#1-premiers-pas)
2. [Tableau de bord](#2-tableau-de-bord)
3. [Module Ventes](#3-module-ventes)
4. [Module Stocks & Achats](#4-module-stocks--achats)
5. [Module Caisse (POS)](#5-module-caisse-pos)
6. [Module Comptabilité](#6-module-comptabilité)
7. [Module Banque](#7-module-banque)
8. [Module Ressources Humaines (RH)](#8-module-ressources-humaines-rh)
9. [Administration](#9-administration)
10. [Facturation Électronique (PPF/Chorus Pro)](#10-facturation-électronique-ppfchorus-pro)
11. [Annexes](#11-annexes)

---

## 1. Premiers pas

### 1.1 Créer son entreprise

Lors de votre première connexion, vous devez créer votre entreprise :

1. Cliquez sur **« Enregistrer une entreprise »**
2. Saisissez votre **numéro SIREN** (9 chiffres) puis cliquez sur **Rechercher**
   - Le système interroge l'API gouvernementale (`recherche-entreprises.api.gouv.fr`) et remplit automatiquement le nom et l'adresse
3. Complétez les informations : **Email**, **Téléphone**
4. Validez — le système crée automatiquement :
   - L'entreprise avec un slug unique
   - Les rôles par défaut (Admin, Manager, Vendeur, Caissier, Comptable)
   - Les permissions associées
   - Votre compte est assigné au rôle **Admin**

### 1.2 Configurer le profil entreprise

Menu : **Profil de l'entreprise** (icône engrenage en bas à gauche)

| Champ | Description |
|-------|-------------|
| Nom de l'entreprise | Raison sociale |
| Email / Téléphone | Contact principal |
| Adresse | Siège social |
| Site web | URL publique |
| N° TVA Intra | Numéro de TVA intracommunautaire |
| SIREN / SIRET | Obligatoire pour la facturation électronique |
| Logo | Image affichée sur les factures et devis (upload) |
| Texte de pied de page | Mentions légales sur les documents |
| Devise | Parmi : XOF, XAF, USD, EUR, GBP, CHF, CAD, AUD, JPY, CNY, INR, BRL, MXN |

#### Activer/Désactiver les modules

Dans la section **Modules** du profil, activez uniquement ce dont vous avez besoin :

| Module | Fonctionnalités débloquées |
|--------|---------------------------|
| **Point de Vente (Caisse)** | Interface caissier, sessions de caisse, historique POS |
| **Gestion de Stock** | Entrepôts, transferts, inventaires, stock consolidé |
| **Ressources Humaines** | Employés, planning, pointage, congés, commissions |
| **Comptabilité** | Grand Livre, écritures, TVA, export FEC, règlements |
| **Banque** | Comptes bancaires, transactions, rapprochement |

> **Note :** Les menus de navigation s'adaptent automatiquement aux modules activés.

---

## 2. Tableau de bord

**Menu :** Tableau de bord (icône maison)

Le tableau de bord affiche une vue d'ensemble de votre activité avec les widgets suivants :

| Widget | Contenu |
|--------|---------|
| **Statistiques clés** | Chiffre d'affaires, nombre de ventes, panier moyen, etc. |
| **Actions rapides** | Boutons d'accès direct aux fonctions fréquentes |
| **Graphique des ventes** | Évolution du CA sur la période |
| **Statistiques commandes** | Volume et montants des commandes |
| **Graphique des devis** | Taux de conversion des devis |
| **Alertes de stock** | Produits en stock bas ou en rupture |
| **Vue entrepôts** | Résumé par entrepôt |
| **Résumé stock** | Vue consolidée des stocks |
| **Stats RH** | Effectifs, présences (si module RH activé) |
| **Graphique présences** | Taux de présence (si module RH activé) |
| **Résumé URSSAF** | Charges sociales à prévoir (si module RH activé) |
| **Résumé TVA** | TVA collectée vs déductible (si module comptabilité activé) |

---

## 3. Module Ventes

### 3.1 Clients

**Menu :** Ventes → Clients

#### Créer un client

1. Cliquez **« Nouveau Client »**
2. Remplissez les champs :
   - **Nom / Raison Sociale** (obligatoire)
   - **SIREN** (9 chiffres) — identifiant entreprise
   - **SIRET** (14 chiffres) — identifiant établissement
   - **N° TVA Intra** — pour les échanges intracommunautaires
   - **Email**, **Téléphone**
   - **Adresse**, **Code postal**, **Ville**, **Pays** (défaut : France)
   - **Code pays ISO** (défaut : FR) — utilisé pour la facturation électronique
3. Section **Notes** (optionnelle) : informations internes

#### Rechercher un client

- Utilisez la barre de recherche en haut du tableau
- Le tableau affiche : Nom, Email, Téléphone, Adresse
- Pagination : 10, 25, 50 ou 100 résultats par page

> **Astuce :** Vous pouvez créer un client directement depuis un formulaire de vente ou de devis grâce au bouton « + » à côté du sélecteur client.

---

### 3.2 Devis

**Menu :** Ventes → Devis

#### Cycle de vie d'un devis

```
Brouillon → Envoyé → Accepté → Converti en vente
                   → Refusé
                   → Expiré (automatique si date dépassée)
```

#### Créer un devis

1. Cliquez **« Nouveau Devis »**
2. **En-tête :**
   - Le **numéro de devis** est généré automatiquement
   - **Date du devis** : date du jour par défaut
   - **Valide jusqu'au** : +30 jours par défaut
   - **Client** : sélectionner ou créer à la volée
3. **Lignes d'articles :**
   - Cliquez **« Ajouter un article »**
   - Sélectionnez un **Produit** → le prix, la description et le taux de TVA se remplissent automatiquement
   - Modifiez la **Quantité**, le **Prix unitaire HT**, le taux de **TVA**, la **Remise %** si besoin
   - Le **Total HT** et **Total TTC** de chaque ligne se calculent en temps réel
4. **Totaux :**
   - **Remise globale** (montant) si applicable
   - Les totaux HT, TVA et TTC se calculent automatiquement
5. **Notes :**
   - **Notes internes** (non visibles par le client)
   - **Conditions générales** (pré-rempli : paiement à 30 jours)

#### Actions sur un devis

| Action | Condition | Description |
|--------|-----------|-------------|
| **Envoyer par email** | Tout statut | Envoie le PDF au client avec un message personnalisable. Passe le statut en « Envoyé » |
| **Copier le lien public** | Tout statut | Génère un lien URL unique que le client peut consulter sans connexion |
| **Accepter** | Statut = Envoyé | Passe le devis en « Accepté » |
| **Refuser** | Statut = Envoyé | Passe le devis en « Refusé » |
| **Convertir en vente** | Statut = Accepté | Crée une vente avec tous les articles du devis, redirige vers la vente |
| **Télécharger PDF** | Tout statut | Ouvre le PDF du devis dans un nouvel onglet |
| **Dupliquer** | Tout statut | Crée une copie du devis avec de nouvelles dates et le statut « Brouillon » |

---

### 3.3 Ventes / Factures

**Menu :** Ventes → Ventes

#### Cycle de vie d'une vente

```
En attente → Validée (stock déduit, écritures comptables générées)
          → Annulée
```

#### Créer une vente

1. Cliquez **« Nouvelle Vente »**
2. **En-tête :**
   - **N° de facture** : généré automatiquement (FAC-YYYY-NNNNN)
   - **Client** : sélectionner
   - **Entrepôt source** : d'où sera déduit le stock
   - **Mode de paiement** : Espèces, Carte bancaire, Virement SEPA, Chèque, Prélèvement SEPA, PayPal
   - **Compte bancaire** : requis quand la vente est validée
3. **Remise globale** : pourcentage appliqué sur le total
4. **Lignes d'articles** (visibles après sélection de l'entrepôt) :
   - **Produit** : filtré par stock disponible dans l'entrepôt sélectionné (affiche le stock)
   - **Stock disponible** : affiché en lecture seule
   - **Quantité** : ne peut pas dépasser le stock disponible
   - **Prix unitaire HT**, **Taux TVA**, **Catégorie TVA**
   - **Total TTC** : calculé automatiquement
5. Les **Totaux HT, TVA, TTC** se mettent à jour en temps réel

#### Validation de la vente

Quand vous passez le statut à **« Validée »** :
- Le **stock est déduit** de l'entrepôt source
- Les **écritures comptables** sont générées automatiquement (si module comptabilité activé) :
  - DÉBIT 411xxx (Client) : montant TTC
  - CRÉDIT 707xxx (Ventes) : montant HT par taux TVA
  - CRÉDIT 4457xx (TVA collectée) : TVA par taux
- Un **paiement** peut être enregistré automatiquement (POS)

#### Actions sur une vente validée

| Action | Description |
|--------|-------------|
| **Facture PDF** | Télécharge le PDF de la facture |
| **Aperçu PDF** | Prévisualise la facture dans un nouvel onglet |
| **Envoyer par email** | Envoie la facture PDF par email avec un message personnalisable |
| **Envoyer au PPF** | Transmet la facture à Chorus Pro (facturation électronique) |
| **Actualiser statut PPF** | Synchronise le statut depuis Chorus Pro |
| **Générer un avoir** | Crée une facture d'avoir négative, restaure le stock, contre-passe les écritures |

> **Important :** Une vente validée ne peut plus être modifiée ni supprimée (immutabilité comptable). Utilisez un **avoir** pour corriger.

---

### 3.4 Commandes récurrentes

**Menu :** Ventes → Commandes

Automatisez les ventes répétitives (abonnements, livraisons régulières).

#### Créer une commande récurrente

1. **Informations :**
   - **Référence** : auto-générée
   - **Nom** : description de la commande
   - **Client** : sélectionner
2. **Planification :**
   - **Fréquence** : Quotidienne, Hebdomadaire, Bimensuelle, Mensuelle, Trimestrielle, Annuelle
   - **Date de début / fin**
   - **Prochaine exécution** : calculée automatiquement
   - **Nombre max d'exécutions** (optionnel)
3. **Articles :** même système que les ventes
4. **Options :**
   - **Génération automatique** : crée la vente sans intervention
   - **Envoi automatique de facture** : envoie la facture par email après génération

#### Cycle de vie

```
Active ↔ En pause → Annulée / Terminée
```

| Action | Description |
|--------|-------------|
| **Exécuter maintenant** | Génère immédiatement une vente |
| **Mettre en pause** | Suspend les exécutions automatiques |
| **Reprendre** | Réactive la commande |
| **Annuler** | Arrête définitivement la commande |
| **Dupliquer** | Copie la commande avec compteurs remis à zéro |

---

### 3.5 Bons de livraison

**Menu :** Ventes → Bons de livraison

#### Cycle de vie

```
En attente → En préparation → Prêt → Expédié → Livré
                                              → Annulé
```

#### Créer un bon de livraison

1. **Lier à une vente** : sélectionner la vente → le client et l'adresse se remplissent
2. **Transporteur** et **N° de suivi**
3. **Articles** : produit, quantité commandée, quantité livrée

#### Actions de workflow

| Bouton | Effet |
|--------|-------|
| **En préparation** | Passe de « En attente » à « En préparation » |
| **Marquer prêt** | Passe en « Prêt » |
| **Expédier** | Demande le transporteur et n° de suivi, passe en « Expédié » |
| **Marquer livré** | Passe en « Livré » |
| **Télécharger PDF** | Génère le bon de livraison au format PDF |

---

## 4. Module Stocks & Achats

### 4.1 Produits

**Menu :** Stocks & Achats → Produits

#### Créer un produit

1. **Informations générales :**
   - **Nom** (obligatoire)
   - **Code** : généré automatiquement, non modifiable après création
   - **Type de code-barres** : Code 128 par défaut
   - **Description**
2. **Prix & TVA :**
   - Activez **« Prix saisis en TTC »** pour basculer entre saisie HT et TTC
   - **Achat :** Prix d'achat HT ou TTC, Taux TVA achat
   - **Vente :** Prix de vente HT ou TTC, Taux TVA vente
   - **Catégorie TVA** (Chorus Pro) : S (Standard), Z (Zéro), E (Exonéré), AE (Autoliquidation), etc.
   - **Indicateurs de marge :** marge brute €, taux de marge %, taux de marque % — affichés en vert (positif) ou rouge (négatif)
3. **Stock :**
   - **Stock initial** (à la création seulement)
   - **Unité** : pièce, kg, litre, mètre, boîte, carton, palette, lot
   - **Stock minimum** : seuil d'alerte
4. **Fournisseur** : sélectionner ou créer

#### Actions sur les produits

| Action | Description |
|--------|-------------|
| **Régénérer le code** | Admin uniquement, irréversible — génère un nouveau code produit |
| **Imprimer étiquettes** | Définir la quantité, le nombre de colonnes (2/3/4), afficher ou masquer le prix |

> **Impression en masse :** Sélectionnez plusieurs produits dans le tableau → Action « Imprimer étiquettes » → définissez la quantité par produit.

---

### 4.2 Fournisseurs

**Menu :** Stocks & Achats → Fournisseurs

CRUD simple : Nom, Email, Téléphone, Adresse, Ville, Pays, Notes.

---

### 4.3 Achats

**Menu :** Stocks & Achats → Achats

Fonctionne en miroir des ventes :

1. **En-tête :** N° facture (auto), Fournisseur, Entrepôt de réception, Mode de paiement
2. **Articles :** Produit (remplit automatiquement le prix d'achat et la TVA), Quantité, Prix unitaire HT, Taux TVA
3. **Remise globale** et **Totaux** (HT, TVA déductible, TTC) calculés automatiquement

#### Validation

Quand l'achat passe en **« Validé »** :
- Le **stock est ajouté** dans l'entrepôt de réception
- Les **écritures comptables** sont générées :
  - CRÉDIT 401xxx (Fournisseur) : montant TTC
  - DÉBIT 607xxx (Achats) : montant HT
  - DÉBIT 4456xx (TVA déductible) : TVA

| Action | Description |
|--------|-------------|
| **Facture PDF** | Télécharge la facture d'achat |
| **Aperçu PDF** | Prévisualise |
| **Envoyer par email** | Envoie la facture au fournisseur |

---

### 4.4 Entrepôts

**Menu :** Stocks & Achats → Entrepôts

> Nécessite le module **Stock** activé.

#### Créer un entrepôt

1. **Général :**
   - **Code** (unique), **Nom**
   - **Type** : Entrepôt, Magasin, Dépôt fournisseur, Dépôt client
   - **Responsable** (utilisateur)
2. **Adresse :** Adresse, Ville, Code postal, Pays
3. **Contact :** Téléphone, Email
4. **Géolocalisation & Pointage :**
   - **Latitude / Longitude** : cliquez « Obtenir ma position » pour remplir automatiquement
   - **Rayon GPS** (10-1 000 m) : zone autorisée pour le pointage des employés
   - Aperçu **OpenStreetMap** de la position
   - Activer **Vérification GPS requise** et/ou **Vérification QR code requise**
5. **Options :**
   - **Entrepôt par défaut** : utilisé par défaut dans les ventes/achats
   - **Point de vente** : disponible comme caisse POS
   - **Autoriser stock négatif**
   - **Actif** : activer/désactiver

#### Section détail (vue)

- **Statistiques** : nombre de produits, valeur totale du stock, produits en stock bas, nombre d'emplacements
- **Onglets relations :** Emplacements, Produits, Mouvements de stock

---

### 4.5 Transferts de stock

**Menu :** Stocks & Achats → Transferts

> Nécessite le module **Stock** activé. Un badge jaune indique le nombre de transferts en cours.

#### Cycle de vie

```
Brouillon → En attente → Approuvé → En transit → Partiel / Terminé
                                                → Annulé
```

#### Créer un transfert

1. **Référence** : auto-générée
2. **Entrepôt source** et **Entrepôt destination** (ne peut pas être le même)
3. **Date de transfert** et **Date d'arrivée prévue**
4. **Articles :**
   - **Produit** : filtré par stock disponible dans l'entrepôt source
   - **Stock disponible** : affiché
   - **Quantité demandée**
   - **Coût unitaire** : prix d'achat du produit (automatique)
   - **N° de lot** et **Date d'expiration** (optionnels)
5. **Expédition** (optionnel) : Transporteur, N° de suivi

#### Actions

| Action | Condition | Effet |
|--------|-----------|-------|
| **Approuver** | En attente | Valide la demande de transfert |
| **Expédier** | Approuvé | Déduit le stock de l'entrepôt source, passe en « En transit » |
| **Recevoir** | En transit | Page dédiée de réception : saisie des quantités reçues |
| **Annuler** | Non terminé | Annule avec saisie du motif obligatoire |

> **Page de réception :** Interface dédiée permettant de saisir la quantité effectivement reçue pour chaque article (gestion des réceptions partielles avec suivi du pourcentage).

---

### 4.6 Inventaires

**Menu :** Stocks & Achats → Inventaires

> Nécessite le module **Stock** activé.

#### Cycle de vie

```
Brouillon → En cours → En attente de validation → Validé
                                                → Annulé
```

#### Types d'inventaire

| Type | Description |
|------|-------------|
| **Complet** | Tous les produits de l'entrepôt |
| **Partiel** | Sélection de produits |
| **Tournant (cycle)** | Par rotation sur une catégorie |

#### Processus

1. **Créer** l'inventaire : choisir l'entrepôt, le type, la date
2. **Démarrer** : passe en « En cours » → accès à la page de comptage
3. **Compter** : page dédiée où vous saisissez les quantités physiques pour chaque produit
4. **Valider** : applique les ajustements de stock (irréversible)
   - Les écarts (quantité comptée - quantité théorique) sont calculés
   - La valeur de la différence est affichée (en vert ou rouge)

#### Indicateurs

- **Progression** : % de produits comptés
- **Écarts** : nombre de produits avec différence (badge rouge si > 0)
- **Valeur de la différence** : impact financier des écarts

---

### 4.7 Stock consolidé

**Menu :** Stocks & Achats → Stock consolidé

> Nécessite le module **Stock** activé.

Vue transversale de tout le stock, tous entrepôts confondus :

| Colonne | Description |
|---------|-------------|
| Code | Code produit (copiable) |
| Produit | Nom du produit |
| Unité | Unité de mesure |
| Stock total | Somme de tous les entrepôts |
| Quantité réservée | Stock engagé (commandes en cours) |
| Stock disponible | = Total − Réservé |
| Nb entrepôts | Dans combien d'entrepôts le produit est présent |
| Prix d'achat | Dernier prix d'achat |
| Valeur du stock | Stock × Prix d'achat |
| Statut | Normal (vert) / Bas (orange) / Rupture (rouge) |

**Filtres :** Par entrepôt, Stock bas uniquement, Rupture uniquement

**Actions :**
- **Voir détails** : popup avec la ventilation par entrepôt
- **Transférer** : lien direct vers la création de transfert

---

## 5. Module Caisse (POS)

> Nécessite le module **Point de Vente** activé. Accessible depuis le panneau « Caisse » (menu latéral séparé).

### 5.1 Sessions de caisse

**Menu Caisse :** Session de caisse

Chaque journée de caisse commence par l'ouverture d'une session :

#### Ouvrir une session

1. Saisissez le **montant de caisse d'ouverture** (fond de caisse)
2. Validez → la session est active

#### Pendant la session

Statistiques en temps réel :
- **Total des ventes** de la session
- Ventilation : Espèces, Carte, Mobile, Autre
- **Montant d'ouverture** et **Montant attendu** (ouverture + espèces)

#### Fermer une session

1. Saisissez le **montant de caisse de fermeture** (comptage physique)
2. Ajoutez des **notes** si nécessaire
3. Le système calcule automatiquement la **différence** :
   - **Équilibrée** (vert) : le compte est bon
   - **Excédent** (bleu) : plus d'argent que prévu
   - **Déficit** (rouge) : moins d'argent que prévu

#### Historique

Les 10 dernières sessions fermées sont affichées avec tous les détails.

---

### 5.2 Interface de vente (Caisse)

**Menu Caisse :** Caisse

> **Prérequis :** Une session de caisse doit être ouverte.

#### Encaisser une vente

1. **Chercher un produit** par nom, code ou code-barres (lecteur de code-barres supporté)
2. Le produit s'ajoute au panier avec la quantité 1
3. Modifiez la **quantité** si besoin (le stock disponible est vérifié en temps réel)
4. **Sélectionnez un client** ou laissez « Client comptoir » (créé automatiquement pour les ventes sans client identifié)
5. Choisissez le **mode de paiement** : Espèces, Carte, Mobile, Autre
6. **Validez la vente** → le stock est déduit, la session de caisse est mise à jour

> **Sécurité :** Le système utilise un verrouillage pessimiste (`lockForUpdate`) pour garantir la cohérence du stock même en cas de ventes simultanées.

---

### 5.3 Historique des ventes

**Menu Caisse :** Historique

- Affiche les **50 dernières ventes du jour** avec le détail des articles
- **Annuler une vente** : restaure automatiquement le stock et met à jour la session de caisse
- **Voir les détails** : visualisation complète de la vente

---

## 6. Module Comptabilité

> Nécessite le module **Comptabilité** activé.

### 6.1 Paramètres comptables

**Menu :** Comptabilité → Paramètres Comptables

Configuration obligatoire avant d'utiliser la comptabilité :

#### Régime fiscal

| Paramètre | Options | Description |
|-----------|---------|-------------|
| **Franchise en base de TVA** | Oui / Non | Si activé (Art. 293 B du CGI), aucune TVA n'est calculée |
| **Régime de TVA** | Sur les débits / Sur les encaissements | Débits = TVA exigible à la facturation. Encaissements = TVA exigible au paiement |

> **Régime encaissements :** La TVA est d'abord comptabilisée dans un compte d'attente (44574x), puis basculée vers le compte de TVA collectée (4457x) à chaque paiement reçu, au prorata du montant encaissé.

#### Plan Comptable Général (PCG)

Comptes par défaut (modifiables) :

| Compte | N° par défaut | Description |
|--------|---------------|-------------|
| Clients | 411000 | Créances clients |
| Fournisseurs | 401000 | Dettes fournisseurs |
| Banque | 512000 | Banque |
| Caisse | 530000 | Caisse |
| Ventes | 707000 | Ventes de marchandises |
| Achats | 607000 | Achats de marchandises |
| Remises accordées | 709000 | RRR accordés |
| Remises obtenues | 609000 | RRR obtenus |
| TVA collectée | 445710 | TVA collectée (ventes) |
| TVA déductible | 445660 | TVA déductible (achats) |

#### Codes journaux

| Code | Libellé |
|------|---------|
| VTE | Journal des Ventes |
| ACH | Journal des Achats |
| BQ | Journal de Banque |
| CAI | Journal de Caisse |
| OD | Opérations Diverses |

---

### 6.2 Catégories comptables

**Menu :** Comptabilité → Catégories comptables

Catégories pour classer vos opérations :
- **Nom**, **Type** (Produit / Charge), **Couleur**
- **Hiérarchie** : catégorie parente possible
- **Système** : catégories prédéfinies non modifiables

---

### 6.3 Règles d'imputation automatique

**Menu :** Comptabilité → Règles d'imputation

Classement automatique des transactions bancaires par motif :

| Champ | Description |
|-------|-------------|
| Nom | Nom de la règle |
| Type de condition | Contient, Commence par, Finit par, Exact |
| Valeur | Le texte à chercher dans le libellé (ex: AMAZON, LOYER) |
| Catégorie | Catégorie comptable à appliquer |
| Priorité | En cas de conflit, la priorité la plus haute gagne |
| Actif | Activer/désactiver la règle |

**Exemples :**

| Règle | Condition | Valeur | Catégorie |
|-------|-----------|--------|-----------|
| Amazon | Contient | AMAZON | Achats fournitures |
| Loyer | Contient | LOYER | Charges locatives |
| Orange | Commence par | ORANGE | Télécom |

---

### 6.4 Grand Livre

**Menu :** Comptabilité → Grand Livre

Le Grand Livre est le registre central de toutes les écritures comptables. 

> **Immutabilité :** Les écritures sont en lecture seule (conformité FEC). Elles ne peuvent être ni modifiées ni supprimées. Seul le **lettrage** est modifiable.

#### Colonnes

| Colonne | Description |
|---------|-------------|
| Date | Date de l'écriture |
| Journal | VTE (vert), ACH (orange), BQ (bleu), CAI (gris), OD (violet) |
| N° Pièce | Référence de la pièce justificative |
| Compte | N° de compte PCG + auxiliaire |
| Libellé | Description de l'opération |
| Débit / Crédit | Montants (avec totaux en pied de tableau) |
| Lettrage | Code de rapprochement |
| Verrouillée | Cadenas (toujours verrouillé) |

#### Filtres

- **Journal** : filtrer par type d'écriture
- **Classe de compte** : Classe 4 (Tiers), 5 (Trésorerie), 6 (Charges), 7 (Produits)
- **Période** : date de début et fin
- **Non lettrées uniquement**

#### Actions

| Action | Description |
|--------|-------------|
| **Lettrer** (en masse) | Sélectionnez plusieurs écritures → vérification que Débits = Crédits → attribuer un code de lettrage |
| **Reclasser** | Crée une écriture OD pour déplacer un montant d'un compte vers un autre (avec choix de date et motif) |

---

### 6.5 Écritures OD (Opérations Diverses)

**Menu :** Comptabilité → Écritures OD

Pour les corrections, reclassements et régularisations manuelles.

#### Créer une écriture OD

1. **En-tête :**
   - **Date de l'écriture**
   - **Journal** : OD (Opérations Diverses), AN (À Nouveau), EX (Extourne)
   - **N° de pièce** : auto-généré si laissé vide
   - **Motif** : description de l'opération
2. **Lignes (minimum 2) :**
   - **N° Compte** : validé au format PCG (6-10 chiffres, commence par 1-9)
   - **Auxiliaire** (optionnel)
   - **Libellé**
   - **Débit** et **Crédit**
3. **Validation :** le total des débits doit être **strictement égal** au total des crédits (tolérance : 0,01 €)

> **Modèle Reclassement :** Cliquez sur le bouton « Modèle Reclassement » pour pré-remplir un formulaire type avec 2 lignes (contre-passation + imputation).

---

### 6.6 Balance Générale

**Menu :** Comptabilité → Balance Générale

Vue synthétique de tous les comptes avec :

| Colonne | Description |
|---------|-------------|
| Compte | N° de compte (coloré par classe) |
| Libellé | Nom du compte (automatique depuis le PCG) |
| Total Débit | Somme de tous les débits |
| Total Crédit | Somme de tous les crédits |
| Solde Débiteur | Si Débit > Crédit |
| Solde Créditeur | Si Crédit > Débit |

- **Filtre par période** : restreindre à une date de début/fin
- Regroupement par **classe PCG** (1-Capitaux, 2-Immobilisations, 3-Stocks, 4-Tiers, 5-Trésorerie, 6-Charges, 7-Produits)
- **Vérification d'équilibre** automatique (total débits = total crédits)
- Données **en cache** (5 minutes) pour la performance, invalidées à chaque nouvelle écriture

---

### 6.7 Rapports & TVA

**Menu :** Comptabilité → Rapports & TVA

Rapport de TVA pour la préparation de la déclaration CA3 :

1. Choisissez la **période** : Mois, Trimestre, Année, ou Personnalisée
2. Le rapport affiche :
   - **Chiffre d'affaires** ventilé par mode de paiement
   - **Ventes HT / TTC** avec détail de la TVA collectée par taux (20%, 10%, 5,5%, 2,1%)
   - **Achats HT / TTC** avec détail de la TVA déductible par taux
   - **TVA nette** à reverser (ou crédit de TVA)
   - **Résultat** (bénéfice / perte)
3. **Export PDF** : téléchargez le rapport TVA complet

---

### 6.8 Journal d'Audit

**Menu :** Comptabilité → Journal d'Audit

Contrôle de santé automatique de votre comptabilité :

#### Piliers de l'audit

| Pilier | Ce qui est vérifié |
|--------|-------------------|
| **Intégrité Ventes** | Total des ventes = Total classe 7 (comptes de produits) |
| **Intégrité Achats** | Total des achats = Total classe 6 (comptes de charges) |
| **Séquences** | Continuité des numéros FEC + détection des trous dans les numéros de factures |
| **Cohérence TVA** | TVA théorique vs TVA comptabilisée (gère aussi le régime encaissements et TVA en attente) |
| **Anomalies** | Ventes/achats sans écritures, pièces déséquilibrées, paiements non lettrés |
| **Lettrage** | % de comptes client/fournisseur rapprochés |

#### Score de santé

Score sur 100 points :
- 30 pts : Intégrité ventes
- 20 pts : Intégrité achats
- 25 pts : Séquences
- 25 pts : TVA

**Actions :**
- **Rafraîchir** : recalcule l'audit (en cache 5 minutes)
- **Télécharger le certificat d'intégrité** : PDF attestant la cohérence de votre comptabilité

---

### 6.9 Export Comptable (FEC)

**Menu :** Comptabilité → Export Comptable

Le **Fichier des Écritures Comptables** (FEC) est obligatoire pour toute entreprise française en cas de contrôle fiscal.

1. Sélectionnez la **période** (date de début / fin)
2. Choisissez le **format** :
   - **FEC** : format réglementaire (séparateur pipe `|`, virgule décimale)
   - **CSV** : format tabuleur (séparateur point-virgule, BOM UTF-8 pour Excel)
3. **Prévisualiser** : affiche les 100 premières lignes avec les en-têtes FEC
4. **Exporter** : télécharge le fichier

#### Colonnes du FEC

JournalCode | JournalLib | EcritureNum | EcritureDate | CompteNum | CompteLib | CompAuxNum | CompAuxLib | PieceRef | PieceDate | EcritureLib | Debit | Credit | EcritureLet | DateLet | ValidDate | Montantdevise | Idevise

---

### 6.10 Règlements

**Menu :** Comptabilité → Règlements

Enregistrement des paiements reçus des clients ou versés aux fournisseurs.

#### Créer un règlement

1. **Type** : Client (vente) ou Fournisseur (achat)
2. **Document** : sélectionnez la vente ou l'achat concerné (seuls les documents validés avec solde restant sont affichés)
3. **Montant** : calculé automatiquement comme le reste à payer
4. **Mode de paiement** : Espèces (530000), Carte bancaire (512000), Virement (512000), Chèque (511200), Mobile (512000)
5. **Date**, **Référence** (n° chèque, réf. virement...), **Notes**

#### Écritures générées automatiquement

**Paiement client :**
- DÉBIT 512xxx/530xxx (Trésorerie) : montant payé
- CRÉDIT 411xxx (Client) : montant payé
- + Bascule TVA si régime encaissements (44574x → 4457x)

**Paiement fournisseur :**
- DÉBIT 401xxx (Fournisseur) : montant payé
- CRÉDIT 512xxx/530xxx (Trésorerie) : montant payé
- + Bascule TVA déductible si régime encaissements (44586x → 4456x)

> Le **lettrage automatique** rapproche l'écriture de paiement avec l'écriture d'origine (vente/achat).

---

### 6.11 Centre de Rapports

**Menu :** Comptabilité → Centre de Rapports

Centralise tous les rapports PDF téléchargeables :

| Rapport | Paramètres | Description |
|---------|------------|-------------|
| **État du stock** | Entrepôt, Stock bas uniquement | Liste des produits avec quantités et valeurs |
| **Export inventaire CSV** | — | Export tabulaire du stock |
| **Bilan comptable** | Date début / fin | Synthèse financière : CA, achats, marge, TVA, résultat, évolution mensuelle, top clients/fournisseurs |
| **Journal des ventes** | Date début / fin | Détail de toutes les ventes validées |
| **Journal des achats** | Date début / fin | Détail de tous les achats validés |

---

## 7. Module Banque

> Nécessite le module **Comptabilité** ou **Banque** activé.

### 7.1 Comptes bancaires

**Menu :** Comptabilité → Comptes bancaires

| Champ | Description |
|-------|-------------|
| Nom | Nom du compte (ex: « Compte courant pro ») |
| Banque | Nom de l'établissement |
| N° de compte / IBAN | Identifiant bancaire |
| Devise | EUR par défaut |
| Solde initial | Solde à l'ouverture |
| Solde actuel | Calculé automatiquement |
| Actif | Activer/désactiver |

---

### 7.2 Transactions bancaires

**Menu :** Comptabilité → Transactions

| Champ | Description |
|-------|-------------|
| Compte bancaire | Sélectionner le compte |
| Date | Date de l'opération |
| Montant | Positif |
| Type | Crédit (entrée) ou Débit (sortie) |
| Libellé | Description de l'opération |
| Référence | Référence bancaire |
| Catégorie comptable | Classification (manuelle ou automatique) |
| Statut | En attente / Rapproché |

#### Catégorisation automatique

Sélectionnez des transactions → Action « **Appliquer les règles automatiques** » → le système applique les règles d'imputation (section 6.3) pour classer automatiquement les transactions par catégorie.

---

## 8. Module Ressources Humaines (RH)

> Nécessite le module **Ressources Humaines** activé.

### 8.1 Employés

**Menu :** RH → Employés

#### Créer un employé

**Onglet « Informations personnelles » :**
- Photo (avatar), Matricule (auto), Prénom, Nom, Email, Téléphone
- Date de naissance, Adresse, Ville, Code postal, Pays
- N° de sécurité sociale

**Onglet « Contrat & Poste » :**
- Poste, Département, Entrepôt/site de rattachement
- **Type de contrat** : CDI, CDD, Intérim, Stage, Apprentissage, Freelance
- Date d'embauche, Date de fin de contrat (si CDD)
- Heures hebdomadaires, Taux horaire, Salaire mensuel
- Taux de commission (%)
- **Statut** : Actif, En congé, Terminé

**Accès système :** Activez « Créer un compte utilisateur » pour donner à l'employé un accès à l'ERP avec un rôle défini et un mot de passe.

**Onglet « Contact urgence & Banque » :**
- Contact d'urgence (nom, téléphone)
- Coordonnées bancaires (IBAN, BIC) pour les virements de salaire

**Onglet « Notes » :** Notes internes libres

#### Actions sur un employé

| Action | Description |
|--------|-------------|
| **Pointer l'entrée** | Enregistre une arrivée |
| **Pointer la sortie** | Enregistre un départ |
| **Activer le compte** | Active l'accès utilisateur lié |
| **Désactiver le compte** | Bloque l'accès utilisateur |

#### Relations

- **Présences** : historique de pointage
- **Documents** : fichiers RH attachés
- **Commissions** : historique des commissions

---

### 8.2 Planning

**Menu :** RH → Planning

Interface principale de gestion des horaires de l'équipe.

#### Vue grille semaine

Grille **Employés × Jours** montrant les horaires de chaque employé :
- Navigation semaine par semaine (précédent / suivant / aujourd'hui)
- Cliquez sur une case pour **créer ou modifier** un horaire

#### Modifier un horaire

| Champ | Description |
|-------|-------------|
| Heure de début | Par tranches de 15 minutes |
| Heure de fin | Par tranches de 15 minutes |
| Durée de pause | 0 à 2h |
| Type de vacation | Matin, Après-midi, Soir, Nuit, Journée complète |
| Notes | Informations complémentaires |

#### Actions en masse

| Action | Description |
|--------|-------------|
| **Appliquer un modèle** | Sélectionnez un modèle de planning → cochez les employés → les horaires sont créés pour toute la semaine |
| **Publier la semaine** | Rend tous les horaires visibles par les employés (dans « Mon Planning ») |
| **Dupliquer la semaine précédente** | Copie les horaires de la semaine d'avant (non publiés) |

---

### 8.3 Modèles de planning

**Menu :** RH → Templates Planning

Modèles réutilisables d'horaires hebdomadaires :

- **Nom** et **Description**
- Pour chaque jour (Lundi → Dimanche) :
  - Heure de début / fin
  - Durée de pause
  - Type de vacation
- **Modèle par défaut** : appliqué automatiquement aux nouveaux employés
- **Total heures/semaine** : calculé automatiquement

---

### 8.4 Calendrier

**Menu :** RH → Calendrier

Vue calendrier interactive (FullCalendar.js) :

- **Vues** : Mois, Semaine, Jour, Liste
- **Premier jour** : Lundi
- **Plage horaire** : 06h00 - 22h00, créneaux de 30 min
- **Fonctionnalités :**
  - **Glisser-déposer** des événements pour déplacer un horaire
  - **Redimensionner** pour modifier la durée
  - **Cliquer** sur un créneau vide pour créer un horaire
  - **Cliquer** sur un événement pour le modifier ou le supprimer
  - Les événements sont **colorés par employé** (10 couleurs automatiques ou couleur personnalisée)
  - Gère les horaires **ponctuels** et **récurrents** (les récurrents sont développés en événements individuels)

---

### 8.5 Mon Planning (espace employé)

**Menu :** RH → Mon Planning

Vue personnelle de l'employé connecté :

- Navigation semaine par semaine
- Affiche uniquement les horaires **publiés** (l'employé ne voit pas les brouillons)
- **Statistiques hebdomadaires :**
  - Total des heures planifiées
  - Nombre de jours travaillés
  - Comparaison avec les heures contractuelles
- **Notifications :** système de notifications pour les changements de planning (avec marquer comme lu)

---

### 8.6 Pointage (manager)

**Menu :** RH → Pointage

Interface pour que le responsable gère les présences de l'équipe :

- **Horloge en temps réel**
- Liste de tous les employés actifs avec leur **statut actuel** :
  - 🔴 Absent | 🟢 Présent | 🟡 En pause | ⚫ Parti
- **Actions par employé :**
  - Pointer l'entrée
  - Pointer la sortie
  - Début de pause
  - Fin de pause
- **Saisie manuelle** : formulaire pour corriger ou ajouter un pointage (employé, date, heure d'entrée, heure de sortie, notes)

---

### 8.7 Pointage employé (self-service)

Interface mobile de pointage pour les employés, avec vérification anti-fraude :

#### Processus en 4 étapes

1. **Sélection du site** : choisir l'entrepôt/magasin
2. **Vérification GPS** (si activé sur le site) : le navigateur demande la géolocalisation, le système vérifie que l'employé est dans le rayon autorisé
3. **Scan QR Code** (si activé sur le site) : scanner le QR code affiché à l'entrée du site, le système valide le token
4. **Confirmation** : le pointage est enregistré avec l'heure, l'adresse IP et la distance au site

> **Anti-fraude :** Le QR code change toutes les 5 minutes et le GPS vérifie la proximité physique du site.

---

### 8.8 QR Code de pointage

**Menu :** RH → QR Code Pointage

> Réservé aux administrateurs et managers.

Page à afficher sur un écran à l'entrée du site :

1. Sélectionnez l'**entrepôt** (uniquement ceux avec le QR activé)
2. Un QR code est généré avec un **token rotatif** (validité configurable, 5 min par défaut)
3. Le QR se **rafraîchit automatiquement** toutes les 30 secondes
4. Bouton de rafraîchissement manuel disponible

---

### 8.9 Logs de pointage

> Menu masqué, accessible via le détail employé.

Journal complet et **en lecture seule** de toutes les tentatives de pointage :

| Colonne | Description |
|---------|-------------|
| Date/Heure | Horodatage précis |
| Employé | Nom |
| Site | Entrepôt/magasin |
| Action | Entrée / Sortie / Début pause / Fin pause |
| Statut | Succès (vert) / Échec (rouge) |
| Raison d'échec | Si échec : hors zone GPS, QR invalide, etc. |
| Distance | Distance en mètres par rapport au site |
| QR valide | Oui / Non |
| Adresse IP | IP de l'appareil |

---

### 8.10 Congés

**Menu :** RH → Congés

> Un badge orange dans le menu indique le nombre de demandes en attente.

#### Types de congés

| Type | Description |
|------|-------------|
| Congés payés | Congés annuels rémunérés |
| Congé sans solde | Non rémunéré |
| Maladie | Arrêt maladie |
| Maternité | Congé maternité |
| Paternité | Congé paternité |
| Autre | Autre motif |

#### Demander un congé

1. Sélectionnez l'**employé** (ou vous-même)
2. Choisissez le **type** de congé
3. **Date de début** et **Date de fin**
4. Le nombre de **jours ouvrés** est calculé automatiquement (weekends exclus)
5. Saisissez le **motif**

#### Cycle de vie

```
En attente → Approuvé ✓
          → Refusé ✗ (avec motif obligatoire)
          → Annulé
```

| Action | Condition | Effet |
|--------|-----------|-------|
| **Approuver** | En attente | Valide la demande, enregistre l'approbateur |
| **Refuser** | En attente | Refuse avec saisie du motif obligatoire |
| **Modifier** | En attente | Seules les demandes en attente sont modifiables |

---

### 8.11 Commissions

> Menu masqué — accessible via le détail employé.

Suivi des commissions commerciales par employé :

| Champ | Description |
|-------|-------------|
| Employé | Le bénéficiaire |
| Vente liée | La vente déclenchant la commission (optionnel) |
| Période | Début et fin de la période de calcul |
| Montant de la vente | Base de calcul |
| Taux de commission | En % |
| Montant de la commission | Calculé automatiquement (taux × montant) |

#### Cycle de vie

```
En attente → Approuvée → Payée
          → Annulée
```

---

## 9. Administration

### 9.1 Utilisateurs

**Menu :** Administration → Utilisateurs

Gestion des comptes d'accès à l'ERP :

| Champ | Description |
|-------|-------------|
| Nom | Nom complet |
| Email | Adresse de connexion |
| Mot de passe | Hashé automatiquement |
| Rôles | Rôles attribués pour l'entreprise en cours (badges colorés) |

> **Multi-entreprise :** Un même utilisateur peut avoir des rôles différents dans plusieurs entreprises.

---

### 9.2 Rôles & Permissions

**Menu :** Administration → Rôles

Le système RBAC (Role-Based Access Control) contrôle l'accès à chaque fonctionnalité :

#### Rôles par défaut

| Rôle | Couleur | Accès type |
|------|---------|------------|
| **Admin** | Rouge | Accès complet (ne peut pas être supprimé) |
| **Manager** | Orange | Gestion opérationnelle |
| **Vendeur** | Bleu | Ventes et clients |
| **Caissier** | Bleu clair | Caisse uniquement |
| **Comptable** | Vert | Comptabilité et rapports |

#### Créer un rôle personnalisé

1. **Nom** : le slug est généré automatiquement
2. **Description**
3. **Rôle par défaut** : attribué automatiquement aux nouvelles invitations
4. **Permissions** : cochez les actions autorisées par module (ex: produits.view, produits.create, produits.update, produits.delete)

> **Modules de permissions :** Produits, Clients, Fournisseurs, Ventes, Achats, Comptabilité, Rapports, Utilisateurs, RH, Planning, Congés, Entrepôts...

---

### 9.3 Invitations

**Menu :** Administration → Invitations

Invitez de nouveaux utilisateurs à rejoindre votre entreprise :

1. Saisissez l'**email** du nouveau collaborateur
2. Choisissez le **rôle** à attribuer
3. Un email d'invitation est envoyé avec un lien unique (valide 7 jours)

| Action | Description |
|--------|-------------|
| **Renvoyer** | Renvoie l'email et prolonge la validité de 7 jours |
| **Copier le lien** | Copie le lien d'invitation dans le presse-papiers |
| **Supprimer** | Annule l'invitation |

**Statuts :** En attente → Acceptée / Expirée

---

### 9.4 Historique des modifications

**Menu :** Administration → Historique des modifications

Journal d'audit **immutable** de toutes les actions effectuées dans le système :

| Colonne | Description |
|---------|-------------|
| Date | Horodatage précis |
| Module | Ventes, Achats, Produits, Clients, Fournisseurs, Utilisateurs (badge coloré) |
| Description | Résumé de l'action |
| Type | Objet concerné |
| Utilisateur | Qui a effectué l'action |
| Événement | Création (vert), Modification (bleu), Suppression (rouge) |

> Ce journal est **en lecture seule**. Aucune entrée ne peut être modifiée ou supprimée (conformité audit).

---

## 10. Facturation Électronique (PPF/Chorus Pro)

### 10.1 Assistant de configuration

**Menu :** Administration → Assistant facturation

> Disparaît automatiquement une fois la configuration terminée.

Wizard en 5 étapes :

1. **Introduction** : informations sur l'obligation de facturation électronique (2026)
2. **Création de compte** : guide pas à pas pour créer un compte Chorus Pro (avec lien externe)
3. **Identifiants** : saisie du SIRET, login (TECH_xxx@cpro.fr) et mot de passe
4. **Test de connexion** : vérification que les identifiants fonctionnent
5. **Confirmation** : activation de l'intégration

### 10.2 Paramètres PPF

**Menu :** Administration → Facturation électronique

Gestion des identifiants Chorus Pro :

| Champ | Description |
|-------|-------------|
| Actif | Activer/désactiver l'intégration |
| SIRET fournisseur | Votre SIRET pour Chorus Pro |
| Login | Identifiant technique (format TECH_xxx@cpro.fr) |
| Mot de passe | Mot de passe Chorus Pro |

**Action :** « Tester la connexion » — vérifie l'authentification avec Chorus Pro.

### 10.3 Envoyer une facture au PPF

Depuis une vente validée (menu Ventes → Ventes) :

1. Ouvrez une vente au statut **« Validée »**
2. Cliquez sur **« Envoyer au PPF »** (disponible uniquement si pas encore envoyée)
3. La facture est transmise à Chorus Pro au format Factur-X

#### Suivi du statut PPF

| Statut | Signification |
|--------|---------------|
| Déposée | Facture déposée sur la plateforme |
| Mise à disposition | Facture disponible pour le client |
| Prise en charge | Facture reçue par le client |
| Mise en paiement | Paiement en cours |
| Payée | Paiement effectué |
| Suspendue | Facture en attente |
| Rejetée | Facture refusée |
| Erreur | Erreur technique |

Cliquez sur **« Actualiser statut PPF »** pour synchroniser le dernier statut depuis Chorus Pro.

---

## 11. Annexes

### 11.1 Raccourcis et astuces

| Astuce | Description |
|--------|-------------|
| **Créer à la volée** | Le bouton « + » à côté des sélecteurs Client/Fournisseur permet de créer un nouvel enregistrement sans quitter le formulaire |
| **Copier** | Cliquez sur les numéros de facture, références et codes pour les copier dans le presse-papiers |
| **Filtres combinés** | Tous les tableaux supportent la combinaison de plusieurs filtres simultanément |
| **Tri** | Cliquez sur les en-têtes de colonnes pour trier |
| **Pagination** | Changez le nombre de lignes par page en bas de chaque tableau |
| **Lecteur code-barres** | La caisse POS supporte les lecteurs de code-barres USB/Bluetooth |

### 11.2 Formats de documents PDF

Tous les documents sont générés au format **A4** :
- Factures de vente et d'achat
- Devis
- Bons de livraison
- Étiquettes produits
- Rapports financiers (bilan, journaux, TVA, stock)
- Certificat d'intégrité comptable
- Rapport de session de caisse

### 11.3 Devises supportées

| Code | Devise |
|------|--------|
| XOF | Franc CFA (UEMOA) |
| XAF | Franc CFA (CEMAC) |
| EUR | Euro |
| USD | Dollar américain |
| GBP | Livre sterling |
| CHF | Franc suisse |
| CAD | Dollar canadien |
| AUD | Dollar australien |
| JPY | Yen japonais |
| CNY | Yuan chinois |
| INR | Roupie indienne |
| BRL | Réal brésilien |
| MXN | Peso mexicain |

### 11.4 Taux de TVA supportés

| Taux | Compte collectée | Compte déductible | Usage |
|------|-------------------|-------------------|-------|
| 20,00% | 445710 | 445660 | Taux normal |
| 10,00% | 445712 | 445662 | Taux intermédiaire |
| 5,50% | 445711 | 445661 | Taux réduit |
| 2,10% | 445713 | 445663 | Taux super-réduit |

### 11.5 Glossaire

| Terme | Définition |
|-------|------------|
| **FEC** | Fichier des Écritures Comptables — export obligatoire pour le fisc |
| **PCG** | Plan Comptable Général — nomenclature française des comptes |
| **OD** | Opérations Diverses — écritures manuelles de correction |
| **TVA** | Taxe sur la Valeur Ajoutée |
| **HT** | Hors Taxes |
| **TTC** | Toutes Taxes Comprises |
| **PPF** | Portail Public de Facturation (Chorus Pro) |
| **Factur-X** | Format de facture électronique hybride (PDF + XML) |
| **Lettrage** | Rapprochement entre une facture et son paiement |
| **Contre-passation** | Écriture inverse pour annuler une écriture existante |
| **Avoir** | Facture négative annulant tout ou partie d'une vente |
| **SIREN** | Identifiant entreprise (9 chiffres) |
| **SIRET** | Identifiant établissement (14 chiffres) |
| **IBAN** | International Bank Account Number |
| **BIC** | Bank Identifier Code |
| **URSSAF** | Organisme de recouvrement des cotisations sociales |

---

> **Support :** Pour toute question, contactez l'équipe FRECORP.

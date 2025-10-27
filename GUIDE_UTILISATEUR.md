# Guide Utilisateur - Interface d'Administration

## Introduction

Ce guide vous accompagne dans l'utilisation de l'interface d'administration de votre système de gestion d'inventaire, construite avec Filament 3.

## Accès à l'interface

### Connexion

1. Rendez-vous sur `votre-domaine.com/admin`
2. Saisissez vos identifiants (email et mot de passe)
3. Cliquez sur "Se connecter"

### Tableau de bord

Une fois connecté, vous accédez au tableau de bord principal qui affiche :
- Statistiques générales
- Alertes de stock faible
- Activité récente
- Widgets personnalisés

## Navigation

L'interface est organisée en sections principales dans la barre latérale :

### 🧊 Gestion du stock
- **Produits** : Gérer le catalogue de produits
- **Fournisseurs** : Gérer les fournisseurs

### 💰 Ventes
- **Ventes** : Créer et gérer les ventes
- **Clients** : Gérer la base clients

### 📦 Achats
- **Achats** : Enregistrer les achats fournisseurs
- **Éléments d'achat** : Détails des achats

## Gestion des Produits

### Créer un nouveau produit

1. Cliquez sur **"Produits"** dans la navigation
2. Cliquez sur **"Nouveau"** en haut à droite
3. Remplissez le formulaire :
   - **Nom** : Le nom du produit (obligatoire)
   - **Code** : Généré automatiquement basé sur le nom
   - **Description** : Description détaillée
   - **Prix d'achat** : Coût d'acquisition
   - **Prix de vente** : Prix de vente public
   - **Stock** : Quantité en stock
   - **Unité** : Unité de mesure (pièce, kg, litre, etc.)
   - **Stock minimum** : Seuil d'alerte
   - **Fournisseur** : Sélectionner le fournisseur principal

4. Cliquez sur **"Créer"**

### Modifier un produit

1. Dans la liste des produits, cliquez sur l'icône d'édition (crayon)
2. Modifiez les champs nécessaires
3. Cliquez sur **"Sauvegarder"**

### Fonctionnalités avancées

- **Recherche** : Utilisez la barre de recherche pour trouver rapidement un produit
- **Filtres** : Filtrez par fournisseur, stock faible, etc.
- **Tri** : Cliquez sur les en-têtes de colonnes pour trier
- **Actions en lot** : Sélectionnez plusieurs produits pour des actions groupées

## Gestion des Ventes

### Créer une vente

1. Accédez à **"Ventes"** → **"Nouveau"**
2. Sélectionnez le **client** (ou créez-en un nouveau)
3. Le **numéro de facture** est généré automatiquement
4. Ajoutez des **éléments de vente** :
   - Sélectionnez le produit
   - Indiquez la quantité
   - Le prix et total sont calculés automatiquement
5. Vérifiez le **total général**
6. Définissez le **statut** :
   - **Brouillon** : Vente en cours de création
   - **En attente** : Vente confirmée mais non traitée
   - **Complétée** : Vente finalisée (stock déduit automatiquement)
   - **Annulée** : Vente annulée

### Finaliser une vente

⚠️ **Important** : Quand vous passez une vente au statut "Complétée", le stock des produits est automatiquement déduit.

### Imprimer une facture

1. Ouvrez la vente concernée
2. Cliquez sur **"Imprimer"** ou **"PDF"**
3. La facture se génère automatiquement

## Gestion des Achats

### Enregistrer un achat

1. Accédez à **"Achats"** → **"Nouveau"**
2. Sélectionnez le **fournisseur**
3. Le **numéro de facture** est généré automatiquement (format ACH-XXXXXXXX)
4. Ajoutez des **éléments d'achat** :
   - Sélectionnez le produit
   - Indiquez la quantité reçue
   - Confirmez le prix d'achat
5. Ajoutez des **notes** si nécessaire
6. Définissez le **statut** :
   - **Brouillon** : Achat en cours de saisie
   - **Commandé** : Commande passée au fournisseur
   - **Complété** : Marchandise reçue (stock ajouté automatiquement)

### Réceptionner des marchandises

Quand vous recevez la livraison :
1. Ouvrez l'achat correspondant
2. Vérifiez les quantités reçues
3. Passez le statut à **"Complété"**
4. Le stock est automatiquement mis à jour

## Gestion des Clients

### Ajouter un client

1. **"Clients"** → **"Nouveau"**
2. Remplissez les informations :
   - **Nom** : Nom du client ou de l'entreprise
   - **Email** : Adresse email
   - **Téléphone** : Numéro de contact
   - **Adresse** : Adresse complète
   - **Ville** et **Pays**
   - **Notes** : Informations complémentaires

### Historique client

Pour chaque client, vous pouvez consulter :
- Toutes les ventes associées
- Montant total des achats
- Dernière commande
- Statut des paiements

## Gestion des Fournisseurs

### Ajouter un fournisseur

1. **"Fournisseurs"** → **"Nouveau"**
2. Saisissez les informations de contact
3. Ajoutez des notes sur les conditions commerciales

### Associer des produits

- Lors de la création d'un produit, sélectionnez le fournisseur principal
- Un fournisseur peut avoir plusieurs produits
- Consultez la liste des produits depuis la fiche fournisseur

## Alertes et Notifications

### Alertes de stock

Le système vous alertera automatiquement quand :
- Un produit atteint son stock minimum
- Un produit est en rupture de stock
- Des ventes ne peuvent pas être complétées par manque de stock

### Notifications

Les notifications apparaissent :
- En haut à droite de l'interface
- Par email (si configuré)
- Dans les logs système

## Rapports et Statistiques

### Rapports disponibles

- **Rapport de stock** : État actuel des stocks
- **Rapport de ventes** : Ventes par période
- **Rapport d'achats** : Achats par fournisseur
- **Analyse de marge** : Rentabilité par produit

### Exporter des données

1. Dans n'importe quelle liste, cliquez sur **"Exporter"**
2. Choisissez le format (CSV, Excel, PDF)
3. Sélectionnez les colonnes à exporter
4. Téléchargez le fichier généré

## Raccourcis et Astuces

### Raccourcis clavier

- `Ctrl + K` : Recherche globale
- `Ctrl + S` : Sauvegarder (dans les formulaires)
- `Esc` : Fermer les modales

### Astuces d'utilisation

1. **Recherche rapide** : Utilisez la barre de recherche globale pour trouver rapidement clients, produits ou factures
2. **Filtres persistants** : Vos filtres sont sauvegardés entre les sessions
3. **Actions en lot** : Sélectionnez plusieurs éléments pour des actions groupées
4. **Tri personnalisé** : Cliquez sur les en-têtes pour trier les colonnes

## Gestion des erreurs

### Erreurs courantes

#### "Stock insuffisant"
- **Cause** : Tentative de vendre plus que le stock disponible
- **Solution** : Vérifiez le stock du produit ou ajustez la quantité

#### "Code produit déjà existant"
- **Cause** : Le code généré automatiquement existe déjà
- **Solution** : Modifiez manuellement le code produit

#### "Fournisseur requis"
- **Cause** : Tentative de créer un produit sans fournisseur
- **Solution** : Créez d'abord un fournisseur ou sélectionnez-en un existant

### Support

En cas de problème :
1. Vérifiez cette documentation
2. Consultez les logs dans l'interface
3. Contactez l'administrateur système

## Maintenance utilisateur

### Nettoyage régulier

Recommandations pour maintenir un système propre :
- Archivez les anciennes ventes complétées
- Supprimez les brouillons non utilisés
- Mettez à jour les informations clients/fournisseurs
- Vérifiez régulièrement les stocks minimum

### Sauvegarde des données

Bien que la sauvegarde soit automatique, vous pouvez :
- Exporter régulièrement vos données importantes
- Télécharger les rapports mensuels
- Conserver une copie des factures PDF

---

**Ce guide couvre l'utilisation quotidienne du système. Pour des fonctionnalités avancées ou des problèmes techniques, consultez la documentation technique ou contactez votre administrateur.**
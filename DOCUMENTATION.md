# Documentation - Système de Gestion d'Inventaire

## Vue d'ensemble

Cette application est un système de gestion d'inventaire développé avec **Laravel 12** et **Filament 3**, conçu pour gérer les produits, les ventes, les achats, les clients et les fournisseurs. L'application utilise une interface d'administration moderne avec Filament et un frontend avec Tailwind CSS et Alpine.js.

## Table des matières

1. [Architecture](#architecture)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Modèles de données](#modèles-de-données)
5. [Fonctionnalités](#fonctionnalités)
6. [Interface d'administration](#interface-dadministration)
7. [API et Routes](#api-et-routes)
8. [Base de données](#base-de-données)
9. [Déploiement](#déploiement)
10. [Maintenance](#maintenance)

## Architecture

### Stack technologique

- **Backend**: Laravel 12 (PHP 8.2+)
- **Interface Admin**: Filament 3.2
- **Frontend**: Vite, Tailwind CSS, Alpine.js
- **Base de données**: Compatible avec MySQL, PostgreSQL, SQLite
- **Authentification**: Laravel Sanctum
- **PDF**: DomPDF pour la génération de documents
- **Tests**: Pest PHP

### Structure du projet

```
├── app/
│   ├── Filament/           # Configuration Filament
│   │   ├── Resources/      # Ressources d'administration
│   │   ├── Pages/          # Pages personnalisées
│   │   └── Widgets/        # Widgets du tableau de bord
│   ├── Http/
│   │   └── Controllers/    # Contrôleurs Laravel
│   ├── Models/             # Modèles Eloquent
│   └── Providers/          # Fournisseurs de services
├── database/
│   ├── migrations/         # Migrations de base de données
│   ├── factories/          # Factories pour les tests
│   └── seeders/           # Seeders
├── routes/                 # Définition des routes
├── resources/             # Vues et assets
├── tests/                 # Tests automatisés
└── config/               # Configuration
```

## Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- Node.js et npm
- Base de données (MySQL, PostgreSQL, ou SQLite)

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone <votre-repo>
cd <nom-du-projet>
```

2. **Installer les dépendances PHP**
```bash
composer install
```

3. **Installer les dépendances JavaScript**
```bash
npm install
```

4. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configurer la base de données**
Éditer le fichier `.env` avec vos paramètres de base de données :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votre_base
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

6. **Exécuter les migrations**
```bash
php artisan migrate
```

7. **Créer un utilisateur administrateur**
```bash
php artisan make:filament-user
```

8. **Compiler les assets**
```bash
npm run build
```

## Configuration

### Variables d'environnement importantes

- `APP_URL`: URL de base de l'application
- `DB_*`: Configuration de la base de données
- `MAIL_*`: Configuration email pour les notifications

### Commandes de développement

Le projet inclut une commande de développement pratique :
```bash
composer run dev
```

Cette commande lance simultanément :
- Serveur Laravel (`php artisan serve`)
- Queue worker (`php artisan queue:listen`)
- Logs en temps réel (`php artisan pail`)
- Serveur de développement Vite (`npm run dev`)

## Modèles de données

### Product (Produit)

Gère les informations des produits en stock.

**Attributs :**
- `name` : Nom du produit
- `code` : Code unique du produit (généré automatiquement)
- `description` : Description détaillée
- `purchase_price` : Prix d'achat
- `price` : Prix de vente
- `stock` : Quantité en stock
- `unit` : Unité de mesure
- `min_stock` : Stock minimum
- `supplier_id` : Référence au fournisseur

**Relations :**
- `supplier()` : Appartient à un fournisseur
- `saleItems()` : A plusieurs éléments de vente

### Sale (Vente)

Gère les transactions de vente.

**Attributs :**
- `invoice_number` : Numéro de facture
- `customer_id` : Référence au client
- `total` : Montant total
- `status` : Statut de la vente

**Relations :**
- `customer()` : Appartient à un client
- `items()` : A plusieurs éléments de vente

**Logique métier :**
- Gestion automatique du stock lors du changement de statut
- Calcul automatique du total
- Restauration du stock lors de la suppression

### Purchase (Achat)

Gère les achats auprès des fournisseurs.

**Attributs :**
- `invoice_number` : Numéro de facture (généré automatiquement : ACH-XXXXXXXX)
- `supplier_id` : Référence au fournisseur
- `status` : Statut de l'achat
- `total` : Montant total
- `notes` : Notes additionnelles

**Relations :**
- `supplier()` : Appartient à un fournisseur
- `items()` : A plusieurs éléments d'achat

**Logique métier :**
- Mise à jour automatique du stock selon le statut
- Génération automatique du numéro de facture

### Customer (Client)

Gère les informations des clients.

**Attributs :**
- `name` : Nom du client
- `email` : Adresse email
- `phone` : Numéro de téléphone
- `address` : Adresse
- `city` : Ville
- `country` : Pays
- `notes` : Notes

**Relations :**
- `sales()` : A plusieurs ventes

### Supplier (Fournisseur)

Gère les informations des fournisseurs.

**Attributs :**
- `name` : Nom du fournisseur
- `email` : Adresse email
- `phone` : Numéro de téléphone
- `address` : Adresse
- `city` : Ville
- `country` : Pays
- `notes` : Notes

**Relations :**
- `products()` : A plusieurs produits

### SaleItem & PurchaseItem

Gèrent les éléments individuels des ventes et achats avec quantités et prix.

## Fonctionnalités

### 1. Gestion des produits
- Création et modification des produits
- Gestion du stock avec alertes de stock minimum
- Association avec les fournisseurs
- Codes produits générés automatiquement

### 2. Gestion des ventes
- Création de factures de vente
- Gestion des clients
- Calcul automatique des totaux
- Mise à jour automatique du stock

### 3. Gestion des achats
- Enregistrement des achats fournisseurs
- Numérotation automatique des factures
- Mise à jour du stock lors de la réception

### 4. Gestion des clients et fournisseurs
- Base de données complète des contacts
- Historique des transactions
- Informations de contact détaillées

### 5. Authentification et autorisation
- Système d'authentification Laravel
- Gestion des rôles utilisateur
- Interface sécurisée avec Filament

## Interface d'administration

L'interface d'administration est construite avec **Filament 3** et organisée en groupes :

### Navigation principale

- **Gestion du stock**
  - Produits
  - Fournisseurs
  
- **Ventes**
  - Ventes
  - Clients
  
- **Achats**
  - Achats
  - Éléments d'achat

### Fonctionnalités Filament

- Interface responsive et moderne
- Formulaires dynamiques avec validation
- Tables avec filtrage et recherche
- Widgets pour le tableau de bord
- Génération automatique de codes
- Gestion des relations

## API et Routes

### Routes web principales

- `/` : Page d'accueil
- `/dashboard` : Tableau de bord (authentifié)
- `/admin` : Interface d'administration Filament
- `/profile/*` : Gestion du profil utilisateur

### Routes d'authentification

Toutes les routes d'authentification standard de Laravel sont disponibles via `auth.php`.

## Base de données

### Migrations principales

1. **Tables de base** (Laravel)
   - `users` : Utilisateurs
   - `cache` : Cache
   - `jobs` : Files d'attente

2. **Tables métier**
   - `company_settings` : Paramètres de l'entreprise
   - `suppliers` : Fournisseurs
   - `customers` : Clients
   - `products` : Produits
   - `purchases` : Achats
   - `purchase_items` : Éléments d'achat
   - `sales` : Ventes
   - `sale_items` : Éléments de vente

### Relations de base de données

```
Supplier (1) ──── (n) Product (n) ──── (n) SaleItem (n) ──── (1) Sale (n) ──── (1) Customer
                      │                                          │
                      └── (n) PurchaseItem (n) ──── (1) Purchase ──── (1) Supplier
```

## Déploiement

### Avec Docker

Le projet inclut un `Dockerfile` et une configuration `docker-compose` :

```bash
docker build -t inventory-app .
docker run -p 8000:8000 inventory-app
```

### Sur Render.com

Le projet inclut un fichier `.render.yaml` pour le déploiement automatique sur Render.

### Déploiement traditionnel

1. Configurer le serveur web (Apache/Nginx)
2. Installer PHP 8.2+ et les extensions requises
3. Configurer la base de données
4. Exécuter les migrations
5. Compiler les assets de production

## Maintenance

### Commandes utiles

- `php artisan migrate` : Exécuter les migrations
- `php artisan db:seed` : Peupler la base de données
- `php artisan queue:work` : Traiter les files d'attente
- `php artisan config:cache` : Mettre en cache la configuration
- `composer run test` : Exécuter les tests

### Monitoring

- Logs disponibles dans `storage/logs/`
- Monitoring en temps réel avec `php artisan pail`
- Tests automatisés avec Pest PHP

### Sauvegarde

Recommandations pour la sauvegarde :
- Base de données quotidienne
- Fichiers d'upload dans `storage/`
- Configuration `.env`

## Sécurité

- Authentification Laravel Sanctum
- Protection CSRF activée
- Validation des données d'entrée
- Gestion des rôles et permissions
- Logs d'audit des actions importantes

## Support et développement

### Tests

Exécuter les tests :
```bash
composer run test
```

### Linting et formatage

```bash
./vendor/bin/pint  # Formatage du code PHP
```

### Environnement de développement

Pour un développement optimal, utilisez :
```bash
composer run dev
```

Cette commande lance tous les services nécessaires en parallèle.

---

**Note** : Cette documentation couvre les aspects principaux du système. Pour des détails spécifiques sur l'utilisation de Filament ou Laravel, consultez leurs documentations officielles respectives.
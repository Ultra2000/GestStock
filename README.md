# GestStock

**GestStock** est une application web développée avec le framework Laravel (PHP) et filamentphp.  
Ce projet vise à offrir une gestion efficace des stocks, des entrées et sorties de produits, ainsi qu’un suivi des inventaires, dans un contexte commercial, industriel ou associatif.  
L’application exploite la puissance de Laravel pour garantir sécurité, performance et évolutivité.

## Fonctionnalités principales
- Gestion des utilisateurs et des droits d’accès
- Suivi des mouvements de stock (entrées, sorties, transferts)
- Gestion des produits et catégories
- Inventaires périodiques et alertes de seuil
- Génération de rapports et statistiques
- Interface utilisateur moderne basée sur Blade et Bootstrap
- Système d’authentification et de notifications

## Structure du projet
- `app/` : logique métier et contrôleurs
- `config/` : configuration de l’application
- `database/` : migrations, seeders, factories
- `public/` : point d’entrée de l’application web
- `resources/` : vues Blade, assets front-end
- `routes/` : définition des routes web et API
- `tests/` : tests automatisés
- `storage/` : logs, fichiers générés, cache

## Installation rapide

```bash
git clone https://github.com/Ultra2000/GestStock.git
cd GestStock
composer install
cp .env.example .env
php artisan key:generate
# Configurer la base de données dans .env
php artisan migrate
php artisan serve
```

## À propos

Ce projet est conçu pour être facilement déployable et adaptable selon les besoins.  
Pour toute contribution, reportez-vous au guide de contribution de Laravel.

---
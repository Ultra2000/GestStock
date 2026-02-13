<h1 align="center">FRECORP ERP</h1>
<p align="center">Application de gestion de stock, facturation & point de vente (POS) construite avec Laravel 12 & Filament v3.</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-12.x-red" />
<img src="https://img.shields.io/badge/PHP-%5E8.2-blue" />
<img src="https://img.shields.io/badge/Filament-v3-purple" />
<img src="https://img.shields.io/badge/License-MIT-green" />
</p>

## ✨ Fonctionnalités principales

- Gestion des produits (stock, stock minimum, fournisseur, prix d'achat / vente, unité)
- Génération automatique de codes produits internes (format `PYYXXXXXX`)
- Génération & aperçu de codes-barres (Code 128) + impression d'étiquettes PDF (sélection & individuel)
- Préparation future EAN‑13 (champ `barcode_type`)
- Factures Achats & Ventes avec : remises (%), TVA (%), numérotation séquentielle, QR code de vérification
- Vérification externe sécurisée via lien signé + hash d'intégrité
- Envoi de facture PDF par e-mail (SMTP configurable)
- Page Caisse (POS) : recherche, scan douchette, scan caméra (ZXing), mode continu, beep & vibration
- Calcul automatique rendu monnaie / remises / TVA
- Alerte stock bas (liste + ticket + messages d’avertissement)
- Client « walk-in » par défaut si non sélectionné
- Impression d’étiquettes multi-colonnes configurable (2/3/4 colonnes, option prix)

## 🗂 Structure technique

| Élément | Détails |
|---------|--------|
| Backend | Laravel 12 |
| Admin UI | Filament v3 |
| Base de données | SQLite (dev) — compatible MySQL/PostgreSQL |
| PDF | `barryvdh/laravel-dompdf` |
| QR Code | `simplesoftwareio/simple-qrcode` |
| Codes-barres | `milon/barcode` (Code128) |
| Scan caméra | ZXing Browser (UMD) |
| Front assets | Vite + Tailwind |

## 🚀 Installation (développement)

```bash
git clone <url-du-repo> frecorp-erp
cd frecorp-erp
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed   # si des seeders existent
npm install
npm run dev                  # ou npm run build pour prod
php artisan serve
```

Ouvre ensuite: http://localhost:8000

### Accès Filament

Panel Filament (admin): `/admin`  
Créer d’abord un utilisateur puis lui attribuer le rôle `admin` (via base ou interface si déjà accessible).

## 🔐 Rôles & accès

| Rôle | Accès |
|------|-------|
| admin | Tous panels, génération/regénération codes produits, gestion complète |
| cashier | Accès page Caisse (POS), création ventes, scan codes |
| (autres) | Accès restreint selon logique future |

Méthode de filtrage : `User::canAccessPanel()` (adaptée pour autoriser admin & cashier).

## 🧾 Facturation

- Numérotation séquentielle configurable (ex: `FAC-000001`, `ACH-000001`)
- Remise (%) appliquée sur sous-total puis TVA sur montant remisé
- QR code sur facture : encode URL de vérification signée
- Lien public sécurisé (signature URL) → page de vérification avec hash calculé (id + numéro + total + date)
- Envoi email PDF via `InvoiceMail`

## 🛒 Caisse (POS)

Fonctionnalités :
- Recherche produit (nom / code)
- Ajout rapide via scan matériel (champ input) ou caméra (sélection device + overlay)
- Mode scan continu optionnel
- Avertissement produits stock bas + badge “Bas”
- Calcul remise / TVA / rendu automatique
- Empêche modification prix pour rôle `cashier` (admin peut modifier)
- Client par défaut « walk-in » si null

API internes utilisées :
- `GET /admin/api/products?q=...` (recherche)
- `GET /admin/api/product-code/{code}` (scan direct)
- `POST /admin/api/cash-sale` (enregistrement vente rapide)

## 🏷 Codes-barres & étiquettes

### Génération
- Automatique à la création produit si absent (`Product::boot()`)
- Format interne : `P` + année (2 chiffres) + séquence sur 6 chiffres
- Table `sequences` pour éviter scan complet

### Régénération
- Action Filament “Régénérer code” (admin uniquement)

### Impression étiquettes
- Bulk action: sélectionner produits → “Imprimer étiquettes”
- Action individuelle sur chaque produit
- Paramètres: quantités (`id:qty,id:qty`), colonnes (2/3/4), afficher prix
- View PDF: `resources/views/pdf/product-labels.blade.php`

### Préparation EAN‑13
- Champ `barcode_type` (`code128` par défaut) → prêt pour extension future (`ean13`)

## 📧 Emails

- Envoi facture PDF client: nécessite configuration SMTP dans `.env` :

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@exemple.com
MAIL_FROM_NAME="FRECORP ERP"
```

## 🗃 Commandes Artisan utiles

| Commande | Description |
|----------|-------------|
| `products:generate-barcodes` | Génère codes manquants |
| `products:generate-barcodes --rewrite` | Regénère tous les codes (attention) |
| `queue:listen` | Si des jobs différés sont ajoutés |

## 🔍 Vérification facture (QR)

1. Facture intègre un QR pointant vers `/verify/(sale|purchase)/{id}?signature=...`
2. Route signée → hash recomputé côté serveur
3. Si correspond → affiche “Authentique” / sinon “Invalide”.

## 🧪 Tests

Base Pest déjà initialisée. Pour exécuter :

```bash
php artisan test
```

Ajouter vos tests dans `tests/Feature` ou `tests/Unit`.

## 📦 Build production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

## 🔐 Sécurité & bonnes pratiques

- Conserver `.env` hors dépôt.
- Régénérer clé app si environnement cloné (`php artisan key:generate`).
- Restreindre accès panel admin par firewall / HTTPS en production.
- Mettre en place backups DB réguliers.

## 🗺 Roadmap (prochaines améliorations possibles)

- Support EAN‑13 complet (chiffre contrôle, double stockage code interne + EAN)
- Ticket de caisse imprimable thermique (58 / 80 mm)
- Multi-modes de paiement (espèces / mobile money / carte)
- Sélection client dynamique + historique achats client
- Cache PNG persistent des codes-barres pour performance PDF massive
- Notifications stock bas (email / dashboard widget)
- Journal d’audit (ventes, modifications stock)

## 🤝 Contribution

Fork → branche `feature/xxx` → PR. Merci de garder un style cohérent et d’ajouter des tests sur les comportements critiques (génération codes, calcul totals).

## 📄 Licence

Projet distribué sous licence MIT. Voir le fichier `LICENSE` si présent ou ajouter un fichier de licence spécifique selon vos besoins.

---

Pour toute question ou amélioration, ouvrez une issue ou proposez une PR. Bon usage de FRECORP ERP !

# Documentation API - Système de Gestion d'Inventaire

## Vue d'ensemble de l'API

Cette documentation décrit l'API interne et les points d'extension du système de gestion d'inventaire Laravel/Filament.

## Authentification

L'application utilise **Laravel Sanctum** pour l'authentification API.

### Endpoints d'authentification

- `POST /login` : Connexion utilisateur
- `POST /logout` : Déconnexion
- `POST /register` : Inscription (si activée)
- `GET /user` : Informations utilisateur connecté

## Modèles et Relations

### Product Model

```php
<?php

namespace App\Models;

class Product extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'purchase_price',
        'price',
        'stock',
        'unit',
        'min_stock',
        'supplier_id',
    ];

    // Relations
    public function supplier(): BelongsTo
    public function saleItems(): HasMany
}
```

**Méthodes utiles :**
- `isLowStock()` : Vérifie si le stock est en dessous du minimum
- `calculateMargin()` : Calcule la marge bénéficiaire

### Sale Model

```php
<?php

namespace App\Models;

class Sale extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'total',
        'status',
    ];

    // Relations
    public function customer(): BelongsTo
    public function items(): HasMany

    // Méthodes
    public function calculateTotal(): void
}
```

**Événements automatiques :**
- `saving` : Mise à jour du stock si statut = 'completed'
- `deleting` : Restauration du stock si vente complétée

### Purchase Model

```php
<?php

namespace App\Models;

class Purchase extends Model
{
    protected $fillable = [
        'invoice_number',
        'supplier_id',
        'status',
        'total',
        'notes',
    ];

    // Relations
    public function supplier(): BelongsTo
    public function items(): HasMany
}
```

**Événements automatiques :**
- `creating` : Génération automatique du numéro de facture (ACH-XXXXXXXX)
- `updated` : Gestion du stock selon le changement de statut
- `deleting` : Ajustement du stock si achat complété

## Filament Resources

### ProductResource

**Chemin :** `App\Filament\Resources\ProductResource`

**Fonctionnalités :**
- Génération automatique du code produit basé sur le nom
- Validation des prix et quantités
- Gestion des relations avec fournisseurs
- Alertes de stock faible

**Formulaire :**
```php
Forms\Components\TextInput::make('name')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(function ($state, Forms\Set $set) {
        if ($state) {
            $set('code', 'PRD-' . strtoupper(Str::slug($state, '')));
        }
    })
```

### SaleResource

**Chemin :** `App\Filament\Resources\SaleResource`

**Fonctionnalités :**
- Gestion des ventes multi-items
- Calcul automatique des totaux
- Intégration avec la gestion du stock
- Génération de factures PDF

### PurchaseResource

**Chemin :** `App\Filament\Resources\PurchaseResource`

**Fonctionnalités :**
- Gestion des achats fournisseurs
- Réception de marchandises
- Mise à jour automatique du stock

## Hooks et Événements

### Événements de modèles

#### Product
```php
// Aucun événement spécifique défini
```

#### Sale
```php
static::saving(function ($sale) {
    if ($sale->isDirty('status') && $sale->status === 'completed') {
        // Déduction automatique du stock
        foreach ($sale->items as $item) {
            $product = $item->product;
            $product->stock -= $item->quantity;
            $product->save();
        }
    }
});

static::deleting(function ($sale) {
    if ($sale->status === 'completed') {
        // Restauration du stock
        foreach ($sale->items as $item) {
            $product = $item->product;
            $product->stock += $item->quantity;
            $product->save();
        }
    }
});
```

#### Purchase
```php
static::creating(function ($purchase) {
    if (empty($purchase->invoice_number)) {
        $purchase->invoice_number = 'ACH-' . strtoupper(Str::random(8));
    }
});

static::updated(function ($purchase) {
    if ($purchase->isDirty('status')) {
        // Gestion du stock selon le changement de statut
        $oldStatus = $purchase->getOriginal('status');
        $newStatus = $purchase->status;
        
        if ($oldStatus === 'completed' && $newStatus !== 'completed') {
            // Retrait du stock
        } elseif ($oldStatus !== 'completed' && $newStatus === 'completed') {
            // Ajout au stock
        }
    }
});
```

## Validation

### Règles de validation communes

#### Product
- `name` : requis, max 255 caractères
- `code` : requis, unique, max 255 caractères
- `purchase_price` : numérique, min 0
- `price` : numérique, min 0
- `stock` : entier, min 0
- `min_stock` : entier, min 0

#### Sale/Purchase
- `total` : numérique, min 0
- `status` : enum ['draft', 'pending', 'completed', 'cancelled']

## Personnalisation

### Étendre les modèles

Pour ajouter des fonctionnalités aux modèles :

```php
// Dans app/Models/Product.php
public function isLowStock(): bool
{
    return $this->stock <= $this->min_stock;
}

public function calculateMargin(): float
{
    return (($this->price - $this->purchase_price) / $this->purchase_price) * 100;
}
```

### Personnaliser les ressources Filament

#### Ajouter des colonnes personnalisées

```php
// Dans ProductResource
Tables\Columns\TextColumn::make('margin')
    ->label('Marge (%)')
    ->state(function (Product $record): string {
        return number_format($record->calculateMargin(), 2) . '%';
    })
```

#### Ajouter des actions personnalisées

```php
Tables\Actions\Action::make('duplicate')
    ->icon('heroicon-o-document-duplicate')
    ->action(function (Product $record) {
        $newProduct = $record->replicate();
        $newProduct->code = 'PRD-' . strtoupper(Str::random(8));
        $newProduct->save();
    })
```

## Widgets et Tableau de bord

### Widgets disponibles

Les widgets Filament peuvent être créés pour afficher :
- Statistiques de ventes
- Alertes de stock faible
- Graphiques de performance
- Activité récente

### Créer un widget personnalisé

```bash
php artisan make:filament-widget StatsWidget --stats
```

## Commandes Artisan

### Commandes personnalisées

Vous pouvez créer des commandes personnalisées pour :
- Synchronisation des stocks
- Génération de rapports
- Nettoyage des données

```bash
php artisan make:command SyncInventory
```

## Factories et Seeders

### Utilisation des factories

```php
// Créer des produits de test
Product::factory(50)->create();

// Créer des ventes avec items
Sale::factory()
    ->has(SaleItem::factory()->count(3), 'items')
    ->create();
```

### Seeders recommandés

1. **UserSeeder** : Créer des utilisateurs de test
2. **SupplierSeeder** : Fournisseurs de base
3. **ProductSeeder** : Catalogue de produits
4. **CustomerSeeder** : Clients de test

## Tests

### Structure des tests

```
tests/
├── Feature/
│   ├── ProductTest.php
│   ├── SaleTest.php
│   └── PurchaseTest.php
└── Unit/
    ├── ProductModelTest.php
    └── StockManagementTest.php
```

### Exemples de tests

```php
// Test de création de produit
it('can create a product', function () {
    $product = Product::factory()->create([
        'name' => 'Test Product',
        'stock' => 100,
        'min_stock' => 10,
    ]);

    expect($product->isLowStock())->toBeFalse();
});

// Test de gestion du stock
it('updates stock when sale is completed', function () {
    $product = Product::factory()->create(['stock' => 100]);
    $sale = Sale::factory()->create(['status' => 'draft']);
    $saleItem = SaleItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $sale->update(['status' => 'completed']);
    
    expect($product->fresh()->stock)->toBe(90);
});
```

## Bonnes pratiques

### Gestion des erreurs

```php
try {
    $sale->update(['status' => 'completed']);
} catch (\Exception $e) {
    Log::error('Erreur lors de la finalisation de la vente', [
        'sale_id' => $sale->id,
        'error' => $e->getMessage()
    ]);
    throw $e;
}
```

### Optimisation des performances

1. **Eager Loading** pour éviter N+1 :
```php
$sales = Sale::with(['customer', 'items.product'])->get();
```

2. **Indexation de base de données** sur les colonnes fréquemment recherchées

3. **Cache** pour les données statiques :
```php
Cache::remember('low_stock_products', 3600, function () {
    return Product::where('stock', '<=', DB::raw('min_stock'))->get();
});
```

## Sécurité

### Autorisations Filament

```php
// Dans une Resource
public static function canViewAny(): bool
{
    return auth()->user()->hasRole('admin');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create_products');
}
```

### Validation des données

```php
// Validation personnalisée
Forms\Components\TextInput::make('stock')
    ->numeric()
    ->minValue(0)
    ->rules(['required', 'integer', 'min:0'])
```

## Extension et développement

### Ajouter un nouveau module

1. Créer le modèle : `php artisan make:model Category -m`
2. Créer la ressource Filament : `php artisan make:filament-resource Category`
3. Configurer les relations
4. Ajouter les tests

### Intégrations externes

Le système peut être étendu pour intégrer :
- APIs de paiement (Stripe, PayPal)
- Services de livraison
- Systèmes comptables
- Plateformes e-commerce

---

**Cette documentation API est complémentaire à la documentation principale et doit être maintenue à jour lors des évolutions du système.**
<?php

/**
 * FRECORP ERP - Script de Déploiement Production
 * 
 * Usage: php deploy.php [options]
 * Options:
 *   --check      : Vérification uniquement (pas de modifications)
 *   --migrate    : Exécuter les migrations
 *   --seed       : Exécuter les seeders (attention en prod!)
 *   --fresh      : Reset complet de la BDD (DANGER!)
 *   --force      : Forcer sans confirmation
 */

// Configuration
define('BASE_PATH', __DIR__);
define('VERSION', '1.0.0');

// Couleurs console
class Colors {
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const WHITE = "\033[37m";
    const RESET = "\033[0m";
    const BOLD = "\033[1m";
}

function color($text, $color) {
    // Windows support
    if (PHP_OS_FAMILY === 'Windows') {
        return $text;
    }
    return $color . $text . Colors::RESET;
}

function success($msg) { echo color("✓ " . $msg, Colors::GREEN) . "\n"; }
function error($msg) { echo color("✗ " . $msg, Colors::RED) . "\n"; }
function warning($msg) { echo color("⚠ " . $msg, Colors::YELLOW) . "\n"; }
function info($msg) { echo color("→ " . $msg, Colors::CYAN) . "\n"; }
function title($msg) { echo "\n" . color("═══ " . $msg . " ═══", Colors::MAGENTA) . "\n\n"; }

function run($command, $silent = false) {
    if (!$silent) {
        info("Exécution: $command");
    }
    $output = [];
    $returnCode = 0;
    exec($command . " 2>&1", $output, $returnCode);
    return ['output' => implode("\n", $output), 'code' => $returnCode];
}

function confirm($question) {
    global $argv;
    if (in_array('--force', $argv)) return true;
    
    echo color($question . " (y/N): ", Colors::YELLOW);
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    return strtolower(trim($line)) === 'y';
}

// Banner
echo "\n";
echo color("╔════════════════════════════════════════════════════════════╗", Colors::CYAN) . "\n";
echo color("║", Colors::CYAN) . color("        FRECORP ERP - Déploiement Production v" . VERSION . "        ", Colors::BOLD) . color("║", Colors::CYAN) . "\n";
echo color("╚════════════════════════════════════════════════════════════╝", Colors::CYAN) . "\n";

// Parse arguments
$checkOnly = in_array('--check', $argv);
$runMigrate = in_array('--migrate', $argv);
$runSeed = in_array('--seed', $argv);
$runFresh = in_array('--fresh', $argv);

if ($checkOnly) {
    warning("Mode vérification uniquement - Aucune modification ne sera effectuée");
}

// ============================================================================
// ÉTAPE 1: Vérifications système
// ============================================================================
title("1. VÉRIFICATIONS SYSTÈME");

$errors = [];
$warnings = [];

// PHP Version
$phpVersion = phpversion();
if (version_compare($phpVersion, '8.2.0', '>=')) {
    success("PHP Version: $phpVersion");
} else {
    error("PHP Version: $phpVersion (minimum requis: 8.2)");
    $errors[] = "PHP version trop ancienne";
}

// Extensions PHP requises
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'gd'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        success("Extension PHP: $ext");
    } else {
        error("Extension PHP manquante: $ext");
        $errors[] = "Extension $ext manquante";
    }
}

// Fichier .env
if (file_exists(BASE_PATH . '/.env')) {
    success("Fichier .env présent");
    
    // Vérifier les variables critiques
    $envContent = file_get_contents(BASE_PATH . '/.env');
    
    $criticalVars = [
        'APP_KEY' => 'Clé d\'application',
        'APP_ENV' => 'Environnement',
        'APP_DEBUG' => 'Mode debug',
        'DB_CONNECTION' => 'Connexion BDD',
        'DB_HOST' => 'Hôte BDD',
        'DB_DATABASE' => 'Nom BDD',
    ];
    
    foreach ($criticalVars as $var => $label) {
        if (preg_match("/^{$var}=(.+)$/m", $envContent, $matches)) {
            $value = trim($matches[1]);
            if ($var === 'APP_DEBUG' && strtolower($value) === 'true') {
                warning("$label: $value (devrait être false en production)");
                $warnings[] = "APP_DEBUG devrait être false";
            } elseif ($var === 'APP_ENV' && $value !== 'production') {
                warning("$label: $value (devrait être 'production')");
                $warnings[] = "APP_ENV devrait être 'production'";
            } else {
                success("$label: " . (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value));
            }
        } else {
            error("$label: Non défini!");
            $errors[] = "$var non défini";
        }
    }
} else {
    error("Fichier .env manquant!");
    $errors[] = ".env manquant";
    
    if (!$checkOnly && confirm("Copier .env.example vers .env ?")) {
        if (file_exists(BASE_PATH . '/.env.example')) {
            copy(BASE_PATH . '/.env.example', BASE_PATH . '/.env');
            success("Fichier .env créé depuis .env.example");
            warning("N'oubliez pas de configurer les variables d'environnement!");
        }
    }
}

// Composer
$composerResult = run('composer --version', true);
if ($composerResult['code'] === 0) {
    success("Composer installé");
} else {
    error("Composer non trouvé");
    $errors[] = "Composer non installé";
}

// Dossiers d'écriture
$writableDirs = ['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'];
foreach ($writableDirs as $dir) {
    $path = BASE_PATH . '/' . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            success("Dossier accessible en écriture: $dir");
        } else {
            error("Dossier non accessible en écriture: $dir");
            $errors[] = "Permissions insuffisantes sur $dir";
        }
    } else {
        warning("Dossier manquant: $dir");
        if (!$checkOnly) {
            mkdir($path, 0755, true);
            success("Dossier créé: $dir");
        }
    }
}

// ============================================================================
// ÉTAPE 2: Installation des dépendances
// ============================================================================
title("2. DÉPENDANCES");

if (!$checkOnly) {
    info("Installation des dépendances Composer (production)...");
    $result = run('composer install --no-dev --optimize-autoloader --no-interaction');
    if ($result['code'] === 0) {
        success("Dépendances installées");
    } else {
        error("Erreur lors de l'installation des dépendances");
        echo $result['output'] . "\n";
    }
} else {
    info("Vérification des dépendances...");
    if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
        success("Vendor présent");
    } else {
        error("Vendor manquant - exécutez 'composer install'");
        $errors[] = "Dépendances non installées";
    }
}

// ============================================================================
// ÉTAPE 3: Génération de la clé si nécessaire
// ============================================================================
title("3. CLÉ D'APPLICATION");

$envContent = file_exists(BASE_PATH . '/.env') ? file_get_contents(BASE_PATH . '/.env') : '';
if (preg_match('/^APP_KEY=$/m', $envContent) || !preg_match('/^APP_KEY=/m', $envContent)) {
    if (!$checkOnly) {
        info("Génération de la clé d'application...");
        $result = run('php artisan key:generate --force');
        if ($result['code'] === 0) {
            success("Clé générée avec succès");
        } else {
            error("Erreur lors de la génération de la clé");
        }
    } else {
        warning("Clé d'application manquante");
        $warnings[] = "APP_KEY à générer";
    }
} else {
    success("Clé d'application présente");
}

// ============================================================================
// ÉTAPE 4: Base de données
// ============================================================================
title("4. BASE DE DONNÉES");

if (!$checkOnly) {
    // Test de connexion
    info("Test de connexion à la base de données...");
    $result = run('php artisan db:show --counts 2>&1', true);
    if ($result['code'] === 0) {
        success("Connexion à la base de données OK");
        
        // Migrations
        if ($runFresh) {
            if (confirm("⚠️  ATTENTION: Ceci va SUPPRIMER TOUTES LES DONNÉES! Confirmer?")) {
                info("Reset complet de la base de données...");
                $result = run('php artisan migrate:fresh --force');
                if ($result['code'] === 0) {
                    success("Base de données réinitialisée");
                }
            }
        } elseif ($runMigrate) {
            info("Exécution des migrations...");
            $result = run('php artisan migrate --force');
            if ($result['code'] === 0) {
                success("Migrations exécutées");
            } else {
                error("Erreur lors des migrations");
                echo $result['output'] . "\n";
            }
        } else {
            info("Vérification du statut des migrations...");
            $result = run('php artisan migrate:status');
            echo $result['output'] . "\n";
        }
        
        // Seeders
        if ($runSeed) {
            if (confirm("Exécuter les seeders?")) {
                info("Exécution des seeders...");
                $result = run('php artisan db:seed --force');
                if ($result['code'] === 0) {
                    success("Seeders exécutés");
                }
            }
        }
    } else {
        error("Impossible de se connecter à la base de données");
        echo $result['output'] . "\n";
        $errors[] = "Connexion BDD échouée";
    }
} else {
    info("Test de connexion ignoré en mode check");
}

// ============================================================================
// ÉTAPE 5: Optimisations
// ============================================================================
title("5. OPTIMISATIONS PRODUCTION");

if (!$checkOnly) {
    // Cache de configuration
    info("Génération du cache de configuration...");
    $result = run('php artisan config:cache');
    if ($result['code'] === 0) {
        success("Cache de configuration généré");
    } else {
        error("Erreur cache config");
    }
    
    // Cache des routes
    info("Génération du cache des routes...");
    $result = run('php artisan route:cache');
    if ($result['code'] === 0) {
        success("Cache des routes généré");
    } else {
        warning("Erreur cache routes (normal si closures dans routes)");
    }
    
    // Cache des vues
    info("Génération du cache des vues...");
    $result = run('php artisan view:cache');
    if ($result['code'] === 0) {
        success("Cache des vues généré");
    } else {
        error("Erreur cache vues");
    }
    
    // Cache des événements
    info("Génération du cache des événements...");
    $result = run('php artisan event:cache');
    if ($result['code'] === 0) {
        success("Cache des événements généré");
    } else {
        warning("Erreur cache événements");
    }
    
    // Optimisation Filament
    info("Optimisation des icônes Filament...");
    $result = run('php artisan icons:cache');
    if ($result['code'] === 0) {
        success("Cache des icônes généré");
    }
    
    // Optimisation globale
    info("Optimisation globale...");
    $result = run('php artisan optimize');
    if ($result['code'] === 0) {
        success("Optimisation terminée");
    }
    
    // Lien storage
    info("Création du lien symbolique storage...");
    $result = run('php artisan storage:link');
    if (strpos($result['output'], 'already exists') !== false) {
        success("Lien storage déjà présent");
    } elseif ($result['code'] === 0) {
        success("Lien storage créé");
    }
} else {
    info("Optimisations ignorées en mode check");
}

// ============================================================================
// ÉTAPE 6: Sécurité
// ============================================================================
title("6. VÉRIFICATIONS DE SÉCURITÉ");

// Fichiers sensibles
$sensitiveFiles = ['.env', 'storage/logs/laravel.log'];
foreach ($sensitiveFiles as $file) {
    $path = BASE_PATH . '/' . $file;
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        if ($perms === '0644' || $perms === '0640' || $perms === '0600') {
            success("Permissions $file: $perms");
        } else {
            warning("Permissions $file: $perms (recommandé: 0640)");
        }
    }
}

// Vérifier que APP_DEBUG est false
if (preg_match('/^APP_DEBUG=true/mi', $envContent)) {
    error("APP_DEBUG est activé! Désactivez-le en production!");
    $errors[] = "APP_DEBUG doit être false";
}

// Vérifier HTTPS
if (preg_match('/^APP_URL=http:\/\//m', $envContent)) {
    warning("APP_URL utilise HTTP au lieu de HTTPS");
    $warnings[] = "Utilisez HTTPS en production";
}

// ============================================================================
// ÉTAPE 7: Permissions finales
// ============================================================================
title("7. PERMISSIONS");

if (!$checkOnly && PHP_OS_FAMILY !== 'Windows') {
    info("Configuration des permissions...");
    
    // Storage et cache
    run('chmod -R 775 storage bootstrap/cache');
    run('chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true');
    
    success("Permissions configurées");
} else {
    info("Configuration des permissions ignorée (Windows ou mode check)");
}

// ============================================================================
// RÉSUMÉ
// ============================================================================
title("RÉSUMÉ DU DÉPLOIEMENT");

if (count($errors) > 0) {
    echo color("\n❌ ERREURS CRITIQUES (" . count($errors) . "):\n", Colors::RED);
    foreach ($errors as $err) {
        echo "   • $err\n";
    }
}

if (count($warnings) > 0) {
    echo color("\n⚠️  AVERTISSEMENTS (" . count($warnings) . "):\n", Colors::YELLOW);
    foreach ($warnings as $warn) {
        echo "   • $warn\n";
    }
}

if (count($errors) === 0 && count($warnings) === 0) {
    echo color("\n✅ DÉPLOIEMENT RÉUSSI!\n", Colors::GREEN);
    echo "\nVotre application est prête pour la production.\n";
} elseif (count($errors) === 0) {
    echo color("\n⚠️  DÉPLOIEMENT TERMINÉ AVEC AVERTISSEMENTS\n", Colors::YELLOW);
    echo "\nCorrigez les avertissements pour une configuration optimale.\n";
} else {
    echo color("\n❌ DÉPLOIEMENT ÉCHOUÉ\n", Colors::RED);
    echo "\nCorrigez les erreurs avant de mettre en production.\n";
}

// Checklist finale
echo "\n" . color("═══ CHECKLIST PRODUCTION ═══", Colors::CYAN) . "\n\n";
$checklist = [
    'APP_ENV=production' => preg_match('/^APP_ENV=production/m', $envContent),
    'APP_DEBUG=false' => preg_match('/^APP_DEBUG=false/mi', $envContent),
    'HTTPS configuré' => preg_match('/^APP_URL=https/m', $envContent),
    'Clé APP_KEY définie' => preg_match('/^APP_KEY=base64:/m', $envContent),
    'BDD configurée' => preg_match('/^DB_DATABASE=(?!laravel)/m', $envContent),
    'Mail configuré' => preg_match('/^MAIL_MAILER=(?!log)/m', $envContent),
];

foreach ($checklist as $item => $ok) {
    echo ($ok ? color("  ✓", Colors::GREEN) : color("  ✗", Colors::RED)) . " $item\n";
}

echo "\n" . color("═══ COMMANDES UTILES ═══", Colors::CYAN) . "\n\n";
echo "  • Vérifier seulement:     php deploy.php --check\n";
echo "  • Avec migrations:        php deploy.php --migrate\n";
echo "  • Reset BDD (DANGER!):    php deploy.php --fresh --force\n";
echo "  • Vider tous les caches:  php artisan optimize:clear\n";
echo "  • Voir les logs:          tail -f storage/logs/laravel.log\n";
echo "\n";

exit(count($errors) > 0 ? 1 : 0);

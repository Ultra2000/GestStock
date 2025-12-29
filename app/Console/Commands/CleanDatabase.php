<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean 
                            {--force : Force l\'exécution sans confirmation}
                            {--keep-config : Garder aussi les configurations (rôles, permissions, etc.)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie toutes les tables sauf users et companies';

    /**
     * Tables à préserver (ne jamais vider)
     */
    protected array $preservedTables = [
        'users',
        'companies',
        'company_user',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'failed_jobs',
        'jobs',
        'job_batches',
    ];

    /**
     * Tables de configuration (préservées avec --keep-config)
     */
    protected array $configTables = [
        'roles',
        'permissions',
        'role_has_permissions',
        'model_has_roles',
        'custom_roles',
        'user_custom_role',
        'sequences',
        'invitations',
        'company_integrations',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('╔══════════════════════════════════════════════════════════════╗');
        $this->warn('║           ⚠️  NETTOYAGE DE LA BASE DE DONNÉES ⚠️              ║');
        $this->warn('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Tables à préserver
        $preserved = $this->preservedTables;
        if ($this->option('keep-config')) {
            $preserved = array_merge($preserved, $this->configTables);
            $this->info('Mode --keep-config: les tables de configuration seront préservées.');
        }

        // Récupérer toutes les tables
        $tables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
            ->pluck('name')
            ->filter(fn($table) => !in_array($table, $preserved))
            ->values();

        if ($tables->isEmpty()) {
            $this->info('Aucune table à nettoyer.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('Tables qui seront VIDÉES (' . $tables->count() . ') :');
        $this->table(['Table'], $tables->map(fn($t) => [$t])->toArray());

        $this->newLine();
        $this->info('Tables PRÉSERVÉES :');
        $this->table(['Table'], collect($preserved)->map(fn($t) => [$t])->toArray());

        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('⚠️  ATTENTION: Cette action est IRRÉVERSIBLE. Voulez-vous continuer ?', false)) {
                $this->info('Opération annulée.');
                return Command::SUCCESS;
            }
        }

        $this->newLine();
        $this->warn('🗑️  Nettoyage en cours...');
        $this->newLine();

        // Désactiver les contraintes de clé étrangère
        DB::statement('PRAGMA foreign_keys = OFF');

        $bar = $this->output->createProgressBar($tables->count());
        $bar->start();

        // Ordre de suppression pour respecter les contraintes (enfants d'abord)
        $orderedTables = $this->getOrderedTables($tables->toArray());

        $errors = [];
        foreach ($orderedTables as $table) {
            try {
                DB::table($table)->truncate();
                $bar->advance();
            } catch (\Exception $e) {
                $errors[$table] = $e->getMessage();
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        // Réactiver les contraintes de clé étrangère
        DB::statement('PRAGMA foreign_keys = ON');

        // Résumé
        if (empty($errors)) {
            $this->info('✅ Nettoyage terminé avec succès !');
            $this->info('   ' . count($orderedTables) . ' tables vidées.');
        } else {
            $this->warn('⚠️  Nettoyage terminé avec ' . count($errors) . ' erreur(s) :');
            foreach ($errors as $table => $error) {
                $this->error("   - {$table}: {$error}");
            }
        }

        $this->newLine();
        $this->info('💡 N\'oubliez pas de recréer les données de test si nécessaire avec:');
        $this->line('   php artisan db:seed');

        return empty($errors) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Ordonne les tables pour supprimer d'abord les tables enfants
     */
    protected function getOrderedTables(array $tables): array
    {
        // Ordre de suppression recommandé (tables enfants d'abord)
        $priority = [
            // Items/détails en premier (tables enfants)
            'sale_items',
            'purchase_items',
            'quote_items',
            'delivery_note_items',
            'inventory_items',
            'recurring_order_items',
            'stock_transfer_items',
            'product_warehouse',
            
            // Mouvements et logs
            'stock_movements',
            'bank_transactions',
            'attendance_logs',
            'audit_logs',
            
            // Documents principaux
            'sales',
            'purchases',
            'quotes',
            'delivery_notes',
            'inventories',
            'recurring_orders',
            'stock_transfers',
            'cash_sessions',
            
            // Entités
            'products',
            'customers',
            'suppliers',
            'employees',
            'employee_documents',
            'warehouses',
            'warehouse_locations',
            'bank_accounts',
            
            // RH
            'attendances',
            'attendance_qr_tokens',
            'leave_requests',
            'commissions',
            'schedules',
            
            // Comptabilité
            'accounting_categories',
            'accounting_rules',
            
            // Autres
            'order_approvals',
        ];

        $ordered = [];
        
        // D'abord ajouter les tables dans l'ordre de priorité
        foreach ($priority as $table) {
            if (in_array($table, $tables)) {
                $ordered[] = $table;
            }
        }
        
        // Ensuite ajouter les tables restantes
        foreach ($tables as $table) {
            if (!in_array($table, $ordered)) {
                $ordered[] = $table;
            }
        }

        return $ordered;
    }
}

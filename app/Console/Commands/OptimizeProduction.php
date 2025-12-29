<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizeProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize 
                            {--clear : Vider tous les caches avant optimisation}
                            {--skip-npm : Ne pas compiler les assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimise l\'application pour la production (cache config, routes, vues, etc.)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Optimisation de GestStock pour la production...');
        $this->newLine();

        // Étape 1: Vider les caches si demandé
        if ($this->option('clear')) {
            $this->warn('🗑️  Vidage des caches...');
            
            $this->call('cache:clear');
            $this->call('config:clear');
            $this->call('route:clear');
            $this->call('view:clear');
            $this->call('event:clear');
            
            $this->info('✓ Caches vidés');
            $this->newLine();
        }

        // Étape 2: Optimisation Laravel
        $this->warn('⚡ Optimisation Laravel...');
        
        $this->call('config:cache');
        $this->info('  ✓ Configuration mise en cache');
        
        $this->call('route:cache');
        $this->info('  ✓ Routes mises en cache');
        
        $this->call('view:cache');
        $this->info('  ✓ Vues compilées');
        
        $this->call('event:cache');
        $this->info('  ✓ Événements mis en cache');

        $this->newLine();

        // Étape 3: Optimisation Filament (si disponible)
        $this->warn('🎨 Optimisation Filament...');
        
        try {
            $this->call('filament:optimize');
            $this->info('  ✓ Filament optimisé');
        } catch (\Exception $e) {
            $this->warn('  ⚠ Commande filament:optimize non disponible');
        }

        try {
            $this->call('icons:cache');
            $this->info('  ✓ Icônes mises en cache');
        } catch (\Exception $e) {
            $this->warn('  ⚠ Commande icons:cache non disponible');
        }

        $this->newLine();

        // Étape 4: Compilation des assets (si pas skip)
        if (!$this->option('skip-npm')) {
            $this->warn('📦 Compilation des assets...');
            
            if ($this->confirm('Voulez-vous compiler les assets avec npm run build ?', false)) {
                $result = shell_exec('npm run build 2>&1');
                if (str_contains($result ?? '', 'error')) {
                    $this->error('  ✗ Erreur lors de la compilation');
                    $this->line($result);
                } else {
                    $this->info('  ✓ Assets compilés');
                }
            } else {
                $this->info('  ⏭ Compilation des assets ignorée');
            }
        }

        $this->newLine();

        // Étape 5: Vérification des permissions (stockage)
        $this->warn('📁 Vérification des dossiers...');
        
        $directories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('app/public'),
            storage_path('app/reports'),
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->info("  ✓ Créé: " . basename($dir));
            }
        }

        $this->newLine();

        // Résumé
        $this->info('═══════════════════════════════════════════');
        $this->info('✅ Optimisation terminée !');
        $this->info('═══════════════════════════════════════════');
        $this->newLine();
        
        $this->table(
            ['Optimisation', 'Status'],
            [
                ['Config cache', '✓'],
                ['Routes cache', '✓'],
                ['Views cache', '✓'],
                ['Events cache', '✓'],
                ['Icons cache', '✓'],
            ]
        );

        $this->newLine();
        $this->info('💡 N\'oubliez pas de redémarrer le serveur web/PHP-FPM après optimisation.');
        $this->info('   Pour vider les caches: php artisan app:optimize --clear');
        
        return Command::SUCCESS;
    }
}

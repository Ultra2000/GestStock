<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefreshBusinessData extends Command
{
    protected $signature = 'db:refresh-business 
                            {--force : Skip confirmation prompt}';

    protected $description = 'Truncate all business data tables while preserving users, roles, permissions, companies and system tables';

    /**
     * Tables to preserve (never truncated)
     */
    protected array $preservedTables = [
        // Auth & Users
        'users',
        'password_reset_tokens',
        'sessions',

        // Roles & Permissions (Spatie)
        'roles',
        'permissions',
        'model_has_roles',
        'role_has_permissions',

        // Companies & associations
        'companies',
        'company_user',

        // System / Laravel
        'migrations',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',

        // SQLite internal
        'sqlite_sequence',
    ];

    public function handle(): int
    {
        $tables = $this->getTablesToTruncate();

        if (empty($tables)) {
            $this->info('No tables to truncate.');
            return 0;
        }

        $this->warn('The following tables will be TRUNCATED (all data deleted):');
        $this->newLine();

        $tableData = [];
        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $tableData[] = [$table, $count];
        }
        $this->table(['Table', 'Rows'], $tableData);

        $this->newLine();
        $this->info('Preserved tables: ' . implode(', ', $this->preservedTables));
        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Are you sure you want to delete ALL data from these tables? This cannot be undone.')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->truncateTables($tables);

        return 0;
    }

    protected function getTablesToTruncate(): array
    {
        $allTables = collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
            ->pluck('name')
            ->toArray();

        return array_values(array_diff($allTables, $this->preservedTables));
    }

    protected function truncateTables(array $tables): void
    {
        $this->info('Disabling foreign key checks...');
        DB::statement('PRAGMA foreign_keys = OFF;');

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        $totalDeleted = 0;

        foreach ($tables as $table) {
            try {
                $count = DB::table($table)->count();
                DB::table($table)->delete();

                // Reset auto-increment for SQLite
                DB::statement("DELETE FROM sqlite_sequence WHERE name = ?", [$table]);

                $totalDeleted += $count;
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to truncate '{$table}': {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);

        DB::statement('PRAGMA foreign_keys = ON;');
        $this->info("Done! Truncated {$totalDeleted} rows across " . count($tables) . " tables.");
        $this->info('Preserved: users, roles, permissions, companies and system tables.');
    }
}

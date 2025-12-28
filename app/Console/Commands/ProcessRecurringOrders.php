<?php

namespace App\Console\Commands;

use App\Models\RecurringOrder;
use Illuminate\Console\Command;

class ProcessRecurringOrders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'orders:process-recurring 
                            {--dry-run : Show what would be processed without making changes}
                            {--company= : Process only for a specific company ID}';

    /**
     * The console command description.
     */
    protected $description = 'Process recurring orders that are due for execution';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $companyId = $this->option('company');

        $this->info('Processing recurring orders...');

        $query = RecurringOrder::where('status', 'active')
            ->where('auto_generate', true)
            ->where('next_execution', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_executions')
                  ->orWhereRaw('executions_count < max_executions');
            });

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $recurringOrders = $query->get();

        if ($recurringOrders->isEmpty()) {
            $this->info('No recurring orders to process.');
            return 0;
        }

        $this->info("Found {$recurringOrders->count()} recurring order(s) to process.");

        $processed = 0;
        $errors = 0;

        foreach ($recurringOrders as $order) {
            $this->line("Processing: {$order->reference} - {$order->name}");

            if ($dryRun) {
                $this->info("  [DRY RUN] Would generate sale for customer: {$order->customer->name}");
                $this->info("  [DRY RUN] Amount: " . number_format($order->total_amount, 2) . " €");
                continue;
            }

            try {
                $sale = $order->generateSale();

                if ($sale) {
                    $processed++;
                    $this->info("  ✓ Sale #{$sale->reference} created for {$order->customer->name}");
                    
                    // Send invoice by email if configured
                    if ($order->auto_send_invoice && $order->customer->email) {
                        try {
                            // TODO: Implement invoice email sending
                            $this->line("    → Invoice email would be sent to {$order->customer->email}");
                        } catch (\Exception $e) {
                            $this->warn("    → Failed to send invoice email: {$e->getMessage()}");
                        }
                    }
                } else {
                    $errors++;
                    $this->error("  ✗ Failed to create sale");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Processing complete:");
        $this->line("  - Processed: {$processed}");
        $this->line("  - Errors: {$errors}");

        return $errors > 0 ? 1 : 0;
    }
}

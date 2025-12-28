<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Entrepôts / Magasins
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('type', ['warehouse', 'store', 'supplier', 'customer'])->default('warehouse');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('FR');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('is_pos_location')->default(false); // Pour la caisse
            $table->json('settings')->nullable(); // Paramètres spécifiques
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'type']);
        });

        // Emplacements dans l'entrepôt (rayons, étagères, zones)
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->enum('type', ['zone', 'aisle', 'rack', 'shelf', 'bin'])->default('shelf');
            $table->string('barcode', 50)->nullable();
            $table->integer('capacity')->nullable(); // Capacité max en unités
            $table->decimal('max_weight', 10, 2)->nullable(); // Poids max en kg
            $table->boolean('is_picking_location')->default(true); // Zone de prélèvement
            $table->boolean('is_receiving_location')->default(false); // Zone de réception
            $table->boolean('is_shipping_location')->default(false); // Zone d'expédition
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
            $table->index(['company_id', 'warehouse_id']);
        });

        // Stock par entrepôt (relation produit-entrepôt)
        Schema::create('product_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0); // Réservé pour commandes
            $table->decimal('min_quantity', 15, 4)->nullable(); // Stock min pour cet entrepôt
            $table->decimal('max_quantity', 15, 4)->nullable(); // Stock max
            $table->decimal('reorder_point', 15, 4)->nullable(); // Point de réapprovisionnement
            $table->decimal('reorder_quantity', 15, 4)->nullable(); // Quantité à commander
            $table->date('last_inventory_date')->nullable();
            $table->decimal('last_inventory_quantity', 15, 4)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'location_id'], 'product_warehouse_location_unique');
            $table->index(['company_id', 'warehouse_id']);
            $table->index(['product_id', 'warehouse_id']);
        });

        // Transferts inter-entrepôts
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 30)->unique();
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses');
            $table->enum('status', [
                'draft',        // Brouillon
                'pending',      // En attente de validation
                'approved',     // Approuvé
                'in_transit',   // En transit
                'partial',      // Partiellement reçu
                'completed',    // Terminé
                'cancelled'     // Annulé
            ])->default('draft');
            $table->date('transfer_date');
            $table->date('expected_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('received_date')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->integer('total_items')->default(0);
            $table->decimal('total_quantity', 15, 4)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'transfer_date']);
        });

        // Lignes de transfert
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('source_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->foreignId('destination_location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->decimal('quantity_requested', 15, 4);
            $table->decimal('quantity_shipped', 15, 4)->default(0);
            $table->decimal('quantity_received', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('stock_transfer_id');
        });

        // Mouvements de stock (historique complet)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->enum('type', [
                'purchase',         // Achat
                'sale',             // Vente
                'transfer_out',     // Sortie transfert
                'transfer_in',      // Entrée transfert
                'adjustment_in',    // Ajustement positif
                'adjustment_out',   // Ajustement négatif
                'inventory',        // Inventaire
                'return_in',        // Retour client
                'return_out',       // Retour fournisseur
                'production_in',    // Production entrée
                'production_out',   // Production sortie
                'waste',            // Perte/Casse
                'initial'           // Stock initial
            ]);
            $table->decimal('quantity', 15, 4); // + pour entrée, - pour sortie
            $table->decimal('quantity_before', 15, 4);
            $table->decimal('quantity_after', 15, 4);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->string('reference')->nullable(); // Référence du document source
            $table->string('moveable_type')->nullable(); // Polymorphic
            $table->unsignedBigInteger('moveable_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'product_id']);
            $table->index(['company_id', 'warehouse_id']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'created_at']);
            $table->index(['moveable_type', 'moveable_id']);
        });

        // Inventaires
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('reference', 30)->unique();
            $table->string('name');
            $table->enum('type', ['full', 'partial', 'cycle'])->default('full');
            $table->enum('status', ['draft', 'in_progress', 'pending_validation', 'validated', 'cancelled'])->default('draft');
            $table->date('inventory_date');
            $table->date('validated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('total_items')->default(0);
            $table->integer('items_counted')->default(0);
            $table->integer('discrepancies_count')->default(0);
            $table->decimal('total_value_expected', 15, 2)->default(0);
            $table->decimal('total_value_counted', 15, 2)->default(0);
            $table->decimal('value_difference', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });

        // Lignes d'inventaire
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->nullOnDelete();
            $table->decimal('quantity_expected', 15, 4);
            $table->decimal('quantity_counted', 15, 4)->nullable();
            $table->decimal('quantity_difference', 15, 4)->nullable();
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('value_difference', 15, 2)->nullable();
            $table->boolean('is_counted')->default(false);
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('inventory_id');
            $table->unique(['inventory_id', 'product_id', 'location_id'], 'inventory_product_location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('product_warehouse');
        Schema::dropIfExists('warehouse_locations');
        Schema::dropIfExists('warehouses');
    }
};

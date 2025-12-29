<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ajoute des index pour améliorer les performances des requêtes fréquentes
     */
    public function up(): void
    {
        // Helper pour vérifier si un index existe (SQLite compatible)
        $indexExists = function ($table, $indexName) {
            try {
                $indexes = DB::select("PRAGMA index_list($table)");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
            } catch (\Exception $e) {
                // Fallback pour autres DB
            }
            return false;
        };

        // Index sur Products
        Schema::table('products', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('products', 'products_company_code_index')) {
                $table->index(['company_id', 'code'], 'products_company_code_index');
            }
            if (!$indexExists('products', 'products_company_name_index')) {
                $table->index(['company_id', 'name'], 'products_company_name_index');
            }
            if (!$indexExists('products', 'products_company_stock_index')) {
                $table->index(['company_id', 'stock'], 'products_company_stock_index');
            }
            if (!$indexExists('products', 'products_supplier_index')) {
                $table->index('supplier_id', 'products_supplier_index');
            }
        });

        // Index sur Sales
        Schema::table('sales', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('sales', 'sales_company_status_date_index')) {
                $table->index(['company_id', 'status', 'created_at'], 'sales_company_status_date_index');
            }
            if (!$indexExists('sales', 'sales_company_customer_index')) {
                $table->index(['company_id', 'customer_id'], 'sales_company_customer_index');
            }
            if (!$indexExists('sales', 'sales_invoice_number_index')) {
                $table->index('invoice_number', 'sales_invoice_number_index');
            }
        });

        // Index sur Purchases
        Schema::table('purchases', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('purchases', 'purchases_company_status_date_index')) {
                $table->index(['company_id', 'status', 'created_at'], 'purchases_company_status_date_index');
            }
            if (!$indexExists('purchases', 'purchases_company_supplier_index')) {
                $table->index(['company_id', 'supplier_id'], 'purchases_company_supplier_index');
            }
            if (!$indexExists('purchases', 'purchases_invoice_number_index')) {
                $table->index('invoice_number', 'purchases_invoice_number_index');
            }
        });

        // Index sur Sale Items
        Schema::table('sale_items', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('sale_items', 'sale_items_sale_product_index')) {
                $table->index(['sale_id', 'product_id'], 'sale_items_sale_product_index');
            }
        });

        // Index sur Purchase Items
        Schema::table('purchase_items', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('purchase_items', 'purchase_items_purchase_product_index')) {
                $table->index(['purchase_id', 'product_id'], 'purchase_items_purchase_product_index');
            }
        });

        // Index sur Customers
        Schema::table('customers', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('customers', 'customers_company_name_index')) {
                $table->index(['company_id', 'name'], 'customers_company_name_index');
            }
            if (!$indexExists('customers', 'customers_company_email_index')) {
                $table->index(['company_id', 'email'], 'customers_company_email_index');
            }
        });

        // Index sur Suppliers
        Schema::table('suppliers', function (Blueprint $table) use ($indexExists) {
            if (!$indexExists('suppliers', 'suppliers_company_name_index')) {
                $table->index(['company_id', 'name'], 'suppliers_company_name_index');
            }
        });

        // Index sur Stock Movements
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('stock_movements', 'stock_movements_company_product_date_index')) {
                    $table->index(['company_id', 'product_id', 'created_at'], 'stock_movements_company_product_date_index');
                }
                if (!$indexExists('stock_movements', 'stock_movements_warehouse_index')) {
                    $table->index('warehouse_id', 'stock_movements_warehouse_index');
                }
            });
        }

        // Index sur Product Warehouse (pivot)
        if (Schema::hasTable('product_warehouse')) {
            Schema::table('product_warehouse', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('product_warehouse', 'product_warehouse_quantity_index')) {
                    $table->index(['product_id', 'quantity'], 'product_warehouse_quantity_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_company_code_index');
            $table->dropIndex('products_company_name_index');
            $table->dropIndex('products_company_stock_index');
            $table->dropIndex('products_supplier_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_company_status_date_index');
            $table->dropIndex('sales_company_customer_index');
            $table->dropIndex('sales_invoice_number_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_company_status_date_index');
            $table->dropIndex('purchases_company_supplier_index');
            $table->dropIndex('purchases_invoice_number_index');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_sale_product_index');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex('purchase_items_purchase_product_index');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('customers_company_name_index');
            $table->dropIndex('customers_company_email_index');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex('suppliers_company_name_index');
        });

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropIndex('stock_movements_company_product_date_index');
                $table->dropIndex('stock_movements_warehouse_index');
            });
        }

        if (Schema::hasTable('product_warehouse')) {
            Schema::table('product_warehouse', function (Blueprint $table) {
                $table->dropIndex('product_warehouse_quantity_index');
            });
        }
    }
};

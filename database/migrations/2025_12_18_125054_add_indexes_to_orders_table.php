<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop existing index to extend it with created_at
            $table->dropIndex('orders_symbol_side_status_price_index');

            // Composite index for order matching queries (filter + sort by price, created_at)
            $table->index(['symbol', 'side', 'status', 'price', 'created_at'], 'orders_matching_index');

            // Index for fetching open orders sorted by date (MatchingController)
            $table->index(['status', 'created_at'], 'orders_status_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_matching_index');
            $table->dropIndex('orders_status_created_index');

            // Restore original index
            $table->index(['symbol', 'side', 'status', 'price'], 'orders_symbol_side_status_price_index');
        });
    }
};

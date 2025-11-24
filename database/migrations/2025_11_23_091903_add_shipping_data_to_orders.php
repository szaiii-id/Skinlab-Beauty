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
            $table->string('shipping_tracking_number')->nullable()->after('shipping_cost');
            $table->string('shipping_label_url')->nullable()->after('shipping_tracking_number');
            $table->string('komerce_order_id')->nullable()->after('shipping_label_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_tracking_number', 'shipping_label_url', 'komerce_order_id']);         
        });
    }
};

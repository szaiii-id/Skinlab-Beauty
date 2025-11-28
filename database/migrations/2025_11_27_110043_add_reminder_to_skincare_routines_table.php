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
        Schema::table('skincare_routines', function (Blueprint $table) {
            $table->time('reminder_time')->nullable()->after('period'); // Jam (08:00:00)
            $table->boolean('is_reminder_active')->default(false)->after('reminder_time'); // Status ON/OFF
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skincare_routines', function (Blueprint $table) {
            $table->dropColumn(['reminder_time', 'is_reminder_active']);
        });
    }
};

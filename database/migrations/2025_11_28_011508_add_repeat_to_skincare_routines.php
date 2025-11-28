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
            $table->integer('repeat_frequency')->default(1)->after('is_reminder_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skincare_routines', function (Blueprint $table) {
            $table->dropColumn('repeat_frequency');
        });
    }
};

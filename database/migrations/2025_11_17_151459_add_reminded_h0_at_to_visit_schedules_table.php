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
        Schema::table('visit_schedules', function (Blueprint $table) {
            $table->dateTime('reminded_h0_at')->nullable()->after('reminded_1h_at');
        });
    }

    public function down(): void
    {
        Schema::table('visit_schedules', function (Blueprint $table) {
            $table->dropColumn('reminded_h0_at');
        });
    }
};

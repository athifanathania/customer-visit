<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Tambahan kolom baru
            if (!Schema::hasColumn('customers', 'code')) {
                $table->string('code', 50)->unique()->after('id'); // KonsumenID
            }
            if (!Schema::hasColumn('customers', 'address')) {
                $table->text('address')->nullable()->after('name'); // Alamat
            }

            // Kolom tak terpakai — hapus jika ada
            foreach (['industry','region_city','email','notes'] as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'code'))    $table->dropColumn('code');
            if (Schema::hasColumn('customers', 'address')) $table->dropColumn('address');

            // opsional: kembalikan kolom lama bila perlu
            // $table->string('industry',100)->nullable();
            // $table->string('region_city')->nullable();
            // $table->string('email',150)->nullable();
            // $table->text('notes')->nullable();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->string('pic_imm')->nullable()->after('location');
            $table->string('cust_pic')->nullable()->after('pic_imm');
            $table->longText('problem_info')->nullable()->after('discussion_points');
            $table->longText('countermeasure')->nullable()->after('problem_info');
        });
    }

    public function down(): void
    {
        Schema::table('visit_reports', function (Blueprint $table) {
            $table->dropColumn(['pic_imm','cust_pic','problem_info','countermeasure']);
        });
    }
};

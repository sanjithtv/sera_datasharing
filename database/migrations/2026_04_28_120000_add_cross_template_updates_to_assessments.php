<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(@@sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");

        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('sr_licensee_assessments', 'cross_template_updates')) {
                $table->integer('cross_template_updates')->default(0)->after('updated_rows');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('sr_licensee_assessments', 'cross_template_updates')) {
                $table->dropColumn('cross_template_updates');
            }
        });
    }
};

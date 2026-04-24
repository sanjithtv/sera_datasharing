<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Relax strict zero-date modes for this session so the ALTER succeeds even if
        // an existing column on the table has a legacy zero-default (e.g. updated_at).
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(@@sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");

        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('sr_licensee_assessments', 'inserted_rows')) {
                $table->integer('inserted_rows')->default(0)->after('duplicate_rows');
            }
            if (!Schema::hasColumn('sr_licensee_assessments', 'updated_rows')) {
                $table->integer('updated_rows')->default(0)->after('inserted_rows');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            if (Schema::hasColumn('sr_licensee_assessments', 'updated_rows')) {
                $table->dropColumn('updated_rows');
            }
            if (Schema::hasColumn('sr_licensee_assessments', 'inserted_rows')) {
                $table->dropColumn('inserted_rows');
            }
        });
    }
};

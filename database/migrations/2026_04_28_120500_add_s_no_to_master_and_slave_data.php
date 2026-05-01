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

        // Staging table: capture s_no for each ingested row.
        Schema::table('slave_master_data', function (Blueprint $table) {
            if (!Schema::hasColumn('slave_master_data', 's_no')) {
                $table->string('s_no', 64)->nullable()->after('row_hash');
            }
        });

        // Master table: store s_no on every cell of a row (denormalized).
        // This is the unique identifier used for cross-template duplicate detection.
        Schema::table('sr_licensee_assessment_master_data', function (Blueprint $table) {
            if (!Schema::hasColumn('sr_licensee_assessment_master_data', 's_no')) {
                $table->string('s_no', 64)->nullable()->after('entry_counter');
            }
        });

        // Composite index used by IngestionUpsertHelper::preloadExistingRecords
        // to look up rows by (sheet, s_no) across the whole template scope.
        $hasIdx = collect(DB::select("SHOW INDEX FROM sr_licensee_assessment_master_data"))
            ->contains(fn ($r) => $r->Key_name === 'idx_md_sheet_sno');
        if (!$hasIdx) {
            DB::statement('CREATE INDEX idx_md_sheet_sno ON sr_licensee_assessment_master_data (template_sheet_id, s_no)');
        }
    }

    public function down(): void
    {
        $hasIdx = collect(DB::select("SHOW INDEX FROM sr_licensee_assessment_master_data"))
            ->contains(fn ($r) => $r->Key_name === 'idx_md_sheet_sno');
        if ($hasIdx) {
            DB::statement('DROP INDEX idx_md_sheet_sno ON sr_licensee_assessment_master_data');
        }

        Schema::table('sr_licensee_assessment_master_data', function (Blueprint $table) {
            if (Schema::hasColumn('sr_licensee_assessment_master_data', 's_no')) {
                $table->dropColumn('s_no');
            }
        });

        Schema::table('slave_master_data', function (Blueprint $table) {
            if (Schema::hasColumn('slave_master_data', 's_no')) {
                $table->dropColumn('s_no');
            }
        });
    }
};

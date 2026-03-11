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
        // 1. Add duplicate_rows to assessments
        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('sr_licensee_assessments', 'duplicate_rows')) {
                $table->integer('duplicate_rows')->default(0)->after('skipped_rows');
            }
        });

        // 2. Add row_hash to slave_master_data
        Schema::table('slave_master_data', function (Blueprint $table) {
            if (!Schema::hasColumn('slave_master_data', 'row_hash')) {
                $table->string('row_hash')->nullable()->after('sheet_id');
                $table->index(['assessment_id', 'row_hash'], 'idx_assessment_hash');
            }
        });

        // 3. Create sr_assessment_row_hashes table
        if (!Schema::hasTable('sr_assessment_row_hashes')) {
            Schema::create('sr_assessment_row_hashes', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('assessment_id');
                $table->unsignedInteger('sheet_id');
                $table->string('row_hash');
                $table->timestamp('created_at')->useCurrent();

                $table->index('assessment_id');
                $table->index('row_hash');
                $table->unique(['assessment_id', 'sheet_id', 'row_hash'], 'uidx_assessment_sheet_hash');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sr_assessment_row_hashes');

        Schema::table('slave_master_data', function (Blueprint $table) {
            $table->dropIndex('idx_assessment_hash');
            $table->dropColumn('row_hash');
        });

        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            $table->dropColumn('duplicate_rows');
        });
    }
};

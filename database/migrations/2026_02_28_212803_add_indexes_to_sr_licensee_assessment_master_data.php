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
        Schema::table('sr_licensee_assessment_master_data', function (Blueprint $table) {
            $table->index('assessment_id');
            // Composite index for the chunked loading in AssessmentController@show
            $table->index(['assessment_id', 'template_sheet_id', 'entry_counter'], 'idx_assessment_sheet_entry');
            // Index for licensee filters
            $table->index('licensee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_licensee_assessment_master_data', function (Blueprint $table) {
            $table->dropIndex(['assessment_id']);
            $table->dropIndex('idx_assessment_sheet_entry');
            $table->dropIndex(['licensee_id']);
        });
    }
};

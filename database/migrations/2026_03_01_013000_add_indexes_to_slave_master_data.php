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
        Schema::table('slave_master_data', function (Blueprint $table) {
            // Composite index for the reviewParsed preview logic
            // (assessment_id, sheet_id, status, row_index)
            $table->index(['assessment_id', 'sheet_id', 'status', 'row_index'], 'idx_review_preview');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slave_master_data', function (Blueprint $table) {
            $table->dropIndex('idx_review_preview');
        });
    }
};

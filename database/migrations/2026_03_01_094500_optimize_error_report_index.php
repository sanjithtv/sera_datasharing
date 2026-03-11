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
            // Optimized index for global assessment error report downloads
            $table->index(['assessment_id', 'status', 'row_index'], 'idx_assessment_status_row');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slave_master_data', function (Blueprint $table) {
            $table->dropIndex('idx_assessment_status_row');
        });
    }
};

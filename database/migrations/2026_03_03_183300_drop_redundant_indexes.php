<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop single-column assessment_id indexes that are fully redundant because
 * they are the left-most prefix of existing composite indexes:
 *
 *  - sr_licensee_assessment_master_data.assessment_id_index
 *    → covered by idx_assessment_sheet_entry (assessment_id, template_sheet_id, entry_counter)
 *
 *  - sr_assessment_row_hashes.assessment_id_index
 *    → covered by uidx_assessment_sheet_hash (assessment_id, sheet_id, row_hash)
 *
 * Keeping them wastes ~15-20% extra write I/O on every INSERT and doubles
 * the memory footprint for those index pages in the buffer pool.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. sr_licensee_assessment_master_data — drop redundant assessment_id index
        Schema::table('sr_licensee_assessment_master_data', function (Blueprint $table) {
            $indexName = 'sr_licensee_assessment_master_data_assessment_id_index';
            if ($this->indexExists('sr_licensee_assessment_master_data', $indexName)) {
                $table->dropIndex($indexName);
            }
        });

        // 2. sr_assessment_row_hashes — drop redundant assessment_id index
        Schema::table('sr_assessment_row_hashes', function (Blueprint $table) {
            $indexName = 'sr_assessment_row_hashes_assessment_id_index';
            if ($this->indexExists('sr_assessment_row_hashes', $indexName)) {
                $table->dropIndex($indexName);
            }
        });
    }

    public function down(): void
    {
        // Re-create the single-column indexes if rolling back
        Schema::table('sr_licensee_assessment_master_data', function (Blueprint $table) {
            $table->index('assessment_id');
        });

        Schema::table('sr_assessment_row_hashes', function (Blueprint $table) {
            $table->index('assessment_id');
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
};

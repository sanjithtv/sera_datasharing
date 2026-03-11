<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adding 'parsed' and 'committing' which were missing in previous status enum migrations
        DB::statement("ALTER TABLE sr_licensee_assessments MODIFY COLUMN status ENUM('draft','active','completed','archived','processing_preview','failed', 'processing', 'parsed', 'committing') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE sr_licensee_assessments MODIFY COLUMN status ENUM('draft','active','completed','archived','processing_preview','failed', 'processing') NOT NULL DEFAULT 'draft'");
    }
};

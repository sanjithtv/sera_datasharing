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
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE sr_licensee_assessments MODIFY COLUMN status ENUM('draft','active','completed','archived','processing_preview','failed', 'processing', 'parsed', 'committing') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE sr_licensee_assessments MODIFY COLUMN status ENUM('draft','active','completed','archived','processing_preview','failed') NOT NULL DEFAULT 'draft'");
    }
};

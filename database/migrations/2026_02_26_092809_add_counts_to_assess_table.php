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
        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            if (!Schema::hasColumn('sr_licensee_assessments', 'imported_rows')) {
                $table->integer('imported_rows')->default(0)->after('status');
            }
            if (!Schema::hasColumn('sr_licensee_assessments', 'skipped_rows')) {
                $table->integer('skipped_rows')->default(0)->after('imported_rows');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            $table->dropColumn(['imported_rows', 'skipped_rows']);
        });
    }
};

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
            $table->integer('total_rows')->default(0)->after('status');
            $table->integer('processed_rows')->default(0)->after('total_rows');
            $table->integer('finalized_rows')->default(0)->after('processed_rows');
            $table->string('processing_error')->nullable()->after('finalized_rows');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sr_licensee_assessments', function (Blueprint $table) {
            $table->dropColumn(['total_rows', 'processed_rows', 'finalized_rows', 'processing_error']);
        });
    }
};

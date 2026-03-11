<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OptimizeDatabaseTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimizes heavy MySQL tables to defragment space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = [
            'sr_licensee_assessment_master_data',
            'slave_master_data',
            'sr_assessment_row_hashes'
        ];

        $this->info('Starting database table optimization...');

        foreach ($tables as $table) {
            $this->info("Optimizing table: {$table}");
            try {
                \Illuminate\Support\Facades\DB::statement("OPTIMIZE TABLE {$table}");
                $this->info("Successfully optimized: {$table}");
            } catch (\Exception $e) {
                $this->error("Failed to optimize {$table}: " . $e->getMessage());
                \Illuminate\Support\Facades\Log::error("Failed to optimize {$table}: " . $e->getMessage());
            }
        }

        $this->info('Database optimization complete!');
        \Illuminate\Support\Facades\Log::info('Daily database table optimization completed successfully.');
    }
}

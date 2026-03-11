<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CleanTempUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-temp-uploads {--days=1 : The number of days to keep files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes old files from temporary upload directories to free up disk space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Starting cleanup of temporary files older than {$days} day(s)...");

        $directories = [
            'temp_uploads',
            'imports',
        ];

        $thresholdTime = Carbon::now()->subDays($days)->timestamp;
        $deletedCount = 0;
        $freedSpace = 0;

        foreach ($directories as $directory) {
            $this->info("Scanning directory: storage/app/{$directory}...");

            if (!Storage::disk('local')->exists($directory)) {
                $this->warn("Directory {$directory} does not exist. Skipping.");
                continue;
            }

            $files = Storage::disk('local')->allFiles($directory);

            foreach ($files as $file) {
                $lastModified = Storage::disk('local')->lastModified($file);

                if ($lastModified < $thresholdTime) {
                    $size = Storage::disk('local')->size($file);
                    Storage::disk('local')->delete($file);
                    
                    $freedSpace += $size;
                    $deletedCount++;
                    
                    $this->line("Deleted: {$file}");
                }
            }
        }

        // Convert bytes to MB
        $freedSpaceMb = round($freedSpace / 1048576, 2);

        $summary = "Cleanup completed. Deleted {$deletedCount} file(s). Freed {$freedSpaceMb} MB of disk space.";
        $this->info($summary);
        Log::info("CleanTempUploads: " . $summary);
    }
}

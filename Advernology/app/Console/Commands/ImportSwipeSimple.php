<?php

namespace App\Console\Commands;

use App\Services\SwipeSimpleImportService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;

class ImportSwipeSimple extends Command
{
    protected $signature = 'swipesimple:import {file} {--since= : Only import transactions on/after this date (Y-m-d)}';
    protected $description = 'Import a SwipeSimple transactions export (email products only)';

    public function handle(SwipeSimpleImportService $service): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $uploaded = new UploadedFile($path, basename($path), null, null, true);
        $log      = $service->import($uploaded, null, $this->option('since'));

        $this->info("Imported: {$log->rows_imported}, Refunded: {$log->rows_refunded}, Skipped: {$log->rows_skipped}");

        if ($log->errors) {
            $this->warn($log->errors);
        }

        return self::SUCCESS;
    }
}

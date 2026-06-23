<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Phase 8 — database backup.
 *
 * Wraps `mysqldump` for the configured MySQL connection and writes a timestamped
 * .sql file to storage/app/backups/, pruning old backups beyond --keep.
 *
 * Usage:
 *   php artisan db:backup
 *   php artisan db:backup --keep=14
 *
 * Restore (manual):
 *   mysql -u <user> -p <database> < storage/app/backups/<file>.sql
 *
 * Schedule it in routes/console.php (e.g. ->daily()) on production, and ensure
 * mysqldump is on the PATH (it ships with the MySQL/MariaDB client tools).
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep=7 : Number of most-recent backups to retain}';

    protected $description = 'Create a timestamped mysqldump of the database into storage/app/backups';

    public function handle(): int
    {
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            $this->error("db:backup supports the MySQL connection only (current: {$connection}).");

            return self::FAILURE;
        }

        $config = config("database.connections.{$connection}");
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        // Caller passes a fixed timestamp via the schedule if reproducibility is
        // needed; here we stamp at run time.
        $stamp = date('Y-m-d_His');
        $file = "{$dir}/{$config['database']}_{$stamp}.sql";

        $arguments = array_filter([
            'mysqldump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            $config['password'] !== '' && $config['password'] !== null ? '--password='.$config['password'] : null,
            '--single-transaction',
            '--routines',
            '--no-tablespaces',
            $config['database'],
        ]);

        $this->info("Backing up '{$config['database']}' → {$file}");

        $process = new Process($arguments);
        $process->setTimeout(600);

        $handle = fopen($file, 'w');

        try {
            $process->run(function ($type, $buffer) use ($handle) {
                if ($type === Process::OUT) {
                    fwrite($handle, $buffer);
                }
            });
        } finally {
            fclose($handle);
        }

        if (! $process->isSuccessful()) {
            // Don't leave a half-written / empty dump behind.
            File::delete($file);
            $this->error('mysqldump failed: '.trim($process->getErrorOutput()));
            $this->line('Ensure the mysqldump client tool is installed and on the PATH.');

            return self::FAILURE;
        }

        $sizeKb = round(filesize($file) / 1024, 1);
        $this->info("Backup complete ({$sizeKb} KB).");

        $this->prune($dir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    /**
     * Keep only the newest $keep .sql backups; delete the rest.
     */
    private function prune(string $dir, int $keep): void
    {
        if ($keep <= 0) {
            return;
        }

        $backups = collect(File::glob("{$dir}/*.sql"))
            ->sortByDesc(fn ($path) => File::lastModified($path))
            ->values();

        $stale = $backups->slice($keep);

        foreach ($stale as $path) {
            File::delete($path);
        }

        if ($stale->isNotEmpty()) {
            $this->line("Pruned {$stale->count()} old backup(s), kept {$keep}.");
        }
    }
}

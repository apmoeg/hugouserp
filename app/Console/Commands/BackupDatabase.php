<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * Signature:
     *  --verify   After backup, run a lightweight verification (checksum / size / restore dry-run if implemented).
     */
    protected $signature = 'system:backup {--verify}';

    protected $description = 'Run database (and configured files) backup with optional verification step.';

    public function __construct(private readonly BackupService $backupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $verify = (bool) $this->option('verify');

        $lock = Cache::lock('cmd:system:backup', 1800); // 30 minutes
        if (! $lock->get()) {
            $this->warn('Another backup process is already running. Exiting.');

            return self::FAILURE;
        }

        try {
            Log::info('Backup started', [
                'request_id' => app()->bound('request_id') ? app('request_id') : null,
            ]);

            $result = $this->backupService->run($verify);
            $path = (string) ($result['path'] ?? '');
            $size = (int) ($result['size'] ?? 0);

            $this->info('✔ Backup completed');
            $this->line("   File: {$path}");
            $this->line("   Size: {$size} bytes");

            if ($verify) {
                $this->line('→ Verifying backup...');
                $ok = $this->backupService->verify($result);
                if ($ok) {
                    $this->info('✔ Verification passed.');
                } else {
                    $this->error('✖ Verification failed!');

                    return self::FAILURE;
                }
            }

            Log::info('Backup finished', [
                'file' => $path,
                'size' => $size,
                'verified' => $verify ?? false,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Backup error', ['error' => $e->getMessage()]);
            $this->error('✖ Backup failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }
}

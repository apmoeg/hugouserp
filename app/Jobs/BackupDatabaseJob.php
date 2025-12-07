<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public $timeout = 900; // 15 min

    public function __construct(public bool $verify = true) {}

    public function handle(): void
    {
        // You can replace with spatie/laravel-backup if installed
        $filename = 'backup_'.now()->format('Ymd_His').'.sql.gz';
        $disk = Storage::disk(config('backup.disk', 'local'));
        $path = 'backups/'.$filename;

        // Try using an artisan command if exists; fallback to mysqldump
        if (Artisan::has('db:dump')) {
            Artisan::call('db:dump', ['--path' => $path]);
        } else {
            // Minimal portable fallback using mysqldump env vars
            $db = config('database.connections.mysql');
            $cmd = sprintf(
                'mysqldump -h%s -u%s -p%s %s | gzip > %s',
                escapeshellarg($db['host'] ?? '127.0.0.1'),
                escapeshellarg($db['username'] ?? ''),
                escapeshellarg($db['password'] ?? ''),
                escapeshellarg($db['database'] ?? ''),
                escapeshellarg(storage_path('app/'.$path))
            );
            @mkdir(dirname(storage_path('app/'.$path)), 0775, true);
            @exec($cmd);
        }

        if ($this->verify && ! $disk->exists($path)) {
            throw new \RuntimeException('Backup file was not generated.');
        }
    }

    public function tags(): array
    {
        return ['maintenance', 'backup'];
    }
}

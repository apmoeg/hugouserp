<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\BackupServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class BackupService implements BackupServiceInterface
{
    use HandlesServiceErrors;

    protected string $disk;

    protected string $dir;

    public function __construct()
    {
        $this->disk = (string) config('backup.disk', 'local');
        $this->dir = (string) config('backup.dir', 'backups');
    }

    public function run(bool $verify = true): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($verify) {
                $filename = 'backup_'.now()->format('Ymd_His').'.sql.gz';
                $path = trim($this->dir, '/').'/'.$filename;

                if (Artisan::has('db:dump')) {
                    Artisan::call('db:dump', ['--path' => $path]);
                } else {
                    dispatch_sync(new \App\Jobs\BackupDatabaseJob(verify: $verify));
                }

                if ($verify && ! Storage::disk($this->disk)->exists($path)) {
                    throw new \RuntimeException('Backup file missing after run.');
                }

                $size = Storage::disk($this->disk)->exists($path) 
                    ? Storage::disk($this->disk)->size($path) 
                    : 0;

                return [
                    'path' => $path,
                    'size' => $size,
                ];
            },
            operation: 'run',
            context: ['verify' => $verify]
        );
    }

    public function verify(array $result): bool
    {
        return $this->handleServiceOperation(
            callback: function () use ($result) {
                $path = $result['path'] ?? '';
                
                if (empty($path)) {
                    return false;
                }

                // Check if file exists
                if (! Storage::disk($this->disk)->exists($path)) {
                    return false;
                }

                // Check if file has content (size > 0)
                $size = Storage::disk($this->disk)->size($path);
                if ($size <= 0) {
                    return false;
                }

                // Basic verification passed
                return true;
            },
            operation: 'verify',
            context: ['path' => $result['path'] ?? ''],
            defaultValue: false
        );
    }

    public function list(): array
    {
        return $this->handleServiceOperation(
            callback: function () {
                $disk = Storage::disk($this->disk);
                $files = $disk->files($this->dir);
                $out = [];
                foreach ($files as $f) {
                    $out[] = [
                        'path' => $f,
                        'size' => (int) $disk->size($f),
                        'modified' => (int) $disk->lastModified($f),
                    ];
                }
                usort($out, fn ($a, $b) => $b['modified'] <=> $a['modified']);

                return $out;
            },
            operation: 'list',
            context: [],
            defaultValue: []
        );
    }

    public function delete(string $path): bool
    {
        return $this->handleServiceOperation(
            callback: fn () => Storage::disk($this->disk)->delete($path),
            operation: 'delete',
            context: ['path' => $path],
            defaultValue: false
        );
    }
}

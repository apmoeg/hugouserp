<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class DiagnosticsService
{
    /**
     * Run all health checks and return results
     *
     * @return array<string, mixed>
     */
    public function runAll(): array
    {
        return [
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'mail' => $this->checkMail(),
            'filesystem' => $this->checkFilesystem(),
            'database' => $this->checkDatabase(),
        ];
    }

    /**
     * Check cache connectivity
     *
     * @return array<string, mixed>
     */
    public function checkCache(): array
    {
        try {
            $key = 'diagnostics_test_'.time();
            $value = 'test_value_'.random_int(1000, 9999);

            // Write test
            Cache::put($key, $value, 60);

            // Read test
            $retrieved = Cache::get($key);

            // Delete test
            Cache::forget($key);

            $success = $retrieved === $value;

            return [
                'status' => $success ? 'ok' : 'error',
                'driver' => Config::get('cache.default'),
                'message' => $success ? 'Cache is operational' : 'Cache read/write failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'driver' => Config::get('cache.default'),
                'message' => 'Cache error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check queue connection
     *
     * @return array<string, mixed>
     */
    public function checkQueue(): array
    {
        try {
            $connection = Config::get('queue.default');

            // For sync driver, it's always available
            if ($connection === 'sync') {
                return [
                    'status' => 'ok',
                    'driver' => $connection,
                    'message' => 'Queue is operational (sync mode)',
                ];
            }

            // For database driver, check if jobs table exists
            if ($connection === 'database') {
                $table = Config::get('queue.connections.database.table', 'jobs');
                $dbConnection = Config::get('queue.connections.database.connection', Config::get('database.default'));
                $schema = DB::connection($dbConnection)->getSchemaBuilder();

                $exists = $schema->hasTable($table);

                return [
                    'status' => $exists ? 'ok' : 'warning',
                    'driver' => $connection,
                    'connection' => $dbConnection,
                    'message' => $exists
                        ? 'Queue is operational'
                        : "Queue table '{$table}' does not exist",
                ];
            }

            return [
                'status' => 'ok',
                'driver' => $connection,
                'message' => 'Queue connection available',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'driver' => Config::get('queue.default'),
                'message' => 'Queue error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check mail configuration
     *
     * @return array<string, mixed>
     */
    public function checkMail(): array
    {
        try {
            $mailer = Config::get('mail.default');
            $config = Config::get("mail.mailers.{$mailer}");

            $warnings = [];

            if ($mailer === 'smtp') {
                if (empty($config['host'])) {
                    $warnings[] = 'SMTP host not configured';
                }
                if (empty($config['username'])) {
                    $warnings[] = 'SMTP username not configured';
                }
                if (empty($config['password'])) {
                    $warnings[] = 'SMTP password not configured';
                }
            }

            $status = empty($warnings) ? 'ok' : 'warning';

            return [
                'status' => $status,
                'driver' => $mailer,
                'message' => empty($warnings)
                    ? 'Mail configuration is valid'
                    : 'Mail configuration warnings: '.implode(', ', $warnings),
                'warnings' => $warnings,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'driver' => Config::get('mail.default'),
                'message' => 'Mail configuration error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check filesystem write permissions
     *
     * @return array<string, mixed>
     */
    public function checkFilesystem(): array
    {
        try {
            $disk = Config::get('filesystems.default');
            $filename = 'diagnostics_test_'.time().'.txt';
            $content = 'test_content_'.random_int(1000, 9999);

            // Write test
            Storage::disk($disk)->put($filename, $content);

            // Read test
            $retrieved = Storage::disk($disk)->get($filename);

            // Delete test
            Storage::disk($disk)->delete($filename);

            $success = $retrieved === $content;

            return [
                'status' => $success ? 'ok' : 'error',
                'disk' => $disk,
                'message' => $success
                    ? 'Filesystem is operational'
                    : 'Filesystem read/write failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'disk' => Config::get('filesystems.default'),
                'message' => 'Filesystem error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check database connectivity
     *
     * @return array<string, mixed>
     */
    public function checkDatabase(): array
    {
        try {
            $connection = Config::get('database.default');
            DB::connection()->getPdo();

            return [
                'status' => 'ok',
                'driver' => $connection,
                'message' => 'Database is operational',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'driver' => Config::get('database.default'),
                'message' => 'Database error: '.$e->getMessage(),
            ];
        }
    }
}

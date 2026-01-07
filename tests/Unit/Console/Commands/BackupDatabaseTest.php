<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Services\BackupService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_backup_runs_without_verification_by_default(): void
    {
        // Mock the BackupService
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupService->shouldReceive('run')
            ->once()
            ->with(false) // verify should be false by default
            ->andReturn([
                'path' => 'backups/backup_20231209_120000.sql.gz',
                'size' => 1024000,
            ]);

        $this->app->instance(BackupService::class, $mockBackupService);

        // Mock cache lock
        $mockLock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $mockLock->shouldReceive('get')->once()->andReturn(true);
        $mockLock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with('cmd:system:backup', 1800)
            ->andReturn($mockLock);

        // Execute command without --verify option
        $this->artisan('system:backup')
            ->assertSuccessful();
    }

    public function test_backup_runs_with_verification_when_flag_provided(): void
    {
        // Mock the BackupService
        $mockBackupService = Mockery::mock(BackupService::class);

        $result = [
            'path' => 'backups/backup_20231209_120000.sql.gz',
            'size' => 1024000,
        ];

        $mockBackupService->shouldReceive('run')
            ->once()
            ->with(true) // verify should be true
            ->andReturn($result);

        $mockBackupService->shouldReceive('verify')
            ->once()
            ->with($result)
            ->andReturn(true);

        $this->app->instance(BackupService::class, $mockBackupService);

        // Mock cache lock
        $mockLock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $mockLock->shouldReceive('get')->once()->andReturn(true);
        $mockLock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with('cmd:system:backup', 1800)
            ->andReturn($mockLock);

        // Execute command with --verify option
        $this->artisan('system:backup', ['--verify' => true])
            ->assertSuccessful();
    }

    public function test_backup_fails_when_another_process_is_running(): void
    {
        // Mock the BackupService (should not be called)
        $mockBackupService = Mockery::mock(BackupService::class);
        $this->app->instance(BackupService::class, $mockBackupService);

        // Mock cache lock that fails to acquire
        $mockLock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $mockLock->shouldReceive('get')->once()->andReturn(false);

        Cache::shouldReceive('lock')
            ->once()
            ->with('cmd:system:backup', 1800)
            ->andReturn($mockLock);

        // Execute command
        $this->artisan('system:backup')
            ->assertFailed();
    }

    public function test_backup_fails_when_verification_fails(): void
    {
        // Mock the BackupService
        $mockBackupService = Mockery::mock(BackupService::class);

        $result = [
            'path' => 'backups/backup_20231209_120000.sql.gz',
            'size' => 1024000,
        ];

        $mockBackupService->shouldReceive('run')
            ->once()
            ->with(true)
            ->andReturn($result);

        $mockBackupService->shouldReceive('verify')
            ->once()
            ->with($result)
            ->andReturn(false); // Verification fails

        $this->app->instance(BackupService::class, $mockBackupService);

        // Mock cache lock
        $mockLock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $mockLock->shouldReceive('get')->once()->andReturn(true);
        $mockLock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with('cmd:system:backup', 1800)
            ->andReturn($mockLock);

        // Execute command with --verify option
        $this->artisan('system:backup', ['--verify' => true])
            ->assertFailed();
    }
}

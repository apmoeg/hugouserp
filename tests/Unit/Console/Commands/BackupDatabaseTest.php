<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\BackupDatabase;
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

        // Mock cache lock
        $mockLock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $mockLock->shouldReceive('get')->once()->andReturn(true);
        $mockLock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with('cmd:system:backup', 1800)
            ->andReturn($mockLock);

        // Create command with mocked service
        $command = new BackupDatabase($mockBackupService);
        $command->setLaravel($this->app);

        // Execute command without --verify option
        $exitCode = $command->handle();

        // Assert success
        $this->assertEquals(BackupDatabase::SUCCESS, $exitCode);
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

        // Mock cache lock
        $mockLock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $mockLock->shouldReceive('get')->once()->andReturn(true);
        $mockLock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with('cmd:system:backup', 1800)
            ->andReturn($mockLock);

        // Mock the option method to return true for verify
        $command = Mockery::mock(BackupDatabase::class, [$mockBackupService])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        $command->shouldReceive('option')
            ->with('verify')
            ->andReturn(true);
        
        $command->shouldReceive('info')->andReturnNull();
        $command->shouldReceive('line')->andReturnNull();
        $command->shouldReceive('warn')->andReturnNull();
        $command->shouldReceive('error')->andReturnNull();

        $command->setLaravel($this->app);

        // Execute command
        $exitCode = $command->handle();

        // Assert success
        $this->assertEquals(BackupDatabase::SUCCESS, $exitCode);
    }

    public function test_backup_fails_when_another_process_is_running(): void
    {
        // Mock the BackupService (should not be called)
        $mockBackupService = Mockery::mock(BackupService::class);
        $mockBackupService->shouldNotReceive('run');

        // Mock cache lock that fails to acquire
        $mockLock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $mockLock->shouldReceive('get')->once()->andReturn(false);

        Cache::shouldReceive('lock')
            ->once()
            ->with('cmd:system:backup', 1800)
            ->andReturn($mockLock);

        // Create command with mocked service
        $command = Mockery::mock(BackupDatabase::class, [$mockBackupService])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        $command->shouldReceive('option')
            ->with('verify')
            ->andReturn(false);
        
        $command->shouldReceive('warn')->once()->andReturnNull();

        $command->setLaravel($this->app);

        // Execute command
        $exitCode = $command->handle();

        // Assert failure
        $this->assertEquals(BackupDatabase::FAILURE, $exitCode);
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

        // Mock cache lock
        $mockLock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $mockLock->shouldReceive('get')->once()->andReturn(true);
        $mockLock->shouldReceive('release')->once();

        Cache::shouldReceive('lock')
            ->once()
            ->with('cmd:system:backup', 1800)
            ->andReturn($mockLock);

        // Create command with mocked service
        $command = Mockery::mock(BackupDatabase::class, [$mockBackupService])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        $command->shouldReceive('option')
            ->with('verify')
            ->andReturn(true);
        
        $command->shouldReceive('info')->andReturnNull();
        $command->shouldReceive('line')->andReturnNull();
        $command->shouldReceive('error')->once()->andReturnNull();

        $command->setLaravel($this->app);

        // Execute command
        $exitCode = $command->handle();

        // Assert failure
        $this->assertEquals(BackupDatabase::FAILURE, $exitCode);
    }
}

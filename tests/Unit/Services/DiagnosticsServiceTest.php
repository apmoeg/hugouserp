<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\DiagnosticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiagnosticsServiceTest extends TestCase
{
    public function test_database_queue_check_uses_configured_connection(): void
    {
        Config::set('queue.default', 'database');
        Config::set('queue.connections.database', [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
            'connection' => 'queue',
        ]);

        Config::set('database.default', 'primary');
        Config::set('database.connections.primary', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        Config::set('database.connections.queue', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::setDefaultConnection('primary');

        Schema::connection('queue')->create('jobs', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        $result = app(DiagnosticsService::class)->checkQueue();

        $this->assertSame('ok', $result['status']);
        $this->assertSame('database', $result['driver']);
        $this->assertSame('queue', $result['connection']);
        $this->assertStringContainsString('Queue is operational', $result['message']);
    }
}

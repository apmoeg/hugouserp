<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DiagnosticsService;
use Illuminate\Console\Command;

class SystemDiagnostics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:diagnostics {--format=text : Output format (text or table)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run system diagnostics to check health of cache, queue, mail, and filesystem';

    /**
     * Execute the console command.
     */
    public function handle(DiagnosticsService $service): int
    {
        $this->info('Running system diagnostics...');
        $this->newLine();

        $results = $service->runAll();

        $format = $this->option('format');

        if ($format === 'table') {
            $this->displayAsTable($results);
        } else {
            $this->displayAsText($results);
        }

        // Determine exit code based on results
        $hasErrors = collect($results)->contains(fn ($result) => $result['status'] === 'error');

        return $hasErrors ? 1 : 0;
    }

    /**
     * Display results as formatted text
     *
     * @param  array<string, mixed>  $results
     */
    private function displayAsText(array $results): void
    {
        foreach ($results as $component => $result) {
            $status = $result['status'];
            $driver = $result['driver'] ?? 'N/A';
            $message = $result['message'];

            $icon = match ($status) {
                'ok' => '✓',
                'warning' => '⚠',
                'error' => '✗',
                default => '?',
            };

            $color = match ($status) {
                'ok' => 'green',
                'warning' => 'yellow',
                'error' => 'red',
                default => 'white',
            };

            $this->line(sprintf(
                '<%s>%s %s</>: %s (driver: %s)',
                $color,
                $icon,
                ucfirst($component),
                $message,
                $driver
            ));

            if (isset($result['warnings']) && ! empty($result['warnings'])) {
                foreach ($result['warnings'] as $warning) {
                    $this->line("  <yellow>- {$warning}</>");
                }
            }
        }

        $this->newLine();
    }

    /**
     * Display results as table
     *
     * @param  array<string, mixed>  $results
     */
    private function displayAsTable(array $results): void
    {
        $rows = [];

        foreach ($results as $component => $result) {
            $status = $result['status'];
            $driver = $result['driver'] ?? 'N/A';
            $message = $result['message'];

            $icon = match ($status) {
                'ok' => '✓',
                'warning' => '⚠',
                'error' => '✗',
                default => '?',
            };

            $rows[] = [
                ucfirst($component),
                $driver,
                $icon.' '.ucfirst($status),
                $message,
            ];
        }

        $this->table(
            ['Component', 'Driver', 'Status', 'Message'],
            $rows
        );
    }
}

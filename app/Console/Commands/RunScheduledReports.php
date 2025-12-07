<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ReportTemplate;
use App\Services\ScheduledReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunScheduledReports extends Command
{
    protected $signature = 'reports:run-scheduled 
                            {--force : Run all active schedules regardless of next_run_at}
                            {--id= : Run a specific schedule by ID}';

    protected $description = 'Run scheduled reports that are due';

    protected ScheduledReportService $reportService;

    public function __construct(ScheduledReportService $reportService)
    {
        parent::__construct();
        $this->reportService = $reportService;
    }

    public function handle(): int
    {
        $this->info('Checking for scheduled reports to run...');

        $query = DB::table('report_schedules')
            ->where('is_active', true);

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        } elseif (! $this->option('force')) {
            $query->where('next_run_at', '<=', now());
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info('No scheduled reports to run.');

            return Command::SUCCESS;
        }

        $this->info("Found {$schedules->count()} report(s) to process.");

        foreach ($schedules as $schedule) {
            $this->processSchedule($schedule);
        }

        $this->info('Scheduled reports processing completed.');

        return Command::SUCCESS;
    }

    protected function processSchedule($schedule): void
    {
        $this->line("Processing: {$schedule->name}");

        try {
            $template = ReportTemplate::find($schedule->report_template_id);

            if (! $template) {
                $this->warn("  Template not found for schedule: {$schedule->name}");

                return;
            }

            $filters = json_decode($schedule->filters ?? '[]', true);

            $result = $this->reportService->generateAndSend(
                $template,
                $schedule->format,
                explode(',', $schedule->recipient_emails),
                $filters,
                $schedule->name
            );

            if ($result['success']) {
                $this->updateScheduleAfterRun($schedule);
                $this->info("  Completed: {$schedule->name}");
                $this->info("  File: {$result['file_path']}");
                $this->info('  Sent to: '.implode(', ', $result['sent_to']));
            } else {
                $this->error("  Failed: {$result['error']}");
            }

        } catch (\Exception $e) {
            Log::error("Scheduled report failed: {$schedule->name}", [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("  Failed: {$e->getMessage()}");
        }
    }

    protected function updateScheduleAfterRun($schedule): void
    {
        $nextRun = $this->calculateNextRun($schedule);

        DB::table('report_schedules')
            ->where('id', $schedule->id)
            ->update([
                'last_run_at' => now(),
                'next_run_at' => $nextRun,
                'updated_at' => now(),
            ]);
    }

    protected function calculateNextRun($schedule): string
    {
        $now = now();
        $time = explode(':', $schedule->time_of_day);
        $hour = (int) $time[0];
        $minute = (int) ($time[1] ?? 0);

        switch ($schedule->frequency) {
            case 'daily':
                $next = $now->copy()->addDay()->setTime($hour, $minute);
                break;
            case 'weekly':
                $next = $now->copy()->next($schedule->day_of_week ?? 1)->setTime($hour, $minute);
                break;
            case 'monthly':
                $next = $now->copy()->addMonth()->day($schedule->day_of_month ?? 1)->setTime($hour, $minute);
                break;
            case 'quarterly':
                $next = $now->copy()->addMonths(3)->day($schedule->day_of_month ?? 1)->setTime($hour, $minute);
                break;
            default:
                $next = $now->addDay();
        }

        return $next->toDateTimeString();
    }
}

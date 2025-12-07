<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\HRMService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunPayroll extends Command
{
    /**
     * Signature:
     *  --period=YYYY-MM   Payroll period (required). If omitted, defaults to current month.
     *  --branch=*         Repeatable: branch IDs or codes; if omitted, runs for all active branches.
     *  --approve          Approve the generated payrolls.
     *  --pay              Post payments for approved payrolls.
     */
    protected $signature = 'hrm:payroll {--period=} {--branch=*} {--approve} {--pay}';

    protected $description = 'Run monthly payroll per branch (with optional approve/pay steps).';

    public function __construct(private readonly HRMService $hrmService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $periodInput = $this->option('period') ?: Carbon::now()->format('Y-m');
        $approve = (bool) $this->option('approve');
        $pay = (bool) $this->option('pay');
        $targets = (array) $this->option('branch');

        // Validate period
        if (! preg_match('/^\d{4}\-\d{2}$/', $periodInput)) {
            $this->error('Invalid --period. Expected YYYY-MM.');

            return self::FAILURE;
        }
        $periodStart = Carbon::createFromFormat('Y-m-d', "{$periodInput}-01")->startOfDay();

        $this->info("Payroll run for period: {$periodInput}".(count($targets) ? ' | targeted branches' : ' | all active branches'));
        $this->line($approve ? 'Approval: ON' : 'Approval: OFF');
        $this->line($pay ? 'Payment: ON' : 'Payment: OFF');

        // Resolve branches
        $branches = Branch::query()->where('active', true);
        if (! empty($targets)) {
            $branches->where(function ($q) use ($targets) {
                $q->whereIn('id', $targets)->orWhereIn('code', $targets);
            });
        }
        $branches = $branches->get();

        $totalRuns = 0;

        foreach ($branches as $branch) {
            $lockKey = "cmd:hrm:payroll:{$branch->id}:{$periodInput}";
            $lock = Cache::lock($lockKey, 900); // 15 minutes

            if (! $lock->get()) {
                $this->warn("Skipped (locked) branch={$branch->id} {$branch->name}");

                continue;
            }

            try {
                Log::info('Payroll run started', [
                    'branch_id' => $branch->id,
                    'period' => $periodInput,
                ]);

                $result = $this->hrmService->runPayroll($branch, $periodStart);
                $count = (int) ($result['employees'] ?? 0);
                $this->line("✔ Branch={$branch->code} ({$branch->name}) | employees={$count}");
                $totalRuns++;

                if ($approve) {
                    $this->line("→ Approving payroll for branch={$branch->code}");
                    $this->hrmService->approvePayroll($branch, $periodStart);
                }
                if ($pay) {
                    $this->line("→ Paying payroll for branch={$branch->code}");
                    $this->hrmService->payPayroll($branch, $periodStart);
                }

                Log::info('Payroll run finished', [
                    'branch_id' => $branch->id,
                    'period' => $periodInput,
                    'employees' => $count,
                    'approved' => $approve,
                    'paid' => $pay,
                ]);
            } catch (\Throwable $e) {
                Log::error('Payroll run error', [
                    'branch_id' => $branch->id,
                    'period' => $periodInput,
                    'error' => $e->getMessage(),
                ]);
                $this->error("✖ Error on branch={$branch->code}: ".$e->getMessage());
            } finally {
                optional($lock)->release();
            }
        }

        $this->info("Payroll completed for {$totalRuns} branch(es).");

        return self::SUCCESS;
    }
}

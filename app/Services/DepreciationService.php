<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssetDepreciation;
use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for calculating and posting asset depreciation
 */
class DepreciationService
{
    /**
     * Calculate depreciation for a specific asset for a given period
     */
    public function calculateDepreciation(FixedAsset $asset, Carbon $date): ?array
    {
        if (!$asset->isActive()) {
            return null;
        }

        if ($asset->isFullyDepreciated()) {
            return null;
        }

        if (!$asset->depreciation_start_date || $date->lt($asset->depreciation_start_date)) {
            return null;
        }

        $method = $asset->depreciation_method ?? 'straight_line';

        return match ($method) {
            'straight_line' => $this->calculateStraightLine($asset, $date),
            'declining_balance' => $this->calculateDecliningBalance($asset, $date),
            'units_of_production' => $this->calculateUnitsOfProduction($asset, $date),
            default => $this->calculateStraightLine($asset, $date),
        };
    }

    /**
     * Straight-line depreciation: equal amount each period
     */
    protected function calculateStraightLine(FixedAsset $asset, Carbon $date): array
    {
        $depreciableAmount = ($asset->purchase_cost ?? 0) - ($asset->salvage_value ?? 0);
        $totalMonths = $asset->getTotalUsefulLifeMonths();
        
        if ($totalMonths <= 0) {
            return [
                'depreciation_amount' => 0,
                'accumulated_depreciation' => $asset->accumulated_depreciation,
                'book_value' => $asset->book_value,
            ];
        }

        $monthlyDepreciation = $depreciableAmount / $totalMonths;
        
        // Don't depreciate below salvage value
        $newAccumulated = min(
            $asset->accumulated_depreciation + $monthlyDepreciation,
            $depreciableAmount
        );
        
        $actualDepreciation = $newAccumulated - $asset->accumulated_depreciation;
        $newBookValue = $asset->purchase_cost - $newAccumulated;

        return [
            'depreciation_amount' => $actualDepreciation,
            'accumulated_depreciation' => $newAccumulated,
            'book_value' => $newBookValue,
        ];
    }

    /**
     * Declining balance depreciation: higher depreciation in early years
     */
    protected function calculateDecliningBalance(FixedAsset $asset, Carbon $date): array
    {
        $rate = $asset->depreciation_rate ?? 20.0; // Default 20% per year
        $monthlyRate = $rate / 100 / 12;
        
        $currentBookValue = $asset->book_value;
        $depreciation = $currentBookValue * $monthlyRate;
        
        // Don't depreciate below salvage value
        if ($currentBookValue - $depreciation < $asset->salvage_value) {
            $depreciation = max(0, $currentBookValue - $asset->salvage_value);
        }
        
        $newAccumulated = $asset->accumulated_depreciation + $depreciation;
        $newBookValue = $asset->purchase_cost - $newAccumulated;

        return [
            'depreciation_amount' => $depreciation,
            'accumulated_depreciation' => $newAccumulated,
            'book_value' => $newBookValue,
        ];
    }

    /**
     * Units of production depreciation: based on usage
     * Note: This requires tracking actual production units
     */
    protected function calculateUnitsOfProduction(FixedAsset $asset, Carbon $date): array
    {
        // Placeholder - would need to track actual production units
        // For now, fall back to straight line
        return $this->calculateStraightLine($asset, $date);
    }

    /**
     * Run depreciation for all active assets for a specific period
     */
    public function runMonthlyDepreciation(int $branchId, Carbon $date): array
    {
        $period = $date->format('Y-m');
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'total_depreciation' => 0,
            'errors' => [],
        ];

        $assets = FixedAsset::where('branch_id', $branchId)
            ->active()
            ->whereNotNull('depreciation_start_date')
            ->where('depreciation_start_date', '<=', $date)
            ->get();

        foreach ($assets as $asset) {
            try {
                // Check if already depreciated for this period
                $existing = AssetDepreciation::where('asset_id', $asset->id)
                    ->where('period', $period)
                    ->first();

                if ($existing) {
                    $results['skipped']++;
                    continue;
                }

                $calculation = $this->calculateDepreciation($asset, $date);
                
                if (!$calculation || $calculation['depreciation_amount'] <= 0) {
                    $results['skipped']++;
                    continue;
                }

                DB::transaction(function () use ($asset, $date, $period, $calculation) {
                    // Create depreciation record
                    AssetDepreciation::create([
                        'asset_id' => $asset->id,
                        'branch_id' => $asset->branch_id,
                        'depreciation_date' => $date,
                        'period' => $period,
                        'depreciation_amount' => $calculation['depreciation_amount'],
                        'accumulated_depreciation' => $calculation['accumulated_depreciation'],
                        'book_value' => $calculation['book_value'],
                        'status' => 'calculated',
                        'created_by' => auth()->id(),
                    ]);

                    // Update asset
                    $asset->update([
                        'accumulated_depreciation' => $calculation['accumulated_depreciation'],
                        'book_value' => $calculation['book_value'],
                        'last_depreciation_date' => $date,
                    ]);
                });

                $results['processed']++;
                $results['total_depreciation'] += $calculation['depreciation_amount'];
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Post depreciation entries to accounting.
     * 
     * Creates journal entries for depreciation and updates the depreciation record.
     * 
     * @param AssetDepreciation $depreciation The depreciation record to post
     * @throws \Exception If depreciation is already posted or account mapping is missing
     * @return void
     */
    public function postDepreciationToAccounting(AssetDepreciation $depreciation): void
    {
        if ($depreciation->isPosted()) {
            throw new \Exception('Depreciation already posted to accounting');
        }

        $asset = $depreciation->asset;
        
        // Get account mappings for depreciation
        $expenseAccountId = app(AccountingService::class)->getAccountMapping('fixed_assets.depreciation_expense');
        $accumulatedAccountId = app(AccountingService::class)->getAccountMapping('fixed_assets.accumulated_depreciation');
        
        if (!$expenseAccountId || !$accumulatedAccountId) {
            throw new \Exception('Depreciation account mappings not configured');
        }
        
        DB::transaction(function () use ($depreciation, $asset, $expenseAccountId, $accumulatedAccountId) {
            // Create journal entry for depreciation
            $journalEntry = app(AccountingService::class)->createEntry([
                'reference_number' => 'DEP-' . $asset->id . '-' . date('Ym'),
                'entry_date' => $depreciation->period_end,
                'description' => "Depreciation for {$asset->name} - " . date('M Y', strtotime($depreciation->period_end)),
                'branch_id' => $asset->branch_id,
                'lines' => [
                    [
                        'account_id' => $expenseAccountId,
                        'debit' => $depreciation->amount,
                        'credit' => 0,
                        'description' => "Depreciation expense for {$asset->name}",
                    ],
                    [
                        'account_id' => $accumulatedAccountId,
                        'debit' => 0,
                        'credit' => $depreciation->amount,
                        'description' => "Accumulated depreciation for {$asset->name}",
                    ],
                ],
            ]);
            
            $depreciation->update([
                'status' => 'posted',
                'journal_entry_id' => $journalEntry->id,
            ]);
        });
    }

    /**
     * Get depreciation schedule for an asset
     */
    public function getDepreciationSchedule(FixedAsset $asset): array
    {
        if ($asset->depreciation_start_date === null) {
            return [];
        }

        $schedule = [];
        $startDate = Carbon::parse($asset->depreciation_start_date);
        $totalMonths = $asset->getTotalUsefulLifeMonths();
        
        $runningAccumulated = 0;
        
        for ($i = 0; $i < $totalMonths; $i++) {
            $date = $startDate->copy()->addMonths($i);
            
            // Create a temporary asset with current accumulated value
            $tempAsset = clone $asset;
            $tempAsset->accumulated_depreciation = $runningAccumulated;
            $tempAsset->book_value = $asset->purchase_cost - $runningAccumulated;
            
            $calculation = $this->calculateDepreciation($tempAsset, $date);
            
            if (!$calculation || $calculation['depreciation_amount'] <= 0) {
                break;
            }
            
            $schedule[] = [
                'period' => $date->format('Y-m'),
                'date' => $date->toDateString(),
                'depreciation_amount' => $calculation['depreciation_amount'],
                'accumulated_depreciation' => $calculation['accumulated_depreciation'],
                'book_value' => $calculation['book_value'],
            ];
            
            $runningAccumulated = $calculation['accumulated_depreciation'];
        }
        
        return $schedule;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingService $service;

    protected Branch $branch;

    protected ChartOfAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AccountingService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->account = ChartOfAccount::create([
            'account_code' => '1000',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_create_journal_entry(): void
    {
        $data = [
            'entry_date' => now(),
            'reference' => 'JE-001',
            'description' => 'Test Entry',
            'branch_id' => $this->branch->id,
            'items' => [
                [
                    'account_id' => $this->account->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->account->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ];

        $entry = $this->service->createJournalEntry($data);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertEquals('JE-001', $entry->reference_number);
    }

    public function test_can_calculate_account_balance(): void
    {
        JournalEntry::create([
            'entry_date' => now(),
            'reference_number' => 'JE-001',
            'description' => 'Test',
            'branch_id' => $this->branch->id,
            'status' => 'posted',
        ])->lines()->create([
            'account_id' => $this->account->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $balance = $this->service->getAccountBalance($this->account->id);

        $this->assertIsNumeric($balance);
    }

    public function test_validates_balanced_journal_entry(): void
    {
        $data = [
            'items' => [
                ['debit' => 1000, 'credit' => 0],
                ['debit' => 0, 'credit' => 1000],
            ],
        ];

        $result = $this->service->validateBalancedEntry($data['items']);

        $this->assertTrue($result);
    }

    public function test_detects_unbalanced_journal_entry(): void
    {
        $data = [
            'items' => [
                ['debit' => 1000, 'credit' => 0],
                ['debit' => 0, 'credit' => 500],
            ],
        ];

        $result = $this->service->validateBalancedEntry($data['items']);

        $this->assertFalse($result);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialReportService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FinancialReportService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);
    }

    public function test_asset_account_balance_calculated_with_correct_sign(): void
    {
        // Create an asset account
        $assetAccount = Account::create([
            'branch_id' => $this->branch->id,
            'account_number' => '1000',
            'name' => 'Cash',
            'name_ar' => 'نقدية',
            'type' => 'asset',
            'is_active' => true,
        ]);

        // Create a journal entry
        $entry = JournalEntry::create([
            'branch_id' => $this->branch->id,
            'entry_date' => now(),
            'reference_number' => 'JE001',
            'description' => 'Test entry',
            'status' => 'posted',
        ]);

        // Debit: 1000, Credit: 200 -> Balance should be 800 (debit - credit for assets)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $assetAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $assetAccount->id,
            'debit' => 0,
            'credit' => 200,
        ]);

        $balance = $this->service->getTrialBalance($this->branch->id);
        $accountData = collect($balance['accounts'])->firstWhere('account_number', '1000');

        $this->assertEquals(800, $accountData['debit']);
        $this->assertEquals(0, $accountData['credit']);
    }

    public function test_liability_account_balance_calculated_with_correct_sign(): void
    {
        // Create a liability account
        $liabilityAccount = Account::create([
            'branch_id' => $this->branch->id,
            'account_number' => '2000',
            'name' => 'Accounts Payable',
            'name_ar' => 'حسابات دائنة',
            'type' => 'liability',
            'is_active' => true,
        ]);

        // Create a journal entry
        $entry = JournalEntry::create([
            'branch_id' => $this->branch->id,
            'entry_date' => now(),
            'reference_number' => 'JE002',
            'description' => 'Test liability',
            'status' => 'posted',
        ]);

        // Debit: 200, Credit: 1000 -> Balance should be 800 (credit - debit for liabilities)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $liabilityAccount->id,
            'debit' => 200,
            'credit' => 0,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $liabilityAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $balance = $this->service->getTrialBalance($this->branch->id);
        $accountData = collect($balance['accounts'])->firstWhere('account_number', '2000');

        $this->assertEquals(0, $accountData['debit']);
        $this->assertEquals(800, $accountData['credit']);
    }

    public function test_revenue_account_balance_calculated_with_correct_sign(): void
    {
        // Create a revenue account
        $revenueAccount = Account::create([
            'branch_id' => $this->branch->id,
            'account_number' => '4000',
            'name' => 'Sales Revenue',
            'name_ar' => 'إيرادات المبيعات',
            'type' => 'revenue',
            'is_active' => true,
        ]);

        // Create a journal entry
        $entry = JournalEntry::create([
            'branch_id' => $this->branch->id,
            'entry_date' => now(),
            'reference_number' => 'JE003',
            'description' => 'Test revenue',
            'status' => 'posted',
        ]);

        // Debit: 100, Credit: 500 -> Balance should be 400 (credit - debit for revenue)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $revenueAccount->id,
            'debit' => 100,
            'credit' => 0,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $revenueAccount->id,
            'debit' => 0,
            'credit' => 500,
        ]);

        $balance = $this->service->getTrialBalance($this->branch->id);
        $accountData = collect($balance['accounts'])->firstWhere('account_number', '4000');

        $this->assertEquals(0, $accountData['debit']);
        $this->assertEquals(400, $accountData['credit']);
    }

    public function test_expense_account_balance_calculated_with_correct_sign(): void
    {
        // Create an expense account
        $expenseAccount = Account::create([
            'branch_id' => $this->branch->id,
            'account_number' => '5000',
            'name' => 'Operating Expenses',
            'name_ar' => 'مصروفات تشغيلية',
            'type' => 'expense',
            'is_active' => true,
        ]);

        // Create a journal entry
        $entry = JournalEntry::create([
            'branch_id' => $this->branch->id,
            'entry_date' => now(),
            'reference_number' => 'JE004',
            'description' => 'Test expense',
            'status' => 'posted',
        ]);

        // Debit: 600, Credit: 100 -> Balance should be 500 (debit - credit for expenses)
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $expenseAccount->id,
            'debit' => 600,
            'credit' => 0,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $expenseAccount->id,
            'debit' => 0,
            'credit' => 100,
        ]);

        $balance = $this->service->getTrialBalance($this->branch->id);
        $accountData = collect($balance['accounts'])->firstWhere('account_number', '5000');

        $this->assertEquals(500, $accountData['debit']);
        $this->assertEquals(0, $accountData['credit']);
    }
}

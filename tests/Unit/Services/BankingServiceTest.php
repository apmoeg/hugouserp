<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\BankAccount;
use App\Models\Branch;
use App\Services\BankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BankingService $service;
    protected Branch $branch;
    protected BankAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BankingService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->account = BankAccount::create([
            'account_name' => 'Main Account',
            'account_number' => '1234567890',
            'bank_name' => 'Test Bank',
            'currency' => 'EGP',
            'current_balance' => 10000,
            'opening_balance' => 10000,
            'opening_date' => now(),
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_get_account_balance(): void
    {
        $balance = $this->service->getAccountBalance($this->account->id);

        $this->assertEquals(10000, $balance);
    }

    public function test_can_record_deposit(): void
    {
        $transaction = $this->service->recordDeposit([
            'account_id' => $this->account->id,
            'amount' => 5000,
            'description' => 'Deposit',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertNotNull($transaction);
    }

    public function test_can_record_withdrawal(): void
    {
        $transaction = $this->service->recordWithdrawal([
            'account_id' => $this->account->id,
            'amount' => 1000,
            'description' => 'Withdrawal',
            'branch_id' => $this->branch->id,
        ]);

        $this->assertNotNull($transaction);
    }

    public function test_validates_sufficient_balance(): void
    {
        $isValid = $this->service->hasSufficientBalance($this->account->id, 5000);

        $this->assertTrue($isValid);
    }
}

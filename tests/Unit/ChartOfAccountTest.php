<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartOfAccountTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_uses_accounts_table(): void
    {
        $chartOfAccount = new ChartOfAccount;
        $this->assertEquals('accounts', $chartOfAccount->getTable());
    }

    /** @test */
    public function it_maps_account_code_to_account_number(): void
    {
        $chartOfAccount = new ChartOfAccount;
        $chartOfAccount->account_code = '1000';

        // Should set account_number internally
        $this->assertEquals('1000', $chartOfAccount->account_number);

        // Should retrieve via account_code
        $this->assertEquals('1000', $chartOfAccount->account_code);
    }

    /** @test */
    public function it_maps_account_name_to_name(): void
    {
        $chartOfAccount = new ChartOfAccount;
        $chartOfAccount->account_name = 'Cash';

        // Should set name internally
        $this->assertEquals('Cash', $chartOfAccount->name);

        // Should retrieve via account_name
        $this->assertEquals('Cash', $chartOfAccount->account_name);
    }

    /** @test */
    public function it_maps_account_type_to_type(): void
    {
        $chartOfAccount = new ChartOfAccount;
        $chartOfAccount->account_type = 'asset';

        // Should set type internally
        $this->assertEquals('asset', $chartOfAccount->type);

        // Should retrieve via account_type
        $this->assertEquals('asset', $chartOfAccount->account_type);
    }
}

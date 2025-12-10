# Enhanced Accounting & Workflow Engine Guide

## Table of Contents
1. [Overview](#overview)
2. [Chart of Accounts](#chart-of-accounts)
3. [Account Mappings](#account-mappings)
4. [Automatic Journal Entries](#automatic-journal-entries)
5. [Financial Reports](#financial-reports)
6. [Workflow Engine](#workflow-engine)
7. [Usage Examples](#usage-examples)

---

## Overview

This guide covers the enhanced accounting system and workflow engine that have been implemented in HugoERP. These features provide:

- **Double-Entry Accounting**: Full support for proper accounting practices
- **Automatic Journal Entries**: Generated from operational transactions (sales, purchases, payroll, rentals)
- **Financial Reporting**: Trial Balance, P&L, Balance Sheet, Aging Reports
- **Workflow Engine**: Multi-stage approval workflows for any business process
- **Multi-Currency Support**: Handle transactions in multiple currencies
- **Fiscal Period Management**: Track and close accounting periods

---

## Chart of Accounts

### Account Structure

The system uses a hierarchical chart of accounts with the following types:

- **Assets** (`asset`)
  - Current Assets: Cash, Bank, Accounts Receivable, Inventory
  - Fixed Assets: Property, Equipment
  
- **Liabilities** (`liability`)
  - Current Liabilities: Accounts Payable, Taxes, Salaries Payable
  - Long-term Liabilities
  
- **Equity** (`equity`)
  - Capital, Retained Earnings
  
- **Revenue** (`revenue`)
  - Sales Revenue, Rental Revenue, Service Revenue
  
- **Expenses** (`expense`)
  - COGS, Salaries, Rent, Utilities, Discounts

### Account Properties

```php
Account::create([
    'branch_id' => 1,
    'account_number' => '1010',
    'name' => 'Cash',
    'name_ar' => 'النقدية',
    'type' => 'asset',
    'account_category' => 'current',
    'sub_category' => null,
    'parent_id' => null, // For hierarchical structure
    'currency_code' => 'USD', // Optional
    'requires_currency' => false,
    'is_system_account' => true, // Reserved for system use
    'is_active' => true,
    'description' => 'Cash on hand',
    'metadata' => ['key' => 'value'], // JSON field for custom data
]);
```

### Creating Accounts

```php
// Create a parent account
$assets = Account::create([
    'account_number' => '1000',
    'name' => 'Current Assets',
    'type' => 'asset',
    'account_category' => 'current',
]);

// Create a child account
$cash = Account::create([
    'account_number' => '1010',
    'name' => 'Cash',
    'type' => 'asset',
    'parent_id' => $assets->id,
]);
```

---

## Account Mappings

Account mappings connect operational modules to accounting accounts. This allows automatic journal entry generation.

### Creating Account Mappings

```php
AccountMapping::create([
    'branch_id' => 1,
    'module_name' => 'sales',
    'mapping_key' => 'sales_revenue',
    'account_id' => $revenueAccount->id,
    'conditions' => null, // Optional conditional mapping
    'is_active' => true,
]);
```

### Standard Mappings

#### Sales Module
- `cash_account` - Where cash payments go
- `accounts_receivable` - For credit sales
- `sales_revenue` - Sales income
- `tax_payable` - Sales tax collected
- `sales_discount` - Discounts given

#### Purchases Module
- `cash_account` - Cash payments
- `accounts_payable` - Credit purchases
- `inventory_account` - Inventory purchases
- `tax_recoverable` - VAT/tax on purchases

#### HRM Module
- `salaries_expense` - Salary expenses
- `salaries_payable` - Accrued salaries

#### Rental Module
- `rental_revenue` - Rental income
- `cash_account` - Cash received

### Conditional Mappings

```php
AccountMapping::create([
    'branch_id' => 1,
    'module_name' => 'sales',
    'mapping_key' => 'sales_revenue',
    'account_id' => $premiumRevenueAccount->id,
    'conditions' => [
        [
            'field' => 'customer_type',
            'operator' => '=',
            'value' => 'premium'
        ]
    ],
    'is_active' => true,
]);
```

---

## Automatic Journal Entries

The `AccountingService` automatically generates journal entries from operational transactions.

### Sales Transaction

When a sale is created, the system automatically generates a journal entry:

```php
use App\Services\AccountingService;

$accountingService = new AccountingService();

// Generate journal entry from sale
$journalEntry = $accountingService->generateSaleJournalEntry($sale);

// Example entry for a cash sale of $1,000 + $100 tax:
// DR: Cash                $1,100
// CR: Sales Revenue       $1,000
// CR: Tax Payable         $100
```

### Purchase Transaction

```php
// Generate journal entry from purchase
$journalEntry = $accountingService->generatePurchaseJournalEntry($purchase);

// Example entry for a credit purchase of $500 + $50 tax:
// DR: Inventory           $500
// DR: Tax Recoverable     $50
// CR: Accounts Payable    $550
```

### Journal Entry Fields

```php
JournalEntry::create([
    'branch_id' => 1,
    'reference_number' => 'SALE-20251207-000001',
    'entry_date' => '2025-12-07',
    'description' => 'Sale #INV-001',
    'status' => 'posted', // draft, posted, cancelled
    'source_module' => 'sales',
    'source_type' => 'Sale',
    'source_id' => 123,
    'fiscal_year' => '2025',
    'fiscal_period' => '12',
    'is_auto_generated' => true,
    'is_reversible' => true,
    'created_by' => 1,
    'approved_by' => 1,
    'approved_at' => now(),
]);
```

### Journal Entry Lines

```php
JournalEntryLine::create([
    'journal_entry_id' => $entry->id,
    'account_id' => $account->id,
    'debit' => 1000.00,
    'credit' => 0.00,
    'description' => 'Sales revenue',
    'dimension1' => 'DEPT-001', // Cost center
    'dimension2' => 'PRJ-001',  // Project
    'currency_id' => 1,
    'exchange_rate' => 1.000000,
    'debit_base' => 1000.00,
    'credit_base' => 0.00,
]);
```

### Posting Journal Entries

```php
// Validate and post a journal entry
$accountingService->postJournalEntry($journalEntry, auth()->id());

// This will:
// 1. Validate debit = credit
// 2. Update account balances
// 3. Mark entry as posted
```

### Reversing Journal Entries

```php
// Reverse a posted entry
$reversalEntry = $accountingService->reverseJournalEntry(
    $originalEntry, 
    'Correcting error in transaction',
    auth()->id()
);
```

---

## Financial Reports

The `FinancialReportService` provides comprehensive financial reports.

### Trial Balance

```php
use App\Services\FinancialReportService;

$reportService = new FinancialReportService();

$trialBalance = $reportService->getTrialBalance(
    branchId: 1,
    startDate: '2025-01-01',
    endDate: '2025-12-31'
);

// Returns:
[
    'accounts' => [
        [
            'account_number' => '1010',
            'account_name' => 'Cash',
            'type' => 'asset',
            'debit' => 15000.00,
            'credit' => 0.00,
        ],
        // ... more accounts
    ],
    'total_debit' => 125000.00,
    'total_credit' => 125000.00,
    'difference' => 0.00,
    'is_balanced' => true,
]
```

### Profit & Loss Statement

```php
$profitLoss = $reportService->getProfitLoss(
    branchId: 1,
    startDate: '2025-01-01',
    endDate: '2025-12-31'
);

// Returns:
[
    'revenue' => [
        'accounts' => [...],
        'total' => 250000.00,
    ],
    'expenses' => [
        'accounts' => [...],
        'total' => 180000.00,
    ],
    'net_income' => 70000.00,
]
```

### Balance Sheet

```php
$balanceSheet = $reportService->getBalanceSheet(
    branchId: 1,
    asOfDate: '2025-12-31'
);

// Returns:
[
    'as_of_date' => '2025-12-31',
    'assets' => [
        'accounts' => [...],
        'total' => 500000.00,
    ],
    'liabilities' => [
        'accounts' => [...],
        'total' => 300000.00,
    ],
    'equity' => [
        'accounts' => [...],
        'total' => 200000.00,
    ],
    'total_liabilities_and_equity' => 500000.00,
    'is_balanced' => true,
]
```

### Accounts Receivable Aging

```php
$arAging = $reportService->getAccountsReceivableAging(
    branchId: 1,
    asOfDate: '2025-12-31'
);

// Returns:
[
    'as_of_date' => '2025-12-31',
    'customers' => [
        [
            'customer_id' => 1,
            'customer_name' => 'John Doe',
            'current' => 5000.00,
            '1_30_days' => 2000.00,
            '31_60_days' => 1000.00,
            '61_90_days' => 500.00,
            'over_90_days' => 100.00,
            'total' => 8600.00,
        ],
        // ... more customers
    ],
    'totals' => [...],
]
```

### Account Statement

```php
$statement = $reportService->getAccountStatement(
    accountId: 1,
    startDate: '2025-01-01',
    endDate: '2025-12-31'
);

// Returns:
[
    'account' => [
        'number' => '1010',
        'name' => 'Cash',
        'type' => 'asset',
    ],
    'period' => [
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ],
    'transactions' => [
        [
            'date' => '2025-01-05',
            'reference' => 'SALE-001',
            'description' => 'Cash sale',
            'debit' => 1000.00,
            'credit' => 0.00,
            'balance' => 1000.00,
        ],
        // ... more transactions
    ],
    'summary' => [
        'total_debit' => 50000.00,
        'total_credit' => 30000.00,
        'ending_balance' => 20000.00,
    ],
]
```

---

## Workflow Engine

The workflow engine provides multi-stage approval processes for any business operation.

### Creating a Workflow Definition

```php
use App\Models\WorkflowDefinition;

$workflow = WorkflowDefinition::create([
    'name' => 'Purchase Order Approval',
    'code' => 'purchase_approval',
    'module_name' => 'purchases',
    'entity_type' => 'Purchase',
    'description' => 'Approval workflow for purchase orders',
    'stages' => [
        [
            'name' => 'Manager Review',
            'order' => 1,
            'approver_role' => 'manager',
        ],
        [
            'name' => 'Finance Approval',
            'order' => 2,
            'approver_role' => 'finance',
        ],
        [
            'name' => 'CEO Approval',
            'order' => 3,
            'approver_id' => 1, // Specific user
        ],
    ],
    'is_active' => true,
    'is_mandatory' => false,
]);
```

### Adding Workflow Rules

```php
use App\Models\WorkflowRule;

WorkflowRule::create([
    'workflow_definition_id' => $workflow->id,
    'name' => 'High Value Purchase Rule',
    'priority' => 10,
    'conditions' => [
        [
            'field' => 'grand_total',
            'operator' => '>',
            'value' => 10000,
        ],
    ],
    'actions' => [
        [
            'type' => 'require_approval',
            'params' => ['stage' => 'CEO Approval'],
        ],
    ],
    'is_active' => true,
]);
```

### Initiating a Workflow

```php
use App\Services\WorkflowService;

$workflowService = new WorkflowService();

$instance = $workflowService->initiateWorkflow(
    moduleName: 'purchases',
    entityType: 'Purchase',
    entityId: $purchase->id,
    entityData: [
        'grand_total' => $purchase->grand_total,
        'supplier_id' => $purchase->supplier_id,
    ],
    userId: auth()->id(),
    branchId: $purchase->branch_id
);

// This will:
// 1. Find applicable workflow definition
// 2. Check if rules match
// 3. Create workflow instance
// 4. Create approval steps
// 5. Send notifications to first approver
```

### Approving a Request

```php
$approval = WorkflowApproval::find($approvalId);

$instance = $workflowService->approve(
    approval: $approval,
    userId: auth()->id(),
    comments: 'Approved - all checks passed'
);

// This will:
// 1. Validate approver
// 2. Mark approval as approved
// 3. Move to next stage or complete workflow
// 4. Send notifications
// 5. Log in audit trail
```

### Rejecting a Request

```php
$instance = $workflowService->reject(
    approval: $approval,
    userId: auth()->id(),
    reason: 'Insufficient budget allocation'
);

// This will:
// 1. Mark approval as rejected
// 2. Complete workflow with rejected status
// 3. Notify initiator
// 4. Log in audit trail
```

### Reassigning an Approval

```php
$approval = $workflowService->reassign(
    approval: $approval,
    newApproverId: $newManager->id,
    userId: auth()->id(),
    reason: 'Manager on leave'
);
```

### Getting Pending Approvals

```php
$pendingApprovals = $workflowService->getPendingApprovalsForUser(auth()->id());

foreach ($pendingApprovals as $approval) {
    echo "Stage: {$approval->stage_name}\n";
    echo "Entity: {$approval->workflowInstance->entity_type} #{$approval->workflowInstance->entity_id}\n";
    echo "Requested: {$approval->requested_at}\n";
}
```

---

## Usage Examples

### Complete Sales Transaction with Accounting

```php
use App\Models\Sale;
use App\Services\AccountingService;

// 1. Create sale
$sale = Sale::create([
    'branch_id' => 1,
    'customer_id' => 10,
    'invoice_number' => 'INV-001',
    'sale_date' => now(),
    'subtotal' => 1000.00,
    'tax_amount' => 100.00,
    'grand_total' => 1100.00,
    'payment_status' => 'paid',
]);

// 2. Generate journal entry
$accountingService = new AccountingService();
$journalEntry = $accountingService->generateSaleJournalEntry($sale);

// 3. Verify entry is balanced
if ($journalEntry->isBalanced()) {
    echo "Journal entry created successfully!";
}
```

### Purchase Approval Workflow

```php
use App\Models\Purchase;
use App\Services\WorkflowService;

// 1. Create purchase order
$purchase = Purchase::create([
    'branch_id' => 1,
    'supplier_id' => 5,
    'reference_number' => 'PO-001',
    'grand_total' => 15000.00,
    'status' => 'pending_approval',
]);

// 2. Initiate approval workflow
$workflowService = new WorkflowService();
$workflow = $workflowService->initiateWorkflow(
    'purchases',
    'Purchase',
    $purchase->id,
    ['grand_total' => $purchase->grand_total],
    auth()->id()
);

// 3. Check workflow status
if ($workflow) {
    echo "Workflow initiated. Current stage: {$workflow->current_stage}";
} else {
    echo "No workflow required. Purchase can proceed.";
}
```

### Monthly Financial Close

```php
use App\Services\FinancialReportService;
use App\Models\FiscalPeriod;

$reportService = new FinancialReportService();

// 1. Generate trial balance
$trialBalance = $reportService->getTrialBalance(
    branchId: 1,
    startDate: '2025-12-01',
    endDate: '2025-12-31'
);

if (!$trialBalance['is_balanced']) {
    throw new Exception('Trial balance is not balanced!');
}

// 2. Generate financial statements
$profitLoss = $reportService->getProfitLoss(1, '2025-12-01', '2025-12-31');
$balanceSheet = $reportService->getBalanceSheet(1, '2025-12-31');

// 3. Close the period
$period = FiscalPeriod::where('year', '2025')
    ->where('period', '12')
    ->first();
    
$period->update(['status' => 'closed']);

echo "Net Income for December: {$profitLoss['net_income']}";
echo "Total Assets: {$balanceSheet['assets']['total']}";
```

### Multi-Currency Transaction

```php
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;

// 1. Get currency
$usd = Currency::where('code', 'USD')->first();
$eur = Currency::where('code', 'EUR')->first();

// 2. Create journal entry with foreign currency
$entry = JournalEntry::create([
    'branch_id' => 1,
    'reference_number' => 'JE-001',
    'entry_date' => now(),
    'description' => 'Foreign currency sale',
    'status' => 'draft',
]);

// 3. Add lines with currency conversion
JournalEntryLine::create([
    'journal_entry_id' => $entry->id,
    'account_id' => $cashAccount->id,
    'debit' => 1000.00, // EUR amount
    'credit' => 0.00,
    'currency_id' => $eur->id,
    'exchange_rate' => 1.10,
    'debit_base' => 1100.00, // USD equivalent
    'credit_base' => 0.00,
]);
```

---

## Best Practices

### Accounting
1. Always validate journal entry balance before posting
2. Use account mappings for consistency
3. Close fiscal periods regularly
4. Review trial balance monthly
5. Keep audit trail of all changes

### Workflows
1. Define clear approval stages
2. Use role-based approvers when possible
3. Set reasonable timeout policies
4. Document approval criteria
5. Regular review of workflow performance

### Security
1. Restrict posting rights to authorized users
2. Implement approval limits
3. Audit all financial transactions
4. Regular backup of financial data
5. Separate duties (entry vs approval)

### Performance
1. Index frequently queried accounts
2. Archive old fiscal periods
3. Use date ranges in reports
4. Cache commonly accessed reports
5. Paginate large result sets

---

## Database Schema Reference

### accounts
- `id`: Primary key
- `branch_id`: Foreign key to branches
- `account_number`: Unique account number
- `name`: Account name (English)
- `name_ar`: Account name (Arabic)
- `type`: asset, liability, equity, revenue, expense
- `currency_code`: Optional currency
- `requires_currency`: Boolean
- `account_category`: current, fixed, etc.
- `sub_category`: Additional categorization
- `parent_id`: For hierarchical structure
- `balance`: Current balance
- `is_active`: Boolean
- `is_system_account`: Reserved accounts
- `description`: Text
- `metadata`: JSON

### journal_entries
- `id`: Primary key
- `branch_id`: Foreign key
- `reference_number`: Unique
- `entry_date`: Date
- `description`: Text
- `status`: draft, posted, cancelled
- `source_module`: sales, purchases, etc.
- `source_type`: Model name
- `source_id`: Source record ID
- `created_by`: User ID
- `approved_by`: User ID
- `approved_at`: Timestamp
- `fiscal_year`: Year
- `fiscal_period`: Period
- `is_auto_generated`: Boolean
- `is_reversible`: Boolean
- `reversed_by_entry_id`: Foreign key

### workflow_definitions
- `id`: Primary key
- `name`: Workflow name
- `code`: Unique code
- `module_name`: Module
- `entity_type`: Model name
- `description`: Text
- `stages`: JSON array
- `rules`: JSON array
- `is_active`: Boolean
- `is_mandatory`: Boolean

---

## Support

For questions or issues:
1. Check this documentation
2. Review code examples
3. Examine test files
4. Contact development team

---

## License

Part of HugoERP - All rights reserved

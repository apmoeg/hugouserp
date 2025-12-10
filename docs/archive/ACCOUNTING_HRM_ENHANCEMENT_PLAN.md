# Enhanced Accounting & HRM Implementation Plan

## Overview
This document outlines the comprehensive implementation plan for:
1. **Accounting Integration Layer** - Making accounting a true layer with automatic journal entries
2. **Enhanced HRM & Payroll** - Complete HR lifecycle management

---

## Phase 1: Accounting Integration Layer

### 1.1 Current State Analysis ✅
**Existing Infrastructure:**
- ✅ `accounts` table with Chart of Accounts structure
- ✅ `Account` model with parent-child hierarchy
- ✅ `journal_entries` and `journal_entry_lines` tables
- ✅ `JournalEntry` and `JournalEntryLine` models
- ✅ `AccountMapping` model for module-account linking
- ✅ Support for: asset, liability, equity, revenue, expense types
- ✅ Multi-currency support per account
- ✅ Fiscal period tracking

### 1.2 What Needs to be Built

#### A. Accounting Service Layer
**File:** `app/Services/AccountingService.php`

**Methods Needed:**
```php
- createJournalEntry(array $data): JournalEntry
- postSalesInvoiceEntry(SalesInvoice $invoice): JournalEntry
- postPurchaseEntry(Purchase $purchase): JournalEntry
- postRentalRevenueEntry(RentalInvoice $invoice): JournalEntry
- postPayrollEntry(Payroll $payroll, array $details): JournalEntry
- postPaymentEntry(Receipt $receipt): JournalEntry
- postDepreciationEntry(FixedAsset $asset, AssetDepreciation $depreciation): JournalEntry
- reverseEntry(JournalEntry $entry): JournalEntry
- getAccountBalance(Account $account, $startDate = null, $endDate = null): float
- getTrialBalance($branchId, $startDate, $endDate): Collection
- getProfitAndLoss($branchId, $startDate, $endDate): array
- getBalanceSheet($branchId, $asOfDate): array
- getAccountStatement(Account $account, $startDate, $endDate): Collection
- getAgingReport(string $type, $branchId, $asOfDate): Collection // type: customer|supplier
```

#### B. Module Configuration System
**Files:** 
- `app/Services/ModuleAccountSetupService.php`
- Migration for `module_account_configurations` table

**Configuration per Module:**
```php
// Sales Module
- sales_revenue_account
- sales_tax_account
- sales_discount_account
- sales_receivable_account
- sales_cash_account

// Purchase Module
- purchase_expense_account
- purchase_asset_account
- purchase_tax_account
- purchase_discount_account
- purchase_payable_account
- purchase_cash_account

// Rental Module
- rental_revenue_account
- rental_tax_account
- rental_receivable_account

// Payroll Module
- payroll_expense_account
- payroll_advance_account
- payroll_deduction_account
- payroll_tax_account
- payroll_payable_account

// Inventory Module
- inventory_asset_account
- cost_of_goods_sold_account
```

#### C. Event-Based Journal Entry Creation
**Files:** 
- `app/Events/` - Various operational events
- `app/Listeners/AccountingListener.php`

**Events to Handle:**
- `SalesInvoiceCreated`
- `PurchaseCreated`
- `PaymentReceived`
- `PaymentMade`
- `RentalInvoiceCreated`
- `PayrollProcessed`
- `DepreciationProcessed`
- `InventoryAdjustment`

#### D. Enhanced Reporting
**Files:**
- `app/Services/AccountingReportService.php`
- Livewire components for reports

**Reports to Build:**
1. **Trial Balance** - All accounts with debit/credit balances
2. **Profit & Loss** - Revenue - Expenses for period
3. **Balance Sheet** - Assets = Liabilities + Equity
4. **Account Statement** - Transaction history for account
5. **Aging Report** - Receivables/Payables by age buckets (0-30, 31-60, 61-90, 90+)
6. **Cash Flow Statement** - Operating, Investing, Financing activities

#### E. Account Validation
**Rules:**
- Cannot post to an inactive account
- Cannot delete account with transactions
- Must configure module accounts before operations
- Journal entries must balance (debit = credit)
- Enforce fiscal period closure
- Require approval for manual journal entries

---

## Phase 2: Enhanced HRM & Payroll

### 2.1 Current State Analysis ✅
**Existing Infrastructure:**
- ✅ `hr_employees` table with basic employee data
- ✅ `HREmployee` model
- ✅ `attendances` table
- ✅ `Attendance` model
- ✅ `shifts` and `employee_shifts` tables (recently added)
- ✅ `Shift` and `EmployeeShift` models
- ✅ `payrolls` table
- ✅ `Payroll` model
- ✅ `leave_requests` table
- ✅ `LeaveRequest` model
- ✅ Payslip PDF template

### 2.2 What Needs to be Built

#### A. Enhanced Employee Management
**Files:** 
- Migration to enhance `hr_employees` table
- Update `HREmployee` model

**Additional Fields Needed:**
```php
// Personal Data
- emergency_contact_name
- emergency_contact_phone
- emergency_contact_relationship

// Employment Data
- employment_type (full_time, part_time, contractor, temporary)
- contract_start_date
- contract_end_date
- probation_end_date
- notice_period_days

// Financial Data
- bank_name
- bank_account_number
- bank_branch
- payment_method (bank_transfer, cash, cheque)
- tax_number
- social_security_number
```

#### B. Attendance & Timesheet System
**Files:**
- New migration for `attendance_logs` table (detailed clock in/out)
- New model `AttendanceLog`
- Service `AttendanceService.php`

**Features:**
```php
// Attendance Tracking
- clockIn($employeeId, $timestamp, $location = null)
- clockOut($employeeId, $timestamp, $location = null)
- calculateWorkHours($attendanceLog)
- detectLateArrival($employee, $attendanceLog)
- detectEarlyDeparture($employee, $attendanceLog)
- markAbsence($employeeId, $date, $reason = null)

// Reports
- getAttendanceSummary($employeeId, $startDate, $endDate)
- getLatenessReport($branchId, $month)
- getAbsenceReport($branchId, $month)
- getOvertimeReport($branchId, $month)
```

#### C. Advanced Payroll Calculation
**Files:**
- New migration for `payroll_rules` table
- New model `PayrollRule`
- New migration for `payroll_components` table (earnings/deductions per employee)
- New model `PayrollComponent`
- Enhanced `PayrollService.php`

**Payroll Rules:**
```php
// Earning Types
- basic_salary
- housing_allowance
- transport_allowance
- mobile_allowance
- food_allowance
- overtime_pay
- commission
- bonus
- incentive

// Deduction Types
- late_deduction
- absence_deduction
- advance_deduction
- loan_deduction
- tax_deduction
- social_insurance_deduction
- health_insurance_deduction
- other_deduction

// Calculation Methods
- fixed_amount
- percentage_of_basic
- percentage_of_gross
- hours_based (overtime)
- days_based (absence)
```

**Payroll Process:**
```php
1. calculateBasicComponents($employee, $month, $year)
2. calculateAttendanceComponents($employee, $month, $year)
   - Late deductions
   - Absence deductions
   - Overtime pay
3. calculateAllowances($employee)
4. calculateDeductions($employee, $month, $year)
   - Advances
   - Loans
   - Taxes
5. calculateNetSalary()
6. generatePayslip()
7. createAccountingEntry()
```

#### D. Leave Management System
**Files:**
- New migration for `leave_types` table
- New model `LeaveType`
- New migration for `leave_balances` table
- New model `LeaveBalance`
- Enhanced `leave_requests` table
- Service `LeaveService.php`

**Leave Types:**
```php
- Annual Leave
- Sick Leave
- Emergency Leave
- Maternity Leave
- Paternity Leave
- Unpaid Leave
- Hajj Leave
- Bereavement Leave

**Per Type:**
- days_per_year
- max_carry_forward
- requires_medical_certificate
- paid/unpaid
- approval_workflow
```

**Leave Management:**
```php
- calculateLeaveBalance($employee, $leaveType, $year)
- submitLeaveRequest($employee, $data)
- approveLeaveRequest($requestId, $approverId)
- rejectLeaveRequest($requestId, $approverId, $reason)
- cancelLeaveRequest($requestId)
- deductFromBalance($employee, $leaveType, $days)
- accrueLeaveBalance($employee, $leaveType)
```

#### E. Loan & Advance System
**Files:**
- New migration for `employee_loans` table
- New model `EmployeeLoan`
- New migration for `employee_advances` table
- New model `EmployeeAdvance`
- Service `LoanAdvanceService.php`

**Features:**
```php
// Loan Management
- createLoan($employeeId, $amount, $installments, $startDate)
- approveLoan($loanId)
- calculateMonthlyInstallment($loan)
- deductInstallment($loan, $payrollId)
- settleEarly($loanId)

// Advance Management
- createAdvance($employeeId, $amount, $reason)
- approveAdvance($advanceId)
- deductAdvance($advanceId, $payrollId, $amount)
```

#### F. Performance Management
**Files:**
- New migration for `performance_reviews` table
- New model `PerformanceReview`
- New migration for `performance_criteria` table
- New model `PerformanceCriterion`
- Service `PerformanceService.php`

**Features:**
```php
// Review Process
- createReviewCycle($data)
- assignReviews($cycleId, $employees)
- submitSelfAssessment($reviewId, $data)
- submitManagerAssessment($reviewId, $data)
- calculateOverallScore($reviewId)
- linkToBonus($reviewId, $amount)

// Criteria Types
- Quality of Work
- Productivity
- Communication
- Teamwork
- Initiative
- Leadership
- Punctuality
```

---

## Implementation Priority

### **IMMEDIATE (Week 1-2)**
1. ✅ Accounting Service with basic journal posting
2. ✅ Module Account Configuration system
3. ✅ Event listeners for Sales & Purchase
4. ✅ Basic accounting reports (Trial Balance, P&L, Balance Sheet)

### **HIGH PRIORITY (Week 3-4)**
5. ✅ Enhanced payroll calculation system
6. ✅ Attendance tracking with late/absence detection
7. ✅ Leave management system
8. ✅ Loan & Advance system

### **MEDIUM PRIORITY (Week 5-6)**
9. ⏳ Performance management
10. ⏳ Livewire UI for accounting reports
11. ⏳ Livewire UI for payroll management
12. ⏳ Livewire UI for attendance & leave

### **LOW PRIORITY (Week 7-8)**
13. ⏳ Advanced reporting dashboards
14. ⏳ Mobile app integration for attendance
15. ⏳ Biometric device integration
16. ⏳ Email notifications for all workflows

---

## Database Schema Summary

### New Tables to Create:
1. `module_account_configurations` - Module to account mapping
2. `attendance_logs` - Detailed clock in/out records
3. `payroll_rules` - Salary component rules
4. `payroll_components` - Employee-specific components
5. `leave_types` - Leave type definitions
6. `leave_balances` - Employee leave balances
7. `employee_loans` - Loan tracking
8. `employee_advances` - Advance tracking
9. `performance_reviews` - Performance review cycles
10. `performance_criteria` - Review criteria and scores

### Tables to Enhance:
1. `hr_employees` - Add financial & contract fields
2. `leave_requests` - Add workflow fields
3. `payrolls` - Add detailed breakdown fields

---

## Success Metrics

### Accounting Layer
- ✅ Every operational transaction auto-generates journal entry
- ✅ Journal entries always balanced
- ✅ Trial Balance matches operational data
- ✅ All financial reports accurate and real-time
- ✅ Module operations blocked if accounts not configured

### HRM & Payroll
- ✅ Accurate payroll calculation with all rules
- ✅ Automated leave accrual and tracking
- ✅ Comprehensive attendance reports
- ✅ Loan repayment auto-deducted
- ✅ Performance reviews linked to bonuses

---

## Next Steps
Starting implementation with foundational work on:
1. Accounting Service infrastructure
2. Enhanced payroll calculation system
3. Database migrations for new tables

**Status**: Ready to begin Phase 1 implementation

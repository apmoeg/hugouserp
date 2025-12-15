<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payroll;
use App\Models\Branch;
use Illuminate\Support\Facades\View;

class PayslipService
{
    /**
     * Generate payslip HTML content
     */
    public function generatePayslipHtml(Payroll $payroll): string
    {
        $employee = $payroll->employee;
        $branch = $employee->branch;
        
        $data = [
            'payroll' => $payroll,
            'employee' => $employee,
            'branch' => $branch,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ];

        return View::make('payslips.template', $data)->render();
    }

    /**
     * Get payslip breakdown
     */
    public function getPayslipBreakdown(Payroll $payroll): array
    {
        return [
            'basic_salary' => [
                'label' => __('Basic Salary'),
                'amount' => $payroll->basic,
                'type' => 'earning',
            ],
            'allowances' => [
                'label' => __('Allowances'),
                'amount' => $payroll->allowances,
                'type' => 'earning',
            ],
            'gross_salary' => [
                'label' => __('Gross Salary'),
                'amount' => $payroll->basic + $payroll->allowances,
                'type' => 'subtotal',
            ],
            'deductions' => [
                'label' => __('Deductions'),
                'amount' => $payroll->deductions,
                'type' => 'deduction',
            ],
            'net_salary' => [
                'label' => __('Net Salary'),
                'amount' => $payroll->net,
                'type' => 'total',
            ],
        ];
    }

    /**
     * Calculate allowances based on company rules from settings
     */
    protected function calculateAllowances(float $basicSalary): array
    {
        $allowances = [];
        $total = 0.0;
        
        // Transportation allowance (configurable percentage or fixed)
        $transportType = setting('hrm.transport_allowance_type', 'percentage');
        $transportValue = (float) setting('hrm.transport_allowance_value', 10);
        if ($transportType === 'percentage') {
            $transportAmount = $basicSalary * ($transportValue / 100);
        } else {
            $transportAmount = $transportValue;
        }
        if ($transportAmount > 0) {
            $allowances['transport'] = round($transportAmount, 2);
            $total += $transportAmount;
        }
        
        // Housing allowance (configurable)
        $housingType = setting('hrm.housing_allowance_type', 'percentage');
        $housingValue = (float) setting('hrm.housing_allowance_value', 0);
        if ($housingType === 'percentage') {
            $housingAmount = $basicSalary * ($housingValue / 100);
        } else {
            $housingAmount = $housingValue;
        }
        if ($housingAmount > 0) {
            $allowances['housing'] = round($housingAmount, 2);
            $total += $housingAmount;
        }
        
        // Meal allowance (fixed)
        $mealAllowance = (float) setting('hrm.meal_allowance', 0);
        if ($mealAllowance > 0) {
            $allowances['meal'] = round($mealAllowance, 2);
            $total += $mealAllowance;
        }
        
        return [
            'breakdown' => $allowances,
            'total' => round($total, 2),
        ];
    }

    /**
     * Calculate deductions based on company rules and tax config
     */
    protected function calculateDeductions(float $grossSalary): array
    {
        $deductions = [];
        $total = 0.0;
        
        // Social Insurance deduction
        $siConfig = config('hrm.social_insurance', []);
        $siRate = (float) ($siConfig['rate'] ?? 0.14);
        $siMaxSalary = (float) ($siConfig['max_salary'] ?? 12600);
        $siBaseSalary = min($grossSalary, $siMaxSalary);
        $socialInsurance = $siBaseSalary * $siRate;
        if ($socialInsurance > 0) {
            $deductions['social_insurance'] = round($socialInsurance, 2);
            $total += $socialInsurance;
        }
        
        // Income Tax (progressive brackets)
        $annualGross = $grossSalary * 12;
        $taxBrackets = config('hrm.tax_brackets', []);
        $annualTax = 0.0;
        $previousLimit = 0;
        
        foreach ($taxBrackets as $bracket) {
            $limit = (float) ($bracket['limit'] ?? PHP_FLOAT_MAX);
            $rate = (float) ($bracket['rate'] ?? 0);
            
            if ($annualGross <= $previousLimit) {
                break;
            }
            
            $taxableInBracket = min($annualGross, $limit) - $previousLimit;
            $annualTax += max(0, $taxableInBracket) * $rate;
            $previousLimit = $limit;
        }
        
        $monthlyTax = $annualTax / 12;
        if ($monthlyTax > 0) {
            $deductions['income_tax'] = round($monthlyTax, 2);
            $total += $monthlyTax;
        }
        
        // Additional fixed deductions from settings
        $healthInsurance = (float) setting('hrm.health_insurance_deduction', 0);
        if ($healthInsurance > 0) {
            $deductions['health_insurance'] = round($healthInsurance, 2);
            $total += $healthInsurance;
        }
        
        return [
            'breakdown' => $deductions,
            'total' => round($total, 2),
        ];
    }

    /**
     * Calculate payroll for employee
     */
    public function calculatePayroll(int $employeeId, string $period): array
    {
        $employee = \App\Models\HREmployee::findOrFail($employeeId);
        
        // Basic salary from employee record
        $basic = (float) $employee->salary;
        
        // Calculate allowances based on configurable company rules
        $allowanceResult = $this->calculateAllowances($basic);
        $allowances = $allowanceResult['total'];
        
        // Gross salary
        $gross = $basic + $allowances;
        
        // Calculate deductions based on configurable rules and tax brackets
        $deductionResult = $this->calculateDeductions($gross);
        $deductions = $deductionResult['total'];
        
        // Net salary
        $net = $gross - $deductions;
        
        return [
            'employee_id' => $employeeId,
            'period' => $period,
            'basic' => round($basic, 2),
            'allowances' => round($allowances, 2),
            'allowance_breakdown' => $allowanceResult['breakdown'],
            'deductions' => round($deductions, 2),
            'deduction_breakdown' => $deductionResult['breakdown'],
            'gross' => round($gross, 2),
            'net' => round($net, 2),
            'status' => 'draft',
        ];
    }

    /**
     * Process payroll for all employees in a branch
     */
    public function processBranchPayroll(int $branchId, string $period): array
    {
        $employees = \App\Models\HREmployee::where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

        $processed = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $payrollData = $this->calculatePayroll($employee->id, $period);
                
                // Only store the fields that match the Payroll model
                $payroll = Payroll::create([
                    'employee_id' => $payrollData['employee_id'],
                    'period' => $payrollData['period'],
                    'basic' => $payrollData['basic'],
                    'allowances' => $payrollData['allowances'],
                    'deductions' => $payrollData['deductions'],
                    'net' => $payrollData['net'],
                    'status' => $payrollData['status'],
                ]);
                $processed[] = $payroll;
            } catch (\Exception $e) {
                $errors[] = [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($employees),
            'success' => count($processed),
            'failed' => count($errors),
        ];
    }
}

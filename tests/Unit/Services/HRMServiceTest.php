<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\HREmployee;
use App\Services\HRMService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HRMServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HRMService $service;
    protected Branch $branch;
    protected HREmployee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(HRMService::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB001',
        ]);

        $this->employee = HREmployee::create([
            'code' => 'EMP001',
            'name' => 'John Doe',
            'position' => 'Developer',
            'salary' => 5000,
            'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_can_get_active_employees(): void
    {
        $employees = $this->service->employees(true);

        $this->assertNotNull($employees);
    }

    public function test_can_get_all_employees(): void
    {
        $employees = $this->service->employees(false);

        $this->assertNotNull($employees);
    }

    public function test_can_log_attendance_check_in(): void
    {
        $attendance = $this->service->logAttendance(
            $this->employee->id,
            'in',  // Use 'in' instead of 'check_in'
            now()->format('Y-m-d H:i:s')
        );

        $this->assertInstanceOf(Attendance::class, $attendance);
        $this->assertEquals($this->employee->id, $attendance->employee_id);
    }

    public function test_can_approve_attendance(): void
    {
        $attendance = Attendance::create([
            'employee_id' => $this->employee->id,
            'branch_id' => $this->branch->id,
            'date' => now()->format('Y-m-d'),
            'check_in' => now()->format('H:i:s'),
            'status' => 'pending',
        ]);

        $approved = $this->service->approveAttendance($attendance->id);

        $this->assertInstanceOf(Attendance::class, $approved);
        $this->assertEquals('approved', $approved->status);
    }
}

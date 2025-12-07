<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\HRM;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Services\Contracts\HRMServiceInterface as HRM;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function __construct(protected HRM $hrm) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);
        $q = Payroll::query()->orderByDesc('id');
        if ($request->filled('period')) {
            $q->where('period', $request->input('period'));
        }

        return $this->ok($q->paginate($per));
    }

    public function run(Request $request)
    {
        $this->validate($request, ['period' => ['required', 'date_format:Y-m']]);

        return $this->ok(['generated' => $this->hrm->runPayroll($request->period)]);
    }

    public function approve(Payroll $payroll)
    {
        $payroll->status = 'approved';
        $payroll->save();

        return $this->ok($payroll, __('Approved'));
    }
}

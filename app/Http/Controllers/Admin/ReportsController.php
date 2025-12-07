<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Contracts\ReportServiceInterface as Reports;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function __construct(protected Reports $reports) {}

    public function finance(Request $request)
    {
        $branchId = (int) $request->integer('branch_id');
        $from = $request->input('from');
        $to = $request->input('to');

        return $this->ok($this->reports->financeSummary($branchId, $from, $to));
    }

    public function topProducts(Request $request)
    {
        $branchId = (int) $request->integer('branch_id');
        $limit = min(max((int) $request->integer('limit', 10), 1), 100);

        return $this->ok($this->reports->topProducts($branchId, $limit));
    }
}

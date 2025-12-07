<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchModule;
use App\Services\Contracts\ModuleServiceInterface as ModuleService;
use Illuminate\Http\Request;

class BranchModuleController extends Controller
{
    public function __construct(protected ModuleService $modules) {}

    public function index(Branch $branch)
    {
        $data = $this->modules->allForBranch($branch->id);

        return $this->ok([
            'branch' => $branch,
            'modules' => $data,
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $this->validate($request, [
            'key' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
        ]);

        if ($data['enabled']) {
            $this->modules->enableForBranch($branch, $data['key']);
        } else {
            $this->modules->disableForBranch($branch, $data['key']);
        }

        $bm = BranchModule::where('branch_id', $branch->id)
            ->where('module_key', $data['key'])
            ->first();

        return $this->ok($bm, __('Module status updated'));
    }
}

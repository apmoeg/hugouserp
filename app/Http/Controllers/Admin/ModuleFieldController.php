<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Contracts\FieldSchemaServiceInterface as Fields;
use Illuminate\Http\Request;

class ModuleFieldController extends Controller
{
    public function __construct(protected Fields $fields) {}

    public function index(Request $request, string $module)
    {
        $branchId = $request->integer('branch_id') ?: null;

        return $this->ok($this->fields->for($module, $branchId));
    }

    public function validatePayload(Request $request, string $module)
    {
        $branchId = $request->integer('branch_id') ?: null;
        $validator = $this->fields->validate($module, $request->all(), $branchId);
        $validator->validate();

        return $this->ok(['valid' => true]);
    }
}

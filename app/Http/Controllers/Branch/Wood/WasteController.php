<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Wood;

use App\Http\Controllers\Controller;
use App\Http\Requests\WasteStoreRequest;
use App\Services\Contracts\WoodServiceInterface as Wood;
use Illuminate\Http\Request;

class WasteController extends Controller
{
    public function __construct(protected Wood $wood) {}

    public function index(Request $request)
    {
        $b = (int) $request->attributes->get('branch_id');

        return $this->ok($this->wood->listWaste($b));
    }

    public function store(WasteStoreRequest $request)
    {
        $data = $request->validated();
        $id = $this->wood->storeWaste($data);

        return $this->ok(['id' => $id], __('Recorded'), 201);
    }
}

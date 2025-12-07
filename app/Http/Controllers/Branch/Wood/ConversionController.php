<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Wood;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConversionRecalcRequest;
use App\Http\Requests\ConversionStoreRequest;
use App\Services\Contracts\WoodServiceInterface as Wood;
use Illuminate\Http\Request;

class ConversionController extends Controller
{
    public function __construct(protected Wood $wood) {}

    public function index(Request $request)
    {
        $b = (int) $request->attributes->get('branch_id');

        return $this->ok($this->wood->conversions($b));
    }

    public function store(ConversionStoreRequest $request)
    {
        $data = $request->validated();
        $id = $this->wood->createConversion($data);

        return $this->ok(['id' => $id], __('Created'), 201);
    }

    public function recalc(ConversionRecalcRequest $request)
    {
        $data = $request->validated();
        $this->wood->recalc((int) $data['id']);

        return $this->ok(null, __('Recalculated'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Rental;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitStatusRequest;
use App\Http\Requests\UnitStoreRequest;
use App\Http\Requests\UnitUpdateRequest;
use App\Models\RentalUnit;
use App\Services\Contracts\RentalServiceInterface as Rental;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function __construct(protected Rental $rental) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);

        return $this->ok(RentalUnit::query()->orderByDesc('id')->paginate($per));
    }

    public function store(UnitStoreRequest $request)
    {
        $data = $request->validated();

        return $this->ok($this->rental->createUnit($data['property_id'], $data), __('Created'), 201);
    }

    public function show(RentalUnit $unit)
    {
        return $this->ok($unit);
    }

    public function update(UnitUpdateRequest $request, RentalUnit $unit)
    {
        $unit->fill($request->validated())->save();

        return $this->ok($unit);
    }

    public function setStatus(UnitStatusRequest $request, RentalUnit $unit)
    {
        $data = $request->validated();

        return $this->ok($this->rental->setUnitStatus($unit->id, $data['status']));
    }
}

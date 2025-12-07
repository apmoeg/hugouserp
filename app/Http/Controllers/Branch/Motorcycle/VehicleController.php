<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Motorcycle;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleStoreRequest;
use App\Http\Requests\VehicleUpdateRequest;
use App\Models\Vehicle;
use App\Services\Contracts\MotorcycleServiceInterface as Motos;

class VehicleController extends Controller
{
    public function __construct(protected Motos $motos) {}

    public function index()
    {
        return $this->ok($this->motos->vehicles());
    }

    public function store(VehicleStoreRequest $request)
    {
        $data = $request->validated();
        $row = Vehicle::create($data + ['branch_id' => (int) $request->attributes->get('branch_id')]);

        return $this->ok($row, __('Created'), 201);
    }

    public function show(Vehicle $vehicle)
    {
        return $this->ok($vehicle);
    }

    public function update(VehicleUpdateRequest $request, Vehicle $vehicle)
    {
        $vehicle->fill($request->validated())->save();

        return $this->ok($vehicle);
    }
}

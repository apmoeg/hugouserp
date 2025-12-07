<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Rental;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyStoreRequest;
use App\Http\Requests\PropertyUpdateRequest;
use App\Models\Property;
use App\Services\Contracts\RentalServiceInterface as Rental;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function __construct(protected Rental $rental) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);

        return $this->ok(Property::query()->orderByDesc('id')->paginate($per));
    }

    public function store(PropertyStoreRequest $request)
    {
        $data = $request->validated();

        return $this->ok($this->rental->createProperty($data), __('Created'), 201);
    }

    public function show(Property $property)
    {
        return $this->ok($property);
    }

    public function update(PropertyUpdateRequest $request, Property $property)
    {
        $property->fill($request->validated())->save();

        return $this->ok($property);
    }
}

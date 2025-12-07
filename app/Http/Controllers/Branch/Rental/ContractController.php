<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Rental;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRenewRequest;
use App\Http\Requests\ContractStoreRequest;
use App\Http\Requests\ContractTerminateRequest;
use App\Http\Requests\ContractUpdateRequest;
use App\Models\RentalContract;
use App\Services\Contracts\RentalServiceInterface as Rental;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(protected Rental $rental) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);

        return $this->ok(RentalContract::query()->orderByDesc('id')->paginate($per));
    }

    public function store(ContractStoreRequest $request)
    {
        $data = $request->validated();

        return $this->ok($this->rental->createContract($data['unit_id'], $data['tenant_id'], $data), __('Created'), 201);
    }

    public function show(RentalContract $contract)
    {
        return $this->ok($contract);
    }

    public function update(ContractUpdateRequest $request, RentalContract $contract)
    {
        $contract->fill($request->validated())->save();

        return $this->ok($contract);
    }

    public function renew(ContractRenewRequest $request, RentalContract $contract)
    {
        return $this->ok($this->rental->renewContract($contract->id, $request->validated()), __('Renewed'));
    }

    public function terminate(ContractTerminateRequest $request, RentalContract $contract)
    {
        return $this->ok($this->rental->terminateContract($contract->id), __('Terminated'));
    }
}

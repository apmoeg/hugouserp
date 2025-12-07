<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch\Rental;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantArchiveRequest;
use App\Http\Requests\TenantStoreRequest;
use App\Http\Requests\TenantUpdateRequest;
use App\Models\Tenant;
use App\Services\Contracts\RentalServiceInterface as Rental;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(protected Rental $rental) {}

    public function index(Request $request)
    {
        $per = min(max($request->integer('per_page', 20), 1), 100);

        return $this->ok(Tenant::query()->orderByDesc('id')->paginate($per));
    }

    public function store(TenantStoreRequest $request)
    {
        $data = $request->validated();

        return $this->ok($this->rental->createTenant($data), __('Created'), 201);
    }

    public function show(Tenant $tenant)
    {
        return $this->ok($tenant);
    }

    public function update(TenantUpdateRequest $request, Tenant $tenant)
    {
        $tenant->fill($request->validated())->save();

        return $this->ok($tenant);
    }

    public function archive(TenantArchiveRequest $request, Tenant $tenant)
    {
        return $this->ok($this->rental->archiveTenant($tenant->id));
    }
}

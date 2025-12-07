<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\VehicleContract;
use App\Models\Warranty;
use Illuminate\Database\Eloquent\Collection;

interface MotorcycleServiceInterface
{
    /** @return Collection<int, \App\Models\Vehicle> */
    public function vehicles();

    public function createContract(int $vehicleId, int $customerId, string $startDate, string $endDate): VehicleContract;

    public function deliverContract(int $contractId): VehicleContract;

    public function upsertWarranty(int $vehicleId, array $payload): Warranty;
}

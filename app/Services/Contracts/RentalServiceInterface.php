<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalInvoice;
use App\Models\RentalUnit;
use App\Models\Tenant;

interface RentalServiceInterface
{
    public function createProperty(array $payload): Property;

    public function createUnit(int $propertyId, array $payload): RentalUnit;

    public function setUnitStatus(int $unitId, string $status): RentalUnit;

    public function createTenant(array $payload): Tenant;

    public function archiveTenant(int $tenantId): Tenant;

    public function createContract(int $unitId, int $tenantId, array $payload): RentalContract;

    public function renewContract(int $contractId, array $payload): RentalContract;

    public function terminateContract(int $contractId): RentalContract;

    public function runRecurring(?string $forDate = null): int;

    public function collectPayment(int $invoiceId, float $amount): RentalInvoice;

    public function applyPenalty(int $invoiceId, float $penalty): RentalInvoice;
}

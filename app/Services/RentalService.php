<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Property;
use App\Models\RentalContract;
use App\Models\RentalInvoice;
use App\Models\RentalUnit;
use App\Models\Tenant;
use App\Services\Contracts\RentalServiceInterface;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\DB;

class RentalService implements RentalServiceInterface
{
    use HandlesServiceErrors;

    public function createProperty(array $payload): Property
    {
        return $this->handleServiceOperation(
            callback: fn () => Property::create([
                'branch_id' => request()->attributes->get('branch_id'),
                'name' => $payload['name'],
                'address' => $payload['address'] ?? null,
                'notes' => $payload['notes'] ?? null,
            ]),
            operation: 'createProperty',
            context: ['payload' => $payload]
        );
    }

    public function createUnit(int $propertyId, array $payload): RentalUnit
    {
        return $this->handleServiceOperation(
            callback: fn () => RentalUnit::create([
                'property_id' => $propertyId,
                'code' => $payload['code'],
                'status' => $payload['status'] ?? 'vacant',
                'area' => $payload['area'] ?? null,
            ]),
            operation: 'createUnit',
            context: ['property_id' => $propertyId, 'payload' => $payload]
        );
    }

    public function setUnitStatus(int $unitId, string $status): RentalUnit
    {
        return $this->handleServiceOperation(
            callback: function () use ($unitId, $status) {
                $u = RentalUnit::findOrFail($unitId);
                $u->status = $status;
                $u->save();

                return $u;
            },
            operation: 'setUnitStatus',
            context: ['unit_id' => $unitId, 'status' => $status]
        );
    }

    public function createTenant(array $payload): Tenant
    {
        return $this->handleServiceOperation(
            callback: fn () => Tenant::create([
                'name' => $payload['name'],
                'phone' => $payload['phone'] ?? null,
                'email' => $payload['email'] ?? null,
            ]),
            operation: 'createTenant',
            context: ['payload' => $payload]
        );
    }

    public function archiveTenant(int $tenantId): Tenant
    {
        return $this->handleServiceOperation(
            callback: function () use ($tenantId) {
                $t = Tenant::findOrFail($tenantId);
                $t->is_archived = true;
                $t->save();

                return $t;
            },
            operation: 'archiveTenant',
            context: ['tenant_id' => $tenantId]
        );
    }

    public function createContract(int $unitId, int $tenantId, array $payload): RentalContract
    {
        return $this->handleServiceOperation(
            callback: fn () => DB::transaction(function () use ($unitId, $tenantId, $payload) {
                $c = RentalContract::create([
                    'unit_id' => $unitId,
                    'tenant_id' => $tenantId,
                    'start_date' => $payload['start_date'],
                    'end_date' => $payload['end_date'],
                    'rent' => (float) $payload['rent'],
                    'status' => 'active',
                ]);

                return $c;
            }),
            operation: 'createContract',
            context: ['unit_id' => $unitId, 'tenant_id' => $tenantId, 'payload' => $payload]
        );
    }

    public function renewContract(int $contractId, array $payload): RentalContract
    {
        return $this->handleServiceOperation(
            callback: function () use ($contractId, $payload) {
                $c = RentalContract::findOrFail($contractId);
                $c->end_date = $payload['end_date'];
                $c->rent = (float) $payload['rent'];
                $c->save();

                return $c;
            },
            operation: 'renewContract',
            context: ['contract_id' => $contractId, 'payload' => $payload]
        );
    }

    public function terminateContract(int $contractId): RentalContract
    {
        return $this->handleServiceOperation(
            callback: function () use ($contractId) {
                $c = RentalContract::findOrFail($contractId);
                $c->status = 'terminated';
                $c->save();

                return $c;
            },
            operation: 'terminateContract',
            context: ['contract_id' => $contractId]
        );
    }

    public function runRecurring(?string $forDate = null): int
    {
        return $this->handleServiceOperation(
            callback: function () use ($forDate) {
                $forDate = $forDate ?: now()->toDateString();
                dispatch_sync(new \App\Jobs\GenerateRecurringInvoicesJob($forDate));

                return 1;
            },
            operation: 'runRecurring',
            context: ['for_date' => $forDate]
        );
    }

    public function collectPayment(int $invoiceId, float $amount): RentalInvoice
    {
        return $this->handleServiceOperation(
            callback: function () use ($invoiceId, $amount) {
                $i = RentalInvoice::findOrFail($invoiceId);
                $i->paid_total = round(($i->paid_total ?? 0) + $amount, 2);
                $i->status = $i->paid_total >= $i->amount ? 'paid' : 'unpaid';
                $i->save();

                return $i;
            },
            operation: 'collectPayment',
            context: ['invoice_id' => $invoiceId, 'amount' => $amount]
        );
    }

    public function applyPenalty(int $invoiceId, float $penalty): RentalInvoice
    {
        return $this->handleServiceOperation(
            callback: function () use ($invoiceId, $penalty) {
                $i = RentalInvoice::findOrFail($invoiceId);
                $i->amount = round($i->amount + max($penalty, 0.0), 2);
                $i->save();

                return $i;
            },
            operation: 'applyPenalty',
            context: ['invoice_id' => $invoiceId, 'penalty' => $penalty]
        );
    }
}

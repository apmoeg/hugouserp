<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\ModuleField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait HasDynamicFields
{
    use HasBranch;
    use HasJsonAttributes;
    use ModuleAware;

    /**
     * اسم الـ entity اللي بيُستخدم جوه module_fields.entity
     * لو مش متعرّف في الموديل، بنستنتجه من اسم الكلاس (product, vehicle, rental_unit, ...)
     */
    protected ?string $dynamicEntity = null;

    public function getDynamicEntityKey(): string
    {
        if ($this->dynamicEntity) {
            return $this->dynamicEntity;
        }

        return Str::snake(class_basename(static::class));
    }

    public function dynamicFields(?int $branchId = null): Collection
    {
        $entity = $this->getDynamicEntityKey();
        $moduleKey = $this->getModuleKey();
        $branchId = $branchId ?? ($this->branch_id ?? null);

        $query = ModuleField::query()
            ->forEntity($entity);

        if ($moduleKey) {
            $query->forModule($moduleKey);
        }

        if ($branchId) {
            $query->forBranch($branchId);
        } else {
            $query->global();
        }

        return $query->visible()->get();
    }

    public function getDynamicValue(string $key, mixed $default = null): mixed
    {
        return $this->getExtra($key, $default);
    }

    public function setDynamicValue(string $key, mixed $value): static
    {
        return $this->setExtra($key, $value);
    }

    public function getValue(string $key, mixed $default = null): mixed
    {
        return $this->getDynamicValue($key, $default);
    }

    public function setValue(string $key, mixed $value): static
    {
        return $this->setDynamicValue($key, $value);
    }

    public function toArrayWithDynamic(): array
    {
        $data = $this->toArray();
        $data[$this->getExtraAttributesColumn()] = (array) ($this->{$this->getExtraAttributesColumn()} ?? []);

        return $data;
    }

    public function scopeWithDynamic(Builder $query): Builder
    {
        return $query;
    }
}

<?php

namespace App\Models;

use Illuminate\Support\Str;

class Warehouse extends BaseModel
{
    protected ?string $moduleKey = 'inventory';

    protected $table = 'warehouses';

    protected $fillable = [
        'uuid', 'code', 'name', 'type', 'status', 'address',
        'notes', 'extra_attributes', 'branch_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'extra_attributes' => 'array',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($m) {
            $m->uuid = $m->uuid ?: (string) Str::uuid();
            $m->code = $m->code ?: 'WH-'.Str::upper(Str::random(6));
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function products()
    {
        return $this->hasManyThrough(Product::class, StockMovement::class, 'warehouse_id', 'id', 'id', 'product_id')->distinct();
    }

    public function transfersFrom()
    {
        return $this->hasMany(Transfer::class, 'from_warehouse_id');
    }

    public function transfersTo()
    {
        return $this->hasMany(Transfer::class, 'to_warehouse_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeSearch($q, $t)
    {
        return $q->where('name', 'like', "%$t%")->orWhere('code', 'like', "%$t%");
    }
}

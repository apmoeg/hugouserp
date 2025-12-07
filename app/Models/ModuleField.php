<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleField extends Model
{
    use HasFactory;

    protected $table = 'module_fields';

    protected $fillable = [
        'branch_id',
        'module_key',
        'entity',
        'name',
        'label',
        'type',
        'options',
        'rules',
        'is_required',
        'is_visible',
        'show_in_list',
        'show_in_export',
        'order',
        'default',
        'meta',
    ];

    protected $casts = [
        'options' => 'array',
        'rules' => 'array',
        'is_required' => 'bool',
        'is_visible' => 'bool',
        'show_in_list' => 'bool',
        'show_in_export' => 'bool',
        'order' => 'int',
        'default' => 'array',
        'meta' => 'array',
        'branch_id' => 'int',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeForModule($query, string $moduleKey)
    {
        return $query->where('module_key', $moduleKey);
    }

    public function scopeForEntity($query, string $entity)
    {
        return $query->where('entity', $entity);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true)->orderBy('order');
    }
}

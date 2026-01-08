<?php

namespace App\Models;

use App\Models\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Leave Holiday Model
 * 
 * Manages public holidays, company holidays, and regional holidays.
 */
class LeaveHoliday extends Model
{
    use HasFactory, SoftDeletes, HasBranch;

    protected $fillable = [
        'name',
        'date',
        'year',
        'type',
        'is_mandatory',
        'description',
        'branch_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'year' => 'integer',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Type constants
    public const TYPE_PUBLIC = 'public';
    public const TYPE_COMPANY = 'company';
    public const TYPE_REGIONAL = 'regional';
    public const TYPE_RELIGIOUS = 'religious';

    // Relationships

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods

    public function isPublic(): bool
    {
        return $this->type === self::TYPE_PUBLIC;
    }

    public function isCompany(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }

    public function isRegional(): bool
    {
        return $this->type === self::TYPE_REGIONAL;
    }

    public function isReligious(): bool
    {
        return $this->type === self::TYPE_RELIGIOUS;
    }

    public function isPast(): bool
    {
        return $this->date->isPast();
    }

    public function isFuture(): bool
    {
        return $this->date->isFuture();
    }

    public function isToday(): bool
    {
        return $this->date->isToday();
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePublic($query)
    {
        return $query->where('type', self::TYPE_PUBLIC);
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now())->orderBy('date');
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get holidays for a date range
     */
    public static function getHolidaysInRange($startDate, $endDate, ?int $branchId = null)
    {
        $query = static::active()
            ->whereBetween('date', [$startDate, $endDate]);

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
            });
        } else {
            $query->whereNull('branch_id');
        }

        return $query->get();
    }
}

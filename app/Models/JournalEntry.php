<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $table = 'journal_entries';

    protected $fillable = [
        'branch_id',
        'reference_number',
        'entry_date',
        'description',
        'status',
        'source_module',
        'source_type',
        'source_id',
        'created_by',
        'approved_by',
        'approved_at',
        'fiscal_year',
        'fiscal_period',
        'is_auto_generated',
        'is_reversible',
        'reversed_by_entry_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'approved_at' => 'datetime',
        'is_auto_generated' => 'boolean',
        'is_reversible' => 'boolean',
    ];

    protected $appends = ['reference'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedByEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_by_entry_id');
    }

    /**
     * Get fiscal period (composite key not directly supported, use helper)
     */
    public function fiscalPeriod()
    {
        return FiscalPeriod::where('year', $this->fiscal_year)
            ->where('period', $this->fiscal_period)
            ->where('branch_id', $this->branch_id)
            ->first();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function getReferenceAttribute(): string
    {
        return (string) ($this->reference_number ?? '');
    }

    public function getTotalDebitAttribute(): float
    {
        return (float) $this->lines()->sum('debit');
    }

    public function getTotalCreditAttribute(): float
    {
        return (float) $this->lines()->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}

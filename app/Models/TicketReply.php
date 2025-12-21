<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketReply extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ticket_replies';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_internal',
        'read_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'reply_id');
    }

    // Scopes
    public function scopeCustomerReplies($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternalNotes($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // Business Methods
    public function isFromCustomer(): bool
    {
        return $this->ticket && $this->user_id === $this->ticket->customer_id;
    }

    public function isFromAgent(): bool
    {
        return !$this->isFromCustomer();
    }

    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->read_at = now();
            $this->save();
        }

        return $this;
    }
}

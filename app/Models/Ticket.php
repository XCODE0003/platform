<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    public const STATUS_OPEN        = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_CLOSED      = 'closed';

    public const CATEGORIES = [
        'technical' => 'Technical Issue',
        'account'   => 'Account Problem',
        'trading'   => 'Trading Question',
        'other'     => 'Other',
    ];

    protected $fillable = [
        'user_id', 'title', 'description', 'category', 'status', 'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return $this->status !== self::STATUS_CLOSED;
    }
}

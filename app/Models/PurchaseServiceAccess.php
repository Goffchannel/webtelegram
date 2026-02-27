<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseServiceAccess extends Model
{
    protected $fillable = [
        'purchase_id',
        'video_id',
        'service_access_line_id',
        'access_token',
        'expires_at',
        'last_viewed_at',
        'reminder_sent_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function line()
    {
        return $this->belongsTo(ServiceAccessLine::class, 'service_access_line_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

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
        'bound_ips',
        'max_ips',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'bound_ips' => 'array',
    ];

    /**
     * Check if the given IP is allowed to access this token.
     * Records the IP if it's new and within the limit.
     * Returns true if allowed, false if blocked.
     */
    public function checkAndBindIp(string $ip): bool
    {
        $ips = $this->bound_ips ?? [];

        if (in_array($ip, $ips, true)) {
            return true; // already known IP
        }

        $max = $this->max_ips ?? 1;

        if (count($ips) >= $max) {
            return false; // too many IPs — likely shared
        }

        $ips[] = $ip;
        $this->update(['bound_ips' => $ips]);
        return true;
    }

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

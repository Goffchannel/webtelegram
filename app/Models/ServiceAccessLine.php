<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceAccessLine extends Model
{
    protected $fillable = [
        'video_id',
        'creator_id',
        'line_name',
        'm3u_url',
        'line_username',
        'line_password',
        'notes',
        'is_assigned',
        'assigned_purchase_id',
        'assigned_at',
    ];

    protected $casts = [
        'is_assigned' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignedPurchase()
    {
        return $this->belongsTo(Purchase::class, 'assigned_purchase_id');
    }
}

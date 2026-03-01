<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseMessage extends Model
{
    protected $fillable = [
        'purchase_id',
        'sender_type',
        'sender_name',
        'message',
        'telegram_message_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function isFromUser(): bool
    {
        return $this->sender_type === 'user';
    }

    public function isUnread(): bool
    {
        return $this->sender_type === 'user' && $this->read_at === null;
    }
}

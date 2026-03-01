<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotBroadcast extends Model
{
    protected $fillable = [
        'telegram_file_id',
        'file_type',
        'caption',
        'trigger',
        'status',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function targets(): HasMany
    {
        return $this->hasMany(BotBroadcastTarget::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function fileTypeIcon(): string
    {
        return match($this->file_type) {
            'photo'     => 'fa-image',
            'animation' => 'fa-film',
            'document'  => 'fa-file',
            default     => 'fa-video',
        };
    }

    public function sendMethod(): string
    {
        return match($this->file_type) {
            'photo'     => 'sendPhoto',
            'animation' => 'sendAnimation',
            'document'  => 'sendDocument',
            default     => 'sendVideo',
        };
    }

    public function fileKey(): string
    {
        return match($this->file_type) {
            'photo'     => 'photo',
            'animation' => 'animation',
            'document'  => 'document',
            default     => 'video',
        };
    }
}

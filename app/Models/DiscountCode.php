<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_amount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'is_active'   => 'boolean',
        'value'       => 'decimal:2',
        'min_amount'  => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function isValid(float $amount = 0): bool
    {
        if (!$this->is_active) return false;
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->min_amount && $amount < $this->min_amount) return false;
        return true;
    }

    public function apply(float $amount): float
    {
        if ($this->type === 'percent') {
            return round($amount * $this->value / 100, 2);
        }
        return min((float) $this->value, $amount);
    }

    public function formattedValue(): string
    {
        return $this->type === 'percent'
            ? "{$this->value}%"
            : "€" . number_format($this->value, 2);
    }
}

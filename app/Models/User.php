<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_username',
        'telegram_user_id',
        'is_admin',
        'is_creator',
        'creator_slug',
        'creator_store_name',
        'creator_bio',
        'creator_subscription_status',
        'creator_subscription_ends_at',
        'creator_payment_methods',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_creator' => 'boolean',
            'creator_subscription_ends_at' => 'datetime',
            'creator_payment_methods' => 'array',
        ];
    }

    public function creatorVideos()
    {
        return $this->hasMany(Video::class, 'creator_id');
    }

    public function creatorPurchases()
    {
        return $this->hasMany(Purchase::class, 'creator_id');
    }

    public function isCreatorActive(): bool
    {
        return $this->is_creator && $this->subscribed('creator');
    }
}

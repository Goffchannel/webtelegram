<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'creator_id',
        'is_hidden',
        'image_path',
        'image_url',
        'image_blob_url',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    /**
     * The videos that belong to the category.
     */
    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the image URL (Vercel Blob or external)
     *
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        // Check for Vercel Blob URL first
        if ($this->image_blob_url) {
            return $this->image_blob_url;
        }

        // Check for local public storage fallback
        if ($this->image_path) {
            return asset('storage/categories/' . $this->image_path);
        }

        // Check for external direct URL
        if ($this->image_url) {
            return $this->image_url;
        }

        return null;
    }

    /**
     * Check if category has an image.
     *
     * @return bool
     */
    public function hasImage(): bool
    {
        return !empty($this->image_blob_url) || !empty($this->image_path) || !empty($this->image_url);
    }
}

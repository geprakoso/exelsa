<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProdukImage extends Model
{
    use HasFactory;

    protected $table = 'produk_images';

    protected $fillable = [
        'produk_id',
        'original_name',
        'disk',
        'path',
        'size',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['url'];

    /**
     * Get the produk that owns the image.
     */
    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    /**
     * Scope: Get only primary images
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Get the full URL for the image
     */
    public function getUrlAttribute(): string
    {
        $disk = $this->disk ?: config('filesystems.default', 'public');
        return Storage::disk($disk)->url($this->path);
    }

    /**
     * Delete the image file from storage when model is deleted
     */
    protected static function booted()
    {
        static::deleting(function (ProdukImage $image) {
            Storage::disk($image->disk)->delete($image->path);
        });
    }
}

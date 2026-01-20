<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CarImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'image_path',
        'image_type',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    // Accessors
    public function getImageUrlAttribute()
    {
        return Storage::url($this->image_path);
    }

    public function getFullImageUrlAttribute()
    {
        return url(Storage::url($this->image_path));
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeGallery($query)
    {
        return $query->where('image_type', 'gallery')->orderBy('sort_order');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('image_type', $type)->orderBy('sort_order');
    }
}
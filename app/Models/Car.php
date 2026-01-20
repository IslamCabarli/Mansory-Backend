<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'description',
        'status',
        'registration_year',
        'mileage',
        'body_type',
        'engine',
        'fuel_type',
        'transmission',
        'power_hp',
        'power_kw',
        'v_max',
        'acceleration',
        'price',
        'currency',
        'color_exterior',
        'color_interior',
        'doors',
        'seats',
        'vin',
        'meta_title',
        'meta_description',
        'is_featured',
        'view_count',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
        'mileage' => 'integer',
        'power_hp' => 'integer',
        'power_kw' => 'integer',
        'v_max' => 'integer',
        'doors' => 'integer',
        'seats' => 'integer',
    ];

    // Relationships
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function images()
    {
        return $this->hasMany(CarImage::class);
    }

    public function specifications()
    {
        return $this->hasMany(CarSpecification::class)->orderBy('sort_order');
    }

    // Accessors
    public function getPrimaryImageAttribute()
    {
        return $this->images()->where('is_primary', true)->first() 
            ?? $this->images()->first();
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    // Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByBrand($query, $brandId)
    {
        return $query->where('brand_id', $brandId);
    }

    // Methods
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
}
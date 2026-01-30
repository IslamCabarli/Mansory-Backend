<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'is_active',
    ];

    
    protected $appends = ['logo_url']; 

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return null; 
        }
        return asset('storage/' . $this->logo);
    }

    // Relationships
    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    // Accessors & Mutators
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
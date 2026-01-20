<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'spec_key',
        'spec_label',
        'spec_value',
        'spec_unit',
        'spec_category',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // Relationships
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    // Accessors
    public function getFormattedValueAttribute()
    {
        if ($this->spec_unit) {
            return $this->spec_value . ' ' . $this->spec_unit;
        }
        return $this->spec_value;
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('spec_category', $category)->orderBy('sort_order');
    }
}
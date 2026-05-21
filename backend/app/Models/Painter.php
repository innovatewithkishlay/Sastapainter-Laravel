<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Painter extends Model
{
    use HasUuids;

    public function toArray()
    {
        $array = parent::toArray();
        $array['_id'] = $this->id;
        $array['createdAt'] = $this->created_at;
        return $array;
    }

    protected $fillable = [
        'name',
        'phone',
        'specialization',
        'experience',
        'address',
        'salary',
        'active',
        'current_bookings',
        'rating',
        'reviews_count',
        'joinedAt'
    ];

    protected $casts = [
        'active' => 'boolean',
        'current_bookings' => 'array',
        'rating' => 'decimal:2',
        'joinedAt' => 'datetime'
    ];
}

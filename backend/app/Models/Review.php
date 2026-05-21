<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Review extends Model
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
        'user_id',
        'inquiry_id',
        'bookings',
        'rating',
        'comment',
        'isPublic'
    ];

    protected $casts = [
        'isPublic' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class);
    }
}

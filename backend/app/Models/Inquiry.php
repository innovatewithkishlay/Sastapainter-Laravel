<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Inquiry extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'city',
        'service_type',
        'message',
        'address',
        'pincode',
        'preferred_date',
        'status',
        'user_id',
        'assignedPainter',
        'editHistory'
    ];

    protected $casts = [
        'preferred_date' => 'datetime',
        'editHistory' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function painter()
    {
        return $this->belongsTo(Painter::class, 'assignedPainter');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SiteVisit extends Model
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
        'city',
        'status',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

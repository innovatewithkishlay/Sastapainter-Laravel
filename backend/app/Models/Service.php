<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Service extends Model
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
        'description',
        'image',
        'category'
    ];
}

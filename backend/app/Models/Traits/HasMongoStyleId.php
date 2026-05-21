<?php

namespace App\Models\Traits;

/**
 * Appends _id as an alias for id in all JSON responses.
 * This ensures the React frontend (built for MongoDB) works without changes.
 */
trait HasMongoStyleId
{
    public function getIdAttribute($value)
    {
        return $value;
    }

    public function getMongoIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    protected function initializeHasMongoStyleId()
    {
        $this->appends = array_merge($this->appends ?? [], ['_id']);
    }

    public function getUnderscoreIdAttribute()
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    /**
     * Laravel calls get{StudlyCase}Attribute() for appended accessors.
     * Attribute named '_id' maps to getUnderscoreIdAttribute.
     */
}

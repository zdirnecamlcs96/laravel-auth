<?php

namespace Zdirnecamlcs96\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;

    const OWNERABLE = "ownerable";

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        "verified_at", "expired_at", "available_at"
    ];

    protected $fillable = [
        "country_id", "number", "verify_code", "flag", "verified_at", "expired_at", "available_at", "ref", "response", "ownerable_id", "ownerable_type"
    ];

    protected $appends = [
        "full_contact"
    ];

    /**
     * ATTRIBUTES
     */
    function getFullContactAttribute()
    {
        $code = $this->country->phone_code ?? null;
        return "+$code" . $this->number;
    }

    /**
     * RELATIONSHIPS
     */

    function country()
    {
        return $this->belongsTo(Country::class);
    }

    function ownerable()
    {
        return $this->morphTo();
    }

    /**
     * SCOPES
     */

    function scopeCheck($query, $type, string $contact, Country $country)
    {
        return $query->whereNumber($contact)
                    ->whereCountryId($country->id)
                    ->whereOwnerableType($type);
    }

}

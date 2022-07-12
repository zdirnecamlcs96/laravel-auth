<?php

namespace Zdirnecamlcs96\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialIdentity extends Model
{

    use SoftDeletes;

    protected $guarded = [
        "id", "created_at", "updated_at"
    ];

    public function user()
    {
        return $this->belongsTo(config("authentication.models.user"));
    }

    function contact()
    {
        return $this->morphOne(Contact::class, 'ownerable')->latest();
    }
}

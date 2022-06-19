<?php

namespace Zdirnecamlcs96\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialIdentity extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'user_id', 'provider_name', 'provider_id', 'token', 'token_expired_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    function contact()
    {
        return $this->morphOne(Contact::class, 'ownerable')->latest();
    }
}

<?php

namespace Zdirnecamlcs96\Auth\Traits;

trait HasSocialIdentity
{
    public function socialIdentities()
    {
        return $this->hasMany(SocialIdentity::class);
    }
}
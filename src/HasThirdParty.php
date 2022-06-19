<?php

namespace Zdirnecamlcs96\Auth;

trait HasSocialIdentity
{
    public function socialIdentities()
    {
        return $this->hasMany(SocialIdentity::class);
    }
}
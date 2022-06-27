<?php

namespace Zdirnecamlcs96\Auth\Traits;

use Zdirnecamlcs96\Auth\Models\Contact;

trait HasContact
{
    /**
     * RELATIONSHIPs
     */

    public function contact()
    {
        return $this->morphOne(Contact::class, Contact::OWNERABLE);
    }

    /**
     * FUNCTIONs
     */

    /**
     * Check verification on phone number
     *
     * @return bool
     */
    public function contactVerified()
    {
        $this->contact()->whereNotNull('verified_at')->exists();
    }
}
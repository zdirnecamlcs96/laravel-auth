<?php

namespace Zdirnecamlcs96\Auth\Contracts;

interface ShouldAuthenticate
{
    CONST PASSPORT = "passport";
    CONST SANCTUM = "sanctum";

    /**
     * Register fields
     *
     * @return array
     */
    public function registerFields();

}
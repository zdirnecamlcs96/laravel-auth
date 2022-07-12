<?php

namespace Zdirnecamlcs96\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Media extends Model
{
    use SoftDeletes;

    const PROFILE_IMAGE = "profile-image";
    const PATH_TO_STORAGE = "storage/images/";

}

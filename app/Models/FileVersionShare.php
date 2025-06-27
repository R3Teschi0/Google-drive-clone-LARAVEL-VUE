<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileVersionShare extends Model
{
    protected  $fillable = [
        'version_id',
        'user_id',
        'created_at',
        'updated_at'
    ];
}

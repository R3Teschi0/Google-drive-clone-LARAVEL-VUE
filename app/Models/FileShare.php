<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileShare extends Model
{
    protected  $fillable = [
        'file_id',
        'user_id',
        'created_at',
        'updated_at'
    ];
}

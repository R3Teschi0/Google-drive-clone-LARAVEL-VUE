<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileVersion extends Model
{
    use SoftDeletes;

    public function file()
    {
        return $this->belongsTo(File::class)->withTrashed();
    }

    public function moveToTrash()
    {
        $this->deleted_at = Carbon::now();
        return $this->save();
    }
}

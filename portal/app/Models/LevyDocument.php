<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LevyDocument extends Model
{
    protected $guarded = ['id'];

    public function levyRecord()
    {
        return $this->belongsTo(LevyRecord::class);
    }
}

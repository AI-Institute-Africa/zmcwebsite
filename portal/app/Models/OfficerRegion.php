<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficerRegion extends Model
{
    protected $fillable = ['user_id', 'region_id'];

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}

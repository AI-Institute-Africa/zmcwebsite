<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaHouseStaff extends Model
{
    use HasFactory;

    protected $table = 'media_house_staff';

    protected $fillable = [
        'media_house_user_id',
        'journalist_user_id',
        'status',
        'role',
    ];

    public function mediaHouse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'media_house_user_id');
    }

    public function journalist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'journalist_user_id');
    }
}

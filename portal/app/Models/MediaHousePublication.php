<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaHousePublication extends Model
{
    protected $fillable = [
        'registration_record_id', 'publication_name', 'type', 'medium',
        'frequency', 'language', 'focus', 'scope', 'reach_cities',
        'reach_provinces', 'editor_manager', 'pub_website', 'pub_email',
        'pub_contacts', 'operational_status', 'license_expiry', 'renewal_history',
    ];

    protected $casts = [
        'license_expiry' => 'date',
    ];

    public function registrationRecord(): BelongsTo
    {
        return $this->belongsTo(RegistrationRecord::class);
    }
}

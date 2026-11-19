<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationMessage extends Model
{
  protected $fillable = [
    'application_id','from_user_id','to_user_id','channel','subject','body','sent_at','read_at'
  ];

  protected $casts = [
    'sent_at' => 'datetime',
    'read_at' => 'datetime',
  ];

  public function application(): BelongsTo
  {
    return $this->belongsTo(Application::class);
  }
}

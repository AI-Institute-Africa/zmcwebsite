<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceEvidenceFile extends Model
{
    use HasFactory;

    protected $table = 'compliance_evidence_files';

    protected $fillable = ['entity_type','entity_id','file_path','original_name','uploaded_by'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

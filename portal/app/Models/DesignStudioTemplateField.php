<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignStudioTemplateField extends Model
{
    protected $table = 'design_studio_template_fields';

    protected $fillable = [
        'template_id', 'field_key', 'field_label', 'field_type',
        'x_position', 'y_position', 'width', 'height',
        'font_family', 'font_size', 'font_weight', 'text_align',
        'color', 'background_color', 'rotation', 'z_index', 'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'x_position'  => 'float',
        'y_position'  => 'float',
        'width'       => 'float',
        'height'      => 'float',
        'rotation'    => 'integer',
        'z_index'     => 'integer',
        'font_size'   => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(DesignStudioTemplate::class, 'template_id');
    }
}

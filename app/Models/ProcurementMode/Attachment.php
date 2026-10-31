<?php

namespace App\Models\ProcurementMode;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';

    protected $fillable = [
        'procurement_mode_phase_id',
        'name',
        'form_id',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProcurementModePhase::class, 'procurement_mode_phase_id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }
    
    public function signatories(): HasMany
    {
        return $this->hasMany(AttachmentSignatory::class, 'attachment_id')->orderBy('sequence', 'asc');
    }
}

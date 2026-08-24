<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementModePhaseDocument extends Model
{
    protected $fillable = ['phase_id', 'document_type_name', 'is_mandatory'];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProcurementModePhase::class, 'phase_id');
    }
}

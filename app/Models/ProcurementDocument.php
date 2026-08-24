<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementDocument extends Model
{
    protected $fillable = ['procurement_id', 'phase_id', 'file_name', 'file_path', 'file_size', 'mime_type', 'uploaded_by'];

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProcurementModePhase::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

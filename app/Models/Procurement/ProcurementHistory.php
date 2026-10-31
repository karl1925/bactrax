<?php

namespace App\Models\Procurement;

use App\Models\ProcurementMode\ProcurementModePhase;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementHistory extends Model
{
    public $timestamps = false; // Uses created_at manually

    protected $fillable = [
        'procurement_id',
        'phase_id',
        'user_id',
        'action',
        'status_from',
        'status_to',
        'remarks',
        'created_at'
    ];

    /**
     * Cast created_at to Carbon datetime object.
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProcurementModePhase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

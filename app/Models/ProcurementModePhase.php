<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementModePhase extends Model
{
    protected $fillable = ['procurement_mode_id', 'phase_sequence', 'phase_name', 'description', 'allowable_days'];

    public function mode(): BelongsTo
    {
        return $this->belongsTo(ProcurementMode::class, 'procurement_mode_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'procurement_mode_phase_roles', 'phase_id', 'role_id')->withPivot('can_act');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProcurementModePhaseDocument::class, 'phase_id');
    }
}

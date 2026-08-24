<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementMode extends Model
{
    protected $fillable = ['code', 'name', 'min_abc', 'max_abc', 'description', 'is_active'];

    public function phases(): HasMany
    {
        return $this->hasMany(ProcurementModePhase::class)->orderBy('phase_sequence');
    }

    public function procurements(): HasMany
    {
        return $this->hasMany(Procurement::class);
    }
}

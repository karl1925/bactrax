<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementAward extends Model
{
    protected $fillable = [
        'procurement_id', 'award_basis', 'opening_at', 'opening_place', 'resolution_number',
        'resolution_date', 'resolution_place', 'recommendation', 'status', 'prepared_by',
        'finalized_by', 'finalized_at',
    ];

    protected function casts(): array
    {
        return ['opening_at' => 'datetime', 'resolution_date' => 'date', 'finalized_at' => 'datetime'];
    }

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ProcurementAwardParticipant::class)->orderBy('sequence');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ProcurementAwardResult::class);
    }
}

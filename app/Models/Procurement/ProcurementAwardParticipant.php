<?php

namespace App\Models\Procurement;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementAwardParticipant extends Model
{
    protected $fillable = [
        'procurement_award_id', 'user_id', 'role_id', 'name', 'position',
        'sequence', 'attended', 'is_retired',
    ];

    protected function casts(): array
    {
        return ['sequence' => 'integer', 'attended' => 'boolean', 'is_retired' => 'boolean'];
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(ProcurementAward::class, 'procurement_award_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}

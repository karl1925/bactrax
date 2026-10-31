<?php

namespace App\Models\Procurement;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementSignatory extends Model
{
    protected $fillable = ['procurement_id', 'sequence', 'configured_role_id', 'signatory_id', 'is_signed', 'signed_at'];

    protected function casts(): array
    {
        return ['sequence' => 'integer', 'is_signed' => 'boolean', 'signed_at' => 'datetime'];
    }

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function configuredRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'configured_role_id');
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signatory_id');
    }
}

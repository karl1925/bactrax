<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Procurement extends Model
{
    protected $fillable = [
        'tracking_number',
        'title',
        'end_user_office_id',
        'procurement_mode_id',
        'current_phase_id',
        'returned_from_phase_id',
        'abc_amount',
        'status',
        'created_by'
    ];

    public function returnedFromPhase(): BelongsTo
    {
        return $this->belongsTo(
            ProcurementModePhase::class,
            'returned_from_phase_id'
        );
    }

    public function endUserOffice(): BelongsTo
    {
        return $this->belongsTo(
            Office::class,
            'end_user_office_id'
        );
    }

    public function mode(): BelongsTo
    {
        return $this->belongsTo(
            ProcurementMode::class,
            'procurement_mode_id'
        );
    }

    public function currentPhase(): BelongsTo
    {
        return $this->belongsTo(
            ProcurementModePhase::class,
            'current_phase_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProcurementDocument::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(ProcurementRoute::class);
    }

    /**
     * Get the currently open route.
     *
     * An open route is one that has not yet been acted upon.
     */
    public function currentRoute()
    {
        return $this->hasOne(
            ProcurementRoute::class,
            'procurement_id'
        )->latestOfMany();
    }

    public function history(): HasMany
    {
        return $this->hasMany(ProcurementHistory::class);
    }

    /**
     * Get the next sequential phase for this project.
     */
    public function getNextPhase(): ?ProcurementModePhase
    {
        // If returned previously, next route step jumps
        // back to the return origin.
        if (
            $this->returned_from_phase_id &&
            $this->returnedFromPhase
        ) {
            return $this->returnedFromPhase;
        }

        $currentSeq = $this->currentPhase?->phase_sequence ?? 0;

        return $this->mode
            ->phases()
            ->where('phase_sequence', '>', $currentSeq)
            ->orderBy('phase_sequence', 'asc')
            ->first();
    }

    /**
     * Fetch offices where users with the next phase's
     * authorized roles exist.
     */
    public function getNextPhaseTargetOffices()
    {
        $nextPhase = $this->getNextPhase();

        if (!$nextPhase) {
            return Office::where('is_active', true)->get();
        }

        $nextRoleIds = $nextPhase->roles
            ->pluck('id')
            ->toArray();

        // If no specific roles are assigned to the next phase,
        // allow all active offices.
        if (empty($nextRoleIds)) {
            return Office::where('is_active', true)->get();
        }

        return Office::where('is_active', true)
            ->whereHas('users.roles', function ($q) use ($nextRoleIds) {
                $q->whereIn('roles.id', $nextRoleIds);
            })
            ->get();
    }

    /**
     * Fetch users who hold authorized roles for the next phase
     * AND belong to target offices.
     */
    public function getNextPhaseTargetUsers()
    {
        $nextPhase = $this->getNextPhase();

        if (!$nextPhase) {
            return User::all();
        }

        $nextRoleIds = $nextPhase->roles
            ->pluck('id')
            ->toArray();

        $targetOfficeIds = $this
            ->getNextPhaseTargetOffices()
            ->pluck('id')
            ->toArray();

        return User::query()
            ->when(!empty($targetOfficeIds), function ($q) use ($targetOfficeIds) {
                $q->whereHas('offices', function ($oq) use ($targetOfficeIds) {
                    $oq->whereIn('offices.id', $targetOfficeIds);
                });
            })
            ->when(!empty($nextRoleIds), function ($q) use ($nextRoleIds) {
                $q->whereHas('roles', function ($rq) use ($nextRoleIds) {
                    $rq->whereIn('roles.id', $nextRoleIds);
                });
            })
            ->get();
    }
}

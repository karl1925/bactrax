<?php

namespace App\Models\Procurement;

use App\Models\Office;
use App\Models\ProcurementMode\ProcurementMode;
use App\Models\ProcurementMode\ProcurementModePhase;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class Procurement extends Model
{
    protected $fillable = [
        'pr_number',
        'purpose',
        'fund_cluster',
        'responsibility_code',
        'pr_date',
        'is_delivery_cd',
        'delivery_cd',
        'delivery_date',
        'quotation_deadline',
        'end_user_office_id',
        'procurement_mode_id',
        'current_phase_id',
        'returned_from_phase_id',
        'abc_amount',
        'status',
        'created_by',
    ];

    protected $casts = [
        'pr_date' => 'datetime',
        'is_delivery_cd' => 'boolean',
        'delivery_cd' => 'integer',
        'delivery_date' => 'date',
        'quotation_deadline' => 'datetime',
        'abc_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementItem::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(ProcurementBid::class);
    }

    public function award(): HasOne
    {
        return $this->hasOne(ProcurementAward::class);
    }

    /**
     * Top-level line items only (standalone items and lot group headers, excluding nested sub-items).
     */
    public function rootItems(): HasMany
    {
        return $this->hasMany(ProcurementItem::class)
            ->whereNull('parent_id')
            ->orderBy('line_no', 'asc');
    }

    /**
     * Top-level Lot headers only.
     */
    public function lotHeaders(): HasMany
    {
        return $this->hasMany(ProcurementItem::class)
            ->where('is_lot', true)
            ->whereNull('parent_id')
            ->orderBy('line_no', 'asc');
    }

    /**
     * Standalone non-lot items only.
     */
    public function standaloneItems(): HasMany
    {
        return $this->hasMany(ProcurementItem::class)
            ->where('is_lot', false)
            ->whereNull('parent_id')
            ->orderBy('line_no', 'asc');
    }
    
    public function returnedFromPhase(): BelongsTo
    {
        return $this->belongsTo(ProcurementModePhase::class, 'returned_from_phase_id');
    }

    public function endUserOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'end_user_office_id');
    }

    public function mode(): BelongsTo
    {
        return $this->belongsTo(ProcurementMode::class, 'procurement_mode_id');
    }

    public function currentPhase(): BelongsTo
    {
        return $this->belongsTo(ProcurementModePhase::class, 'current_phase_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
        if ($this->returned_from_phase_id && $this->returnedFromPhase) {
            return $this->returnedFromPhase;
        }

        $currentSeq = $this->currentPhase?->phase_sequence ?? 0;

        return $this->mode
            ?->phases()
            ->where('phase_sequence', '>', $currentSeq)
            ->orderBy('phase_sequence', 'asc')
            ->first();
    }

    /**
     * Fetch offices where users with the next phase's authorized roles exist.
     */
    public function getNextPhaseTargetOffices(): Collection
    {
        $nextPhase = $this->getNextPhase();

        if (!$nextPhase) {
            return Office::where('is_active', true)->get();
        }

        $nextRoleIds = $nextPhase->roles->pluck('id')->toArray();

        if (empty($nextRoleIds)) {
            return Office::where('is_active', true)->get();
        }

        return Office::query()
            ->where('is_active', true)
            ->whereHas('users.roles', fn($query) => $query->whereIn('roles.id', $nextRoleIds))
            ->get();
    }

    /**
     * Fetch users who hold authorized roles for the next phase AND belong to target offices.
     */
    public function getNextPhaseTargetUsers(): Collection
    {
        $nextPhase = $this->getNextPhase();

        if (!$nextPhase) {
            return User::all();
        }

        $nextRoleIds = $nextPhase->roles->pluck('id')->toArray();
        $targetOfficeIds = $this->getNextPhaseTargetOffices()->pluck('id')->toArray();

        return User::query()
            ->when(!empty($targetOfficeIds), fn($query) => $query->whereHas(
                'offices',
                fn($officeQuery) => $officeQuery->whereIn('offices.id', $targetOfficeIds)
            ))
            ->when(!empty($nextRoleIds), fn($query) => $query->whereHas(
                'roles',
                fn($roleQuery) => $roleQuery->whereIn('roles.id', $nextRoleIds)
            ))
            ->get();
    }

    /**
     * Calculate the total item cost directly in the database.
     */
    public function getTotalCost(): float
    {
        return (float) $this->items()->where('is_lot', false)->sum(DB::raw('COALESCE(quantity, 0) * COALESCE(cost, 0)'));
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProcurementAttachment::class);
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(ProcurementSignatory::class)->orderBy('sequence');
    }

    public function rfqDocuments(): BelongsToMany
    {
        return $this->belongsToMany(RfqDocument::class, 'procurement_required_docs', 'procurement_id', 'rfq_document_id')
            ->withTimestamps();
    }
}

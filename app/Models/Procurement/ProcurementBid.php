<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementBid extends Model
{
    protected $fillable = [
        'procurement_id', 'supplier_id', 'revision', 'status', 'received_at',
        'landbank_account_name', 'landbank_account_number',
        'total_bid_amount', 'is_responsive', 'evaluation_remarks', 'evaluated_by', 'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'revision' => 'integer', 'received_at' => 'datetime', 'total_bid_amount' => 'decimal:2',
            'is_responsive' => 'boolean', 'evaluated_at' => 'datetime',
        ];
    }

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementBidItem::class);
    }

    public function presentedDocuments(): HasMany
    {
        return $this->hasMany(ProcurementBidDocument::class)->orderBy('sequence');
    }

    public function awardResults(): HasMany
    {
        return $this->hasMany(ProcurementAwardResult::class);
    }
}

<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementAwardResult extends Model
{
    protected $fillable = [
        'procurement_award_id', 'procurement_item_id', 'procurement_bid_id',
        'awarded_amount', 'result_status', 'remarks', 'determined_by', 'determined_at',
    ];

    protected function casts(): array
    {
        return ['awarded_amount' => 'decimal:2', 'determined_at' => 'datetime'];
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(ProcurementAward::class, 'procurement_award_id');
    }

    public function procurementItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementItem::class);
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(ProcurementBid::class, 'procurement_bid_id');
    }
}

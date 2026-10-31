<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementBidItem extends Model
{
    protected $fillable = [
        'procurement_bid_id', 'procurement_item_id', 'quoted_quantity', 'unit_price', 'line_total',
        'brand', 'model', 'offered_specifications', 'compliance_status', 'is_responsive', 'evaluation_remarks',
    ];

    protected function casts(): array
    {
        return [
            'quoted_quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2',
            'is_responsive' => 'boolean',
        ];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(ProcurementBid::class, 'procurement_bid_id');
    }

    public function procurementItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementItem::class);
    }
}

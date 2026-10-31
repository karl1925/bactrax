<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementBidDocument extends Model
{
    protected $fillable = [
        'procurement_bid_id', 'document_type', 'document_name', 'reference_number', 'issuer',
        'issued_at', 'expires_at', 'details', 'compliance_status', 'evaluation_remarks', 'sequence',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date', 'sequence' => 'integer'];
    }

    public function bid(): BelongsTo
    {
        return $this->belongsTo(ProcurementBid::class, 'procurement_bid_id');
    }
}

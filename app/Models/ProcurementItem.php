<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementItem extends Model
{
    protected $fillable = ['procurement_id', 'item_code', 'description', 'quantity', 'unit_of_measure', 'unit_cost', 'total_cost'];

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }
}

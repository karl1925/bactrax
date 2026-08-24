<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRoute extends Model
{
    protected $fillable = ['procurement_id', 'from_office_id', 'to_office_id', 'from_user_id', 'to_user_id', 'action_taken', 'remarks', 'received_at', 'acted_at'];

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    public function fromOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'from_office_id');
    }

    public function toOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'to_office_id');
    }
}

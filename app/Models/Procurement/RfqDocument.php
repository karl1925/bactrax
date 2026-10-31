<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RfqDocument extends Model
{
    protected $fillable = [
        'sequence',
        'title',
        'is_active',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'is_active' => 'boolean',
    ];

    public function procurements(): BelongsToMany
    {
        return $this->belongsToMany(Procurement::class, 'procurement_required_docs', 'rfq_document_id', 'procurement_id')
            ->withTimestamps();
    }
}

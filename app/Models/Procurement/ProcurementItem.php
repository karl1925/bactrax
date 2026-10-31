<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'item_bank_item_id',
        'parent_id',
        'line_no',
        'is_lot',
        'unit',
        'description',
        'quantity',
        'cost',
    ];

    protected $casts = [
        'is_lot' => 'boolean',
        'line_no' => 'integer',
        'quantity' => 'decimal:3',
        'cost' => 'decimal:2',
    ];

    /**
     * Parent Lot Header (if this is a sub-item).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProcurementItem::class, 'parent_id');
    }

    /**
     * Child sub-items belonging to this Lot Header.
     */
    public function subItems(): HasMany
    {
        return $this->hasMany(ProcurementItem::class, 'parent_id')->orderBy('line_no', 'asc');
    }

    /**
     * Parent Procurement Request.
     */
    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    /** Canonical catalog record, if this procurement line has been matched. */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(ItemBankItem::class, 'item_bank_item_id');
    }
}

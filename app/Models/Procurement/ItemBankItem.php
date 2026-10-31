<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemBankItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['item_code', 'description', 'category', 'unit', 'reference_price', 'price_source', 'reference_date', 'specifications', 'is_active', 'created_by'];

    protected function casts(): array
    {
        return ['reference_price' => 'decimal:2', 'reference_date' => 'date', 'is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function procurementItems(): HasMany
    {
        return $this->hasMany(ProcurementItem::class, 'item_bank_item_id');
    }

    /** Submitted and evaluated bid prices remain in their source records. */
    public function historicalBidItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProcurementBidItem::class,
            ProcurementItem::class,
            'item_bank_item_id',
            'procurement_item_id',
            'id',
            'id'
        )
            ->whereNotNull('procurement_bid_items.unit_price')
            ->whereHas('bid', fn ($query) => $query->whereIn('status', ['SUBMITTED', 'EVALUATED']));
    }
}

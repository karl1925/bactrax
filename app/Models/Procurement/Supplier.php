<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_name',
        'business_type',
        'contact_person',
        'email',
        'phone',
        'business_address',
        'tin',
        'philgeps_registration_number',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function bids(): HasMany
    {
        return $this->hasMany(ProcurementBid::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /** Latest uploaded version for each document type. Older versions remain available via documents(). */
    public function latestDocuments(): HasMany
    {
        return $this->hasMany(SupplierDocument::class)->whereIn('supplier_documents.id', function (Builder $query) {
            $query->selectRaw('MAX(latest.id)')
                ->from('supplier_documents as latest')
                ->whereColumn('latest.supplier_id', 'supplier_documents.supplier_id')
                ->groupBy('latest.document_type');
        });
    }
}

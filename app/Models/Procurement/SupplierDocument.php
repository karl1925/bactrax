<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDocument extends Model
{
    protected $fillable = [
        'supplier_id', 'document_type', 'document_name', 'document_number', 'issuer',
        'issued_at', 'expires_at', 'disk', 'file_path', 'original_filename',
        'mime_type', 'file_size', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date', 'file_size' => 'integer'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

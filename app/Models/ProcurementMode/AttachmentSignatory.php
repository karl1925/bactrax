<?php

namespace App\Models\ProcurementMode;

use App\Models\Role;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttachmentSignatory extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachment_id',
        'sequence',
        'role_id',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }

    /**
     * Get the role designated as the required signatory step.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}

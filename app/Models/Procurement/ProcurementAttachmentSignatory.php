<?php

namespace App\Models\Procurement;

use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One configured signature slot for a procurement attachment file.
 */
class ProcurementAttachmentSignatory extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'procurement_attachment_signatories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'procurement_attachments_id',
        'sequence',
        'configured_role_id',
        'is_signed',
        'signed_at',
        'signatory_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sequence' => 'integer',
        'is_signed' => 'boolean',
        'signed_at' => 'datetime',
    ];

    /**
     * The procurement attachment (stored PDF record) this slot belongs to.
     */
    public function attachment(): BelongsTo
    {
        return $this->belongsTo(ProcurementAttachment::class, 'procurement_attachments_id');
    }

    /**
     * The user who signed this slot.
     */
    public function signatory(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signatory_id');
    }

    /** The role this signature slot was configured for. */
    public function configuredRole(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Role::class, 'configured_role_id');
    }
}

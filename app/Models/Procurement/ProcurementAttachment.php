<?php

namespace App\Models\Procurement;

use App\Models\User;
use App\Models\ProcurementMode\Attachment as ConfiguredAttachment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The uploaded or compiled file for one procurement and configured phase attachment.
 * Signing replaces `file_path` in place; the record itself is never recreated.
 */
class ProcurementAttachment extends Model
{
    protected $fillable = [
        'procurement_id',
        'phase_id',
        'attachment_id',
        'form_id',
        'form_data',
        'is_completed',
        'workflow_status',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['is_completed' => 'boolean', 'form_data' => 'array'];
    }

    /**
     * The procurement this file belongs to.
     */
    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class);
    }

    /**
     * The user who uploaded / compiled the file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function configuredAttachment(): BelongsTo
    {
        return $this->belongsTo(ConfiguredAttachment::class, 'attachment_id');
    }

    /**
     * Signature slots recorded for this file.
     */
    public function signatories(): HasMany
    {
        return $this->hasMany(ProcurementAttachmentSignatory::class, 'procurement_attachments_id')->orderBy('sequence');
    }
}

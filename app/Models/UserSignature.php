<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'p12_path',
        'p12_name',
        'p12_password',
        'signature_text',
        'signature_image_path',
        'certificate_subject',
        'certificate_issuer',
        'certificate_serial',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        /*
         * Laravel automatically encrypts this value when saving
         * and decrypts it when retrieving it.
         */
        'p12_password' => 'encrypted',

        'valid_before' => 'datetime',
        'valid_until' => 'datetime',

        'password_required' => 'boolean',

        'is_active' => 'boolean',
    ];

    /**
     * User who owns this signing configuration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine whether this certificate is usable.
     */
    public function isUsable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (empty($this->p12_path)) {
            return false;
        }

        if (
            $this->certificate_expires_at !== null &&
            $this->certificate_expires_at->isPast()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user needs to provide
     * the P12 password during signing.
     */
    public function requiresPassword(): bool
    {
        return $this->password_required;
    }

    /**
     * Determine whether an encrypted password is stored.
     */
    public function hasStoredPassword(): bool
    {
        return !empty($this->p12_password);
    }
}

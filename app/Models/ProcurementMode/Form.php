<?php

namespace App\Models\ProcurementMode;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Form extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'path',
        'status',
        'configuration',
    ];

    protected $casts = [
        'configuration' => 'array',
    ];

    /**
     * Auto-delete associated HTML file when model record is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Form $form) {
            if ($form->path && Storage::disk('private')->exists($form->path)) {
                Storage::disk('private')->delete($form->path);
            }
        });
    }
}

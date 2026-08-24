<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = ['code', 'name', 'location_type', 'is_active'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'office_users')->withPivot('is_primary');
    }

    public function procurements(): HasMany
    {
        return $this->hasMany(Procurement::class, 'end_user_office_id');
    }
}

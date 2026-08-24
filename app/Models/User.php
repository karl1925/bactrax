<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function offices(): BelongsToMany
    {
        return $this->belongsToMany(
            Office::class,
            'office_users'
        )->withPivot('is_primary');
    }

    public function primaryOffice(): ?Office
    {
        return $this->offices()
            ->wherePivot('is_primary', true)
            ->first();
    }

    public function procurements(): HasMany
    {
        return $this->hasMany(Procurement::class, 'created_by');
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists();
    }

    public function hasPrivilege(string $privilege): bool
    {
        return $this->roles()
            ->whereHas('privileges', function ($query) use ($privilege) {
                $query->where('code', $privilege);
            })
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->roles()
            ->where('name', 'admin')
            ->exists();
    }

    /**
     * Check if user holds a regional/central processing role.
     */
    public function isCentralRole(): bool
    {
        return $this->roles()->whereIn('name', [
            'admin',
            'bac_secretariat',
            'hope',
            'budget_officer',
            'twg_member',
            'canvasser'
        ])->exists();
    }

    /**
     * Determine whether the user is allowed to act on the
     * currently assigned procurement.
     *
     * Rules:
     * - Admin can always act.
     * - If the active route targets a specific user,
     *   only that user can act.
     * - If no target user is specified, normal phase
     *   authorization applies.
     */
    public function canActOnProcurement(Procurement $procurement): bool
    {
        $currentRoute = $procurement->currentRoute;

        // No active route — fall back to normal phase authorization.
        if (!$currentRoute) {
            return $procurement->currentPhase
                && $this->canActOnPhase($procurement->currentPhase);
        }

        // A specific user was targeted.
        // Only that user may act.
        if ($currentRoute->to_user_id !== null) {
            return (int) $currentRoute->to_user_id === (int) $this->id;
        }

        // Admin can always act.
        if ($this->isAdmin()) {
            return true;
        }

        // No specific user was targeted.
        // Use normal phase-role authorization.
        return $procurement->currentPhase
            && $this->canActOnPhase($procurement->currentPhase);
    }

    /**
     * Check if the user holds an authorized role assigned to the target phase.
     */
    public function canActOnPhase(?ProcurementModePhase $phase): bool
    {
        if (!$phase) {
            return false;
        }

        $phaseRoleIds = $phase->roles->pluck('id')->toArray();

        // If no roles are configured for the phase step, default to allowing access
        if (empty($phaseRoleIds)) {
            return true;
        }

        // If Admin, allow access
        if ($this->isAdmin()) {
            return true;
        }

        // Strictly check if the user holds one of the authorized phase roles
        return $this->roles()->whereIn('roles.id', $phaseRoleIds)->exists();
    }
}

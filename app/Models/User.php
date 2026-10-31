<?php

namespace App\Models;

use App\Models\Procurement\Procurement;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProcurementMode\ProcurementModePhase;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'oidc_sub',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'user.'.$this->getKey();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        );
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
            ->exists() || $this->isAdmin();
    }

    public function hasPrivilege(string $privilege): bool
    {
        return $this->roles()
            ->whereHas('privileges', function ($query) use ($privilege) {
                $query->where('code', $privilege);
            })
            ->exists() || $this->isAdmin();
    }

    /**
     * Check if user holds a regional/central processing role.
     */
    public function isCentralRole(): bool
    {
        return $this->roles()->whereIn('name', [
            'admin',
            'bac_chair',
            'bac_secretariat',
            'hope',
            'budget_officer',
            'twg_member',
            'canvasser'
        ])->exists();
    }

    /** Determine whether the user may act in the procurement's current phase. */
    public function canActOnProcurement(Procurement $procurement): bool
    {
        return $procurement->currentPhase
            && $this->canActOnPhase($procurement->currentPhase);
    }

    /**
     * Check if the user holds an authorized role assigned to the target phase.
     */
    public function canActOnPhase(?ProcurementModePhase $phase): bool
    {
        if (!$phase) return false;

        $phaseRoleIds = $phase->roles
            ->filter(fn($role) => (bool) ($role->pivot?->can_act ?? true))
            ->pluck('id');
        if ($phaseRoleIds->isEmpty()) return false;

        return $this->roles()->whereIn('roles.id', $phaseRoleIds)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->roles()
            ->where('roles.name', 'admin')
            ->exists();
    }

    public function syncOidcRoles(array $roleNames): array
    {
        $allowedRoles = [
            'admin',
            'end_user',
            'canvasser',
            'budget_officer',
            'bac_chair',
            'bac_secretariat',
            'twg_member',
            'hope',
        ];

        $roleNames = collect($roleNames)
            ->filter(fn($role) => is_string($role))
            ->map(fn($role) => trim($role))
            ->filter(fn($role) => in_array($role, $allowedRoles, true))
            ->unique()
            ->values();

        $roleIds = Role::query()
            ->whereIn('name', $roleNames)
            ->pluck('id')
            ->all();

        $this->roles()->sync($roleIds);

        return $roleNames->all();
    }

}

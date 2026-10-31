<?php

namespace App\Policies;

use App\Models\Procurement\Procurement;
use App\Models\User;

class ProcurementPolicy
{
    public function view(User $user, Procurement $procurement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isCentralRole()) {
            return true;
        }
        $userOfficeIds = $user->offices->pluck('id')->toArray();

        if (in_array($procurement->end_user_office_id, $userOfficeIds)) {
            return true;
        }

        if ($procurement->mode?->phases?->contains(fn ($phase) => $user->canActOnPhase($phase))) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('procurement.add');
    }



    public function update(User $user, Procurement $procurement): bool
    {
        if (!in_array($procurement->status, ['DRAFT', 'RETURNED'])) {
            return false;
        }

        if (!$user->hasPermission('procurement.edit')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $procurement->created_by;
    }

    public function route(User $user, Procurement $procurement): bool
    {
        if (in_array($procurement->status, ['COMPLETED', 'CANCELLED', 'REJECTED'])) {
            return false;
        }

        // Block routing if currently on the final phase
        $lastPhaseSequence = $procurement->mode?->phases?->max('phase_sequence');
        $isLastPhase = $procurement->currentPhase && $procurement->currentPhase->phase_sequence == $lastPhaseSequence;

        if ($isLastPhase) {
            return false;
        }

        return $user->canActOnProcurement($procurement);
    }

    public function return(User $user, Procurement $procurement): bool
    {
        if (in_array($procurement->status, ['DRAFT', 'CANCELLED', 'COMPLETED', 'REJECTED'])) {
            return false;
        }

        // Block return if on Phase 1
        $firstPhaseSequence = $procurement->mode?->phases?->min('phase_sequence') ?? 1;
        $isFirstPhase = $procurement->currentPhase && $procurement->currentPhase->phase_sequence == $firstPhaseSequence;

        if ($isFirstPhase) {
            return false;
        }

        return $this->canPerformAction($user, $procurement, 'can_return');
    }

    public function complete(User $user, Procurement $procurement): bool
    {
        if (in_array($procurement->status, ['DRAFT', 'RETURNED', 'COMPLETED', 'CANCELLED', 'REJECTED'])) {
            return false;
        }

        // ONLY allow completion if currently on the final phase
        $lastPhaseSequence = $procurement->mode?->phases?->max('phase_sequence');
        $isLastPhase = $procurement->currentPhase && $procurement->currentPhase->phase_sequence == $lastPhaseSequence;

        if (!$isLastPhase) {
            return false;
        }

        return $this->canPerformAction($user, $procurement, 'can_complete');
    }

    public function cancel(User $user, Procurement $procurement): bool
    {
        if (in_array($procurement->status, ['COMPLETED', 'CANCELLED', 'REJECTED'])) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $procurement->created_by && $user->hasPrivilege('can_cancel');
    }

    public function reject(User $user, Procurement $procurement): bool
    {
        if (in_array($procurement->status, ['DRAFT', 'RETURNED', 'COMPLETED', 'CANCELLED', 'REJECTED'])) {
            return false;
        }

        return $this->canPerformAction($user, $procurement, 'can_reject');
    }

    protected function canPerformAction(User $user, Procurement $procurement, string $privilegeCode): bool
    {
        if (!$user->hasPrivilege($privilegeCode)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->canActOnPhase($procurement->currentPhase);
    }
}

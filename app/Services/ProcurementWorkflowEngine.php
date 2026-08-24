<?php

namespace App\Services;

use App\Models\Procurement;
use App\Models\ProcurementRoute;
use App\Models\ProcurementHistory;
use App\Models\User;
use App\Exceptions\ProcurementWorkflowException;
use Illuminate\Support\Facades\DB;

class ProcurementWorkflowEngine
{
    /**
     * Routes procurement to next phase (with Fast-Track Return support).
     */
    public function routeToNextPhase(
        Procurement $procurement,
        User $actor,
        int $destinationOfficeId,
        ?string $remarks = null,
        ?int $targetUserId = null
    ): Procurement {
        return DB::transaction(function () use ($procurement, $actor, $destinationOfficeId, $remarks, $targetUserId) {
            $currentPhase = $procurement->currentPhase;

            // 1. Mandatory Document Check for Current Phase
            if ($currentPhase) {
                $mandatoryDocs = $currentPhase->documents()->where('is_mandatory', true)->get();
                $uploadedDocs = $procurement->documents()
                    ->where('phase_id', $currentPhase->id)
                    ->pluck('file_name');

                foreach ($mandatoryDocs as $reqDoc) {
                    $hasDoc = $uploadedDocs->contains(function ($name) use ($reqDoc) {
                        return str_contains(strtolower($name), strtolower($reqDoc->document_type_name));
                    });

                    if (!$hasDoc) {
                        throw new ProcurementWorkflowException(
                            "Cannot route: Missing mandatory document '{$reqDoc->document_type_name}' for phase '{$currentPhase->phase_name}'."
                        );
                    }
                }
            }

            // 2. Determine Next Target Phase Step
            if ($procurement->returned_from_phase_id && $procurement->returned_from_phase_id > $currentPhase?->id) {
                // FAST-TRACK: Jump straight back to Phase 4 (Origin of Return)
                $nextPhase = $procurement->returnedFromPhase;
                $actionLabel = "FAST-TRACKED BACK TO: {$nextPhase->phase_name}";
            } else {
                // NORMAL: Move to the next sequential phase step (Phase 3)
                $nextPhase = $procurement->mode->phases()
                    ->where('phase_sequence', '>', $currentPhase ? $currentPhase->phase_sequence : 0)
                    ->orderBy('phase_sequence', 'asc')
                    ->first();
                $actionLabel = $nextPhase ? "FORWARDED: {$nextPhase->phase_name}" : "FINAL_APPROVAL";
            }

            /*
            * 3. Validate Target User
            *
            * If a specific user was selected, make sure that user
            * actually belongs to the selected destination office.
            */
            $targetUser = null;

            if ($targetUserId) {
                $targetUser = User::where('id', $targetUserId)
                    ->whereHas('offices', function ($query) use ($destinationOfficeId) {
                        $query->where('offices.id', $destinationOfficeId);
                    })
                    ->first();

                if (!$targetUser) {
                    throw new ProcurementWorkflowException(
                        'The selected target user does not belong to the destination office.'
                    );
                }
            }

            $previousStatus = $procurement->status;

            // Advance Phase and CLEAR returned_from_phase_id memory
            $procurement->current_phase_id = $nextPhase?->id;
            $procurement->returned_from_phase_id = null; // Clear fast-track memory after jumping
            $procurement->status = $nextPhase ? 'ROUTED' : 'COMPLETED';
            $procurement->save();

            // 3. Close open routes & Create new route track
            ProcurementRoute::where('procurement_id', $procurement->id)
                ->whereNull('acted_at')
                ->update(['acted_at' => now()]);

            $actorOffice = $actor->offices()->first();

            ProcurementRoute::create([
                'procurement_id' => $procurement->id,
                'from_office_id' => $actorOffice ? $actorOffice->id : $procurement->end_user_office_id,
                'to_office_id' => $destinationOfficeId,
                'from_user_id' => $actor->id,
                'to_user_id' => $targetUser?->id,
                'action_taken' => $actionLabel,
                'remarks' => $remarks,
                'received_at' => now(),
            ]);

            // 4. Record Immutable Audit History
            ProcurementHistory::create([
                'procurement_id' => $procurement->id,
                'phase_id' => $currentPhase?->id,
                'user_id' => $actor->id,
                'action' => 'PHASE_TRANSITION',
                'status_from' => $previousStatus,
                'status_to' => $procurement->status,
                'remarks' => $remarks ?? "Advanced to phase {$nextPhase?->phase_name}",
                'created_at' => now(),
            ]);

            return $procurement;
        });
    }

    /**
     * Returns procurement to an earlier phase while remembering current origin phase.
     */
    // app/Services/ProcurementWorkflowEngine.php

    public function returnProcurement(
        Procurement $procurement,
        User $actor,
        int $targetPhaseId,
        string $reason
    ): Procurement {
        $firstPhaseSeq = $procurement->mode?->phases?->min('phase_sequence') ?? 1;

        // Backend Guard: Prevent returning if currently in Phase 1
        if ($procurement->currentPhase && $procurement->currentPhase->phase_sequence == $firstPhaseSeq) {
            throw new ProcurementWorkflowException("Procurements in Phase 1 cannot be returned.");
        }

        return DB::transaction(function () use ($procurement, $actor, $targetPhaseId, $reason) {
            $previousStatus = $procurement->status;

            $procurement->returned_from_phase_id = $procurement->current_phase_id;
            $procurement->current_phase_id = $targetPhaseId;
            $procurement->status = 'RETURNED';
            $procurement->save();

            $targetPhase = $procurement->mode->phases()->find($targetPhaseId);
            $actorOffice = $actor->offices()->first();

            ProcurementRoute::where('procurement_id', $procurement->id)
                ->whereNull('acted_at')
                ->update(['acted_at' => now()]);

            ProcurementRoute::create([
                'procurement_id' => $procurement->id,
                'from_office_id' => $actorOffice ? $actorOffice->id : $procurement->end_user_office_id,
                'to_office_id' => $procurement->end_user_office_id,
                'from_user_id' => $actor->id,
                'action_taken' => "RETURNED TO: {$targetPhase?->phase_name}",
                'remarks' => $reason,
                'received_at' => now(),
            ]);

            ProcurementHistory::create([
                'procurement_id' => $procurement->id,
                'phase_id' => $targetPhaseId,
                'user_id' => $actor->id,
                'action' => 'RETURNED_TO_PHASE',
                'status_from' => $previousStatus,
                'status_to' => 'RETURNED',
                'remarks' => "Returned to [{$targetPhase?->phase_name}]. Reason: {$reason}",
                'created_at' => now(),
            ]);

            return $procurement;
        });
    }

    /**
     * Finalizes and completes the procurement request.
     */
    public function completeProcurement(
        Procurement $procurement,
        User $actor,
        ?string $remarks = null
    ): Procurement {
        if (in_array($procurement->status, ['COMPLETED', 'CANCELLED'])) {
            throw new ProcurementWorkflowException("Procurement is already in a terminal state.");
        }

        return DB::transaction(function () use ($procurement, $actor, $remarks) {
            $previousStatus = $procurement->status;

            $procurement->status = 'COMPLETED';
            $procurement->save();

            ProcurementRoute::where('procurement_id', $procurement->id)
                ->whereNull('acted_at')
                ->update(['acted_at' => now()]);

            $actorOffice = $actor->offices()->first();

            ProcurementRoute::create([
                'procurement_id' => $procurement->id,
                'from_office_id' => $actorOffice ? $actorOffice->id : $procurement->end_user_office_id,
                'to_office_id' => $procurement->end_user_office_id,
                'from_user_id' => $actor->id,
                'action_taken' => 'COMPLETED',
                'remarks' => $remarks ?? 'Procurement workflow marked as completed.',
                'received_at' => now(),
            ]);

            ProcurementHistory::create([
                'procurement_id' => $procurement->id,
                'phase_id' => $procurement->current_phase_id,
                'user_id' => $actor->id,
                'action' => 'COMPLETED',
                'status_from' => $previousStatus,
                'status_to' => 'COMPLETED',
                'remarks' => $remarks ?? 'Procurement successfully completed.',
                'created_at' => now(),
            ]);

            return $procurement;
        });
    }

    /**
     * Cancels the procurement request.
     */
    public function cancelProcurement(
        Procurement $procurement,
        User $actor,
        string $reason
    ): Procurement {
        if (in_array($procurement->status, ['COMPLETED', 'CANCELLED'])) {
            throw new ProcurementWorkflowException("Cannot cancel a procurement that is already terminal.");
        }

        return DB::transaction(function () use ($procurement, $actor, $reason) {
            $previousStatus = $procurement->status;

            $procurement->status = 'CANCELLED';
            $procurement->save();

            ProcurementRoute::where('procurement_id', $procurement->id)
                ->whereNull('acted_at')
                ->update(['acted_at' => now()]);

            $actorOffice = $actor->offices()->first();

            ProcurementRoute::create([
                'procurement_id' => $procurement->id,
                'from_office_id' => $actorOffice ? $actorOffice->id : $procurement->end_user_office_id,
                'to_office_id' => $procurement->end_user_office_id,
                'from_user_id' => $actor->id,
                'action_taken' => 'CANCELLED',
                'remarks' => $reason,
                'received_at' => now(),
            ]);

            ProcurementHistory::create([
                'procurement_id' => $procurement->id,
                'phase_id' => $procurement->current_phase_id,
                'user_id' => $actor->id,
                'action' => 'CANCELLED',
                'status_from' => $previousStatus,
                'status_to' => 'CANCELLED',
                'remarks' => "Cancelled. Reason: {$reason}",
                'created_at' => now(),
            ]);

            return $procurement;
        });
    }

    /**
     * Rejects the procurement request.
     */
    public function rejectProcurement(
        Procurement $procurement,
        User $actor,
        string $reason
    ): Procurement {
        if (in_array($procurement->status, ['COMPLETED', 'CANCELLED', 'REJECTED'])) {
            throw new ProcurementWorkflowException("Cannot reject a procurement that is already terminal.");
        }

        return DB::transaction(function () use ($procurement, $actor, $reason) {
            $previousStatus = $procurement->status;

            $procurement->status = 'REJECTED';
            $procurement->save();

            ProcurementRoute::where('procurement_id', $procurement->id)
                ->whereNull('acted_at')
                ->update(['acted_at' => now()]);

            $actorOffice = $actor->offices()->first();

            ProcurementRoute::create([
                'procurement_id' => $procurement->id,
                'from_office_id' => $actorOffice ? $actorOffice->id : $procurement->end_user_office_id,
                'to_office_id' => $procurement->end_user_office_id,
                'from_user_id' => $actor->id,
                'action_taken' => 'REJECTED',
                'remarks' => $reason,
                'received_at' => now(),
            ]);

            ProcurementHistory::create([
                'procurement_id' => $procurement->id,
                'phase_id' => $procurement->current_phase_id,
                'user_id' => $actor->id,
                'action' => 'REJECTED',
                'status_from' => $previousStatus,
                'status_to' => 'CANCELLED',
                'remarks' => "Rejected. Reason: {$reason}",
                'created_at' => now(),
            ]);

            return $procurement;
        });
    }
}

<?php

namespace App\Services;

use App\Models\Procurement\{Procurement, ProcurementAttachment, ProcurementAttachmentSignatory, ProcurementSignatory};
use App\Models\ProcurementMode\{Attachment, ProcurementModePhase};
use App\Models\Role;
use App\Models\User;
use App\Notifications\ProcurementEmailNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcurementEmailNotificationService
{
    public function procurementChanged(int $procurementId, ?string $fromStatus, ?string $toStatus, bool $phaseChanged = false): void
    {
        $procurement = Procurement::query()->with(['creator', 'mode'])->find($procurementId);
        if (!$procurement) return;

        $recipients = collect();
        if ($procurement->creator && filled($procurement->creator->email)) {
            $recipients->put($procurement->creator->id, $procurement->creator);
        }

        $phase = $procurement->current_phase_id
            ? ProcurementModePhase::query()->with('roles')->find($procurement->current_phase_id)
            : null;
        $roleIds = $phase?->roles
            ->filter(fn($role) => (bool)($role->pivot?->can_act ?? true))
            ->pluck('id')
            ->map(fn($id) => (int)$id)
            ->all() ?? [];

        if ($roleIds !== []) {
            User::query()
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereHas('roles', fn($query) => $query->whereIn('roles.id', $roleIds))
                ->get()
                ->each(fn(User $user) => $recipients->put($user->id, $user));
        }

        if ($recipients->isEmpty()) return;

        $lines = ['Purchase Request '.($procurement->pr_number ?: '#'.$procurement->id).' has been updated.'];
        $statusDetail = null;
        if ($fromStatus !== $toStatus) {
            $statusDetail = ($fromStatus ? Str::headline(Str::lower($fromStatus)).' → ' : '').Str::headline(Str::lower((string)$procurement->status));
        } elseif ($toStatus) {
            $statusDetail = Str::headline(Str::lower($toStatus));
        }
        $details = $this->procurementDetails($procurement, $phase);
        if ($phaseChanged && $phase) {
            foreach ($details as &$detail) {
                if ($detail['label'] === 'Phase') $detail['label'] = 'Moved to phase';
            }
            unset($detail);
        }
        if ($statusDetail) array_splice($details, 1, 0, [[
            'label' => 'Status',
            'value' => $statusDetail,
        ]]);

        $this->send(
            $recipients->values(),
            new ProcurementEmailNotification(
                'Purchase Request status updated: '.($procurement->pr_number ?: '#'.$procurement->id),
                $lines,
                route('procurements.show', $procurement),
                'Open purchase request',
                'procurement',
                $details,
            ),
            $procurement,
        );
    }

    public function nextSignatureTurn(Procurement $procurement, string $attachmentId): void
    {
        $procurement->loadMissing(['creator', 'mode']);

        if ($attachmentId === 'pr') {
            $submission = ProcurementAttachment::query()
                ->where('procurement_id', $procurement->id)
                ->whereNull('attachment_id')
                ->where('workflow_status', 'ROUTING')
                ->whereNotNull('file_path')
                ->first();
            if (!$submission) return;

            $pending = ProcurementSignatory::query()
                ->with('configuredRole')
                ->where('procurement_id', $procurement->id)
                ->where('is_signed', false)
                ->orderBy('sequence')
                ->first();
            if (!$pending?->configuredRole) return;

            $this->notifyRoleTurn(
                $procurement,
                $pending->configuredRole,
                'Purchase Request',
                null,
                (int)$pending->sequence,
                ProcurementSignatory::query()->where('procurement_id', $procurement->id)->count(),
            );
            return;
        }

        $attachment = Attachment::query()
            ->with(['phase', 'signatories.role'])
            ->find((int)$attachmentId);
        $submission = ProcurementAttachment::query()
            ->where('procurement_id', $procurement->id)
            ->where('attachment_id', (int)$attachmentId)
            ->where('workflow_status', 'ROUTING')
            ->whereNotNull('file_path')
            ->first();
        if (!$attachment || !$submission) return;

        $signedSlots = ProcurementAttachmentSignatory::query()
            ->where('procurement_attachments_id', $submission->id)
            ->where('is_signed', true)
            ->get(['sequence', 'configured_role_id']);
        $configured = $attachment->signatories
            ->sortBy(fn($step) => (int)($step->sequence ?? $step->pivot?->sequence ?? $step->order ?? $step->id))
            ->values();

        foreach ($configured as $index => $step) {
            $sequence = (int)($step->sequence ?? $step->pivot?->sequence ?? $step->order ?? ($index + 1));
            $role = $step->role;
            $roleId = (int)($step->role_id ?? $role?->id ?? 0);
            $alreadySigned = $signedSlots->contains(fn($slot) =>
                (int)$slot->sequence === $sequence || ($roleId > 0 && (int)$slot->configured_role_id === $roleId)
            );
            if ($alreadySigned || !$role) continue;

            $this->notifyRoleTurn($procurement, $role, $attachment->name, $attachment->phase, $sequence, $configured->count());
            return;
        }
    }

    private function notifyRoleTurn(
        Procurement $procurement,
        Role $role,
        string $attachmentName,
        ?ProcurementModePhase $phase,
        int $sequence,
        int $totalSteps,
    ): void {
        $isEndUser = Str::of((string)$role->name)->lower()->replace([' ', '-'], '_')->toString() === 'end_user';
        if ($isEndUser) {
            $creator = $procurement->creator;
            $recipients = $creator && filled($creator->email)
                && $creator->roles()->where('roles.id', $role->id)->exists()
                    ? collect([$creator])
                    : collect();
        } else {
            $recipients = $role->users()
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get()
                ->keyBy('id')
                ->values();
        }

        if ($recipients->isEmpty()) return;

        $prNumber = $procurement->pr_number ?: '#'.$procurement->id;
        $lines = [
            'Your turn to sign Purchase Request '.$prNumber.' has arrived.',
        ];

        $this->send(
            $recipients,
            new ProcurementEmailNotification(
                'Signature required: '.$prNumber.' — '.$attachmentName,
                $lines,
                route('procurements.show', $procurement),
                'Open and sign',
                'signing',
                $this->procurementDetails($procurement, $phase, [
                    ['label' => 'Document', 'value' => $attachmentName],
                ], $sequence, $totalSteps),
            ),
            $procurement,
        );
    }

    public function signatureCompleted(Procurement $procurement, string $attachmentName, int $signerId): void
    {
        $procurement->loadMissing(['creator', 'mode']);
        $recipients = collect();
        $creator = $procurement->creator && filled($procurement->creator->email)
            ? $procurement->creator
            : null;
        if ($creator) $recipients->put($creator->id, $creator);

        $phase = $procurement->current_phase_id
            ? ProcurementModePhase::query()->with('roles')->find($procurement->current_phase_id)
            : null;
        $roleIds = $phase?->roles
            ->filter(fn ($role) => (bool) ($role->pivot?->can_act ?? true))
            ->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
        if ($roleIds !== []) {
            User::query()->whereNotNull('email')->where('email', '!=', '')
                ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roleIds))
                ->get()->each(fn (User $user) => $recipients->put($user->id, $user));
        }

        // The creator still needs the audit/update email when they are also the signer.
        // Exclude the signer only from the wider current-phase role audience.
        if (!$creator || (int) $creator->id !== $signerId) {
            $recipients->forget($signerId);
        }
        if ($recipients->isEmpty()) return;

        $prNumber = $procurement->pr_number ?: '#'.$procurement->id;
        $this->send(
            $recipients->values(),
            new ProcurementEmailNotification(
                'Document signed: '.$prNumber.' — '.$attachmentName,
                ['A required document for Purchase Request '.$prNumber.' has been signed.'],
                route('procurements.show', $procurement),
                'View purchase request',
                'signing',
                $this->procurementDetails($procurement, null, [
                    ['label' => 'Document signed', 'value' => $attachmentName],
                ]),
            ),
            $procurement,
        );
    }

    /** @param Collection<int, User> $recipients */
    private function send(Collection $recipients, ProcurementEmailNotification $notification, Procurement $procurement): void
    {
        foreach ($recipients as $recipient) {
            try {
                $recipient->notify($notification);
            } catch (Throwable $exception) {
                Log::error('Unable to send procurement email notification.', [
                    'procurement_id' => $procurement->id,
                    'recipient_id' => $recipient->id,
                    'notification_type' => $notification::class,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }

    /** @param list<array{label: string, value: string}> $additional
     *  @return list<array{label: string, value: string}>
     */
    private function procurementDetails(Procurement $procurement, ?ProcurementModePhase $phase, array $additional = [], ?int $signingSequence = null, ?int $totalSigningSteps = null): array
    {
        return [
            ['label' => 'Procurement title / purpose', 'value' => $procurement->purpose ?: ($procurement->pr_number ?: '#'.$procurement->id)],
            ['label' => 'Phase', 'value' => $phase?->phase_name ?: '—'],
            ['label' => 'Signing Step', 'value' => $this->signingStepLabel($procurement, $signingSequence, $totalSigningSteps)],
            ['label' => 'Overall Progress', 'value' => $this->overallProgress($procurement).'%'],
            ...$additional,
        ];
    }

    private function signingStepLabel(Procurement $procurement, ?int $sequence = null, ?int $totalSteps = null): string
    {
        if ($sequence !== null) {
            $total = $totalSteps ?? ProcurementSignatory::query()->where('procurement_id', $procurement->id)->count();
            return $total ? $sequence.' of '.$total : (string) $sequence;
        }

        $prSubmission = ProcurementAttachment::query()
            ->where('procurement_id', $procurement->id)
            ->whereNull('attachment_id')
            ->where('workflow_status', 'ROUTING')
            ->first();
        if ($prSubmission) {
            $pending = ProcurementSignatory::query()
                ->where('procurement_id', $procurement->id)
                ->where('is_signed', false)
                ->orderBy('sequence')
                ->first();
            if ($pending) {
                $total = ProcurementSignatory::query()->where('procurement_id', $procurement->id)->count();
                return $pending->sequence.' of '.$total;
            }
        }

        $attachmentIds = null;
        if ($procurement->current_phase_id) {
            $phase = ProcurementModePhase::query()->with('attachments')
                ->find($procurement->current_phase_id);
            $attachmentIds = $phase?->attachments->pluck('id')->all() ?? [];
        }
        if ($attachmentIds) {
            $submission = ProcurementAttachment::query()
                ->with('signatories')
                ->where('procurement_id', $procurement->id)
                ->whereIn('attachment_id', $attachmentIds)
                ->where('workflow_status', 'ROUTING')
                ->orderBy('id')
                ->get()
                ->first(fn ($record) => $record->signatories->contains(fn ($slot) => !$slot->is_signed));
            $pending = $submission?->signatories->first(fn ($slot) => !$slot->is_signed);
            if ($pending) {
                return $pending->sequence.' of '.$submission->signatories->count();
            }
        }

        return '—';
    }

    private function overallProgress(Procurement $procurement): int
    {
        $mode = $procurement->mode;
        if (!$mode) return 0;

        $phases = ProcurementModePhase::query()
            ->with('attachments.signatories')
            ->where('procurement_mode_id', $mode->id)
            ->get();
        $submissions = ProcurementAttachment::query()
            ->where('procurement_id', $procurement->id)
            ->whereNotNull('attachment_id')
            ->get()
            ->keyBy('attachment_id');
        $required = 0;
        $ready = 0;

        foreach ($phases as $phase) {
            foreach ($phase->attachments as $attachment) {
                $isRequired = true;
                foreach (['is_mandatory', 'is_required', 'mandatory', 'required'] as $key) {
                    if (array_key_exists($key, $attachment->getAttributes())) {
                        $isRequired = (bool) $attachment->getAttribute($key);
                        break;
                    }
                }
                if (!$isRequired) continue;

                $required++;
                $submission = $submissions->get($attachment->id);
                if ($submission && ($submission->workflow_status === 'COMPLETED'
                    || ($attachment->signatories->isEmpty() && filled($submission->file_path)))) {
                    $ready++;
                }
            }
        }

        return $required ? (int) round(($ready / $required) * 100) : 100;
    }
}

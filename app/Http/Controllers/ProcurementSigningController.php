<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Procurement\{Procurement, ProcurementAttachmentSignatory, ProcurementAttachment, ProcurementHistory, ProcurementSignatory};
use App\Models\ProcurementMode\ProcurementMode;
use App\Models\ProcurementMode\Form;
use App\Models\Setting;
use App\Services\{DigiSignClient, DigiSignException};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\{Collection, Str};

class ProcurementSigningController extends Controller
{
    private const CLOSED = ['ARCHIVED', 'CANCELLED', 'COMPLETED', 'REJECTED'];
    private const END_USER_ROLE = 'end_user';

    /** GET: should the Sign button (and password box) be shown for this attachment? */
    public function status(Request $request, Procurement $procurement, string $attachmentId, DigiSignClient $digisign): JsonResponse
    {
        $user = $request->user();
        $out = ['ok' => true, 'can_sign' => false, 'has_turn' => false, 'password_required' => false, 'workflow_status' => null, 'signed_count' => 0, 'signatory_count' => 0, 'message' => ''];

        $turn = $this->turn($procurement, $attachmentId, $user);
        if ($turn['silent']) return response()->json($out);              // nothing to sign / not applicable
        $out['workflow_status'] = $turn['attachment']?->workflow_status;
        $out['signed_count'] = $turn['signed_count'];
        $out['signatory_count'] = $turn['total'];
        if (! $turn['allowed']) return response()->json([...$out, 'message' => $turn['message']]);
        $out['has_turn'] = true;
        $out['role_name'] = $turn['role_name'];

        try {
            $status = $digisign->digicheck($user);
        } catch (DigiSignException $e) {
            return response()->json([...$out, 'message' => $e->getMessage()]);
        }
        if (! $status['ok']) return response()->json([...$out, 'message' => $status['message']]);

        return response()->json([...$out, 'can_sign' => true, 'password_required' => $status['password_required'], 'role_name' => $turn['role_name']]);
    }

    /** POST: send the stored PDF + signature box to DigiSign; replace the file and update the records. */
    public function sign(Request $request, Procurement $procurement, string $attachmentId, DigiSignClient $digisign): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'page' => ['required', 'integer', 'min:1'],
            'left' => ['required', 'numeric', 'min:0'],
            'top' => ['required', 'numeric', 'min:0'],
            'width' => ['required', 'numeric', 'gt:0'],
            'height' => ['required', 'numeric', 'gt:0'],
            'page_width' => ['required', 'numeric', 'gt:0'],
            'page_height' => ['required', 'numeric', 'gt:0'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);
        if ($data['left'] + $data['width'] > $data['page_width'] + 1 || $data['top'] + $data['height'] > $data['page_height'] + 1) {
            return $this->fail('The signature box must be inside the page.', 422);
        }

        $turn = $this->turn($procurement, $attachmentId, $user);
        if (! $turn['allowed']) return $this->fail($turn['message'] ?: 'You cannot sign this document.', 403);
        $procurementAttachment = $turn['attachment'];
        $pending = $turn['pending'];
        $sequence = $turn['sequence'];

        $disk = Storage::disk('procurements');
        if (! $disk->exists($procurementAttachment->file_path)) return $this->fail('The attached PDF could not be found on the server.', 404);

        // PDF user space has its origin at the bottom-left (configurable if DigiSign expects top-left).
        $bottomOrigin = config('services.digisign.y_origin', 'bottom') !== 'top';
        $x1 = round($data['left'], 2);
        $x2 = round($data['left'] + $data['width'], 2);
        $y1 = round($bottomOrigin ? $data['page_height'] - ($data['top'] + $data['height']) : $data['top'], 2);
        $y2 = round($bottomOrigin ? $data['page_height'] - $data['top'] : $data['top'] + $data['height'], 2);
        $fields = [[
            'field' => "Signature_{$sequence}",
            'page' => (int) $data['page'],
            'x1' => $x1,
            'y1' => $y1,
            'x2' => $x2,
            'y2' => $y2,
            'name' => $user->name,
        ]];

        try {
            $status = $digisign->digicheck($user);
            if (! $status['ok']) return $this->fail($status['message'], 422);
            if ($status['password_required'] && blank($data['password'] ?? null)) return $this->fail('Enter your P12 password to sign.', 422);

            $result = $digisign->digisign(
                $user,
                $disk->get($procurementAttachment->file_path),
                $procurementAttachment->file_name ?: 'document.pdf',
                $fields,
                $status['password_required'] ? $data['password'] : null
            );
        } catch (DigiSignException $e) {
            return $this->fail($e->getMessage(), $e->httpStatus());
        }

        $originalPath = $procurementAttachment->file_path;
        $newPath = "procurements/{$procurement->id}/attachments/" . Str::uuid() . '.pdf';
        $disk->put($newPath, $result['bytes']);

        $allSigned = false;
        try {
            $fresh = DB::transaction(function () use ($procurementAttachment, $originalPath, $newPath, $result, $user, $procurement, $sequence, $turn, $attachmentId, &$allSigned) {
                $locked = ProcurementAttachment::whereKey($procurementAttachment->id)->lockForUpdate()->firstOrFail();
                if ($attachmentId === 'pr') {
                    $row = ProcurementSignatory::where('procurement_id', $procurement->id)->where('sequence', $sequence)->lockForUpdate()->firstOrFail();
                    abort_if($locked->file_path !== $originalPath || $row->is_signed, 409, 'This document changed while it was being signed. Reload and try again.');
                    abort_if((int)$row->configured_role_id !== (int)($turn['pending']?->role_id ?? $turn['pending']?->role?->id), 409, 'The configured signatory changed. Reload and try again.');
                    $row->update(['signatory_id' => $user->id, 'is_signed' => true, 'signed_at' => now()]);
                    $allSigned = !ProcurementSignatory::where('procurement_id', $procurement->id)->where('is_signed', false)->exists();
                    $locked->forceFill(['file_path' => $newPath, 'file_size' => strlen($result['bytes']), 'mime_type' => 'application/pdf', 'workflow_status' => $allSigned ? 'COMPLETED' : 'ROUTING'])->save();
                    $status = strtoupper((string)$procurement->status);
                    ProcurementHistory::create(['procurement_id' => $procurement->id, 'user_id' => $user->id, 'phase_id' => null, 'action' => 'PR_FORM_SIGNED', 'status_from' => $status, 'status_to' => $status, 'remarks' => "Signed the Purchase Request as {$turn['role_name']} via DigiSign.", 'created_at' => now()]);
                    return $locked->fresh();
                }
                $row = ProcurementAttachmentSignatory::firstOrNew(['procurement_attachments_id' => $locked->id, 'sequence' => $sequence]);
                // The file changed or this slot was signed while DigiSign was working: don't clobber it.
                abort_if($locked->file_path !== $originalPath || $row->is_signed, 409, 'This document changed while it was being signed. Reload and try again.');
                $configuredRoleId = $turn['pending']?->role_id ?? $turn['pending']?->role?->id;
                abort_if(!$configuredRoleId, 409, 'The configured signatory role could not be found. Reload and try again.');
                $roleAlreadySigned = ProcurementAttachmentSignatory::where('procurement_attachments_id', $locked->id)
                    ->where('configured_role_id', $configuredRoleId)
                    ->where('is_signed', true)
                    ->exists();
                abort_if($roleAlreadySigned, 409, 'This role has already signed the document. Reload to see the next signatory.');

                $row->fill([
                    'configured_role_id' => $configuredRoleId,
                    'signatory_id' => $user->id,
                    'is_signed' => true,
                    'signed_at' => now(),
                ])->save();

                $signedSlots = ProcurementAttachmentSignatory::where('procurement_attachments_id', $locked->id)
                    ->where('is_signed', true)
                    ->get(['sequence', 'configured_role_id']);
                $allSigned = $turn['configured']->every(function ($step, $index) use ($signedSlots) {
                    $sequence = (int) ($step->sequence ?? $step->pivot?->sequence ?? $step->order ?? ($index + 1));
                    $roleId = (int) ($step->role_id ?? $step->role?->id ?? 0);

                    return $signedSlots->contains(fn($slot) =>
                        (int) $slot->sequence === $sequence || ($roleId && (int) $slot->configured_role_id === $roleId)
                    );
                });

                $locked->forceFill([
                    'file_path' => $newPath,
                    'file_size' => strlen($result['bytes']),
                    'mime_type' => 'application/pdf',
                    'workflow_status' => $allSigned ? 'COMPLETED' : 'ROUTING',
                ])->save();

                $status = strtoupper((string) $procurement->status);
                ProcurementHistory::create([
                    'procurement_id' => $procurement->id,
                    'user_id' => $user->id,
                    'phase_id' => $locked->phase_id,
                    'action' => 'ATTACHMENT_SIGNED',
                    'status_from' => $status,
                    'status_to' => $status,
                    'remarks' => "Signed \"{$locked->file_name}\" as {$turn['role_name']} via DigiSign.",
                    'created_at' => now(),
                ]);

                return $locked->fresh();
            });
        } catch (\Throwable $e) {
            $disk->delete($newPath);
            throw $e;
        }

        if ($originalPath !== $newPath && $disk->exists($originalPath)) $disk->delete($originalPath);

        app(\App\Services\ProcurementEmailNotificationService::class)->signatureCompleted(
            $procurement,
            $fresh->file_name ?: 'a procurement document',
            (int) $user->id,
        );

        if (!$allSigned) {
            app(\App\Services\ProcurementEmailNotificationService::class)->nextSignatureTurn($procurement, $attachmentId);
        }

        $signedCount = $attachmentId === 'pr'
            ? ProcurementSignatory::where('procurement_id', $procurement->id)->where('is_signed', true)->count()
            : ProcurementAttachmentSignatory::where('procurement_attachments_id', $fresh->id)->where('is_signed', true)->whereNotNull('configured_role_id')->distinct('configured_role_id')->count('configured_role_id');
        return response()->json([
            'ok' => true,
            'attachment_id' => $attachmentId,
            'is_ready' => true,
            'is_complete' => $allSigned,
            'workflow_status' => $fresh->workflow_status,
            'signed_count' => $signedCount,
            'signatory_count' => $turn['total'],
            'all_signed' => $allSigned,
            'file_name' => $fresh->file_name,
            'file_url' => route('procurements.attachments.file', ['procurement' => $procurement->id, 'attachmentId' => $attachmentId]),
            'message' => $allSigned
                ? 'Document signed. All signatories have signed.'
                : 'Document signed. The next signatory can now sign.',
        ]);
    }

    /**
     * Decide whose turn it is.
     *
     * Rules: the attachment must have configured signatories (phase attachment config, ordered by sequence);
     * the pending one is the first whose sequence has no signed record; the auth user must hold that role;
     * and when the role is end_user the auth user must also be the creator of the procurement.
     *
     * @return array{silent:bool,allowed:bool,message:string,attachment:?ProcurementAttachment,pending:mixed,sequence:int,total:int,role_name:string,configured:Collection}
     */
    private function turn(Procurement $procurement, string $attachmentId, User $user): array
    {
        $r = ['silent' => true, 'allowed' => false, 'message' => '', 'attachment' => null, 'pending' => null, 'sequence' => 0, 'total' => 0, 'signed_count' => 0, 'role_name' => '', 'configured' => collect()];

        if (in_array(strtoupper((string) $procurement->status), self::CLOSED, true)) return $r;

        $procurementAttachmentQuery = ProcurementAttachment::where('procurement_id', $procurement->id);
        if ($attachmentId === 'pr') {
            $configuredFormId = Setting::get('pr_form');
            $prForm = $configuredFormId
                ? Form::whereKey($configuredFormId)->where('status', 'published')->first()
                : null;
            $prForm ??= Form::where('code', 'PR')->where('status', 'published')->first();
            if (! $prForm) return $r;
            $procurementAttachmentQuery->whereNull('attachment_id')->where('form_id', $prForm->id);
        } else {
            $procurementAttachmentQuery->where('attachment_id', (int)$attachmentId);
        }
        $procurementAttachment = $procurementAttachmentQuery->first();
        if (! $procurementAttachment || ! filled($procurementAttachment->file_path)) return $r;

        if ($attachmentId === 'pr') {
            $configured = $procurement->signatories()->with('configuredRole')->orderBy('sequence')->get()->map(fn($step) => (object)[
                'sequence' => $step->sequence, 'role_id' => $step->configured_role_id, 'role' => $step->configuredRole,
            ]);
            if ($configured->isEmpty()) return $r;
            $signedRows = ProcurementSignatory::where('procurement_id', $procurement->id)->where('is_signed', true)->get();
            $r['silent'] = false;
            $r['attachment'] = $procurementAttachment;
            $r['configured'] = $configured;
            $r['total'] = $configured->count();
            $r['signed_count'] = $signedRows->count();
            if ($procurementAttachment->workflow_status !== 'ROUTING') return [...$r, 'message' => $procurementAttachment->workflow_status === 'COMPLETED' ? 'All signatories have signed this document.' : 'This document is ready and waiting to be routed.'];
            $pending = $configured->first(fn($step) => !$signedRows->contains(fn($signed) => (int)$signed->sequence === (int)$step->sequence));
            if (!$pending) return [...$r, 'message' => 'All signatories have signed this document.'];
            $role = $pending->role;
            $roleId = $pending->role_id;
            $r = [...$r, 'pending' => $pending, 'sequence' => $pending->sequence, 'role_name' => $role?->name ?? 'the next signatory'];
            if (!$user->isAdmin() && (!$roleId || !$user->roles()->where('roles.id', $roleId)->exists())) return [...$r, 'message' => "Waiting for {$r['role_name']} to sign."];
            if (!$user->isAdmin() && $this->isEndUserRole($role) && (int)$user->id !== (int)$procurement->created_by) return [...$r, 'message' => 'Only the creator may sign as the End User.'];
            return [...$r, 'allowed' => true];
        }

        $attachmentId = (int)$attachmentId;
        $mode = ProcurementMode::with(['phases.roles', 'phases.attachments.signatories.role'])->findOrFail($procurement->procurement_mode_id);
        $config = $mode->phases->flatMap(fn($phase) => $phase->attachments)->first(fn($a) => (int) $a->id === $attachmentId);
        if (! $config) return $r;
        /** @var Collection $configured */
        $configured = ($config->signatories ?? collect())
            ->sortBy(fn($s) => $s->sequence ?? $s->pivot?->sequence ?? $s->order ?? $s->id)->values();
        if ($configured->isEmpty()) return $r;                            // attachment has no configured signatories

        $seqOf = fn($s, int $i) => (int) ($s->sequence ?? $s->pivot?->sequence ?? $s->order ?? ($i + 1));
        $signed = ProcurementAttachmentSignatory::where('procurement_attachments_id', $procurementAttachment->id)->where('is_signed', true)->pluck('sequence')->map(fn($v) => (int) $v)->all();
        $signedRoleIds = ProcurementAttachmentSignatory::where('procurement_attachments_id', $procurementAttachment->id)
            ->where('is_signed', true)
            ->whereNotNull('configured_role_id')
            ->pluck('configured_role_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $r['silent'] = false;
        $r['attachment'] = $procurementAttachment;
        $r['configured'] = $configured;
        $stepKeys = $configured->map(fn($step, $i) => $step->role_id ? 'role:' . $step->role_id : 'sequence:' . $seqOf($step, $i))->unique();
        $r['total'] = $stepKeys->count();
        $r['signed_count'] = $stepKeys->filter(function ($key) use ($signed, $signedRoleIds) {
            [$kind, $id] = explode(':', $key, 2);
            return $kind === 'role' ? in_array((int)$id, $signedRoleIds, true) : in_array((int)$id, $signed, true);
        })->count();
        if ($procurementAttachment->workflow_status !== 'ROUTING') return [...$r, 'message' => $procurementAttachment->workflow_status === 'COMPLETED' ? 'All signatories have signed this document.' : 'This document is ready and waiting to be routed.'];

        $pending = null;
        $sequence = 0;
        foreach ($configured as $i => $signatory) {
            $roleId = (int) ($signatory->role_id ?? $signatory->role?->id ?? 0);
            if (! in_array($seqOf($signatory, $i), $signed, true) && ! ($roleId && in_array($roleId, $signedRoleIds, true))) {
                $pending = $signatory;
                $sequence = $seqOf($signatory, $i);
                break;
            }
        }
        if (! $pending) return [...$r, 'message' => 'All signatories have signed this document.'];

        $role = $pending->role;
        $roleName = $role?->name ?? 'the next signatory';
        $roleId = $role?->id ?? $pending->role_id ?? null;
        $r = [...$r, 'pending' => $pending, 'sequence' => $sequence, 'role_name' => $roleName];

        if (! $user->isAdmin() && (! $roleId || ! $user->roles()->where('roles.id', $roleId)->exists())) {
            return [...$r, 'message' => "Waiting for {$roleName} to sign."];
        }
        if (! $user->isAdmin() && $this->isEndUserRole($role) && (int) $user->id !== (int) $procurement->created_by) {
            $creator = $procurement->creator?->name ?? 'the creator of this purchase request';
            return [...$r, 'message' => "Waiting for {$creator} ({$roleName}) to sign."];
        }

        return [...$r, 'allowed' => true];
    }

    private function isEndUserRole(mixed $role): bool
    {
        if (! $role) return false;
        return collect([$role->slug ?? null, $role->code ?? null, $role->name ?? null])
            ->contains(fn($v) => Str::of((string) $v)->lower()->replace([' ', '-'], '_')->toString() === self::END_USER_ROLE);
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $message], $status);
    }
}

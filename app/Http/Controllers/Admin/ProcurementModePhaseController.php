<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhaseRequest;
use App\Models\Procurement\ProcurementAttachment;
use App\Models\ProcurementMode\Attachment;
use App\Models\ProcurementMode\ProcurementMode;
use App\Models\ProcurementMode\ProcurementModePhase;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementModePhaseController extends Controller
{
    /**
     * Store a new phase along with its authorized roles, attachments, and signatories.
     */
    public function store(StorePhaseRequest $request, ProcurementMode $mode)
    {
        $validated = $request->validated();
        $attachments = $validated['attachments'] ?? [];
        $this->assertPrFormNotPhaseBound($attachments);
        DB::transaction(function () use ($mode, $validated, $attachments, &$phase) {
            $phase = $mode->phases()->create(collect($validated)->except(['role_ids', 'attachments'])->all());

            if (array_key_exists('role_ids', $validated)) {
                $phase->roles()->sync($validated['role_ids'] ?? []);
            }

            if ($attachments) {
                $this->syncAttachments($phase, $attachments);
            }
        });

        return back()->with('success', "Phase '{$phase->phase_name}' added to {$mode->code}.");
    }

    /**
     * Update an existing phase and re-sync its roles, attachments, and signatories.
     */
    public function update(StorePhaseRequest $request, ProcurementMode $mode, ProcurementModePhase $phase)
    {
        abort_unless((int) $phase->procurement_mode_id === (int) $mode->id, 404);
        $validated = $request->validated();
        $attachments = $validated['attachments'] ?? [];
        $this->assertPrFormNotPhaseBound($attachments);
        $existingIds = collect($attachments)->pluck('id')->filter()->map(fn ($id) => (int)$id)->values();
        if ($existingIds->unique()->count() !== $existingIds->count()) {
            throw ValidationException::withMessages(['attachments' => 'An attachment requirement was submitted more than once.']);
        }
        $phaseAttachmentIds = $phase->attachments()->pluck('id')->map(fn ($id) => (int)$id);
        abort_unless($existingIds->diff($phaseAttachmentIds)->isEmpty(), 404, 'An attachment requirement does not belong to this phase.');

        DB::transaction(function () use ($validated, $attachments, $phase) {
            $phase->update(collect($validated)->except(['role_ids', 'attachments'])->all());

            if (array_key_exists('role_ids', $validated)) {
                $phase->roles()->sync($validated['role_ids'] ?? []);
            }

            if (array_key_exists('attachments', $validated)) {
                $this->syncAttachments($phase, $attachments, true);
            }
        });

        return back()->with('success', "Phase '{$phase->phase_name}' updated successfully.");
    }

    /**
     * Remove a phase from the workflow sequence and re-index remaining steps.
     */
    public function destroy(ProcurementMode $mode, ProcurementModePhase $phase)
    {
        abort_unless((int) $phase->procurement_mode_id === (int) $mode->id, 404);
        DB::transaction(function () use ($mode, $phase) {
            $phaseName = $phase->phase_name;
            $phase->delete();

            // Re-index remaining sequences cleanly
            $mode->phases()->orderBy('phase_sequence')->get()->each(function ($p, $index) {
                $p->update(['phase_sequence' => $index + 1]);
            });
        });

        return back()->with('success', "Phase removed from workflow.");
    }

    /**
     * Reorder phase sequences via drag-and-drop AJAX payload.
     */
    public function reorder(Request $request, ProcurementMode $mode)
    {
        $request->validate([
            'phases' => ['required', 'array', 'min:1', 'max:100'],
            'phases.*.id' => ['required', 'exists:procurement_mode_phases,id'],
            'phases.*.sequence' => ['required', 'integer', 'min:1'],
        ]);

        $modePhaseIds = $mode->phases()->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(collect($request->input('phases'))->every(fn ($item) => in_array((int) $item['id'], $modePhaseIds, true)), 422, 'A phase does not belong to this procurement mode.');
        $submitted = collect($request->input('phases'));
        abort_unless($submitted->pluck('id')->map(fn ($id) => (int) $id)->unique()->count() === count($modePhaseIds), 422, 'Submit each phase exactly once.');
        abort_unless($submitted->pluck('sequence')->map(fn ($sequence) => (int) $sequence)->sort()->values()->all() === range(1, count($modePhaseIds)), 422, 'Phase positions must be sequential and unique.');

        DB::transaction(function () use ($request, $modePhaseIds) {
            $phases = $request->input('phases');
            abort_unless(count($phases) === count($modePhaseIds), 422, 'Submit the full phase sequence.');

            // Step 1: Shift sequences to temporary negative values to avoid UNIQUE constraint collisions
            foreach ($phases as $item) {
                ProcurementModePhase::where('id', $item['id'])->update([
                    'phase_sequence' => -1 * (int) $item['sequence'],
                ]);
            }

            // Step 2: Set final sequence numbers
            foreach ($phases as $item) {
                ProcurementModePhase::where('id', $item['id'])->update([
                    'phase_sequence' => (int) $item['sequence'],
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Phase sequence order updated successfully.',
        ]);
    }

    /**
     * Sync authorized roles independently.
     */
    public function syncRoles(Request $request, ProcurementMode $mode, ProcurementModePhase $phase)
    {
        abort_unless((int) $phase->procurement_mode_id === (int) $mode->id, 404);
        $request->validate([
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        $phase->roles()->sync($request->input('role_ids', []));

        return back()->with('success', "Authorized roles updated for phase '{$phase->phase_name}'.");
    }

    /**
     * Add an individual attachment rule directly to a phase.
     */
    public function storeDocumentRequirement(Request $request, ProcurementMode $mode, ProcurementModePhase $phase)
    {
        abort_unless((int) $phase->procurement_mode_id === (int) $mode->id, 404);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'form_id' => ['nullable', 'integer', 'exists:forms,id,status,published'],
            'is_mandatory' => ['nullable', 'boolean'],
            'signatories' => ['nullable', 'array', 'max:50'],
            'signatories.*.role_id' => ['required', 'integer', 'exists:roles,id'],
            'signatories.*.sequence' => ['required', 'integer', 'min:1', 'max:50'],
        ]);
        $this->assertPrFormNotPhaseBound([['form_id' => $request->input('form_id')]]);

        DB::transaction(function () use ($request, $phase) {
            $attachment = $phase->attachments()->create([
                'name' => $request->input('name'),
                'form_id' => $request->input('form_id'),
                'is_mandatory' => $request->boolean('is_mandatory', true),
                'is_active' => true,
            ]);

            if ($request->has('signatories')) {
                foreach ($request->input('signatories', []) as $sigData) {
                    $attachment->signatories()->create([
                        'role_id' => $sigData['role_id'],
                        'sequence' => $sigData['sequence'],
                    ]);
                }
            }
        });

        return back()->with('success', "Attachment requirement added to phase '{$phase->phase_name}'.");
    }

    private function assertPrFormNotPhaseBound(array $attachments): void
    {
        $prFormId = (int)Setting::get('pr_form', 0);
        if (!$prFormId) return;
        foreach ($attachments as $attachment) {
            abort_if((int)($attachment['form_id'] ?? 0) === $prFormId, 422, 'The active Purchase Request form is assigned at the procurement level and cannot be attached to a phase.');
        }
    }

    /**
     * Remove an individual attachment requirement rule.
     */
    public function destroyDocumentRequirement(ProcurementMode $mode, ProcurementModePhase $phase, Attachment $document)
    {
        abort_unless((int) $phase->procurement_mode_id === (int) $mode->id && (int) $document->procurement_mode_phase_id === (int) $phase->id, 404);
        $document->delete();

        return back()->with('success', "Attachment requirement removed.");
    }

    /**
     * Helper to create attachments along with their signatory sequence chains.
     */
    private function syncAttachments(ProcurementModePhase $phase, array $attachmentsData, bool $allowExisting = false): void
    {
        $retainedIds = [];
        foreach ($attachmentsData as $attData) {
            if (empty($attData['name'])) {
                continue;
            }

            $attributes = [
                'name' => $attData['name'],
                'form_id' => !empty($attData['form_id']) ? $attData['form_id'] : null,
                'is_mandatory' => !empty($attData['is_mandatory']),
                'is_active' => true,
            ];
            $attachmentId = !empty($attData['id']) ? (int)$attData['id'] : null;
            if ($attachmentId && !$allowExisting) {
                throw ValidationException::withMessages(['attachments' => 'Existing requirement IDs cannot be supplied when creating a new phase.']);
            }

            if ($attachmentId) {
                $attachment = $phase->attachments()->whereKey($attachmentId)->firstOrFail();
                $retainedIds[] = $attachmentId;
                $signatories = collect($attData['signatories'] ?? [])->map(fn ($signatory) => [
                    'role_id' => (int)$signatory['role_id'],
                    'sequence' => (int)$signatory['sequence'],
                ])->sortBy('sequence')->values()->all();
                $existingSignatories = $attachment->signatories()->get(['role_id', 'sequence'])
                    ->map(fn ($signatory) => ['role_id' => (int)$signatory->role_id, 'sequence' => (int)$signatory->sequence])
                    ->sortBy('sequence')->values()->all();
                $hasProcurementHistory = ProcurementAttachment::query()->where('attachment_id', $attachmentId)->exists();
                $configurationChanged = $attachment->name !== $attributes['name']
                    || (int)($attachment->form_id ?? 0) !== (int)($attributes['form_id'] ?? 0)
                    || (bool)$attachment->is_mandatory !== (bool)$attributes['is_mandatory']
                    || $existingSignatories !== $signatories;
                if ($hasProcurementHistory && $configurationChanged) {
                    throw ValidationException::withMessages(['attachments' => "{$attachment->name} already has procurement history and its requirement cannot be changed."]);
                }
                if (!$hasProcurementHistory) {
                    $attachment->update($attributes);
                    $attachment->signatories()->delete();
                    $this->createAttachmentSignatories($attachment, $signatories);
                }
                continue;
            }

            $attachment = $phase->attachments()->create($attributes);
            $signatories = collect($attData['signatories'] ?? [])->map(fn ($signatory) => [
                'role_id' => (int)$signatory['role_id'],
                'sequence' => (int)$signatory['sequence'],
            ])->sortBy('sequence')->values()->all();
            $this->createAttachmentSignatories($attachment, $signatories);
        }

        if ($allowExisting) {
            $removed = $phase->attachments()->whereNotIn('id', $retainedIds)->get();
            $removedIds = $removed->pluck('id')->all();
            if ($removedIds && ProcurementAttachment::query()->whereIn('attachment_id', $removedIds)->exists()) {
                throw ValidationException::withMessages(['attachments' => 'A requirement with procurement history cannot be removed. Deactivate it or retain it to preserve its records.']);
            }
            foreach ($removed as $attachment) $attachment->delete();
        }
    }

    private function createAttachmentSignatories(Attachment $attachment, array $signatories): void
    {
        foreach ($signatories as $signatory) {
            $attachment->signatories()->create($signatory);
        }
    }
}

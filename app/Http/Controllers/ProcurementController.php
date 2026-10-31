<?php

namespace App\Http\Controllers;

use App\Http\Requests\{StoreProcurementRequest, UpdateProcurementRequest};
use App\Models\{FundCluster, Office, Setting, User};
use App\Models\Role;
use App\Models\Procurement\{ItemBankItem, Procurement, ProcurementAttachment, ProcurementHistory, ProcurementItem, ProcurementSignatory, RfqDocument};
use App\Models\ProcurementMode\{Form, ProcurementMode, ProcurementModePhase};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\ProcurementFormTemplateRenderer;
use App\Services\ProcurementFormPdfRenderer;
use Illuminate\Validation\ValidationException;

class ProcurementController extends Controller
{
    use AuthorizesRequests;

    /** Exact catalog-match lookup cache for repeated item descriptions in a single request. */
    private array $catalogItemMatchCache = [];

    public function index()
    {
        $request = request();
        $query = Procurement::with(['mode', 'endUserOffice'])->where('status', '!=', 'ARCHIVED');
        $this->applyDirectoryFilters($query, $request, false);
        $procurements = $query->latest()->paginate(15)->withQueryString();
        $modes = ProcurementMode::where('is_active', true)->orderBy('name')->get();
        $officeQuery = Office::where('is_active', true);
        if (! $request->user()->isAdmin() && ! $request->user()->isCentralRole()) {
            $officeQuery->whereIn('id', $request->user()->offices()->pluck('offices.id'));
        }
        $offices = $officeQuery->orderBy('name')->get();
        $statuses = ['DRAFT', 'ROUTING', 'RETURNED', 'REJECTED', 'COMPLETED', 'CANCELLED'];
        $user = $request->user();
        $canViewArchive = $this->hasPermission($user, 'procurement.archive') || $this->hasPermission($user, 'procurement.delete');
        $canArchiveRequests = $this->hasPermission($user, 'procurement.archive');

        return view('procurements.index', compact('procurements', 'modes', 'offices', 'statuses', 'canViewArchive', 'canArchiveRequests'));
    }

    public function create(Request $request, $selectedModeId = null)
    {
        abort_unless($this->hasPermission($request->user(), 'procurement.add') || $this->hasPermission($request->user(), 'procurement.create'), 403);
        $modes = ProcurementMode::where('is_active', true)->orderBy('name')->get();
        if ($modes->isEmpty()) return redirect()->route('procurements.index')->with('error', 'No active procurement modes configured.');
        $selectedModeId = $selectedModeId ?: $request->integer('procurement_mode_id') ?: $modes->first()->id;
        $selectedMode = ProcurementMode::with(['phases' => fn($q) => $q->orderBy('phase_sequence')->with('roles')])->findOrFail($selectedModeId);
        abort_unless((bool)$selectedMode->is_active, 404);
        $officeQuery = Office::where('is_active', true);
        if (!$request->user()->isAdmin() && !$request->user()->isCentralRole()) {
            $officeQuery->whereIn('id', $request->user()->offices()->where('offices.is_active', true)->pluck('offices.id'));
        }
        $offices = $officeQuery->orderBy('name')->get();
        $fundClusters = FundCluster::where('is_active', true)->orderBy('code')->get();
        $refererPath = parse_url((string)$request->headers->get('referer'), PHP_URL_PATH) ?: '';
        $returnTo = $request->query('return_to');
        if (!in_array($returnTo, ['dashboard', 'directory'], true)) $returnTo = str_ends_with(rtrim($refererPath, '/'), '/dashboard') ? 'dashboard' : 'directory';
        $returnUrl = $returnTo === 'dashboard' ? route('dashboard') : route('procurements.index');
        $phaseAccess = $selectedMode->phases->mapWithKeys(fn($phase) => [(string)$phase->id => $this->userCanActInPhase($request->user(), $phase)]);
        return view('procurements.create', compact('selectedMode', 'modes', 'offices', 'fundClusters', 'returnTo', 'returnUrl', 'phaseAccess'));
    }

    public function update(UpdateProcurementRequest $request, Procurement $procurement)
    {
        $validatedInput = $request->validated();
        abort_unless($this->canEditProcurement($request->user(), $procurement), 403);
        abort_unless(in_array(strtoupper((string)$procurement->status), ['DRAFT', 'RETURNED'], true), 403, 'Only draft or returned purchase requests can be edited.');
        if (array_key_exists('end_user_office_id', $validatedInput)) {
            $this->assertOfficeChangeInScope($request->user(), $validatedInput['end_user_office_id'], (int)$procurement->end_user_office_id);
        }
        if ($request->input('save_action', 'draft') === 'route') throw ValidationException::withMessages(['save_action' => 'Start routing from the Proceed Next Phase action after all required attachments are ready.']);
        $attachmentFields = $this->decodeArray($validatedInput['attachment_fields'] ?? null);
        $completionData = $this->decodeArray($validatedInput['attachment_completion_data'] ?? null);
        foreach ($attachmentFields as $fields) {
            if (! is_array($fields)) throw ValidationException::withMessages(['attachment_fields' => 'The form data has an invalid structure.']);
            $this->assertBoundedFormFields($fields);
        }
        if (count($completionData) > 1000) throw ValidationException::withMessages(['attachment_completion_data' => 'Too many completion values were submitted.']);
        $oldFiles = [];

        DB::transaction(function () use ($request, $procurement, $validatedInput, $attachmentFields, $completionData, &$oldFiles) {
            $procurement = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array(strtoupper((string)$procurement->status), ['DRAFT', 'RETURNED'], true), 403, 'Only draft or returned purchase requests can be edited.');
            $mode = ProcurementMode::with(['phases' => fn($query) => $query->orderBy('phase_sequence')->with(['roles', 'attachments.form', 'attachments.signatories'])])->findOrFail($validatedInput['procurement_mode_id']);
            abort_unless($mode->is_active || (int)$mode->id === (int)$procurement->procurement_mode_id, 422, 'Choose an active procurement mode.');
            if ((int)$mode->id !== (int)$procurement->procurement_mode_id && ProcurementAttachment::where('procurement_id', $procurement->id)->exists()) {
                throw ValidationException::withMessages(['procurement_mode_id' => 'The procurement mode cannot be changed after attachment records have been created.']);
            }
            $mainFields = $this->mainPurchaseRequestFields($mode, $attachmentFields);
            if (array_key_exists('end_user_office_id', $mainFields)) {
                $officeValue = is_array($mainFields['end_user_office_id']) ? reset($mainFields['end_user_office_id']) : $mainFields['end_user_office_id'];
                $this->assertOfficeChangeInScope($request->user(), $officeValue, (int)$procurement->end_user_office_id);
            }
            $rawItems = $validatedInput['items'] ?? [];
            $rawItems = is_array($rawItems) ? $rawItems : [];
            if (!$rawItems && $mainFields) $rawItems = $this->itemsFromFormFields($mainFields);
            $this->validateDraftItemPayload($rawItems);
            $changes = ['procurement_mode_id' => $mode->id];
            foreach (['purpose', 'fund_cluster', 'responsibility_code', 'pr_date', 'end_user_office_id'] as $field) {
                if (array_key_exists($field, $mainFields)) {
                    $value = is_array($mainFields[$field]) ? reset($mainFields[$field]) : $mainFields[$field];
                    if ($field === 'end_user_office_id') {
                        $officeId = Office::where('code', trim((string)$value))->value('id');
                        $changes[$field] = $officeId ?: (is_numeric($value) && Office::whereKey((int)$value)->exists() ? (int)$value : null);
                    } else {
                        $changes[$field] = $value !== '' ? $value : null;
                    }
                }
                elseif (array_key_exists($field, $validatedInput)) $changes[$field] = $validatedInput[$field];
            }
            $shouldSyncItems = array_key_exists('items', $validatedInput) || !empty($mainFields);
            if ($shouldSyncItems) {
                $changes['abc_amount'] = $this->syncProcurementItems($procurement, $rawItems);
            }
            $procurement->update($changes);
            $oldFiles = $this->syncAttachmentFiles($request, $procurement, $mode, $request->user(), $completionData);
        });
        foreach (array_unique($oldFiles) as $oldPath) if (Storage::disk('procurements')->exists($oldPath)) Storage::disk('procurements')->delete($oldPath);
        $message = "Purchase Request {$procurement->pr_number} updated successfully.";
        return redirect()->route('procurements.show', $procurement->id)->with('success', $message);
    }

    public function store(StoreProcurementRequest $request)
    {
        $data = $request->validated();
        $procurement = DB::transaction(function () use ($data, $request) {
            $mode = ProcurementMode::where('is_active', true)->findOrFail($data['procurement_mode_id']);
            $procurement = Procurement::create([
                'pr_number' => Setting::getNextPrNumberAndIncrement(),
                'purpose' => $data['purpose'],
                'fund_cluster' => $data['fund_cluster'],
                'responsibility_code' => $data['responsibility_code'] ?? null,
                'pr_date' => $data['pr_date'],
                'end_user_office_id' => $data['end_user_office_id'],
                'procurement_mode_id' => $mode->id,
                'current_phase_id' => null,
                'abc_amount' => 0,
                'status' => 'DRAFT',
                'created_by' => $request->user()->id,
            ]);
            ProcurementHistory::create(['procurement_id' => $procurement->id, 'user_id' => $request->user()->id, 'action' => 'DRAFT_CREATED', 'status_from' => '', 'status_to' => 'DRAFT', 'remarks' => 'Draft PR created.', 'created_at' => now()]);
            return $procurement;
        });
        return redirect()->route('procurements.show', $procurement)->with('success', 'Draft PR created. Please complete required attachments to start routing this PR.');
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) return $value;
        if (!is_string($value) || trim($value) === '') return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** Bound nested form data before using it for validation, persistence, or item synchronization. */
    private function assertBoundedFormFields(array $fields): void
    {
        if (count($fields) > 10000) {
            throw ValidationException::withMessages(['fields' => 'The form contains too many values.']);
        }

        $validateValue = function (mixed $value, int $depth = 0) use (&$validateValue): bool {
            if ($depth > 4) return false;
            if (is_array($value)) {
                if (count($value) > 1000) return false;
                foreach ($value as $entry) if (! $validateValue($entry, $depth + 1)) return false;
                return true;
            }
            if (is_string($value)) return mb_strlen($value) <= 10000;
            return $value === null || is_bool($value) || is_int($value) || is_float($value);
        };

        foreach ($fields as $key => $value) {
            if (! is_string($key) || mb_strlen($key) > 255 || ! $validateValue($value)) {
                throw ValidationException::withMessages(['fields' => 'The form contains an unsupported or oversized value.']);
            }
        }
    }

    private function numericValue(mixed $value): float
    {
        $raw = str_replace(',', '', trim((string)($value ?? '')));
        return is_numeric($raw) ? (float)$raw : 0.0;
    }

    private function nullableNumber(mixed $value): ?float
    {
        if ($value === null || trim((string)$value) === '') return null;
        $raw = str_replace(',', '', trim((string)$value));
        return is_numeric($raw) ? (float)$raw : null;
    }

    private function mainPurchaseRequestFields(ProcurementMode $mode, array $formData): array
    {
        $prForm = $this->resolvePurchaseRequestForm();
        if (!$prForm) return [];
        foreach ($mode->phases as $phase) foreach ($phase->attachments as $attachment) {
            if ((int)($attachment->form_id ?? $attachment->form?->id ?? 0) === (int)$prForm->id) return (array)($formData[(string)$attachment->id] ?? []);
        }
        return [];
    }

    /** Resolve the designated PR form, falling back to the published form whose code is PR. */
    private function resolvePurchaseRequestForm(): ?Form
    {
        $configuredFormId = Setting::where('key', 'pr_form')->value('value');
        $form = $configuredFormId
            ? Form::whereKey($configuredFormId)->where('status', 'published')->first()
            : null;

        return $form ?? Form::where('code', 'PR')->where('status', 'published')->first();
    }

    private function isPurchaseRequestFormId(?int $formId): bool
    {
        $prForm = $this->resolvePurchaseRequestForm();

        return $prForm && $formId && (int)$prForm->id === $formId;
    }

    private function itemsFromFormFields(array $fields): array
    {
        $rows = [];
        foreach ($fields as $key => $value) {
            if (preg_match('/^items\[(\d+)\]\[sub_items\]\[(\d+)\]\[([^\]]+)\]$/', (string)$key, $m)) $rows[(int)$m[1]]['sub_items'][(int)$m[2]][$m[3]] = is_array($value) ? reset($value) : $value;
            elseif (preg_match('/^items\[(\d+)\]\[([^\]]+)\]$/', (string)$key, $m)) $rows[(int)$m[1]][$m[2]] = is_array($value) ? reset($value) : $value;
        }
        ksort($rows);
        $result = [];
        foreach ($rows as $row) {
            if (!empty($row['is_lot'])) {
                $members = [];
                foreach (($row['sub_items'] ?? []) as $sub) $members[] = ['id' => $sub['id'] ?? null, 'item_bank_item_id' => $sub['item_bank_item_id'] ?? null, 'unit' => $sub['unit'] ?? '', 'description' => $sub['description'] ?? '', 'quantity' => $sub['quantity'] ?? null, 'cost' => $sub['cost'] ?? null];
                $result[] = ['id' => $row['id'] ?? null, 'is_lot' => 1, 'description' => $row['description'] ?? '', 'quantity' => $row['quantity'] ?? null, 'cost' => $row['cost'] ?? null, 'sub_items' => $members];
            } else $result[] = ['id' => $row['id'] ?? null, 'is_lot' => 0, 'item_bank_item_id' => $row['item_bank_item_id'] ?? null, 'unit' => $row['unit'] ?? '', 'description' => $row['description'] ?? '', 'quantity' => $row['quantity'] ?? null, 'cost' => $row['cost'] ?? null];
        }
        return $result;
    }

    private function syncAttachmentFiles(Request $request, Procurement $procurement, ProcurementMode $mode, User $user, array $completionData): array
    {
        $attachmentMap = [];
        foreach ($mode->phases as $phase) foreach ($phase->attachments as $attachment) $attachmentMap[(string)$attachment->id] = ['phase' => $phase, 'phase_id' => $phase->id, 'form_id' => $attachment->form_id ?? $attachment->form?->id, 'has_signatories' => $attachment->signatories->isNotEmpty()];
        $files = (array)$request->file('attachment_files', []);
        foreach (array_keys($files) as $id) if (!isset($attachmentMap[(string)$id])) throw ValidationException::withMessages(['attachment_files' => 'An uploaded file does not belong to an attachment in the selected procurement mode.']);
        $ids = array_unique(array_map('strval', array_merge(array_keys($completionData), array_keys($files))));
        $oldPaths = [];
        foreach ($ids as $id) {
            if (!isset($attachmentMap[$id])) continue;
            $hasCompletion = array_key_exists($id, $completionData);
            $file = $files[$id] ?? null;
            $formId = $attachmentMap[$id]['form_id'];
            if ($file && $formId) throw ValidationException::withMessages(['attachment_files' => 'Attachments with a bound form cannot accept a separate PDF upload. Open the form and use Compile PDF.']);
            $procurementAttachment = ProcurementAttachment::where('procurement_id', $procurement->id)->where('attachment_id', (int)$id)->lockForUpdate()->first();
            $procurementAttachment ??= new ProcurementAttachment(['procurement_id' => $procurement->id, 'attachment_id' => (int)$id]);
            if (!$hasCompletion && !$file && !$procurementAttachment->exists) continue;
            $nextCompletion = $hasCompletion ? filter_var($completionData[$id], FILTER_VALIDATE_BOOLEAN) : (bool)$procurementAttachment->is_completed;
            $completionChanged = $hasCompletion && $nextCompletion !== (bool)$procurementAttachment->is_completed;
            if (!$completionChanged && !$file) continue;
            abort_unless($this->userCanWorkOnPhase($user, $procurement, $attachmentMap[$id]['phase']), 403, 'You cannot modify attachments in this phase.');
            abort_unless(!in_array(strtoupper((string)$procurementAttachment->workflow_status), ['ROUTING', 'COMPLETED'], true), 422, 'A routed or completed attachment cannot be changed.');
            $procurementAttachment->phase_id = $attachmentMap[$id]['phase_id'];
            $procurementAttachment->form_id = $formId;
            if ($hasCompletion) $procurementAttachment->is_completed = $nextCompletion;
            elseif (!$procurementAttachment->exists) $procurementAttachment->is_completed = false;
            if ($file && $file->isValid()) {
                if ($procurementAttachment->file_path) \App\Models\Procurement\ProcurementAttachmentSignatory::where('procurement_attachments_id', $procurementAttachment->id)->delete();
                $path = $file->store("procurements/{$procurement->id}/attachments", 'procurements');
                if ($procurementAttachment->file_path && $procurementAttachment->file_path !== $path) $oldPaths[] = $procurementAttachment->file_path;
                $procurementAttachment->file_path = $path;
                $procurementAttachment->file_name = $file->getClientOriginalName();
                $procurementAttachment->file_size = $file->getSize();
                $procurementAttachment->mime_type = $file->getClientMimeType();
                $procurementAttachment->uploaded_by = $user->id;
                $procurementAttachment->is_completed = true;
                $procurementAttachment->workflow_status = $attachmentMap[$id]['has_signatories'] ? 'READY' : 'COMPLETED';
            }
            $procurementAttachment->save();
        }
        return $oldPaths;
    }

    public function show(Procurement $procurement)
    {
        $schemaCatalog = json_decode((string) file_get_contents(database_path('form-schemas/procurement.json')), true, flags: JSON_THROW_ON_ERROR);
        $prTemplateSchema = $schemaCatalog['formTemplates']['PR'] ?? [];
        $procurement->load([
            'endUserOffice',
            'currentPhase',
            'returnedFromPhase',
            'creator',
            'items' => fn($query) => $query->orderBy('line_no'),
            'rootItems.subItems',
            'mode.phases.roles',
            'mode.phases.attachments.form',
            'mode.phases.attachments.signatories.role',
            'attachments.uploader',
            'history.user',
        ]);
        $phases = $procurement->mode?->phases?->sortBy('phase_sequence')->values() ?? collect();
        // This collection represents phase attachments only; the procurement-level PR row has a null attachment_id.
        $procurementAttachments = ProcurementAttachment::with(['signatories.signatory', 'signatories.configuredRole'])
            ->where('procurement_id', $procurement->id)
            ->whereNotNull('attachment_id')
            ->get()
            ->keyBy('attachment_id');
        $user = request()->user();
        $status = strtoupper((string)$procurement->status);
        $terminal = in_array($status, ['ARCHIVED', 'COMPLETED', 'CANCELLED', 'REJECTED'], true);
        $canCancel = !$terminal && $this->canManageProcurement($user, $procurement, 'procurement.cancel');
        $canEdit = !$terminal && $this->canEditProcurement($user, $procurement);
        $canEditPrForm = !$terminal && $this->canEditPrForm($user, $procurement);
        $canConfigurePrForm = $this->hasPermission($user, 'settings.view')
            && $this->hasPermission($user, 'settings.update');
        $canArchive = $status !== 'ARCHIVED' && $this->roleHasPermission($user, 'procurement.archive');
        $canDelete = $status === 'ARCHIVED' && $this->hasPermission($user, 'procurement.delete');
        $currentPhase = $procurement->currentPhase ?: ($status === 'DRAFT' ? $phases->first() : null);
        $firstPhase = $phases->first();
        $isFirstPhase = $currentPhase && $firstPhase && (int)$currentPhase->id === (int)$firstPhase->id;
        $canProceed = false;
        $phaseAccess = $phases->mapWithKeys(fn($phase) => [(string)$phase->id => !$terminal && $this->userCanActInPhase($user, $phase)]);
        $canRouteAttachments = $this->roleHasPermission($user, 'procurement.route') && in_array($status, ['DRAFT', 'ROUTING', 'RETURNED'], true);
        if ($this->roleHasPermission($user, 'procurement.route') && in_array($status, ['DRAFT', 'ROUTING', 'RETURNED'], true) && $currentPhase && $this->userCanActInPhase($user, $currentPhase)) {
            $canProceed = $this->phaseRequirementsReady($procurement, $currentPhase);
        }
        $firstPhase = $phases->first();
        $canReturn = $this->roleHasPermission($user, 'procurement.return') && $procurement->currentPhase && $this->userCanActInPhase($user, $procurement->currentPhase) && $status === 'ROUTING' && $firstPhase && (int)$procurement->currentPhase->id !== (int)$firstPhase->id;

        $selectedMode = $procurement->mode;
        $selectedMode?->loadMissing(['phases' => fn($q) => $q->orderBy('phase_sequence')->with(['roles', 'attachments.form', 'attachments.signatories.role'])]);
        $canUsePhaseAttachments = $phaseAccess->contains(true);
        // The PR's office is procurement-level data. Keep its saved office available
        // as a select option even if that office has since been deactivated.
        $offices = ($canEdit || $canEditPrForm || $canUsePhaseAttachments)
            ? Office::query()
                ->where(fn($query) => $query->where('is_active', true)->orWhereKey($procurement->end_user_office_id))
                ->orderBy('name')
                ->get()
            : collect();
        $fundClusters = ($canEdit || $canEditPrForm || $canUsePhaseAttachments) ? FundCluster::where('is_active', true)->orderBy('code')->get() : collect();
        // Resolve the procurement-level PR form for every view, including terminal
        // records where the viewer has no edit permissions.
        $prForm = $this->resolvePurchaseRequestForm();
        $prFormHtml = null;
        $attachmentForms = [];
        if (($canEdit || $canEditPrForm || $canUsePhaseAttachments) && $selectedMode) {
            $prFormHtml = $this->renderFormHtml($prForm, $offices, $fundClusters, $procurement->pr_number);
            $attachmentForms = $this->buildAttachmentForms($selectedMode, $offices, $fundClusters, $prForm, $prFormHtml, $procurement->pr_number);
        }
        $prStatusForm = $prForm ?? $this->resolvePurchaseRequestForm();
        $procurementPrAttachment = $prStatusForm ? ProcurementAttachment::where('procurement_id', $procurement->id)->whereNull('attachment_id')->where('form_id', $prStatusForm->id)->first() : null;
        $procurementPrSignatories = $procurement->signatories()->with('configuredRole')->get();
        $hasPrSignatories = $procurementPrSignatories->isNotEmpty();
        $hasSignedPrSignatory = $procurementPrSignatories->contains(fn($slot) => (bool)$slot->is_signed);
        $prWorkflowStatus = strtoupper((string)($procurementPrAttachment?->workflow_status ?? 'INCOMPLETE'));
        $prHasCompiledPdf = filled($procurementPrAttachment?->file_path);
        $prStatusForRouting = match ($status) {
            'DRAFT' => $prWorkflowStatus === 'READY' ? 'READY' : 'DRAFT',
            'ROUTING' => 'ONGOING',
            default => $status,
        };
        $allowedCurrentPhaseAttachmentStatuses = ['READY', 'ROUTING', 'COMPLETED'];
        $currentPhaseAttachmentsRouteReady = $currentPhase
            && $currentPhase->attachments
                ->filter(fn($attachment) => $this->isAttachmentMandatory($attachment))
                ->every(function ($attachment) use ($procurementAttachments, $allowedCurrentPhaseAttachmentStatuses) {
                $submission = $procurementAttachments->get($attachment->id);
                $attachmentStatus = strtoupper((string)($submission?->workflow_status ?? 'NOT_READY'));

                return in_array($attachmentStatus, $allowedCurrentPhaseAttachmentStatuses, true);
            });
        $canRoutePr = $this->roleHasPermission($user, 'procurement.route')
            && in_array($prStatusForRouting, ['READY', 'ONGOING', 'RETURNED'], true)
            && $prWorkflowStatus === 'READY'
            && $prHasCompiledPdf
            && $currentPhaseAttachmentsRouteReady
            && $hasPrSignatories
            && !$hasSignedPrSignatory;
        $prSignedCount = $procurementPrSignatories->where('is_signed', true)->count();
        $prSignatoryCount = $procurementPrSignatories->count();
        $prFullySigned = $prHasCompiledPdf && $hasPrSignatories && $prSignatoryCount > 0 && $prSignedCount === $prSignatoryCount && $prWorkflowStatus === 'COMPLETED';
        $prPendingSequence = $prWorkflowStatus === 'ROUTING'
            ? $procurementPrSignatories->first(fn($step) => !(bool)$step->is_signed)?->sequence
            : null;
        $modeCode = $procurement->mode?->code ?? 'PR';
        $phaseForStats = $currentPhase ?? ($status === 'DRAFT' ? $phases->first() : null);
        $phaseRequired = $phaseReady = $overallRequired = $overallReady = $completedPhases = 0;
        foreach ($phases as $statPhase) {
            $statPhaseRequired = $statPhaseReady = 0;
            foreach ($statPhase->attachments as $statAttachment) {
                $required = true;
                foreach (['is_mandatory', 'is_required', 'mandatory', 'required'] as $requiredKey) {
                    if (array_key_exists($requiredKey, $statAttachment->getAttributes())) {
                        $required = (bool)$statAttachment->getAttribute($requiredKey);
                        break;
                    }
                }
                if (!$required) continue;
                $submission = $procurementAttachments->get($statAttachment->id);
                $complete = $submission && ($submission->workflow_status === 'COMPLETED' || ($statAttachment->signatories->isEmpty() && filled($submission->file_path)));
                $overallRequired++;
                $statPhaseRequired++;
                if ($complete) {
                    $overallReady++;
                    $statPhaseReady++;
                }
                if ($phaseForStats && (int)$statPhase->id === (int)$phaseForStats->id) {
                    $phaseRequired++;
                    if ($complete) $phaseReady++;
                }
            }
            if (!$statPhaseRequired || $statPhaseReady === $statPhaseRequired) $completedPhases++;
        }
        $overallPercent = $overallRequired ? (int)round(($overallReady / $overallRequired) * 100) : 100;
        $phasePercent = $phaseRequired ? (int)round(($phaseReady / $phaseRequired) * 100) : 100;
        $lastPhase = $phases->last();
        $isFinalPhase = $currentPhase && $lastPhase && (int)$currentPhase->id === (int)$lastPhase->id;
        $firstPhase = $phases->first();
        $priorPhases = $currentPhase ? $phases->filter(fn($phase) => (int)$phase->phase_sequence < (int)$currentPhase->phase_sequence) : collect();
        $timelineCurrent = $currentPhase ?? ($status === 'DRAFT' ? $firstPhase : null);
        $latestHistory = $procurement->history->sortByDesc('created_at')->first();
        $decision = in_array($status, ['RETURNED', 'REJECTED'], true)
            ? $procurement->history->sortByDesc('created_at')->first(fn($event) =>
                in_array(strtoupper((string)($event->status_to ?? '')), [$status], true)
                || strtoupper((string)($event->action ?? '')) === $status
            )
            : null;
        $headerStatusRemark = match (true) {
            in_array($status, ['RETURNED', 'REJECTED'], true) && filled($decision?->remarks) => $decision->remarks,
            $isFirstPhase && $hasPrSignatories && $prWorkflowStatus === 'READY' => 'PR compiled · ready for routing/signing',
            $isFirstPhase && $hasPrSignatories && $prWorkflowStatus === 'COMPLETED' => 'PR completed',
            $isFirstPhase && $hasPrSignatories && $prWorkflowStatus === 'ROUTING' => "PR routing · {$prSignedCount}/{$prSignatoryCount} signed",
            default => null,
        };
        $headerStatusText = filled($headerStatusRemark) ? $headerStatusRemark : str_replace('_', ' ', $status);
        $headerStatusTone = match (true) {
            in_array($status, ['RETURNED'], true) => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-300',
            in_array($status, ['REJECTED', 'CANCELLED'], true) => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-300',
            $status === 'COMPLETED' || $headerStatusRemark === 'PR completed' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300',
            $status === 'ROUTING' || str_starts_with((string)$headerStatusRemark, 'PR routing') => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-300',
            default => 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
        };
        $authUserRoleIds = $user->roles()->pluck('roles.id')->map(fn($id) => (int)$id)->all();
        $authUserId = (int)$user->id;
        $authUserIsAdmin = $user->isAdmin();
        $pendingPrSignatory = strtoupper((string)$procurementPrAttachment?->workflow_status) === 'ROUTING'
            ? $procurementPrSignatories->first(fn($slot) => !(bool)$slot->is_signed)
            : null;
        $pendingPrRole = $pendingPrSignatory?->configuredRole;
        $pendingPrRoleId = (int)($pendingPrSignatory?->configured_role_id ?? 0);
        $pendingPrIsEndUser = collect([$pendingPrRole?->slug, $pendingPrRole?->code, $pendingPrRole?->name])
            ->contains(fn($value) => strtolower(str_replace([' ', '-'], '_', (string)$value)) === 'end_user');
        $canAuthUserSignPr = $pendingPrSignatory
            && ($authUserIsAdmin || in_array($pendingPrRoleId, $authUserRoleIds, true))
            && ($authUserIsAdmin || !$pendingPrIsEndUser || $authUserId === (int)$procurement->created_by);
        $savedPrFormFields = $procurementPrAttachment?->form_data ?? [];
        $existingAttachmentData = $procurementAttachments->mapWithKeys(fn($s) => [(string)$s->attachment_id => [
            'ready' => filled($s->file_path), 'completed' => filled($s->file_path),
            'form_complete' => (bool)$s->is_completed, 'workflow_status' => $s->workflow_status ?: 'INCOMPLETE', 'signed_count' => $s->signatories->where('is_signed', true)->pluck('configured_role_id')->filter()->unique()->count(), 'file_name' => $s->file_name,
            'file_url' => $s->file_path ? $this->attachmentFileUrl($procurement, (string)$s->attachment_id) : null,
            'form_data' => ($phaseAccess->get((string)$s->phase_id, false) ? ($s->form_data ?? []) : []),
        ]])->all();
        if ($procurementPrAttachment) $existingAttachmentData['pr'] = [
            'ready' => filled($procurementPrAttachment->file_path), 'completed' => filled($procurementPrAttachment->file_path),
            'form_complete' => (bool)$procurementPrAttachment->is_completed, 'workflow_status' => $procurementPrAttachment->workflow_status ?: 'INCOMPLETE',
            'file_name' => $procurementPrAttachment->file_name, 'file_url' => $procurementPrAttachment->file_path ? $this->attachmentFileUrl($procurement, 'pr') : null,
        ];
        // Keep saved PR rows available to phase assignees who can fill the PR attachment.
        $existingItems = $procurement->rootItems->map(function ($item) {
            if ($item->is_lot || $item->subItems->isNotEmpty()) return ['id' => (int)$item->id, 'is_lot' => 1, 'line_no' => $item->line_no, 'unit' => 'Lot', 'description' => $item->description === 'Lot Item Package' ? '' : $item->description, 'quantity' => $item->quantity, 'cost' => $item->cost, 'sub_items' => $item->subItems->map(fn($sub) => ['id' => (int)$sub->id, 'line_no' => $sub->line_no, 'item_bank_item_id' => $sub->item_bank_item_id, 'unit' => $sub->unit, 'description' => $sub->description, 'quantity' => $sub->quantity === null ? null : (float)$sub->quantity, 'cost' => $sub->cost === null ? null : (float)$sub->cost])->values()->toArray()];
            return ['id' => (int)$item->id, 'is_lot' => 0, 'line_no' => $item->line_no, 'item_bank_item_id' => $item->item_bank_item_id, 'unit' => $item->unit, 'description' => $item->description, 'quantity' => (float)$item->quantity, 'cost' => (float)$item->cost];
        })->values()->toArray();
        $formSources = [
            'offices' => $offices->map(fn($office) => ['id' => $office->id, 'code' => $office->code, 'name' => $office->name, 'location_type' => $office->location_type])->values()->all(),
            'fund_clusters' => $fundClusters->map(fn($cluster) => ['code' => $cluster->code, 'name' => $cluster->name])->values()->all(),
            'procurement_modes' => ProcurementMode::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'min_abc', 'max_abc'])->toArray(),
        ];
        $prFormContext = [
            'procurement' => [
                'pr_number' => $procurement->pr_number,
                'purpose' => $procurement->purpose,
                'fund_cluster' => $procurement->fund_cluster,
                'responsibility_code' => $procurement->responsibility_code,
                'pr_date' => $procurement->pr_date?->format('Y-m-d'),
                'end_user_office_id' => $procurement->end_user_office_id,
                'end_user_office' => [
                    'id' => $procurement->endUserOffice?->id,
                    'name' => $procurement->endUserOffice?->name,
                ],
                'abc_amount' => (float) ($procurement->abc_amount ?? 0),
                'items' => $existingItems,
            ],
            'sources' => $formSources,
        ];
        $activeRfqDocuments = RfqDocument::query()
            ->where('is_active', true)
            ->orderBy('sequence')
            ->orderBy('id')
            ->get(['id', 'sequence', 'title']);
        $selectedRfqDocumentIds = $procurement->rfqDocuments()
            ->pluck('rfq_documents.id')
            ->map(fn($id) => (int)$id)
            ->all();
        $requiredRfqDocuments = $activeRfqDocuments->map(fn(RfqDocument $document) => [
            'id' => (int)$document->id,
            'sequence' => (int)$document->sequence,
            'label' => $document->title,
            'checked_mark' => in_array((int)$document->id, $selectedRfqDocumentIds, true) ? '✓' : '',
        ])->all();
        $attachmentFormContexts = [];
        foreach ($phases as $phase) foreach ($phase->attachments as $attachment) {
            $attachmentFormContexts[(string)$attachment->id] = array_replace_recursive([
                'procurement' => [
                    'purpose' => $procurement->purpose,
                    'abc_amount' => (float)($procurement->abc_amount ?? 0),
                    'pr_number' => $procurement->pr_number,
                    'rfq_number' => preg_replace('/^PR/i', 'RFQ', (string)$procurement->pr_number),
                    'is_delivery_cd' => (bool)$procurement->is_delivery_cd,
                    'delivery_cd' => $procurement->delivery_cd ?? 7,
                    'delivery_date' => $procurement->delivery_date?->format('Y-m-d') ?? now()->addDays(7)->format('Y-m-d'),
                    'quotation_deadline' => $procurement->quotation_deadline?->format('Y-m-d\TH:i'),
                    'rfq_documents' => $selectedRfqDocumentIds,
                    'end_user_office' => ['id' => $procurement->endUserOffice?->id, 'name' => $procurement->endUserOffice?->name ?? ''],
                    'mode' => ['id' => $selectedMode?->id, 'code' => $selectedMode?->code, 'name' => $selectedMode?->name ?? ''],
                    'items' => $procurement->items->map(fn($item) => [
                        'id' => (int)$item->id,
                        'parent_id' => $item->parent_id ? (int)$item->parent_id : null,
                        'is_lot' => (bool)$item->is_lot,
                        'line_no' => (int)$item->line_no,
                        'unit' => $item->unit,
                        'description' => $item->description,
                        'quantity' => $item->quantity === null ? null : (float)$item->quantity,
                        'cost' => $item->cost === null ? null : (float)$item->cost,
                    ])->values()->all(),
                    'root_items' => $existingItems,
                ],
                'sources' => $formSources,
            ], (array)($attachment->form?->configuration['template_data'] ?? []));
            $attachmentFormContexts[(string)$attachment->id]['procurement']['rfq_documents'] = $selectedRfqDocumentIds;
            $attachmentFormContexts[(string)$attachment->id]['rfq']['required_documents'] = $requiredRfqDocuments;
        }

        $signingTurnItems = collect();
        if ($canAuthUserSignPr && filled($procurementPrAttachment?->file_path)) {
            $signingTurnItems->push([
                'id' => 'pr',
                'name' => 'Purchase Request',
                'role_name' => $pendingPrRole?->name ?? 'Signatory',
                'url' => $this->attachmentFileUrl($procurement, 'pr'),
                'file_name' => $procurementPrAttachment->file_name,
            ]);
        }
        foreach ($phases as $phase) {
            foreach ($phase->attachments as $attachment) {
                $submission = $procurementAttachments->get($attachment->id);
                if (!$submission?->file_path || strtoupper((string)$submission->workflow_status) !== 'ROUTING') continue;
                $configured = ($attachment->signatories ?? collect())
                    ->sortBy(fn($step) => $step->sequence ?? $step->pivot?->sequence ?? $step->order ?? $step->id)
                    ->values();
                $signedSlots = $submission->signatories;
                $pending = $configured->first(function ($step, $index) use ($signedSlots) {
                    $sequence = (int)($step->sequence ?? $step->pivot?->sequence ?? $step->order ?? ($index + 1));
                    $roleId = (int)($step->role_id ?? $step->role?->id ?? 0);
                    return !$signedSlots->contains(fn($slot) => (int)$slot->sequence === $sequence || ($roleId > 0 && (int)$slot->configured_role_id === $roleId));
                });
                if (!$pending) continue;
                $role = $pending->role;
                $roleId = (int)($pending->role_id ?? $role?->id ?? 0);
                $hasRole = $authUserIsAdmin || in_array($roleId, $authUserRoleIds, true);
                $isEndUser = collect([$role?->slug, $role?->code, $role?->name])
                    ->contains(fn($value) => strtolower(str_replace([' ', '-'], '_', (string)$value)) === 'end_user');
                if (!$hasRole || (!$authUserIsAdmin && $isEndUser && $authUserId !== (int)$procurement->created_by)) continue;
                $signingTurnItems->push([
                    'id' => (string)$attachment->id,
                    'name' => $attachment->name,
                    'role_name' => $role?->name ?? 'Signatory',
                    'url' => $this->attachmentFileUrl($procurement, (string)$attachment->id),
                    'file_name' => $submission->file_name,
                ]);
            }
        }

        $canRouteAllAttachments = $canRouteAttachments && $phases->contains(fn($phase) => ($phaseAccess[(string)$phase->id] ?? false) && $phase->attachments->contains(fn($attachment) => $attachment->signatories->isNotEmpty()));
        return view('procurements.show', compact('procurement', 'phases', 'procurementAttachments', 'procurementPrAttachment', 'procurementPrSignatories', 'hasPrSignatories', 'hasSignedPrSignatory', 'prWorkflowStatus', 'headerStatusText', 'headerStatusTone', 'prHasCompiledPdf', 'prSignedCount', 'prSignatoryCount', 'prFullySigned', 'prPendingSequence', 'modeCode', 'status', 'isFirstPhase', 'phaseForStats', 'phaseRequired', 'phaseReady', 'overallRequired', 'overallReady', 'completedPhases', 'overallPercent', 'phasePercent', 'lastPhase', 'isFinalPhase', 'priorPhases', 'timelineCurrent', 'latestHistory', 'decision', 'canRoutePr', 'authUserRoleIds', 'authUserId', 'canAuthUserSignPr', 'savedPrFormFields', 'currentPhase', 'canCancel', 'canEdit', 'canEditPrForm', 'canConfigurePrForm', 'canArchive', 'canDelete', 'canProceed', 'canReturn', 'canRouteAttachments', 'canRouteAllAttachments', 'phaseAccess', 'selectedMode', 'offices', 'fundClusters', 'prForm', 'prFormHtml', 'prTemplateSchema', 'attachmentForms', 'existingAttachmentData', 'existingItems', 'attachmentFormContexts', 'prFormContext', 'signingTurnItems'));
    }

    /** Return a procurement PDF only after checking record scope and its private storage path. */
    public function attachmentFile(Request $request, Procurement $procurement, string $attachmentId)
    {
        $procurement->loadMissing(['mode.phases.roles']);
        $this->authorize('view', $procurement);

        $query = ProcurementAttachment::query()->where('procurement_id', $procurement->id);
        if ($attachmentId === 'pr') {
            $record = $query->whereNull('attachment_id')->firstOrFail();
        } else {
            abort_unless(ctype_digit($attachmentId), 404);
            $record = $query->where('attachment_id', (int) $attachmentId)->firstOrFail();
        }

        $path = (string) $record->file_path;
        $prefix = "procurements/{$procurement->id}/attachments/";
        abort_unless(str_starts_with($path, $prefix) && ! str_contains($path, '..') && ! str_contains($path, '\\'), 404);

        $disk = Storage::disk('procurements');
        abort_unless($disk->exists($path), 404, 'PDF not found.');
        $bytes = $disk->get($path);
        abort_unless(is_string($bytes) && str_starts_with($bytes, '%PDF-'), 404, 'Stored file is not a valid PDF.');

        $download = $request->boolean('download');
        $fileName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) ($record->file_name ?: 'document.pdf')));
        $disposition = $download ? 'attachment' : 'inline';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$fileName.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function attachmentFileUrl(Procurement $procurement, string $attachmentId): string
    {
        return route('procurements.attachments.file', ['procurement' => $procurement->id, 'attachmentId' => $attachmentId]);
    }

    public function toggleRfqDocument(Request $request, Procurement $procurement, string $attachmentId, string $rfqDocumentId)
    {
        $validated = $request->validate(['selected' => ['required', 'boolean']]);
        abort_unless(ctype_digit($attachmentId) && ctype_digit($rfqDocumentId), 404);
        abort_if(in_array(strtoupper((string)$procurement->status), ['ARCHIVED', 'COMPLETED', 'CANCELLED', 'REJECTED'], true), 403, 'This purchase request can no longer be edited.');

        $mode = ProcurementMode::with(['phases' => fn($query) => $query->with(['roles', 'attachments.form'])])
            ->findOrFail($procurement->procurement_mode_id);
        $phase = null;
        $attachment = null;
        foreach ($mode->phases as $candidatePhase) {
            $candidate = $candidatePhase->attachments->first(fn($item) => (int)$item->id === (int)$attachmentId);
            if ($candidate) {
                $phase = $candidatePhase;
                $attachment = $candidate;
                break;
            }
        }

        abort_unless($attachment && $phase && strtoupper((string)$attachment->form?->code) === 'RFQ', 404, 'The selected attachment is not an RFQ form.');
        abort_unless($this->userCanWorkOnPhase($request->user(), $procurement, $phase), 403, 'You cannot modify attachments in this phase.');
        $document = RfqDocument::query()->where('is_active', true)->findOrFail((int)$rfqDocumentId);
        $selected = (bool)$validated['selected'];
        $oldPath = null;

        DB::transaction(function () use ($request, $procurement, $phase, $attachment, $document, $selected, &$oldPath) {
            $lockedProcurement = Procurement::query()->whereKey($procurement->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->userCanWorkOnPhase($request->user(), $lockedProcurement, $phase), 403, 'You cannot modify attachments in this phase.');

            $submission = ProcurementAttachment::query()
                ->where('procurement_id', $lockedProcurement->id)
                ->where('attachment_id', $attachment->id)
                ->lockForUpdate()
                ->first();
            abort_unless(!in_array(strtoupper((string)$submission?->workflow_status), ['ROUTING', 'COMPLETED'], true), 422, 'A routed or completed attachment cannot be changed.');

            if ($selected) {
                $lockedProcurement->rfqDocuments()->syncWithoutDetaching([$document->id]);
            } else {
                $lockedProcurement->rfqDocuments()->detach($document->id);
            }

            if ($submission) {
                if ($submission->file_path) {
                    \App\Models\Procurement\ProcurementAttachmentSignatory::query()
                        ->where('procurement_attachments_id', $submission->id)
                        ->delete();
                    $oldPath = $submission->file_path;
                }
                $submission->file_path = null;
                $submission->file_name = null;
                $submission->file_size = null;
                $submission->mime_type = null;
                $submission->workflow_status = 'INCOMPLETE';
                $submission->is_completed = false;
                $submission->save();
            }
        });

        if ($oldPath && Storage::disk('procurements')->exists($oldPath)) {
            Storage::disk('procurements')->delete($oldPath);
        }

        return response()->json([
            'ok' => true,
            'rfq_document_id' => $document->id,
            'selected' => $selected,
            'is_ready' => false,
            'is_complete' => false,
            'workflow_status' => 'INCOMPLETE',
            'file_url' => null,
        ]);
    }

    public function saveAttachment(Request $request, Procurement $procurement, string $attachmentId)
    {
        if ($attachmentId === 'pr') return $this->saveProcurementPrForm($request, $procurement);
        $attachmentId = (int)$attachmentId;
        abort_if(in_array(strtoupper((string)$procurement->status), ['ARCHIVED', 'COMPLETED', 'CANCELLED', 'REJECTED'], true), 403, 'This purchase request can no longer be edited.');
        $request->validate([
            'fields' => ['nullable', 'json', 'max:2000000'],
            'is_complete' => ['nullable', 'boolean'],
            'is_compiled' => ['nullable', 'boolean'],
            'attachment_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'rendered_html' => ['nullable', 'string', 'max:2000000'],
            'pdf_page_width' => ['required_with:rendered_html', 'numeric', 'min:50', 'max:500'],
            'pdf_page_height' => ['required_with:rendered_html', 'numeric', 'min:50', 'max:500'],
            'pdf_margin_top' => ['required_with:rendered_html', 'numeric', 'min:0', 'max:50'],
            'pdf_margin_right' => ['required_with:rendered_html', 'numeric', 'min:0', 'max:50'],
            'pdf_margin_bottom' => ['required_with:rendered_html', 'numeric', 'min:0', 'max:50'],
            'pdf_margin_left' => ['required_with:rendered_html', 'numeric', 'min:0', 'max:50'],
        ]);
        $mode = ProcurementMode::with(['phases' => fn($q) => $q->orderBy('phase_sequence')->with(['roles', 'attachments.form', 'attachments.signatories.role'])])->findOrFail($procurement->procurement_mode_id);
        $phase = null;
        $attachment = null;
        foreach ($mode->phases as $candidatePhase) {
            $candidate = $candidatePhase->attachments->first(fn($item) => (int)$item->id === $attachmentId);
            if ($candidate) {
                $phase = $candidatePhase;
                $attachment = $candidate;
                break;
            }
        }
        abort_unless($attachment && $phase, 404, 'Attachment is not configured for this procurement mode.');
        abort_unless($this->userCanWorkOnPhase($request->user(), $procurement, $phase), 403, 'You cannot modify attachments in this phase.');
        $formId = $attachment->form_id ?? $attachment->form?->id;
        $file = $request->file('attachment_file');
        $renderedHtml = $request->input('rendered_html');
        $isCompiled = $request->boolean('is_compiled');
        $hasFields = $request->exists('fields');
        $formFields = $this->decodeArray($request->input('fields'));
        $this->assertBoundedFormFields($formFields);
        $boundFormFields = $formFields;
        $procurementFieldValues = [];
        $itemCostUpdates = [];
        foreach ($formFields as $path => $value) {
            if (preg_match('/^procurement\.items\.(\d+)\.cost$/', (string)$path, $match)) {
                if ($value !== '' && $value !== null && (!is_numeric($value) || (float)$value < 0 || (float)$value > 9999999999999.99)) {
                    throw ValidationException::withMessages(['fields' => 'Item ABC per unit must be a non-negative amount with up to 13 integer digits.']);
                }
                $itemCostUpdates[(int)$match[1]] = ($value === '' || $value === null) ? null : round((float)$value, 2);
                unset($formFields[$path]);
                continue;
            }
            if (str_starts_with((string)$path, 'procurement.')) {
                $field = substr((string)$path, strlen('procurement.'));
                if (!in_array($field, ['is_delivery_cd', 'delivery_cd', 'delivery_date', 'quotation_deadline'], true)) {
                    throw ValidationException::withMessages(['fields' => "The form cannot edit procurement field: {$field}."]);
                }
                // Generic select controls submit the literal strings "true" and "false".
                // Laravel's boolean rule accepts booleans and 0/1, so normalize these
                // explicit boolean strings before validation and persistence.
                if ($field === 'is_delivery_cd' && is_string($value)) {
                    $normalizedBoolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($normalizedBoolean !== null) $value = $normalizedBoolean;
                }
                $procurementFieldValues[$field] = is_string($value) && trim($value) === '' ? null : $value;
                unset($formFields[$path]);
            }
        }
        $procurementFieldValues = Validator::make($procurementFieldValues, [
            'is_delivery_cd' => ['sometimes', 'boolean'],
            'delivery_cd' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'quotation_deadline' => ['sometimes', 'nullable', 'date'],
        ])->validate();
        if (array_key_exists('is_delivery_cd', $procurementFieldValues)) {
            if ((bool)$procurementFieldValues['is_delivery_cd']) $procurementFieldValues['delivery_date'] = null;
            else $procurementFieldValues['delivery_cd'] = null;
        }
        $isComplete = $request->boolean('is_complete');
        if (!$isCompiled && is_string($renderedHtml)) throw ValidationException::withMessages(['rendered_html' => 'Rendered form HTML is only accepted when compiling a completed form.']);
        if ($formId && $file && !$isCompiled) throw ValidationException::withMessages(['attachment_file' => 'This attachment has a bound form. Compile the completed form to PDF instead of uploading a separate PDF.']);
        if ($isCompiled && (!$formId || (!$file && !is_string($renderedHtml)) || !$isComplete)) throw ValidationException::withMessages(['attachment_file' => 'A bound form must pass its completeness check before a compiled PDF can be saved.']);
        if ($isCompiled && $file && is_string($renderedHtml)) throw ValidationException::withMessages(['attachment_file' => 'Submit either a PDF or rendered form HTML, not both.']);
        $isMainPrForm = $this->isPurchaseRequestFormId($formId ? (int)$formId : null);
        if ($isCompiled && !$isMainPrForm) {
            $this->validateBoundFormRequiredInputs($attachment->form, $boundFormFields, $procurement);
        }
        if ($isCompiled && $isMainPrForm && $this->calculatedTotalFromFields($formFields) <= 0) {
            throw ValidationException::withMessages(['fields' => 'The calculated total amount must be greater than zero before compiling the Purchase Request.']);
        }
        if ($isCompiled && $isMainPrForm) {
            $rowError = $this->purchaseRequestItemRowsError($formFields);
            if ($rowError) throw ValidationException::withMessages(['fields' => $rowError]);
        }
        if (!$formId && !$file && !$hasFields) throw ValidationException::withMessages(['attachment_file' => 'Choose a PDF to upload.']);
        $temporaryPdfPath = null;
        if ($isCompiled && is_string($renderedHtml)) {
            [$file, $temporaryPdfPath] = $this->createRenderedFormPdf(
                $renderedHtml,
                (float) $request->input('pdf_page_width'),
                (float) $request->input('pdf_page_height'),
                (float) $request->input('pdf_margin_top'),
                (float) $request->input('pdf_margin_right'),
                (float) $request->input('pdf_margin_bottom'),
                (float) $request->input('pdf_margin_left'),
                (string) ($attachment->name ?? 'attachment'),
            );
        }
        $oldPath = null;
        try {
            $procurementAttachment = DB::transaction(function () use ($request, $procurement, $phase, $attachment, $formId, $file, $isCompiled, $hasFields, $formFields, $procurementFieldValues, $itemCostUpdates, $isMainPrForm, $isComplete, &$oldPath) {
                $lockedProcurement = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
                abort_unless($this->userCanWorkOnPhase($request->user(), $lockedProcurement, $phase), 403, 'You cannot modify attachments in this phase.');
                $procurementAttachment = ProcurementAttachment::where('procurement_id', $lockedProcurement->id)->where('attachment_id', $attachment->id)->lockForUpdate()->first();
                $procurementAttachment ??= new ProcurementAttachment(['procurement_id' => $lockedProcurement->id, 'attachment_id' => $attachment->id]);
                abort_unless(!in_array(strtoupper((string)$procurementAttachment->workflow_status), ['ROUTING', 'COMPLETED'], true), 422, 'A routed or completed attachment cannot be changed.');
                // Form values sync to procurement fields/items. Editing a bound form invalidates its previous PDF.
                $dataChanged = $hasFields && !$isCompiled;
                $procurementAttachment->phase_id = $phase->id;
                $procurementAttachment->attachment_id = $attachment->id;
                $procurementAttachment->form_id = $formId;
                if ($formId) $procurementAttachment->is_completed = $isComplete;
                if ($formId && $dataChanged && $procurementAttachment->file_path) {
                    \App\Models\Procurement\ProcurementAttachmentSignatory::where('procurement_attachments_id', $procurementAttachment->id)->delete();
                    $procurementAttachment->workflow_status = 'INCOMPLETE';
                    $oldPath = $procurementAttachment->file_path;
                    $procurementAttachment->file_path = null;
                    $procurementAttachment->file_name = null;
                    $procurementAttachment->file_size = null;
                    $procurementAttachment->mime_type = null;
                }
                if ($file && $file->isValid()) {
                    if ($procurementAttachment->file_path) \App\Models\Procurement\ProcurementAttachmentSignatory::where('procurement_attachments_id', $procurementAttachment->id)->delete();
                    $newPath = $file->store("procurements/{$procurement->id}/attachments", 'procurements');
                    if ($procurementAttachment->file_path && $procurementAttachment->file_path !== $newPath) $oldPath = $procurementAttachment->file_path;
                    $procurementAttachment->file_path = $newPath;
                    $procurementAttachment->file_name = $file->getClientOriginalName();
                    $procurementAttachment->file_size = $file->getSize();
                    $procurementAttachment->mime_type = $file->getClientMimeType();
                    $procurementAttachment->uploaded_by = $request->user()->id;
                    if (!$formId || $isCompiled) $procurementAttachment->is_completed = true;
                    $procurementAttachment->workflow_status = $attachment->signatories->isEmpty() ? 'COMPLETED' : 'READY';
                }
                if ($hasFields && $isMainPrForm) $this->syncProcurementFieldsAndItems($lockedProcurement, $formFields, true, $request->user());
                elseif ($hasFields) $procurementAttachment->form_data = $formFields;
                if (!$isMainPrForm && ($procurementFieldValues || $itemCostUpdates)) {
                    foreach ($itemCostUpdates as $itemId => $cost) {
                        $item = ProcurementItem::query()
                            ->where('procurement_id', $lockedProcurement->id)
                            ->whereKey($itemId)
                            ->where('is_lot', false)
                            ->lockForUpdate()
                            ->firstOrFail();
                        $item->cost = $cost;
                        $item->save();
                    }
                    if ($itemCostUpdates) {
                        $lockedProcurement->abc_amount = round((float)$lockedProcurement->items()->where('is_lot', false)->sum(DB::raw('COALESCE(quantity, 0) * COALESCE(cost, 0)')), 2);
                    }
                    if ($procurementFieldValues) $lockedProcurement->fill($procurementFieldValues);
                    $lockedProcurement->save();
                }
                $procurementAttachment->save();
                return $procurementAttachment->fresh();
            });
        } finally {
            $this->deleteTemporaryPdf($temporaryPdfPath);
        }
        if ($oldPath && Storage::disk('procurements')->exists($oldPath)) Storage::disk('procurements')->delete($oldPath);
        return response()->json(['ok' => true, 'attachment_id' => $attachment->id, 'is_ready' => filled($procurementAttachment->file_path), 'is_complete' => $procurementAttachment->workflow_status === 'COMPLETED', 'form_complete' => (bool)$procurementAttachment->is_completed, 'workflow_status' => $procurementAttachment->workflow_status, 'signed_count' => 0, 'signatory_count' => $attachment->signatories->unique(fn($step) => $step->role_id ? 'role:'.$step->role_id : 'sequence:'.$step->sequence)->count(), 'procurement_item_count' => $procurement->items()->count(), 'file_name' => $procurementAttachment->file_name, 'file_url' => $procurementAttachment->file_path ? $this->attachmentFileUrl($procurement, (string)$attachment->id) : null]);
    }

    private function saveProcurementPrForm(Request $request, Procurement $procurement)
    {
        abort_unless($this->canEditPrForm($request->user(), $procurement), 403, 'You do not have the required PR form modification privilege or procurement access.');
        abort_unless(in_array(strtoupper((string)$procurement->status), ['DRAFT', 'RETURNED'], true), 403, 'Only draft or returned purchase requests can be edited.');
        $request->validate([
            'fields' => ['nullable', 'json', 'max:2000000'],
            'is_complete' => ['nullable', 'boolean'],
            'is_compiled' => ['nullable', 'boolean'],
            'attachment_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'rendered_html' => ['nullable', 'string', 'max:2000000'],
            'pdf_page_width' => ['required_with:rendered_html', 'numeric', 'min:50', 'max:500'],
            'pdf_page_height' => ['required_with:rendered_html', 'numeric', 'min:50', 'max:500'],
            'pdf_margin_top' => ['required_with:rendered_html', 'numeric', 'min:0', 'max:50'],
            'pdf_margin_right' => ['required_with:rendered_html', 'numeric', 'min:0', 'max:50'],
            'pdf_margin_bottom' => ['required_with:rendered_html', 'numeric', 'min:0', 'max:50'],
            'pdf_margin_left' => ['required_with:rendered_html', 'numeric', 'min:0', 'max:50'],
        ]);
        $form = $this->resolvePurchaseRequestForm();
        abort_unless($form, 422, 'No active Purchase Request form is configured in Settings.');
        $fields = $this->decodeArray($request->input('fields'));
        $this->assertBoundedFormFields($fields);
        // Accept the schema's full binding paths as well as the compact form keys
        // emitted by the PR controls. Keep the stored fields compatible with existing
        // form_data while always syncing the canonical procurement attributes.
        foreach (['purpose', 'fund_cluster', 'end_user_office_id', 'pr_date', 'responsibility_code'] as $field) {
            $binding = "procurement.{$field}";
            if (array_key_exists($binding, $fields) && !array_key_exists($field, $fields)) {
                $fields[$field] = $fields[$binding];
            }
        }
        // Office is printed from the procurement relation in the PR template, so it
        // is not posted as an editable field. Preserve its procurement-level FK for
        // required-field validation and saved PR form data.
        if (!array_key_exists('end_user_office_id', $fields)) {
            $fields['end_user_office_id'] = $procurement->end_user_office_id;
        }
        $file = $request->file('attachment_file');
        $renderedHtml = $request->input('rendered_html');
        $compiled = $request->boolean('is_compiled');
        $complete = $request->boolean('is_complete');
        if (!$compiled && is_string($renderedHtml)) throw ValidationException::withMessages(['rendered_html' => 'Rendered form HTML is only accepted when compiling a completed form.']);
        if ($compiled && ((!$file && !is_string($renderedHtml)) || !$complete)) throw ValidationException::withMessages(['attachment_file' => 'Complete the PR form before compiling its PDF.']);
        if ($compiled && $file && is_string($renderedHtml)) throw ValidationException::withMessages(['attachment_file' => 'Submit either a PDF or rendered form HTML, not both.']);
        if ($compiled) $this->validateRequiredPrFields($fields);
        if ($compiled && $this->calculatedTotalFromFields($fields) <= 0) throw ValidationException::withMessages(['fields' => 'The calculated total amount must be greater than zero before compiling the Purchase Request.']);
        if ($compiled && ($error = $this->purchaseRequestItemRowsError($fields))) throw ValidationException::withMessages(['fields' => $error]);

        $temporaryPdfPath = null;
        if ($compiled && is_string($renderedHtml)) {
            [$file, $temporaryPdfPath] = $this->createRenderedFormPdf(
                $renderedHtml,
                (float) $request->input('pdf_page_width'),
                (float) $request->input('pdf_page_height'),
                (float) $request->input('pdf_margin_top'),
                (float) $request->input('pdf_margin_right'),
                (float) $request->input('pdf_margin_bottom'),
                (float) $request->input('pdf_margin_left'),
                'Purchase Request',
            );
        }
        $oldPath = null;
        try {
            $record = DB::transaction(function () use ($request, $procurement, $form, $fields, $file, $compiled, $complete, &$oldPath) {
                $lockedProcurement = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
                abort_unless(in_array(strtoupper((string)$lockedProcurement->status), ['DRAFT', 'RETURNED'], true), 403, 'Only draft or returned purchase requests can be edited.');
                $record = ProcurementAttachment::where('procurement_id', $lockedProcurement->id)->whereNull('attachment_id')->where('form_id', $form->id)->lockForUpdate()->first();
                $record ??= new ProcurementAttachment(['procurement_id' => $lockedProcurement->id, 'attachment_id' => null, 'form_id' => $form->id]);
                $record->phase_id = null;
                $record->form_data = $fields;
                $record->is_completed = $complete;
                if (!$compiled && $record->file_path) {
                    $oldPath = $record->file_path;
                    $record->file_path = $record->file_name = $record->file_size = $record->mime_type = null;
                    $record->workflow_status = 'INCOMPLETE';
                    ProcurementSignatory::where('procurement_id', $procurement->id)->update(['is_signed' => false, 'signatory_id' => null, 'signed_at' => null]);
                }
                if ($compiled && $file?->isValid()) {
                    if ($record->file_path) $oldPath = $record->file_path;
                    ProcurementSignatory::where('procurement_id', $procurement->id)->update(['is_signed' => false, 'signatory_id' => null, 'signed_at' => null]);
                    $record->file_path = $file->store("procurements/{$procurement->id}/attachments", 'procurements');
                    $record->file_name = 'Purchase Request.pdf';
                    $record->file_size = $file->getSize();
                    $record->mime_type = 'application/pdf';
                    $record->uploaded_by = $request->user()->id;
                    $record->workflow_status = 'READY';
                    $this->initializeProcurementSignatories($lockedProcurement);
                }
                $record->save();
                $this->syncProcurementFieldsAndItems($lockedProcurement, $fields, true, $request->user());
                return $record->fresh();
            });
        } finally {
            $this->deleteTemporaryPdf($temporaryPdfPath);
        }
        if ($oldPath && Storage::disk('procurements')->exists($oldPath)) Storage::disk('procurements')->delete($oldPath);
        return response()->json(['ok' => true, 'attachment_id' => 'pr', 'is_ready' => filled($record->file_path), 'is_complete' => $record->workflow_status === 'COMPLETED', 'form_complete' => (bool)$record->is_completed, 'workflow_status' => $record->workflow_status ?: 'INCOMPLETE', 'signed_count' => ProcurementSignatory::where('procurement_id', $procurement->id)->where('is_signed', true)->count(), 'signatory_count' => ProcurementSignatory::where('procurement_id', $procurement->id)->count(), 'procurement_item_count' => $procurement->items()->count(), 'file_name' => $record->file_name, 'file_url' => $record->file_path ? $this->attachmentFileUrl($procurement, 'pr') : null]);
    }

    private function createRenderedFormPdf(
        string $html,
        float $pageWidth,
        float $pageHeight,
        float $marginTop,
        float $marginRight,
        float $marginBottom,
        float $marginLeft,
        string $name,
    ): array {
        $pdfContents = app(ProcurementFormPdfRenderer::class)->render(
            $html,
            $pageWidth,
            $pageHeight,
            $marginTop,
            $marginRight,
            $marginBottom,
            $marginLeft,
        );
        $temporaryPath = tempnam(sys_get_temp_dir(), 'procurement-form-pdf-');

        if ($temporaryPath === false) {
            throw new \RuntimeException('A temporary file could not be created for the compiled PDF.');
        }

        if (file_put_contents($temporaryPath, $pdfContents) !== strlen($pdfContents)) {
            if (! unlink($temporaryPath)) {
                logger()->warning('A partial temporary procurement PDF could not be deleted.', ['path' => $temporaryPath]);
            }

            throw new \RuntimeException('The compiled PDF could not be written to a temporary file.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', pathinfo($name, PATHINFO_FILENAME)) ?: 'attachment';
        $file = new UploadedFile($temporaryPath, "{$safeName}.pdf", 'application/pdf', UPLOAD_ERR_OK, true);

        return [$file, $temporaryPath];
    }

    private function deleteTemporaryPdf(?string $path): void
    {
        if ($path && is_file($path) && ! unlink($path)) {
            logger()->warning('A temporary procurement PDF could not be deleted.', ['path' => $path]);
        }
    }

    /** Enforce the JSON-declared PR field contract on the server before PDF compilation. */
    private function validateRequiredPrFields(array $fields): void
    {
        $schema = json_decode((string) file_get_contents(database_path('form-schemas/procurement.json')), true, flags: JSON_THROW_ON_ERROR);
        $definitions = $schema['formTemplates']['PR']['fields'] ?? [];
        foreach ($definitions as $path => $definition) {
            if (empty($definition['required']) || str_starts_with($path, 'item.')) continue;
            $key = str_starts_with($path, 'procurement.') ? substr($path, strlen('procurement.')) : $path;
            $value = $fields[$key] ?? $fields[$path] ?? null;
            if (is_array($value)) $value = reset($value);
            if ($value === null || (is_string($value) && trim($value) === '')) {
                throw ValidationException::withMessages(['fields' => 'Complete the required form field: '.$path.'.']);
            }
            if (($definition['inputType'] ?? null) === 'number') {
                if (!is_numeric($value) || (isset($definition['min']) && (float)$value < (float)$definition['min'])) {
                    throw ValidationException::withMessages(['fields' => 'Enter a valid value for '.$path.'.']);
                }
            }
            if (($definition['inputType'] ?? null) === 'date' && strtotime((string)$value) === false) {
                throw ValidationException::withMessages(['fields' => 'Enter a valid date for '.$path.'.']);
            }
        }
    }

    private function initializeProcurementSignatories(Procurement $procurement): void
    {
        if ($procurement->signatories()->exists()) return;
        $roles = json_decode(Setting::get('pr_signatories', '["end_user","budget_officer","hope"]'), true);
        if (!is_array($roles) || count($roles) !== 3 || collect($roles)->contains(fn($role) => !is_string($role) || trim($role) === '') || count(array_unique($roles)) !== 3) {
            throw ValidationException::withMessages(['pr_signatories' => 'Configure three distinct PR signatory roles in Settings before compiling the PR.']);
        }
        $roleIds = Role::whereIn('name', $roles)->pluck('id', 'name');
        if ($roleIds->count() !== 3) {
            throw ValidationException::withMessages(['pr_signatories' => 'One or more configured PR signatory roles no longer exist. Update Settings before compiling the PR.']);
        }
        foreach (array_values($roles) as $index => $roleName) {
            $procurement->signatories()->create(['sequence' => $index + 1, 'configured_role_id' => $roleIds[$roleName]]);
        }
    }

    public function routeAttachment(Request $request, Procurement $procurement, string $attachmentId)
    {
        abort_unless($this->roleHasPermission($request->user(), 'procurement.route'), 403);
        $status = strtoupper((string)$procurement->status);
        abort_unless(in_array($status, ['DRAFT', 'ROUTING', 'RETURNED'], true), 422);
        if ($attachmentId === 'pr') {
            $prForm = $this->resolvePurchaseRequestForm();
            abort_unless($prForm, 422, 'No active Purchase Request form is configured in Settings.');
            $record = ProcurementAttachment::where('procurement_id', $procurement->id)->whereNull('attachment_id')->where('form_id', $prForm->id)->firstOrFail();
            abort_unless(filled($record->file_path), 422, 'Compile the Purchase Request PDF before routing it to signatories.');
            abort_unless($procurement->signatories()->exists(), 422, 'No PR signatory roles are configured.');
            abort_unless(!$procurement->signatories()->where('is_signed', true)->exists(), 422, 'The Purchase Request has already been signed.');
            abort_unless($record->workflow_status === 'READY', 422, 'The Purchase Request is not ready to route.');
            $record->update(['workflow_status' => 'ROUTING']);
            $statusFrom = strtoupper((string)$procurement->status);
            if ($status === 'DRAFT') {
                $procurement->update(['status' => 'ROUTING', 'current_phase_id' => $procurement->current_phase_id ?: $procurement->mode?->phases()->orderBy('phase_sequence')->value('id')]);
                $status = 'ROUTING';
            }
            ProcurementHistory::create(['procurement_id' => $procurement->id, 'user_id' => $request->user()->id, 'phase_id' => null, 'action' => 'PR_FORM_ROUTING_STARTED', 'status_from' => $statusFrom, 'status_to' => $status, 'remarks' => 'Routed the compiled Purchase Request to its configured signatories.', 'created_at' => now()]);
            app(\App\Services\ProcurementEmailNotificationService::class)->nextSignatureTurn($procurement, 'pr');
            return back()->with('success', 'Purchase Request routed to its configured signatories.');
        }
        $attachmentId = (int)$attachmentId;
        $mode = ProcurementMode::with(['phases.roles', 'phases.attachments.signatories.role'])->findOrFail($procurement->procurement_mode_id);
        $attachment = $mode->phases->flatMap(fn($configuredPhase) => $configuredPhase->attachments)->firstWhere('id', $attachmentId);
        abort_unless($attachment && $attachment->signatories->isNotEmpty(), 404);
        $phase = $mode->phases->first(fn($configuredPhase) => $configuredPhase->attachments->contains(fn($candidate) => (int)$candidate->id === $attachmentId));
        abort_unless($phase && $this->userCanWorkOnPhase($request->user(), $procurement, $phase), 403, 'You cannot route attachments in this phase.');
        $procurementAttachment = ProcurementAttachment::with('signatories')->where('procurement_id', $procurement->id)->where('attachment_id', $attachmentId)->firstOrFail();
        abort_unless(filled($procurementAttachment->file_path), 422, 'This attachment is not ready to route.');
        $workflowStatus = strtoupper((string)$procurementAttachment->workflow_status);
        if (in_array($workflowStatus, ['', 'INCOMPLETE'], true) && !$procurementAttachment->signatories->contains(fn($slot) => (bool)$slot->is_signed)) {
            // Older attachment records can have a saved PDF while workflow_status is still empty/INCOMPLETE.
            $procurementAttachment->forceFill(['workflow_status' => 'READY'])->save();
            $workflowStatus = 'READY';
        }
        abort_unless($workflowStatus === 'READY' && !$procurementAttachment->signatories->contains(fn($slot) => (bool)$slot->is_signed), 422, 'This attachment is not ready to route.');
        $this->startAttachmentRouting($request, $procurement, $procurementAttachment, $status);
        app(\App\Services\ProcurementEmailNotificationService::class)->nextSignatureTurn($procurement, (string)$attachmentId);
        return back()->with('success', 'Attachment routed to its configured signatories.');
    }

    public function routePhaseAttachments(Request $request, Procurement $procurement)
    {
        abort_unless($this->roleHasPermission($request->user(), 'procurement.route'), 403);
        $status = strtoupper((string)$procurement->status);
        abort_unless(in_array($status, ['DRAFT', 'ROUTING', 'RETURNED'], true), 422);
        $mode = ProcurementMode::with(['phases' => fn($q) => $q->orderBy('phase_sequence')->with(['roles', 'attachments.signatories.role'])])->findOrFail($procurement->procurement_mode_id);
        $request->validate(['phase_id' => ['nullable', 'integer']]);
        $defaultPhase = $status === 'DRAFT' ? $mode->phases->first() : $mode->phases->firstWhere('id', (int)$procurement->current_phase_id);
        $phase = $request->filled('phase_id') ? $mode->phases->firstWhere('id', (int)$request->input('phase_id')) : $defaultPhase;
        abort_unless($phase, 422, 'The requested workflow phase is not configured for this procurement.');
        abort_unless($this->userCanWorkOnPhase($request->user(), $procurement, $phase), 403, 'You cannot route attachments in this phase.');
        $candidates = ProcurementAttachment::with('signatories')
            ->where('procurement_id', $procurement->id)
            ->where('phase_id', $phase->id)
            ->whereNotNull('file_path')
            ->where(function ($query) {
                $query->whereIn('workflow_status', ['READY', 'INCOMPLETE'])->orWhereNull('workflow_status');
            })
            ->get()
            ->filter(function ($procurementAttachment) use ($phase) {
                $attachment = $phase->attachments->firstWhere('id', $procurementAttachment->attachment_id);
                if (!$attachment?->signatories?->isNotEmpty()) return false;
                $status = strtoupper((string)$procurementAttachment->workflow_status);
                return in_array($status, ['READY', '', 'INCOMPLETE'], true) && !$procurementAttachment->signatories->contains(fn($slot) => (bool)$slot->is_signed);
            });
        abort_if($candidates->isEmpty(), 422, 'There are no ready attachments with signatories to route.');
        DB::transaction(function () use ($request, $procurement, $candidates, $phase) {
            $lockedProcurement = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array(strtoupper((string)$lockedProcurement->status), ['DRAFT', 'ROUTING', 'RETURNED'], true), 422, 'This purchase request can no longer route attachments.');
            abort_unless($this->userCanWorkOnPhase($request->user(), $lockedProcurement, $phase), 403, 'You cannot route attachments in this phase.');
            foreach ($candidates as $procurementAttachment) {
                $lockedAttachment = ProcurementAttachment::whereKey($procurementAttachment->id)->lockForUpdate()->firstOrFail();
                abort_unless(strtoupper((string)$lockedAttachment->workflow_status) !== 'ROUTING' && !$lockedAttachment->signatories()->where('is_signed', true)->exists(), 422, 'An attachment has already started routing. Refresh and try again.');
                if (strtoupper((string)$lockedAttachment->workflow_status) !== 'READY') $lockedAttachment->forceFill(['workflow_status' => 'READY'])->save();
                $this->startAttachmentRouting($request, $lockedProcurement, $lockedAttachment, strtoupper((string)$lockedProcurement->status));
            }
        });
        foreach ($candidates as $candidate) {
            app(\App\Services\ProcurementEmailNotificationService::class)->nextSignatureTurn($procurement, (string)$candidate->attachment_id);
        }
        return back()->with('success', $candidates->count().' attachment'.($candidates->count() === 1 ? '' : 's').' routed to their configured signatories.');
    }

    private function startAttachmentRouting(Request $request, Procurement $procurement, ProcurementAttachment $procurementAttachment, string $status): void
    {
        $statusFrom = strtoupper((string)$procurement->status);
        $procurementAttachment->forceFill(['workflow_status' => 'ROUTING'])->save();
        if ($statusFrom === 'DRAFT') {
            $initialPhaseId = $procurement->current_phase_id ?: $procurement->mode?->phases()->orderBy('phase_sequence')->value('id') ?: $procurementAttachment->phase_id;
            $procurement->update(['status' => 'ROUTING', 'current_phase_id' => $initialPhaseId]);
        }
        ProcurementHistory::create(['procurement_id' => $procurement->id, 'user_id' => $request->user()->id, 'phase_id' => $procurementAttachment->phase_id, 'action' => 'ATTACHMENT_ROUTING_STARTED', 'status_from' => $statusFrom, 'status_to' => strtoupper((string)$procurement->status), 'remarks' => "Routed \"{$procurementAttachment->file_name}\" to its configured signatories.", 'created_at' => now()]);
    }

    public function proceedNextPhase(Request $request, Procurement $procurement)
    {
        abort_unless($this->roleHasPermission($request->user(), 'procurement.route'), 403);
        $status = strtoupper((string)$procurement->status);
        abort_unless(in_array($status, ['DRAFT', 'ROUTING', 'RETURNED'], true), 422, 'This purchase request cannot proceed in its current status.');
        $mode = ProcurementMode::with(['phases' => fn($q) => $q->orderBy('phase_sequence')->with(['roles', 'attachments.signatories'])])->findOrFail($procurement->procurement_mode_id);
        $phases = $mode->phases->sortBy('phase_sequence')->values();
        $current = $status === 'DRAFT' ? $phases->first() : $phases->firstWhere('id', (int)$procurement->current_phase_id);
        if (!$current) throw ValidationException::withMessages(['phase' => 'No current workflow phase is configured. Configure the mode phases before proceeding.']);
        abort_unless($this->userCanActInPhase($request->user(), $current), 403, 'You are not assigned to the current phase.');
        if (!$this->phaseRequirementsReady($procurement, $current)) throw ValidationException::withMessages(['attachments' => 'The Purchase Request and all required attachments in the current phase must be signed by every configured signatory.']);
        if ($procurement->abc_amount !== null) {
            if (($mode->min_abc ?? 0) > 0 && (float)$procurement->abc_amount < (float)$mode->min_abc) throw ValidationException::withMessages(['abc_amount' => 'The calculated ABC is below the minimum for this procurement mode.']);
            if ($mode->max_abc !== null && (float)$procurement->abc_amount > (float)$mode->max_abc) throw ValidationException::withMessages(['abc_amount' => 'The calculated ABC exceeds the maximum for this procurement mode.']);
        }
        $next = $phases->first(fn($phase) => (int)$phase->phase_sequence > (int)$current->phase_sequence);
        DB::transaction(function () use ($procurement, $current, $next, $status, $request, $mode) {
            $lockedProcurement = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
            abort_unless(strtoupper((string)$lockedProcurement->status) === $status, 409, 'The procurement status changed. Reload and try again.');
            if ($status !== 'DRAFT') abort_unless((int)$lockedProcurement->current_phase_id === (int)$current->id, 409, 'The current phase changed. Reload and try again.');
            abort_unless($this->phaseRequirementsReady($lockedProcurement, $current), 422, 'The Purchase Request and all required attachments in the current phase must be signed by every configured signatory.');
            $changes = [];
            if (!$procurement->pr_number) $changes['pr_number'] = Setting::getNextPrNumberAndIncrement();
            if ($next) {
                $changes['current_phase_id'] = $next->id;
                $changes['status'] = 'ROUTING';
            } else {
                $changes['current_phase_id'] = $current->id;
                $changes['status'] = 'COMPLETED';
            }
            $procurement->update($changes);
            ProcurementHistory::create(['procurement_id' => $procurement->id, 'user_id' => $request->user()->id, 'phase_id' => $next?->id ?? $current->id, 'action' => $next ? 'PHASE_ADVANCED' : 'COMPLETED', 'status_from' => $status, 'status_to' => $next ? 'ROUTING' : 'COMPLETED', 'remarks' => $next ? "Proceeded from {$current->phase_name} to {$next->phase_name}." : "Completed the final workflow phase: {$current->phase_name}.", 'created_at' => now()]);
        });
        $message = $next ? "Proceeded to {$next->phase_name}." : 'Final workflow phase completed. Purchase request marked Completed.';
        return redirect()->route('procurements.show', $procurement)->with('success', $message);
    }

    public function cancelRequest(Request $request, Procurement $procurement)
    {
        abort_unless($this->canManageProcurement($request->user(), $procurement, 'procurement.cancel'), 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($procurement, $request, $data) {
            $procurement = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
            $statusFrom = strtoupper((string)$procurement->status);
            abort_if(in_array($statusFrom, ['ARCHIVED', 'COMPLETED', 'CANCELLED'], true), 422, 'This purchase request cannot be cancelled in its current status.');
            $procurement->update(['status' => 'CANCELLED']);
            ProcurementHistory::create(['procurement_id' => $procurement->id, 'user_id' => $request->user()->id, 'phase_id' => $procurement->current_phase_id, 'action' => 'CANCELLED', 'status_from' => $statusFrom, 'status_to' => 'CANCELLED', 'remarks' => trim($data['reason']), 'created_at' => now()]);
        });
        return redirect()->route('procurements.index')->with('success', 'Procurement Cancelled');
    }

    public function archiveRequest(Request $request, Procurement $procurement)
    {
        abort_unless($this->roleHasPermission($request->user(), 'procurement.archive'), 403);
        abort_if(strtoupper((string)$procurement->status) === 'ARCHIVED', 422, 'This purchase request is already archived.');
        $statusFrom = strtoupper((string)$procurement->status);
        DB::transaction(function () use ($procurement, $request, $statusFrom) {
            $procurement->update(['status' => 'ARCHIVED']);
            ProcurementHistory::create(['procurement_id' => $procurement->id, 'user_id' => $request->user()->id, 'phase_id' => $procurement->current_phase_id, 'action' => 'ARCHIVED', 'status_from' => $statusFrom, 'status_to' => 'ARCHIVED', 'remarks' => 'Purchase request moved to archive.', 'created_at' => now()]);
        });
        return redirect()->route('procurements.index')->with('success', 'Purchase Request moved to Archive');
    }

    public function destroy(Request $request, Procurement $procurement)
    {
        $isArchived = strtoupper((string)$procurement->status) === 'ARCHIVED';

        if ($isArchived) {
            abort_unless($this->hasPermission($request->user(), 'procurement.delete'), 403);
        } else {
            abort_unless($this->hasPermission($request->user(), 'procurement.archive'), 403);
            $statusFrom = strtoupper((string)$procurement->status);

            DB::transaction(function () use ($procurement, $request, $statusFrom) {
                $procurement->update(['status' => 'ARCHIVED']);
                ProcurementHistory::create([
                    'procurement_id' => $procurement->id,
                    'user_id' => $request->user()->id,
                    'phase_id' => $procurement->current_phase_id,
                    'action' => 'ARCHIVED',
                    'status_from' => $statusFrom,
                    'status_to' => 'ARCHIVED',
                    'remarks' => 'Purchase request moved to archive.',
                    'created_at' => now(),
                ]);
            });

            return redirect()->route('procurements.index')->with('success', 'Purchase request moved to archive.');
        }

        $paths = ProcurementAttachment::where('procurement_id', $procurement->id)
            ->pluck('file_path')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // The procurement's foreign keys cascade to its items, history, attachment records, and signatories.
        DB::transaction(fn () => $procurement->delete());

        if ($paths !== []) Storage::disk('procurements')->delete($paths);

        return redirect()->route('procurements.archive')->with('success', 'Archived purchase request and its attachments permanently deleted.');
    }

    public function archivedIndex(Request $request)
    {
        abort_unless($request->user()?->isAdmin() || $this->roleHasPermission($request->user(), 'procurement.archive') || $this->roleHasPermission($request->user(), 'procurement.delete'), 403);
        $query = Procurement::with(['mode', 'endUserOffice', 'creator'])->where('status', 'ARCHIVED');
        $this->applyDirectoryFilters($query, $request, true);
        $procurements = $query->latest()->paginate(15)->withQueryString();
        $modes = ProcurementMode::orderBy('name')->get();
        $offices = Office::orderBy('name')->get();
        return view('procurements.archive', compact('procurements', 'modes', 'offices'));
    }

    private function applyDirectoryFilters($query, Request $request, bool $archived): void
    {
        $user = $request->user();
        if ($user && ! $user->isAdmin() && ! $user->isCentralRole()) {
            $officeIds = $user->offices()->pluck('offices.id');
            $roleIds = $user->roles()->pluck('roles.id');
            $query->where(function ($scope) use ($officeIds, $roleIds) {
                $scope->whereIn('end_user_office_id', $officeIds)
                    ->orWhereHas('mode.phases.roles', fn ($roles) => $roles->whereIn('roles.id', $roleIds)->where('procurement_mode_phase_roles.can_act', true));
            });
        }

        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:DRAFT,ROUTING,RETURNED,REJECTED,COMPLETED,CANCELLED'],
            'mode' => ['nullable', 'integer'],
            'office' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        if (!$archived && filled($data['status'] ?? null)) {
            $query->where('status', $data['status']);
        }
        if (filled($data['mode'] ?? null)) {
            $query->where('procurement_mode_id', $data['mode']);
        }
        if (filled($data['office'] ?? null)) {
            $query->where('end_user_office_id', $data['office']);
        }
        if (filled($data['date_from'] ?? null)) {
            $query->whereDate('pr_date', '>=', $data['date_from']);
        }
        if (filled($data['date_to'] ?? null)) {
            $query->whereDate('pr_date', '<=', $data['date_to']);
        }

        $term = trim($data['search'] ?? '');
        if ($term !== '') {
            $query->where(function ($search) use ($term) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
                $search->where('pr_number', 'like', $like)
                    ->orWhere('purpose', 'like', $like)
                    ->orWhereHas('mode', fn ($mode) => $mode->where('name', 'like', $like)->orWhere('code', 'like', $like))
                    ->orWhereHas('endUserOffice', fn ($office) => $office->where('name', 'like', $like));
                if (ctype_digit($term)) {
                    $search->orWhere('id', (int) $term);
                }
            });
        }
    }

    public function returnToPhase(Request $request, Procurement $procurement)
    {
        abort_unless($this->roleHasPermission($request->user(), 'procurement.return'), 403);
        abort_unless(strtoupper((string)$procurement->status) === 'ROUTING', 422, 'Only a purchase request in routing can be returned to a prior phase.');
        $data = $request->validate(['phase_id' => ['required', 'integer'], 'reason' => ['nullable', 'string', 'max:5000']]);
        $mode = ProcurementMode::with(['phases' => fn($q) => $q->orderBy('phase_sequence')->with('roles')])->findOrFail($procurement->procurement_mode_id);
        $phases = $mode->phases->sortBy('phase_sequence')->values();
        $current = $phases->firstWhere('id', (int)$procurement->current_phase_id);
        $target = $phases->firstWhere('id', (int)$data['phase_id']);
        abort_unless($current && $target && (int)$target->phase_sequence < (int)$current->phase_sequence, 422, 'Choose a phase before the current phase.');
        abort_unless($this->userCanActInPhase($request->user(), $current), 403, 'You are not assigned to the current phase.');
        $statusFrom = strtoupper((string)$procurement->status);
        DB::transaction(function () use ($procurement, $current, $target, $request, $data, $statusFrom) {
            $procurement->update(['current_phase_id' => $target->id, 'returned_from_phase_id' => $current->id, 'status' => 'RETURNED']);
            $remarks = "Returned from {$current->phase_name} to {$target->phase_name}.";
            if (filled($data['reason'] ?? null)) $remarks .= ' Reason: ' . trim($data['reason']);
            ProcurementHistory::create(['procurement_id' => $procurement->id, 'user_id' => $request->user()->id, 'phase_id' => $target->id, 'action' => 'RETURNED', 'status_from' => $statusFrom, 'status_to' => 'RETURNED', 'remarks' => $remarks, 'created_at' => now()]);
        });
        return redirect()->route('procurements.show', $procurement)->with('success', "PR Returned to {$target->phase_name}");
    }

    private function hasPermission(?User $user, string $permission): bool
    {
        if (!$user) return false;
        if ($user->isAdmin()) return true;
        if ($user->roles()->whereHas('permissions', fn($q) => $q->where('name', $permission))->exists()) return true;
        $aliases = [
            'procurement.add' => ['can_add', 'can_create', 'procurement.add'],
            'procurement.create' => ['can_add', 'can_create', 'procurement.create'],
            'procurement.edit' => ['can_edit', 'procurement.edit'],
            'procurement.delete' => ['can_delete', 'procurement.delete'],
            'procurement.cancel' => ['can_cancel', 'procurement.cancel'],
            'procurement.archive' => ['can_archive', 'procurement.archive'],
            'procurement.route' => ['can_route', 'procurement.route'],
            'procurement.return' => ['can_return', 'procurement.return'],
            'procurement.complete' => ['can_complete', 'procurement.complete'],
        ];
        foreach ($aliases[$permission] ?? [$permission] as $code) {
            if ($user->roles()->whereHas('privileges', fn($q) => $q->where('code', $code))->exists()) return true;
        }
        return false;
    }

    private function roleHasPermission(?User $user, string $permission): bool
    {
        return $this->hasPermission($user, $permission);
    }

    private function canManageProcurement(?User $user, Procurement $procurement, string $permission): bool
    {
        if (! $user) return false;
        if ($user->isAdmin() || (int) $user->id === (int) $procurement->created_by) return true;

        $inOriginOffice = $user->offices()->where('offices.id', $procurement->end_user_office_id)->exists();
        return $this->hasPermission($user, $permission) && ($user->isCentralRole() || $inOriginOffice);
    }

    private function userCanActInPhase(?User $user, ProcurementModePhase $phase): bool
    {
        if (!$user) return false;
        $assignedRoleIds = $phase->roles
            ->filter(fn($role) => (bool)($role->pivot?->can_act ?? true))
            ->pluck('id');
        return $assignedRoleIds->isNotEmpty() && $user->roles()->whereIn('roles.id', $assignedRoleIds)->exists();
    }

    private function canEditProcurement(?User $user, Procurement $procurement): bool
    {
        if (! $user) return false;
        if ($user->isAdmin() || (int) $user->id === (int) $procurement->created_by) return true;

        $inOriginOffice = $user->offices()->where('offices.id', $procurement->end_user_office_id)->exists();
        return $this->hasPermission($user, 'procurement.edit') && ($user->isCentralRole() || $inOriginOffice);
    }

    private function canEditPrForm(?User $user, Procurement $procurement): bool
    {
        if (! $user || ! in_array(strtoupper((string)$procurement->status), ['DRAFT', 'RETURNED'], true)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $hasModifyPermission = $this->hasPermission($user, 'procurement.modify_pr_form');
        $isInScope = (int) $user->id === (int) $procurement->created_by
            || $user->isCentralRole()
            || $user->offices()->where('offices.id', $procurement->end_user_office_id)->exists();

        return $hasModifyPermission && $isInScope;
    }

    private function userCanWorkOnPhase(?User $user, Procurement $procurement, ProcurementModePhase $phase): bool
    {
        if (!$this->userCanActInPhase($user, $phase)) return false;
        $status = strtoupper((string)$procurement->status);
        if ($status === 'DRAFT') return true;
        if (!in_array($status, ['ROUTING', 'RETURNED'], true)) return false;
        if (!$procurement->current_phase_id) return false;
        $currentSequence = ProcurementModePhase::whereKey($procurement->current_phase_id)->value('phase_sequence');
        return $currentSequence !== null && (int)$phase->phase_sequence >= (int)$currentSequence;
    }

    private function isAttachmentMandatory($attachment): bool
    {
        foreach (['is_mandatory', 'is_required', 'mandatory', 'required'] as $key) if (array_key_exists($key, $attachment->getAttributes())) return (bool)$attachment->getAttribute($key);
        return true;
    }

    private function phaseRequirementsReady(Procurement $procurement, $phase): bool
    {
        if ((int)($phase->phase_sequence ?? 0) === 1) {
            $prForm = $this->resolvePurchaseRequestForm();
            if (!$prForm) return false;
            $prRecord = ProcurementAttachment::where('procurement_id', $procurement->id)->whereNull('attachment_id')->where('form_id', $prForm->id)->first();
            if (!$prRecord?->file_path || !$prRecord->is_completed) return false;
            if (!$procurement->signatories()->exists()) return false;
            if (strtoupper((string)$prRecord->workflow_status) !== 'COMPLETED') return false;
            if ($procurement->signatories()->where('is_signed', false)->exists()) return false;
        }
        $attachments = $phase->attachments;
        if ($attachments->isEmpty()) return true;
        $required = $attachments->filter(fn($attachment) => $this->isAttachmentMandatory($attachment));
        if ($required->isEmpty()) return true;
        $procurementAttachments = ProcurementAttachment::with('signatories')->where('procurement_id', $procurement->id)->whereIn('attachment_id', $required->pluck('id'))->get()->keyBy('attachment_id');
        foreach ($required as $attachment) {
            $procurementAttachment = $procurementAttachments->get($attachment->id);
            if (!$procurementAttachment || !filled($procurementAttachment->file_path)) return false;
            $configured = $attachment->signatories ?? collect();
            if ($configured->isEmpty()) continue;
            if (strtoupper((string)$procurementAttachment->workflow_status) !== 'COMPLETED') return false;
            $signedRoles = $procurementAttachment->signatories->where('is_signed', true)->pluck('configured_role_id')->filter()->map(fn($id) => 'role:'.$id);
            $signedSequences = $procurementAttachment->signatories->where('is_signed', true)->pluck('sequence')->map(fn($sequence) => 'sequence:'.$sequence);
            $requiredSignatories = $configured->map(fn($step, $index) => $step->role_id ? 'role:'.$step->role_id : 'sequence:'.($step->sequence ?? $index + 1))->unique();
            if ($requiredSignatories->diff($signedRoles->merge($signedSequences))->isNotEmpty()) return false;
        }
        return true;
    }

    private function renderFormHtml(?Form $form, $offices, $fundClusters, ?string $prNumber = null): ?string
    {
        return app(ProcurementFormTemplateRenderer::class)->render($form, $offices, $fundClusters, $prNumber);
    }

    /** Validate declared data-required inputs for phase forms without coupling them to PR fields. */
    private function validateBoundFormRequiredInputs(?Form $form, array $fields, ?Procurement $procurement = null): void
    {
        if (!$form?->path || !Storage::disk('private')->exists($form->path)) return;
        $html = Storage::disk('private')->get($form->path);
        $collectionByAlias = [];
        preg_match_all('/<[a-z][a-z0-9:-]*\b(?=[^>]*\bdata-each\s*=\s*(["\'])(.*?)\1)(?=[^>]*\bdata-as\s*=\s*(["\'])(.*?)\3)[^>]*>/is', $html, $collections, PREG_SET_ORDER);
        foreach ($collections as $collection) $collectionByAlias[html_entity_decode($collection[4], ENT_QUOTES | ENT_HTML5)] = html_entity_decode($collection[2], ENT_QUOTES | ENT_HTML5);
        preg_match_all('/<([a-z][a-z0-9:-]*)\b(?=[^>]*\bdata-required\b)([^>]*)>/is', $html, $elements, PREG_SET_ORDER);
        foreach ($elements as $element) {
            if (!preg_match('/\bdata-input\s*=\s*(["\'])(.*?)\1/is', $element[2], $binding)) continue;
            $path = html_entity_decode($binding[2], ENT_QUOTES | ENT_HTML5);
            $attributes = $element[2];
            $readAttribute = static function (string $name) use ($attributes): ?string {
                return preg_match('/\b'.preg_quote($name, '/').'\s*=\s*(["\'])(.*?)\1/is', $attributes, $match)
                    ? html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5)
                    : null;
            };
            $truthy = static fn($value): bool => in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
            $showIf = $readAttribute('data-show-if');
            $showIfNot = $readAttribute('data-show-if-not');
            if ($showIf !== null && !$truthy($fields[$showIf] ?? null)) continue;
            if ($showIfNot !== null && $truthy($fields[$showIfNot] ?? null)) continue;

            $requiredCollection = $readAttribute('data-required-collection');
            // Older saved form copies may predate data-required-collection. Infer the
            // repeated collection from the template's data-each/data-as declaration.
            if ($requiredCollection === null && str_contains($path, '.')) {
                $alias = substr($path, 0, strpos($path, '.'));
                $requiredCollection = $collectionByAlias[$alias] ?? null;
            }
            if ($requiredCollection !== null) {
                $field = str_contains($path, '.') ? substr($path, strrpos($path, '.') + 1) : $path;
                if ($requiredCollection === 'procurement.items' && $field === 'cost' && $procurement) {
                    $items = $procurement->items()->where('is_lot', false)->get(['id']);
                    if ($items->isEmpty()) {
                        throw ValidationException::withMessages(['fields' => 'Add at least one non-lot procurement item before compiling this form.']);
                    }
                    foreach ($items as $item) {
                        $value = $fields["procurement.items.{$item->id}.cost"] ?? null;
                        if ($value === null || (is_string($value) && trim($value) === '')) {
                            throw ValidationException::withMessages(['fields' => "Complete the required form field: item.cost (item {$item->id})."]);
                        }
                    }
                    continue;
                }
                $prefix = rtrim($requiredCollection, '.').'.';
                $values = [];
                foreach ($fields as $fieldPath => $fieldValue) {
                    if (preg_match('/^'.preg_quote($prefix, '/').'\d+\.'.preg_quote($field, '/').'$/', (string)$fieldPath)) $values[] = $fieldValue;
                }
                if (!$values || collect($values)->contains(fn($value) => is_array($value) ? collect($value)->every(fn($entry) => trim((string)$entry) === '') : trim((string)($value ?? '')) === '')) {
                    throw ValidationException::withMessages(['fields' => "Complete every required {$field} value in {$requiredCollection}."]);
                }
                continue;
            }
            $value = $fields[$path] ?? null;
            if (is_string($value) ? trim($value) === '' : ($value === null || $value === [])) {
                throw ValidationException::withMessages(['fields' => "Complete the required form field: {$path}."]);
            }
        }
    }

    private function buildAttachmentForms(ProcurementMode $mode, $offices, $fundClusters, ?Form $prForm, ?string $prFormHtml, ?string $prNumber = null): array
    {
        $result = [];
        foreach ($mode->phases as $phase) foreach ($phase->attachments as $attachment) {
            $bound = $attachment->form;
            $mainPr = $bound && $prForm && (string)$bound->id === (string)$prForm->id;
            // All stored templates are treated as inert HTML; the PR uses its existing canvas editor.
            $html = $mainPr
                ? ($prFormHtml ?? $this->renderFormHtml($bound, $offices, $fundClusters, $prNumber))
            : app(ProcurementFormTemplateRenderer::class)->render($bound, $offices, $fundClusters, $prNumber);
            $result[$attachment->id] = ['html' => $html, 'form_id' => $attachment->form_id ?? $bound?->id, 'main_pr' => (bool)$mainPr, 'config' => is_array($bound?->configuration ?? null) ? $bound->configuration : (is_array($prForm?->configuration ?? null) ? $prForm->configuration : [])];
        }
        return $result;
    }

    /** Total amount (quantity x unit cost) of every entered row, mirroring syncProcurementDataFromPrForm(). */
    private function calculatedTotalFromFields(array $fields): float
    {
        $total = 0.0;
        foreach ($this->itemsFromFormFields($fields) as $item) {
            $qty = $this->nullableNumber($item['quantity'] ?? null);
            $cost = $this->nullableNumber($item['cost'] ?? null);
            if ($qty !== null && $cost !== null) $total += $qty * $cost;
            if (!empty($item['is_lot'])) {
                foreach (($item['sub_items'] ?? []) as $sub) {
                    $subQty = $this->nullableNumber($sub['quantity'] ?? null);
                    $subCost = $this->nullableNumber($sub['cost'] ?? null);
                    if ($subQty !== null && $subCost !== null) $total += $subQty * $subCost;
                }
            }
        }
        return round($total, 2);
    }

    private function hasAtLeastOneEnteredItem(array $fields): bool
    {
        $rows = [];
        foreach ($fields as $key => $value) {
            if (preg_match('/^items\[(\d+)\]\[sub_items\]\[(\d+)\]\[([^\]]+)\]$/', (string)$key, $match)) {
                $rows[(int)$match[1]]['sub_items'][(int)$match[2]][$match[3]] = is_array($value) ? reset($value) : $value;
            } elseif (preg_match('/^items\[(\d+)\]\[([^\]]+)\]$/', (string)$key, $match)) {
                $rows[(int)$match[1]][$match[2]] = is_array($value) ? reset($value) : $value;
            }
        }
        $hasData = static function (array $row): bool {
            foreach (['unit', 'description', 'cost'] as $field) {
                $value = $row[$field] ?? '';
                if (is_array($value)) $value = collect($value)->first(fn($item) => trim((string)$item) !== '', '');
                if (trim((string)$value) !== '') return true;
            }
            $quantity = $row['quantity'] ?? '';
            if (is_array($quantity)) $quantity = reset($quantity);
            $quantity = str_replace(',', '', trim((string)$quantity));
            return $quantity !== '' && is_numeric($quantity) && (float)$quantity !== 1.0;
        };
        foreach ($rows as $row) {
            if (!empty($row['is_lot'])) {
                // A lot heading is not an item; at least one actual lot member is required.
                foreach (($row['sub_items'] ?? []) as $subItem) if ($hasData($subItem)) return true;
            } elseif ($hasData($row)) return true;
        }
        return false;
    }

    private function purchaseRequestItemRowsError(array $fields): ?string
    {
        $items = $this->itemsFromFormFields($fields);
        if (!$items) return 'Add at least one item row to the Purchase Request.';
        foreach ($items as $item) {
            if (!empty($item['is_lot'])) {
                // The lot parent is the priced/quantified line. Members only
                // need descriptions so a zero or blank member cost is valid.
                if (trim((string)($item['description'] ?? '')) === '') return 'Enter a description for every lot.';
                $quantity = $this->nullableNumber($item['quantity'] ?? null);
                if ($quantity === null || $quantity < 1) return 'Quantity must be at least 1.00 for every lot.';
                $cost = $this->nullableNumber($item['cost'] ?? null);
                if ($cost === null || $cost < 1) return 'Unit cost must be at least 1.00 for every lot.';

                $members = $item['sub_items'] ?? [];
                if (!$members) return 'Every lot must have at least one item entered.';
                foreach ($members as $member) {
                    if (trim((string)($member['description'] ?? '')) === '') return 'Enter a description for every lot member.';
                }
                continue;
            }

            if (trim((string)($item['unit'] ?? '')) === '') return 'Enter a unit for every standalone item.';
            if (trim((string)($item['description'] ?? '')) === '') return 'Enter a description for every item.';
            $quantity = $this->nullableNumber($item['quantity'] ?? null);
            if ($quantity === null || $quantity < 1) return 'Quantity must be at least 1.00 for every item.';
            $cost = $this->nullableNumber($item['cost'] ?? null);
            if ($cost === null || $cost < 1) return 'Unit cost must be at least 1.00 for every item.';
        }
        return null;
    }

    private function syncProcurementFieldsAndItems(Procurement $procurement, array $fields, bool $isPurchaseRequestForm, ?User $user = null): void
    {
        $scalar = static fn($value) => is_array($value) ? (reset($value) ?: null) : $value;
        $changes = [];
        foreach (['purpose', 'fund_cluster', 'responsibility_code', 'pr_date'] as $key) if (array_key_exists($key, $fields)) $changes[$key] = $scalar($fields[$key]) !== '' ? $scalar($fields[$key]) : null;
        if (isset($changes['purpose']) && mb_strlen((string)$changes['purpose']) > 5000) {
            throw ValidationException::withMessages(['purpose' => 'The purchase purpose cannot exceed 5,000 characters.']);
        }
        if (isset($changes['responsibility_code']) && mb_strlen((string)$changes['responsibility_code']) > 255) {
            throw ValidationException::withMessages(['responsibility_code' => 'The responsibility center code cannot exceed 255 characters.']);
        }
        if (isset($changes['fund_cluster']) && $changes['fund_cluster'] !== $procurement->fund_cluster
            && !FundCluster::query()->where('code', $changes['fund_cluster'])->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['fund_cluster' => 'Choose an active fund cluster.']);
        }
        if (isset($changes['pr_date']) && strtotime((string)$changes['pr_date']) === false) {
            throw ValidationException::withMessages(['pr_date' => 'Enter a valid Purchase Request date.']);
        }
        if (array_key_exists('end_user_office_id', $fields)) {
            $officeValue = trim((string)$scalar($fields['end_user_office_id']));
            if ($officeValue !== '' && !ctype_digit($officeValue)) {
                throw ValidationException::withMessages(['end_user_office_id' => 'Choose an office using its office ID.']);
            }
            $office = $officeValue === '' ? null : Office::query()->whereKey((int)$officeValue)
                ->where(fn ($query) => $query->where('is_active', true)->orWhereKey($procurement->end_user_office_id))
                ->first();
            $officeId = $office?->id;
            if ($officeValue !== '' && !$office) throw ValidationException::withMessages(['end_user_office_id' => 'Choose a valid office.']);
            if ($officeId && $user && ! $user->isAdmin() && ! $user->isCentralRole()
                && ! $user->offices()->where('offices.id', $officeId)->exists()) {
                throw ValidationException::withMessages(['end_user_office_id' => 'Choose an office assigned to your account.']);
            }
            $changes['end_user_office_id'] = $officeId;
        }
        if ($changes) $procurement->update($changes);
        if (!$isPurchaseRequestForm) return;

        $items = $this->itemsFromFormFields($fields);
        $this->validateDraftItemPayload($items);
        $procurement->update(['abc_amount' => $this->syncProcurementItems($procurement, $items)]);
    }

    /**
     * Reconcile PR rows by their persisted IDs so editing one row does not recreate
     * every item or detach lot members. IDs are always scoped to this procurement,
     * while row moves between standalone and lot positions remain supported.
     */
    private function syncProcurementItems(Procurement $procurement, array $items): float
    {
        $existing = ProcurementItem::query()
            ->where('procurement_id', $procurement->id)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $retainedIds = [];
        $lineNo = 1;
        $total = 0.0;

        $upsert = function (array $data, ?ProcurementItem $parent, bool $isLot) use ($procurement, $existing, &$retainedIds, &$lineNo): ProcurementItem {
            $id = $data['id'] ?? null;
            if ($id !== null && $id !== '') {
                if (!is_numeric($id) || (int)$id < 1 || !$existing->has((int)$id)) {
                    throw ValidationException::withMessages(['items' => 'An item row does not belong to this purchase request.']);
                }
                if (isset($retainedIds[(int)$id])) {
                    throw ValidationException::withMessages(['items' => 'An item row was submitted more than once.']);
                }
                $row = $existing->get((int)$id);
            } else {
                $row = new ProcurementItem();
            }

            $description = trim((string)($data['description'] ?? ''));
            $unit = $isLot ? 'Lot' : trim((string)($data['unit'] ?? ''));
            $quantity = $this->nullableNumber($data['quantity'] ?? null);
            $cost = $this->nullableNumber($data['cost'] ?? null);
            $row->fill([
                'procurement_id' => $procurement->id,
                'parent_id' => $parent?->id,
                'line_no' => $lineNo++,
                'is_lot' => $isLot,
                'item_bank_item_id' => $isLot ? null : $this->resolveSelectedCatalogItemId(
                    $data['item_bank_item_id'] ?? null,
                    $description,
                    $unit !== '' ? $unit : null
                ),
                'unit' => $unit !== '' ? $unit : null,
                'description' => $description !== '' ? $description : null,
                'quantity' => $quantity,
                'cost' => $cost,
            ]);
            $row->save();
            $retainedIds[(int)$row->id] = true;

            return $row;
        };

        $hasEnteredData = static function (array $row): bool {
            $quantity = trim(str_replace(',', '', (string)($row['quantity'] ?? '')));
            return trim((string)($row['unit'] ?? '')) !== ''
                || trim((string)($row['description'] ?? '')) !== ''
                || trim((string)($row['cost'] ?? '')) !== ''
                || ($quantity !== '' && is_numeric($quantity) && (float)$quantity !== 1.0);
        };

        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (!empty($item['is_lot'])) {
                $members = array_values(array_filter(
                    (array)($item['sub_items'] ?? []),
                    fn ($member) => is_array($member) && $hasEnteredData($member)
                ));
                $headerDescription = trim((string)($item['description'] ?? ''));
                $headerQuantity = trim(str_replace(',', '', (string)($item['quantity'] ?? '')));
                $hasHeaderData = $headerDescription !== ''
                    || trim((string)($item['cost'] ?? '')) !== ''
                    || ($headerQuantity !== '' && is_numeric($headerQuantity) && (float)$headerQuantity !== 1.0);
                if (!$members && !$hasHeaderData) continue;

                $lot = $upsert($item, null, true);
                $quantity = $this->nullableNumber($item['quantity'] ?? null);
                $cost = $this->nullableNumber($item['cost'] ?? null);
                if ($quantity !== null && $cost !== null) $total += $quantity * $cost;
                foreach ($members as $member) {
                    $child = $upsert($member, $lot, false);
                    $childQuantity = $this->nullableNumber($member['quantity'] ?? null);
                    $childCost = $this->nullableNumber($member['cost'] ?? null);
                    if ($childQuantity !== null && $childCost !== null) $total += $childQuantity * $childCost;
                }
                continue;
            }

            if (!$hasEnteredData($item)) continue;
            $row = $upsert($item, null, false);
            $quantity = $this->nullableNumber($item['quantity'] ?? null);
            $cost = $this->nullableNumber($item['cost'] ?? null);
            if ($quantity !== null && $cost !== null) $total += $quantity * $cost;
        }

        $staleIds = $existing->keys()->map(fn ($id) => (int)$id)->reject(fn ($id) => isset($retainedIds[$id]));
        if ($staleIds->isNotEmpty()) {
            ProcurementItem::query()->where('procurement_id', $procurement->id)
                ->whereNotNull('parent_id')->whereIn('id', $staleIds)->delete();
            ProcurementItem::query()->where('procurement_id', $procurement->id)
                ->whereNull('parent_id')->whereIn('id', $staleIds)->delete();
        }

        return round($total, 2);
    }

    /**
     * Link only an unambiguous exact description/unit match to the canonical catalog.
     * Similar or ambiguous items remain unlinked for a person to review.
     */
    private function matchingCatalogItemId(string $description, ?string $unit): ?int
    {
        $description = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $description) ?? $description));
        if ($description === '' || mb_strlen($description) > 255) return null;
        $unit = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $unit) ?? (string) $unit));
        $cacheKey = $description."\0".$unit;
        if (array_key_exists($cacheKey, $this->catalogItemMatchCache)) {
            return $this->catalogItemMatchCache[$cacheKey];
        }

        $catalogQuery = ItemBankItem::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(TRIM(description)) = ?', [$description]);
        if ($unit === '') {
            $catalogQuery->where(function ($query) {
                $query->whereNull('unit')->orWhereRaw("TRIM(unit) = ''");
            });
        } else {
            $catalogQuery->whereRaw('LOWER(TRIM(unit)) = ?', [$unit]);
        }

        $matches = $catalogQuery->limit(2)->pluck('id');

        return $this->catalogItemMatchCache[$cacheKey] = $matches->count() === 1 ? (int) $matches->first() : null;
    }

    /** Keep an explicit picker link only when its active catalog record matches the submitted text. */
    private function resolveSelectedCatalogItemId(mixed $selectedId, string $description, ?string $unit): ?int
    {
        if (is_numeric($selectedId)) {
            $item = ItemBankItem::query()->where('is_active', true)->find((int) $selectedId);
            $normalize = fn ($value) => mb_strtolower(trim(preg_replace('/\\s+/u', ' ', (string) $value) ?? (string) $value));
            if ($item && $normalize($item->description) === $normalize($description)
                && ($unit === null || $normalize($item->unit) === $normalize($unit))) {
                return (int) $item->id;
            }
        }

        return $this->matchingCatalogItemId($description, $unit);
    }

    /** Reject malformed or out-of-range item values before replacing persisted procurement lines. */
    private function validateDraftItemPayload(array $items): void
    {
        if (count($items) > 500) {
            throw ValidationException::withMessages(['items' => 'A procurement can contain no more than 500 item rows.']);
        }

        $validateRow = static function (mixed $row, bool $lotMember = false): void {
            if (!is_array($row)) {
                throw ValidationException::withMessages(['items' => 'The submitted item data has an invalid structure.']);
            }
            foreach (['description' => 2000, 'unit' => 50] as $field => $maxLength) {
                if (isset($row[$field]) && (!is_string($row[$field]) && !is_numeric($row[$field]) || mb_strlen((string)$row[$field]) > $maxLength)) {
                    throw ValidationException::withMessages(['items' => 'An item description or unit is invalid or too long.']);
                }
            }
            foreach (['quantity' => 999999999.999, 'cost' => 9999999999999.99] as $field => $maximum) {
                $value = $row[$field] ?? null;
                if ($value === null || $value === '') continue;
                if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0 || (float)$value > $maximum) {
                    throw ValidationException::withMessages(['items' => "The item {$field} is outside the supported range."]);
                }
            }
            if (!$lotMember && isset($row['sub_items']) && (!is_array($row['sub_items']) || count($row['sub_items']) > 100)) {
                throw ValidationException::withMessages(['items' => 'A lot may contain no more than 100 sub-items.']);
            }
        };

        foreach ($items as $row) {
            $validateRow($row);
            foreach (($row['sub_items'] ?? []) as $subItem) $validateRow($subItem, true);
        }
    }

    private function assertOfficeChangeInScope(User $user, mixed $officeId, ?int $existingOfficeId = null): void
    {
        if ($officeId === null || $officeId === '') return;
        $officeQuery = Office::query();
        if (is_numeric($officeId)) $officeQuery->whereKey((int) $officeId);
        else $officeQuery->where('code', trim((string) $officeId));
        $office = $officeQuery->first();
        if (!$office || (!$office->is_active && (int)$office->id !== (int)$existingOfficeId)) {
            throw ValidationException::withMessages(['end_user_office_id' => 'Choose an active office.']);
        }
        if (!$user->isAdmin() && !$user->isCentralRole() && !$user->offices()->where('offices.id', $office->id)->exists()) {
            throw ValidationException::withMessages(['end_user_office_id' => 'Choose an office assigned to your account.']);
        }
    }

}

{{-- Prepare the phase collections and values shared by the current, previous, and upcoming phase views. --}}
@php
    $procurement = $procurement ?? null;
    $selectedMode->loadMissing(['phases' => fn($q) => $q->orderBy('phase_sequence')->with(['attachments.form', 'attachments.signatories.role'])]);
    $phases = $selectedMode->phases->sortBy('phase_sequence')->values();
    $config = is_array($prForm?->configuration ?? null) ? $prForm->configuration : [];
    $margins = $config['margins'] ?? ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10];
    $activePhaseId = $procurement->current_phase_id ?? $phases->first()?->id;
    $activePhase = $phases->firstWhere('id', $activePhaseId) ?? $phases->first();
    // $compactPhaseView = request()->routeIs('procurements.show');
    $compactPhaseView = false;
    $workspaceStatus = strtoupper((string)$procurement->status);
    $currentWorkspacePhases = $phases;
    $existingItems = $existingItems ?? [];
    $existingPrData = [
        'items' => $existingItems,
        'fields' => array_merge((array)($savedPrFormFields ?? []), [
            'purpose' => $procurement->purpose,
            'fund_cluster' => $procurement->fund_cluster,
            'responsibility_code' => $procurement->responsibility_code,
            'pr_date' => $procurement->pr_date
                ? \Illuminate\Support\Carbon::parse($procurement->pr_date)->format('Y-m-d')
                : null,
            'end_user_office_id' => $procurement->end_user_office_id,
        ]),
    ];
@endphp
<div
    id="pr-workspace"
    x-data="prWorkspace()"
    x-init="initWorkspace()"
    :data-scale="scale"
    @pr-completeness-change.window="isComplete = $event.detail.complete"
    @pr-set-scale.window="setScale($event.detail.scale)"
    @pr-sign-mode.window="signMode = !!$event.detail.active; if (!signMode) signPlacement = null"
    @pr-signature-placed.window="signPlacement = $event.detail"
    @pr-signature-cleared.window="signPlacement = null"
    @pr-attachment-completion.window="markAttachmentCompleted($event.detail.id, $event.detail.complete); if (selectedAttachment && String($event.detail.id) === String(selectedAttachment.id)) formComplete = !!$event.detail.complete"
    @pr-form-changed.window="queueAutoSave($event.detail.id)"
    @open-pr-form.window="selectAttachment('pr', 'Purchase Request', true, true, 'form', @js(isset($procurementPrAttachment) && $procurementPrAttachment?->file_path ? route('procurements.attachments.file', ['procurement' => $procurement->id, 'attachmentId' => 'pr']) : null), @js($procurementPrAttachment?->file_name)); canEdit=true"
    @open-pr-pdf.window="selectAttachment('pr', 'Purchase Request', false, true, 'upload', @js(isset($procurementPrAttachment) && $procurementPrAttachment?->file_path ? route('procurements.attachments.file', ['procurement' => $procurement->id, 'attachmentId' => 'pr']) : null), @js($procurementPrAttachment?->file_name)); canEdit=false"
    class="pr-workspace relative z-0 min-w-0 {{ $compactPhaseView ? 'h-full' : '' }}"
>
    <form action="{{ route('procurements.update', $procurement) }}" method="POST" enctype="multipart/form-data" id="pr-form" data-can-edit="{{ ($canEdit ?? false) ? '1' : '0' }}" data-can-edit-pr-form="{{ ($canEditPrForm ?? false) ? '1' : '0' }}" data-pr-fully-signed="{{ ($prFullySigned ?? false) ? '1' : '0' }}" data-abc-min="{{ $selectedMode->min_abc ?? 0 }}" data-abc-max="{{ $selectedMode->max_abc ?? '' }}" data-has-phases="{{ $phases->isNotEmpty() ? '1' : '0' }}" data-attachment-save-url="{{ route('procurements.attachments.save', ['procurement' => $procurement->id, 'attachmentId' => '__ID__']) }}" data-rfq-document-toggle-url="{{ route('procurements.attachments.rfq-documents.toggle', ['procurement' => $procurement->id, 'attachmentId' => '__ID__', 'rfqDocumentId' => '__DOCUMENT__']) }}" data-signing-status-url="{{ route('procurements.attachments.signing-status', ['procurement' => $procurement->id, 'attachmentId' => '__ID__']) }}" data-sign-url="{{ route('procurements.attachments.sign', ['procurement' => $procurement->id, 'attachmentId' => '__ID__']) }}" class="min-w-0 {{ $compactPhaseView ? 'h-full' : '' }}" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="procurement_mode_id" value="{{ $selectedMode->id }}">
        <input type="hidden" name="form_id" id="active-form-id" value="{{ $prForm?->id }}">
        <input type="hidden" name="save_action" id="save-action" value="draft">
        <input type="hidden" name="attachment_fields" id="attachment-fields" value="">
        <input type="hidden" name="attachment_completion_data" id="attachment-completion-data" value="">
        <script type="application/json" id="existing-pr-data">@json($existingPrData)</script>
        <script type="application/json" id="existing-attachment-data">@json($existingAttachmentData ?? [])</script>
        <script type="application/json" id="pr-template-schema">@json($prTemplateSchema ?? [])</script>
        <section id="pr-left-column" class="min-w-0 space-y-5">

            @if($signingTurnItems->isNotEmpty())
            <section id="signing-turn-alert" aria-label="Attachments awaiting your signature" class="rounded-2xl border-2 border-amber-300 bg-amber-100/90 p-4 shadow-md shadow-amber-900/10 ring-4 ring-amber-300/20 dark:border-amber-700 dark:bg-amber-950/50 dark:ring-amber-500/10">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-9 w-9 items-center justify-center rounded-xl bg-amber-400 text-amber-950 shadow-sm dark:bg-amber-500"><i class="fa-solid fa-signature"></i></span>
                    <div><h2 class="text-sm font-black text-amber-950 dark:text-amber-100">Your turn to sign</h2><p class="text-[10px] font-semibold text-amber-900 dark:text-amber-200">The following routed documents are waiting for your signatory role.</p></div>
                </div>
                <div class="mt-3 space-y-2">
                    @foreach($signingTurnItems as $candidate)
                    <button type="button" @click="selectAttachment(@js($candidate['id']), @js($candidate['name']), false, false, 'upload', @js($candidate['url']), @js($candidate['file_name']))" class="flex w-full items-center justify-between gap-3 rounded-xl border border-amber-300 bg-white px-3 py-2.5 text-left shadow-sm transition hover:border-amber-500 hover:bg-amber-50 dark:border-amber-800 dark:bg-slate-900 dark:hover:bg-amber-950/50">
                        <span class="flex min-w-0 items-center gap-2"><i class="fa-solid fa-file-signature shrink-0 text-amber-700 dark:text-amber-300"></i><span class="min-w-0"><span class="block truncate text-[10px] font-extrabold text-slate-900 dark:text-slate-100">{{ $candidate['name'] }}</span><span class="block truncate text-[9px] text-slate-500 dark:text-slate-400">Signing as {{ $candidate['role_name'] }} · {{ $candidate['file_name'] ?: 'PDF attachment' }}</span></span></span>
                        <span class="shrink-0 rounded-lg bg-amber-500 px-2.5 py-1.5 text-[9px] font-black text-amber-950 shadow-sm">Sign as {{ $candidate['role_name'] }}</span>
                    </button>
                    @endforeach
                </div>
            </section>
            @endif
            
            {{-- Main phase cards hold the forms, uploads, signing actions, and routing controls. --}}
            <div id="phase-timeline-view" class="space-y-5" x-show="!selectedAttachment">

                @if($currentWorkspacePhases->isNotEmpty())
                <div class="relative space-y-6 pl-[64px]">
                @foreach($currentWorkspacePhases as $phase)
                    @php
                        $phaseTerminal = in_array($workspaceStatus, ['COMPLETED', 'ARCHIVED', 'CANCELLED', 'REJECTED'], true);
                        $phaseActive = (string)$phase->id === (string)$activePhaseId && !$phaseTerminal;
                        $phasePassed = in_array($workspaceStatus, ['COMPLETED', 'ARCHIVED'], true) || ($procurement->currentPhase && $phase->phase_sequence < $procurement->currentPhase->phase_sequence);
                        $canWorkPhaseAttachments = !$phaseTerminal && (bool)($phaseAccess[(string)$phase->id] ?? false);
                        $canEditPhaseAttachments = $canWorkPhaseAttachments && !$phasePassed;
                        $canRouteThisPhase = ($canRouteAttachments ?? false) && $canEditPhaseAttachments;
                        $phaseComplete = $phasePassed;
                        $hasMandatoryAttachments = $phase->attachments->contains(fn($attachment) => (bool)($attachment->is_mandatory ?? $attachment->is_required ?? $attachment->required ?? true));
                        $mandatoryAttachments = $phase->attachments->filter(function ($attachment) {
                            foreach (['is_mandatory', 'is_required', 'mandatory', 'required'] as $key) {
                                if (array_key_exists($key, $attachment->getAttributes())) return (bool)$attachment->getAttribute($key);
                            }
                            return true;
                        });
                        $phaseMandatoryReady = $mandatoryAttachments->every(function ($attachment) use ($procurementAttachments) {
                            $submission = $procurementAttachments->get($attachment->id);
                            return in_array(strtoupper((string)($submission?->workflow_status ?? 'NOT_READY')), ['READY', 'ROUTING', 'COMPLETED'], true);
                        });
                        $hasReadyAttachmentsToRoute = $phase->attachments->contains(fn($attachment) => $attachment->signatories->isNotEmpty() && $procurementAttachments->get($attachment->id)?->workflow_status === 'READY' && !$procurementAttachments->get($attachment->id)?->signatories?->contains(fn($slot) => (bool)$slot->is_signed));
                    @endphp
                    <div class="relative">
                    @if(!$loop->last)<span aria-hidden="true" class="absolute -left-[42px] top-[68px] bottom-[-68px] z-0 w-1 rounded-full {{ $phasePassed ? 'bg-emerald-500' : ($phaseActive ? ($phaseMandatoryReady ? 'bg-blue-600' : 'bg-slate-400 dark:bg-slate-600') : 'bg-slate-300 dark:bg-slate-700') }}"></span>@endif
                    <span aria-hidden="true" class="absolute -left-[64px] top-5 z-10 flex h-12 w-12 items-center justify-center rounded-full border-0 text-sm font-black shadow-md transition {{ $phaseActive ? 'scale-110 bg-blue-600 text-lg text-white shadow-blue-600/30' : ($phaseComplete ? 'bg-emerald-500 text-white shadow-emerald-700/20' : 'bg-slate-300 text-slate-700 shadow-slate-900/10 dark:bg-slate-700 dark:text-slate-100') }}">{{ $phase->phase_sequence }}</span>
                    <section id="phase-card-{{ $phase->id }}" class="overflow-hidden rounded-2xl border transition {{ $phaseActive ? 'border-blue-400 bg-white shadow-xl shadow-blue-900/15 dark:border-blue-500 dark:bg-slate-900' : ($phaseComplete ? 'border-emerald-200/70 bg-white/45 opacity-80 shadow-none backdrop-blur-sm dark:border-emerald-900/70 dark:bg-slate-900/35' : 'border-slate-300/70 bg-white/35 opacity-75 shadow-none backdrop-blur-sm dark:border-slate-700/70 dark:bg-slate-900/30') }}" data-phase-id="{{ $phase->id }}" data-has-mandatory-attachments="{{ $hasMandatoryAttachments ? 1 : 0 }}">
                        <header class="flex flex-col gap-3 border-b px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 {{ $phaseActive ? 'border-blue-300 bg-blue-100 px-5 py-5 sm:px-6 dark:border-blue-900 dark:bg-blue-950/70' : 'border-slate-200/70 bg-white/35 backdrop-blur-sm dark:border-slate-700/70 dark:bg-slate-950/25' }}"><div class="flex min-w-0 items-center gap-3"><span class="flex flex-shrink-0 items-center justify-center rounded-xl {{ $phaseActive ? 'h-12 w-12 rounded-2xl bg-blue-700 text-white shadow-lg shadow-blue-900/20' : 'h-10 w-10 bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }} {{ $phaseComplete && !$phaseActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : '' }}"><i class="fa-solid {{ $phaseActive ? 'fa-location-crosshairs text-lg' : ($phaseComplete ? 'fa-check' : 'fa-flag') }}"></i></span><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="font-mono text-[10px] font-extrabold uppercase tracking-wider {{ $phaseActive ? 'text-blue-950 dark:text-blue-200' : 'text-slate-500' }}">Phase {{ $phase->phase_sequence }}</span>@if($phaseActive)<span class="rounded-full bg-white px-2.5 py-1 text-[9px] font-black uppercase tracking-wide text-blue-800 shadow-sm dark:bg-slate-900 dark:text-blue-200">Current phase</span>@elseif($phaseComplete)<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">Completed</span>@elseif($phaseTerminal)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide text-slate-500 dark:bg-slate-800">Closed</span>@else<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide text-slate-500 dark:bg-slate-800">Upcoming</span>@endif</div><h2 class="mt-1 truncate {{ $phaseActive ? 'text-lg font-black tracking-tight text-blue-950 dark:text-white sm:text-xl' : 'text-sm font-extrabold text-slate-900 dark:text-white' }}">{{ $phase->phase_name }}</h2></div></div><span class="inline-flex items-center gap-2 self-start rounded-lg border px-3 py-2 font-bold {{ $phaseActive ? 'border-blue-300 bg-white text-xs text-blue-950 shadow-sm dark:border-blue-800 dark:bg-blue-900 dark:text-blue-100' : 'border-slate-200 bg-white text-[10px] text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}"><i class="fa-regular fa-clock text-blue-700 dark:text-blue-300"></i>{{ $phase->allowable_days ?? 0 }} day SLA</span>@if(($canRouteAllAttachments ?? false) && $canRouteThisPhase && $hasMandatoryAttachments && $phase->attachments->contains(fn($attachment) => $attachment->signatories->isNotEmpty()))<button type="button" data-route-all-button style="{{ $hasReadyAttachmentsToRoute ? '' : 'display:none' }}" class="inline-flex items-center gap-2 self-start rounded-lg bg-blue-700 {{ $phaseActive ? 'px-4 py-3 text-xs shadow-md' : 'px-3 py-2 text-[10px]' }} font-extrabold text-white transition hover:bg-blue-800" @click="$dispatch('open-confirmation', {action: '{{ route('procurements.route-phase-attachments', $procurement) }}?phase_id={{ $phase->id }}', title: 'Route all ready attachments?', message: 'Every ready attachment in this phase with configured signatories will be routed in one action.', eyebrow: 'Attachment routing', button: 'Route all', method: 'POST', tone: 'blue', icon: 'fa-share-nodes'})"><i class="fa-solid fa-share-nodes"></i>Route all</button>@endif</header>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($phase->attachments as $att)
                                @php
                                    $sub = $procurementAttachments->get($att->id);
                                    $info = $attachmentForms[$att->id] ?? ['html' => null, 'form_id' => $att->form_id ?? $att->form?->id, 'main_pr' => false, 'config' => $config];
                                    $formId = $att->form_id ?? $att->form?->id ?? $info['form_id'] ?? null;
                                    $hasForm = (bool)$formId;
                                    $signatories = ($att->signatories ?? collect())->sortBy(fn($s) => $s->sequence ?? $s->pivot?->sequence ?? $s->order ?? $s->id)->values();
                                    $ready = !empty($sub?->file_path);
                                    $workflowStatus = $sub?->workflow_status ?: ($ready ? ($att->signatories->isEmpty() ? 'COMPLETED' : 'READY') : 'INCOMPLETE');
                                    $signedCount = $sub?->signatories?->where('is_signed', true)->pluck('configured_role_id')->filter()->unique()->count() ?? 0;
                                    $hasAnySigned = $sub?->signatories?->contains(fn($slot) => (bool)$slot->is_signed) ?? false;
                                    $signatoryCount = $signatories->map(fn($step, $index) => $step->role_id ? 'role:'.$step->role_id : 'sequence:'.($step->sequence ?? $index + 1))->unique()->count();
                                    $formComplete = (bool)($sub?->is_completed ?? false);
                                    $pendingSignatory = $workflowStatus === 'ROUTING' ? $signatories->first(function($step, $index) use ($sub) {
                                        $sequence = (int)($step->sequence ?? $step->pivot?->sequence ?? $step->order ?? ($index + 1));
                                        $roleId = (int)($step->role_id ?? $step->role?->id ?? 0);
                                        $alreadySigned = $sub?->signatories?->contains(fn($slot) => (int)$slot->sequence === $sequence || ($roleId > 0 && (int)$slot->configured_role_id === $roleId)) ?? false;
                                        return !$alreadySigned;
                                    }) : null;
                                    $pendingSignatoryRole = $pendingSignatory?->role;
                                    $pendingSignatoryRoleId = (int)($pendingSignatory?->role_id ?? $pendingSignatoryRole?->id ?? 0);
                                    $pendingSignatoryIsEndUser = collect([$pendingSignatoryRole?->slug, $pendingSignatoryRole?->code, $pendingSignatoryRole?->name])
                                        ->contains(fn($value) => strtolower(str_replace([' ', '-'], '_', (string)$value)) === 'end_user');
                                    $isAuthUserTurnToSign = $pendingSignatory
                                        && in_array($pendingSignatoryRoleId, $authUserRoleIds, true)
                                        && (!$pendingSignatoryIsEndUser || $authUserId === (int)$procurement->created_by);
                                    $fileUrl = $sub?->file_path ? route('procurements.attachments.file', ['procurement' => $procurement->id, 'attachmentId' => $att->id]) : null;
                                    $isRequired = (bool)($att->is_mandatory ?? $att->is_required ?? $att->required ?? true);
                                    $mainPr = (bool)($info['main_pr'] ?? false);
                                    $attachmentIconTone = $workflowStatus === 'COMPLETED'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : (!$isRequired
                                            ? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                                            : ($hasForm ? 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300'));
                                @endphp
                                <article class="grid gap-4 {{ $phaseActive ? 'bg-blue-50/30 px-5 py-5 dark:bg-blue-950/10 sm:px-6 sm:py-6' : 'px-4 py-4' }} sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center" data-attachment-card="{{ $att->id }}" data-attachment-name="{{ $att->name }}" data-attachment-phase-id="{{ $phase->id }}" data-workflow-status="{{ $workflowStatus }}" data-is-mandatory="{{ $isRequired ? 1 : 0 }}" data-has-form="{{ $hasForm ? 1 : 0 }}" data-has-signatories="{{ $signatories->isNotEmpty() ? '1' : '0' }}" data-has-signed="{{ $hasAnySigned ? '1' : '0' }}" data-can-act="{{ $canEditPhaseAttachments ? '1' : '0' }}">
                                    <div class="flex min-w-0 items-start gap-3"><span data-attachment-icon class="flex flex-shrink-0 items-center justify-center rounded-xl {{ $phaseActive ? 'h-12 w-12 shadow-sm' : 'h-10 w-10' }} {{ $attachmentIconTone }}"><i class="fa-solid {{ $hasForm ? 'fa-file-pen' : 'fa-file-pdf' }} {{ $phaseActive ? 'text-base' : '' }}"></i></span><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h3 class="{{ $phaseActive ? 'text-sm font-black text-slate-950 dark:text-white sm:text-base' : 'text-xs font-extrabold text-slate-900 dark:text-white' }}">{{ $att->name }}</h3>@if($isRequired)<span class="rounded-full bg-rose-50 px-2 py-0.5 {{ $phaseActive ? 'text-[10px]' : 'text-[9px]' }} font-extrabold text-rose-700 dark:bg-rose-950 dark:text-rose-300">Required</span>@else<span class="rounded-full bg-slate-100 px-2 py-0.5 {{ $phaseActive ? 'text-[10px]' : 'text-[9px]' }} font-bold text-slate-500 dark:bg-slate-800">Optional</span>@endif</div><div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 {{ $phaseActive ? 'text-[11px]' : 'text-[10px]' }} text-slate-500 dark:text-slate-400"><span data-attachment-status-for="{{ $att->id }}" class="inline-flex items-center gap-1.5 font-extrabold {{ $workflowStatus === 'COMPLETED' ? 'text-emerald-700 dark:text-emerald-300' : ($workflowStatus === 'ROUTING' ? 'text-blue-700 dark:text-blue-300' : ($workflowStatus === 'READY' ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400')) }}"><i class="fa-solid {{ $workflowStatus === 'COMPLETED' ? 'fa-circle-check' : ($workflowStatus === 'ROUTING' ? 'fa-share-nodes' : ($workflowStatus === 'READY' ? 'fa-clock' : 'fa-circle-minus')) }}"></i><span>{{ $workflowStatus === 'COMPLETED' ? 'Completed' : ($workflowStatus === 'ROUTING' ? 'Routing · '.$signedCount.'/'.$signatoryCount.' signed' : ($workflowStatus === 'READY' ? 'Ready to route' : ($formComplete && $hasForm ? 'Form complete · compile PDF' : 'Not ready'))) }}</span></span><span>{{ $hasForm ? 'Bound online form' : 'PDF attachment' }}</span>@if($isAuthUserTurnToSign)<span class="inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-100 px-2 py-0.5 font-extrabold text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200"><i class="fa-solid fa-signature"></i>Signing as {{ $pendingSignatoryRole?->name ?? 'Signatory' }}</span>@endif @if($sub?->file_name)<span class="max-w-full break-all">{{ $sub->file_name }}</span>@endif</div>
                                        @if($signatories->isNotEmpty())
                                            <div class="mt-2.5">
                                                <p class="mb-1 {{ $phaseActive ? 'text-[10px] text-blue-700 dark:text-blue-300' : 'text-[9px] text-slate-400' }} font-extrabold uppercase tracking-wider">Signatory sequence</p>
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    @foreach($signatories as $signatory)
                                                        @php
                                                            $sequence = (int)($signatory->sequence ?? $signatory->pivot?->sequence ?? $loop->iteration);
                                                            $roleId = (int)($signatory->role_id ?? $signatory->role?->id ?? 0);
                                                            $signedSlot = $sub?->signatories?->first(fn($slot) => (int)$slot->sequence === $sequence)
                                                                ?? ($roleId > 0 ? $sub?->signatories?->first(fn($slot) => (int)$slot->configured_role_id === $roleId) : null);
                                                            $hasSigned = (bool)($signedSlot?->is_signed);
                                                            $signatoryName = $signatory->role?->name ?? $signatory->name ?? 'Signatory';
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 rounded-lg border {{ $phaseActive ? 'px-2.5 py-1.5 text-[10px]' : 'px-2 py-1 text-[9px]' }} font-bold {{ $hasSigned ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300' : ($phaseActive ? 'border-blue-200 bg-white text-blue-900 shadow-sm dark:border-blue-800 dark:bg-slate-900 dark:text-blue-200' : 'border-slate-200 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300') }}" @if($hasSigned && $signedSlot?->signatory?->name) title="Signed by {{ $signedSlot->signatory->name }}" @endif>
                                                            <span class="flex {{ $phaseActive ? 'h-5 w-5 text-[9px]' : 'h-4 w-4 text-[8px]' }} items-center justify-center rounded-full {{ $hasSigned ? 'bg-emerald-200 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' : ($phaseActive ? 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200' : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300') }}">@if($hasSigned)<i class="fa-solid fa-check"></i>@else{{ $loop->iteration }}@endif</span>
                                                            {{ $signatoryName }}
                                                            @if($hasSigned)<span class="uppercase tracking-wide">{{ $signedSlot->signatory?->name ? 'Signed · '.$signedSlot->signatory->name : 'Signed' }}</span>@endif
                                                        </span>
                                                        @if(!$loop->last)<i class="fa-solid fa-chevron-right text-[8px] text-slate-300"></i>@endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap gap-2 sm:justify-end">
                                        @if($canEditPhaseAttachments)<div data-actions-not-ready="{{ $att->id }}" class="flex flex-wrap gap-2 {{ $ready ? 'hidden' : '' }}">
                                            @if($hasForm)<button type="button" @click="selectAttachment('{{ $att->id }}', @js($att->name), true, {{ $mainPr ? 'true' : 'false' }}, 'form', @js($fileUrl), @js($sub?->file_name))" class="inline-flex items-center gap-2 rounded-xl bg-[#134991] px-3.5 py-2.5 text-[10px] font-extrabold text-white transition hover:bg-blue-800"><i class="fa-solid fa-pen-to-square"></i>{{ $formComplete ? 'Open / Edit Form' : 'Open Form' }}</button>@else<button type="button" @click="startUpload('{{ $att->id }}', @js($att->name), false, false, @js($fileUrl), @js($sub?->file_name))" class="inline-flex items-center gap-2 rounded-xl bg-[#134991] px-3.5 py-2.5 text-[10px] font-extrabold text-white transition hover:bg-blue-800"><i class="fa-solid fa-upload"></i>Upload PDF</button>@endif
                                        </div>@endif
                                        <div data-actions-ready="{{ $att->id }}" class="flex flex-wrap gap-2 {{ $ready ? '' : 'hidden' }}">
                                            @if($canRouteThisPhase && $workflowStatus === 'READY' && $signatories->isNotEmpty() && !$hasAnySigned)<button type="button" data-route-attachment class="inline-flex items-center gap-2 rounded-xl bg-blue-600 {{ $phaseActive ? 'px-4 py-3 text-xs shadow-md' : 'px-3.5 py-2.5 text-[10px]' }} font-extrabold text-white transition hover:bg-blue-700" @click="$dispatch('open-confirmation', {action: '{{ route('procurements.attachments.route', [$procurement, $att->id]) }}', title: 'Route this attachment?', message: 'This document will be sent to its configured signatories in order. The procurement status will change from Draft to Routing if this is the first routed document.', eyebrow: 'Attachment routing', button: 'Route attachment', method: 'POST', tone: 'blue', icon: 'fa-share-nodes'})"><i class="fa-solid fa-share-nodes"></i>Route</button>@endif

                                            @if($fileUrl)<button type="button" data-attachment-action="view" data-attachment-id="{{ $att->id }}" data-attachment-name="{{ $att->name }}" data-has-form="{{ $hasForm ? '1' : '0' }}" data-main-pr="{{ $mainPr ? '1' : '0' }}" data-file-url="{{ $fileUrl }}" data-file-name="{{ $sub?->file_name }}" @click="openAttachmentAction($event, 'view')" aria-label="View {{ $att->name }}" title="View attachment" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:border-blue-400 hover:text-[#134991] dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"><i class="fa-solid fa-eye"></i></button>@endif
                                            @if($canEditPhaseAttachments)@if($hasForm)<button type="button" data-attachment-action="replace" data-attachment-id="{{ $att->id }}" data-attachment-name="{{ $att->name }}" data-has-form="{{ $hasForm ? '1' : '0' }}" data-main-pr="{{ $mainPr ? '1' : '0' }}" data-file-url="{{ $fileUrl }}" data-file-name="{{ $sub?->file_name }}" @click="openAttachmentAction($event, 'replace')" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2.5 text-[10px] font-extrabold text-blue-800 transition hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300"><i class="fa-solid fa-rotate"></i>Replace / Edit</button>@else<button type="button" data-attachment-action="replace" data-attachment-id="{{ $att->id }}" data-attachment-name="{{ $att->name }}" data-has-form="{{ $hasForm ? '1' : '0' }}" data-main-pr="{{ $mainPr ? '1' : '0' }}" data-file-url="{{ $fileUrl }}" data-file-name="{{ $sub?->file_name }}" @click="openAttachmentAction($event, 'replace')" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2.5 text-[10px] font-extrabold text-blue-800 transition hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-300"><i class="fa-solid fa-rotate"></i>Replace PDF</button>@endif @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <p class="px-5 py-8 text-center text-xs text-slate-400">No attachments are configured for this phase.</p>
                            @endforelse
                        </div>
                    </section>
                    </div>
                @endforeach
                </div>
                @else
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"><i class="fa-solid fa-triangle-exclamation mr-2"></i>This procurement mode has no workflow phases. Ask an administrator to configure its phases and attachments.</div>
                @endif
            {{-- Teleport the modal above the application shell and its stacking contexts. --}}
            <template x-teleport="body">
            <section id="attachment-modal" x-show="selectedAttachment" x-cloak @fullscreenchange.window="fullscreen = document.fullscreenElement === $el; $nextTick(() => requestAnimationFrame(() => fitWidth()))" class="fixed inset-0 z-[100] bg-slate-950/70 p-0 backdrop-blur-sm sm:p-3" role="dialog" aria-modal="true" aria-labelledby="canvas-attachment-title">
                <div id="attachment-modal-panel" class="mx-auto flex h-[100dvh] w-full flex-col overflow-hidden bg-slate-100 shadow-2xl dark:bg-slate-950 sm:h-[calc(100dvh-1.5rem)] sm:max-w-[1700px] sm:rounded-2xl sm:ring-1 sm:ring-white/20">
                    <header class="relative z-10 grid flex-shrink-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-b border-slate-200 bg-white px-3 py-3 dark:border-slate-800 dark:bg-slate-900 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:px-5">
                        <div class="min-w-0"><p class="text-[9px] font-extrabold uppercase tracking-[.18em] text-blue-600 dark:text-blue-400">Attachment workspace</p><h2 id="canvas-attachment-title" class="mt-1 truncate text-sm font-extrabold text-slate-900 dark:text-white" x-text="selectedAttachment?.name || 'Attachment'"></h2><p id="canvas-attachment-subtitle" class="mt-0.5 truncate text-[10px] text-slate-500" x-text="viewMode === 'form' ? 'Changes save automatically' : 'PDF preview or upload'"></p></div>
                        <div class="pr-toolbar col-span-2 row-start-2 flex items-center justify-self-center gap-1 rounded-xl border border-slate-200 bg-slate-100 p-1 shadow-inner dark:border-slate-700 dark:bg-slate-950 sm:col-span-1 sm:row-auto">
                            <button type="button" class="canvas-control" @click="zoomOut" title="Zoom out" aria-label="Zoom out"><i class="fa-solid fa-minus"></i></button>
                            <button type="button" class="min-w-12 rounded-lg px-2 py-1.5 font-mono text-[10px] font-extrabold text-slate-700 transition hover:bg-white dark:text-slate-200 dark:hover:bg-slate-800" @click="resetZoom" title="Reset zoom" aria-label="Reset zoom" x-text="Math.round(scale * 100) + '%'">100%</button>
                            <button type="button" class="canvas-control" @click="zoomIn" title="Zoom in" aria-label="Zoom in"><i class="fa-solid fa-plus"></i></button>
                            <span class="mx-1 h-5 w-px bg-slate-300 dark:bg-slate-700"></span>
                            <button type="button" class="canvas-control" @click="fitWidth" title="Fit to width" aria-label="Fit to width"><i class="fa-solid fa-arrows-left-right-to-line"></i></button>
                        </div>
                        <div class="col-start-2 row-start-1 flex items-center justify-end gap-2 sm:col-auto sm:row-auto">
                            <button type="button" class="canvas-control rounded-lg p-2 text-slate-600 transition-colors hover:bg-slate-200 hover:text-slate-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white" @click="toggleFullscreen" :title="fullscreen ? 'Exit fullscreen' : 'Enter fullscreen'" :aria-label="fullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"><i class="fa-solid" :class="fullscreen ? 'fa-compress' : 'fa-expand'"></i></button>
                            <button type="button" class="canvas-control rounded-lg p-2 text-slate-600 transition-colors hover:bg-rose-100 hover:text-rose-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-500 dark:text-slate-300 dark:hover:bg-rose-950 dark:hover:text-rose-300" @click="closeAttachment" title="Close attachment" aria-label="Close attachment"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </header>
                    <section x-show="selectedAttachment && viewMode === 'form'" x-cloak class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden"><div id="canvas-scroll-area" class="custom-scrollbar min-h-0 min-w-0 flex-1 overflow-auto overscroll-contain p-3 pt-5 sm:p-6" aria-label="Selected attachment form"><div class="pr-canvas-stage flex min-h-full min-w-full items-start justify-center"><div id="canvas-zoom-shell" class="relative flex-none" style="width:0;height:0"><div id="form-canvas-root" x-ref="shadowHost" x-init="$el.attachShadow({mode:'open'})" class="pr-form-canvas rounded-sm bg-white text-slate-900 shadow-2xl ring-1 ring-slate-900/10"></div></div></div></div></section>
                    <section x-show="selectedAttachment && viewMode === 'upload'" x-cloak class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden"><div id="pdf-scroll-area" class="custom-scrollbar min-h-0 flex-1 overflow-auto p-3 pt-5 sm:p-6"><div class="mx-auto max-w-2xl" x-show="!pdfPreviewUrl"><div class="rounded-2xl border-2 border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900"><i class="fa-solid fa-file-pdf mb-3 text-3xl text-rose-500"></i><h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Upload a PDF attachment</h3><p class="mx-auto mt-2 max-w-md text-xs leading-5 text-slate-500">Choose a PDF. The file is saved immediately and can be replaced later.</p><button type="button" x-show="canEdit" @click="openUploadPicker" class="mt-5 rounded-xl bg-[#134991] px-4 py-2.5 text-xs font-extrabold text-white hover:bg-blue-800"><i class="fa-solid fa-upload mr-2"></i>Choose PDF</button></div></div><div id="pdf-stage" x-show="pdfPreviewUrl" x-cloak class="flex w-max min-w-full items-start justify-center"><div id="pdf-zoom-shell" class="flex-none" style="width:794px"><div id="pdf-canvas-pages" role="document" aria-label="PDF attachment preview" style="width:794px;transform-origin:top left"></div></div></div></div></section>
                    <footer class="relative flex flex-shrink-0 flex-col gap-3 border-t border-slate-200 bg-white px-3 py-3 dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center sm:justify-between sm:px-5"><div class="flex flex-wrap items-center gap-2"><p id="attachment-save-feedback" x-show="viewMode === 'form' || feedbackMessage || signing.loading || signing.message || signing.hasTurn" x-text="viewMode === 'form' ? feedbackMessage : (signing.loading ? 'Checking signing status…' : (signing.message || (signing.hasTurn ? (signing.canSign ? 'Your signing turn is available. Place a signature to continue.' : 'It is your turn, but signing is currently unavailable.') : feedbackMessage)))" :class="feedbackSuccess ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'" class="min-h-4 text-[10px] font-semibold" role="status" aria-live="polite"></p><span x-show="signMode && signPlacement" x-cloak class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[10px] font-extrabold text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200"><i class="fa-solid fa-file-lines"></i><span x-text="`Page ${signPlacement?.page || '—'}`"></span></span></div>
                        <div class="flex w-full flex-wrap justify-end gap-2 sm:w-auto">
                            <a x-show="selectedAttachment && viewMode !== 'form' && pdfPreviewUrl" x-cloak :href="pdfPreviewUrl + (pdfPreviewUrl.includes('?') ? '&' : '?') + 'download=1'" :download="pdfFileName || selectedAttachment?.name || 'attachment.pdf'" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:bg-emerald-700"><i class="fa-solid fa-download"></i>Download PDF</a>
                            <button type="button" x-show="canEdit && selectedAttachment?.hasForm && viewMode === 'form' && formComplete && !completedAttachments.includes(String(selectedAttachment?.id))" x-cloak @click="compileAttachmentPdf" :disabled="saveBusy" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60"><i class="fa-solid fa-file-pdf"></i>Compile PDF</button>
                            <input type="password" x-show="viewMode !== 'form' && pdfPreviewUrl && signing.hasTurn && signing.passwordRequired" x-cloak x-model="signPassword" @keydown.enter.prevent="signMode && signPlacement && submitDigiSign()" autocomplete="new-password" spellcheck="false" placeholder="P12 password" aria-label="P12 password" class="w-44 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <button type="button" x-show="selectedAttachment && viewMode !== 'form' && pdfPreviewUrl && signing.hasTurn" x-cloak @click="signAttachment()" :disabled="!signing.canSign || saveBusy || signBusy" class="inline-flex items-center gap-2 rounded-xl bg-[#134991] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:bg-blue-800 cursor-pointer disabled:cursor-wait disabled:opacity-60" :class="signMode ? 'ring-4 ring-blue-300' : ''"><i class="fa-solid fa-signature"></i><span x-text="signMode ? 'Cancel signature' : `Sign as ${signing.roleName || 'Signatory'}`"></span></button>
                            <button type="button" x-show="signMode && signPlacement && signing.hasTurn" x-cloak @click="submitDigiSign()" :disabled="!signing.canSign || signBusy || (signing.passwordRequired && !signPassword)" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition hover:bg-emerald-700 cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"><i class="fa-solid" :class="signBusy ? 'fa-spinner fa-spin' : 'fa-file-signature'"></i><span x-text="signBusy ? 'Signing…' : `Sign as ${signing.roleName || 'Signatory'}`"></span></button>
                        </div>
                    </footer>
                </div>
            </section>
            </template>
        </section>
        <template x-teleport="body"><section x-show="replaceDialogOpen" x-cloak class="fixed inset-0 z-[115] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="replace-attachment-title"><div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-[#134991] dark:bg-blue-950 dark:text-blue-300"><i class="fa-solid fa-rotate"></i></span><h2 id="replace-attachment-title" class="mt-4 text-base font-extrabold text-slate-900 dark:text-white">Replace attachment?</h2><p class="mt-2 text-sm leading-6 text-slate-500" x-text="pendingReplace?.hasForm ? 'This attachment uses a bound form. You will reopen the form; editing it will invalidate its compiled PDF until you compile a replacement.' : 'Choose a replacement PDF. It will be saved to this attachment line immediately.'"></p><p class="mt-2 text-xs font-extrabold text-slate-800 dark:text-slate-200" x-text="pendingReplace?.name || 'Attachment'"></p><div class="mt-6 flex justify-end gap-2"><button type="button" @click="replaceDialogOpen=false;pendingReplace=null" class="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">Keep current</button><button type="button" @click="confirmReplace" class="rounded-xl bg-[#134991] px-4 py-2.5 text-xs font-extrabold text-white hover:bg-blue-800" x-text="pendingReplace?.hasForm ? 'Open form' : 'Choose replacement PDF'"></button></div></div></section></template>
        @foreach($phases as $phase)
            @foreach($phase->attachments as $att)
                @php
                    $info = $attachmentForms[$att->id] ?? ['html' => null, 'form_id' => $att->form_id ?? $att->form?->id, 'main_pr' => false, 'config' => $config];
                    $boundForm = $att->form ?? null;
                    $formCfg = is_array($info['config'] ?? null) ? $info['config'] : $config;
                    $formPageSize = $formCfg['page_size'] ?? 'A4';
                    $formPaperWidth = match ($formPageSize) { 'Letter', 'Legal', 'Folio' => '8.5in', 'A3' => '297mm', 'A5' => '148mm', 'Custom' => (($formCfg['custom_width'] ?? 210) . 'mm'), default => '210mm' };
                    $formPaperHeight = match ($formPageSize) { 'Letter' => '11in', 'Legal' => '14in', 'Folio' => '13in', 'A3' => '420mm', 'A5' => '210mm', 'Custom' => (($formCfg['custom_height'] ?? 297) . 'mm'), default => '297mm' };
                    $formMargins = $formCfg['margins'] ?? $margins;
                @endphp
                @if(!empty($info['html']))<template id="attachment-form-template-{{ $att->id }}" data-form-id="{{ $info['form_id'] }}" data-main-pr="{{ !empty($info['main_pr']) ? '1' : '0' }}" data-form-context="{{ json_encode($attachmentFormContexts[(string)$att->id] ?? [], JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_TAG) }}" data-paper-width="{{ $formPaperWidth }}" data-paper-height="{{ $formPaperHeight }}" data-margin-top="{{ ($formMargins['top'] ?? 10) . 'mm' }}" data-margin-right="{{ ($formMargins['right'] ?? 10) . 'mm' }}" data-margin-bottom="{{ ($formMargins['bottom'] ?? 10) . 'mm' }}" data-margin-left="{{ ($formMargins['left'] ?? 10) . 'mm' }}">{!! $info['html'] !!}</template>@endif
                @if(empty($info['form_id']))<input type="file" id="attachment-pdf-upload-{{ $att->id }}" accept="application/pdf,.pdf" class="hidden" @change="attachmentFileSelected($event, '{{ $att->id }}')">@endif
            @endforeach
        @endforeach
        @if(!empty($prFormHtml) && ($canEditPrForm ?? false))
            @php
                $formCfg = is_array($prForm?->configuration ?? null) ? $prForm->configuration : [];
                $formPageSize = $formCfg['page_size'] ?? 'A4';
                $formPaperWidth = match ($formPageSize) { 'Letter', 'Legal', 'Folio' => '8.5in', 'A3' => '297mm', 'A5' => '148mm', 'Custom' => (($formCfg['custom_width'] ?? 210) . 'mm'), default => '210mm' };
                $formPaperHeight = match ($formPageSize) { 'Letter' => '11in', 'Legal' => '14in', 'Folio' => '13in', 'A3' => '420mm', 'A5' => '210mm', 'Custom' => (($formCfg['custom_height'] ?? 297) . 'mm'), default => '297mm' };
                $formMargins = $formCfg['margins'] ?? $margins;
            @endphp
            <template id="attachment-form-template-pr" data-form-id="{{ $prForm->id }}" data-main-pr="1" data-form-context="{{ json_encode($prFormContext ?? [], JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP|JSON_HEX_TAG) }}" data-paper-width="{{ $formPaperWidth }}" data-paper-height="{{ $formPaperHeight }}" data-margin-top="{{ ($formMargins['top'] ?? 10) . 'mm' }}" data-margin-right="{{ ($formMargins['right'] ?? 10) . 'mm' }}" data-margin-bottom="{{ ($formMargins['bottom'] ?? 10) . 'mm' }}" data-margin-left="{{ ($formMargins['left'] ?? 10) . 'mm' }}">{!! $prFormHtml !!}</template>
        @endif
    </form>
</div>

<style>
    .pr-workspace{isolation:isolate;overscroll-behavior:none}.pr-workspace,.pr-workspace form{min-width:0}.pr-workspace [x-cloak]{display:none!important}#pr-left-column{isolation:isolate}#pdf-stage{width:max-content;min-width:100%}.pr-workspace button:not(:disabled),.pr-workspace [role=button]{cursor:pointer}#pdf-canvas-pages.pr-signing .pr-pdf-page{cursor:crosshair;touch-action:none;user-select:none;-webkit-user-select:none}#pdf-canvas-pages.pr-signing canvas{pointer-events:none}.pr-sign-box{position:absolute;box-sizing:border-box;background:rgba(19,73,145,.5);border:2px dashed #134991;border-radius:4px;cursor:grab;touch-action:none;display:flex;align-items:center;justify-content:center;color:#fff;font:700 11px/1 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;overflow:hidden}.pr-sign-box span{pointer-events:none}.pr-sign-box.is-dragging{cursor:grabbing}#canvas-scroll-area,#pdf-scroll-area{overscroll-behavior:contain;touch-action:pan-x pan-y;scrollbar-gutter:stable;-webkit-overflow-scrolling:touch}#canvas-scroll-area{scroll-behavior:smooth}.pr-canvas-stage{width:100%;min-width:100%;display:flex;justify-content:center;align-items:flex-start}#canvas-zoom-shell,#pdf-zoom-shell{flex:0 0 auto;transform-origin:top left}#form-canvas-root{display:block;margin:0;transform-origin:top left;box-sizing:border-box}.pr-toolbar button{white-space:nowrap}.custom-scrollbar::-webkit-scrollbar{width:6px;height:6px}.custom-scrollbar::-webkit-scrollbar-track{background:transparent}.custom-scrollbar::-webkit-scrollbar-thumb{background-color:#cbd5e1;border-radius:20px}.dark .custom-scrollbar::-webkit-scrollbar-thumb{background-color:#334155}.opacity-65{opacity:.65}#attachment-modal{isolation:isolate}#attachment-modal:fullscreen{width:100vw;height:100dvh;padding:0;background:rgb(2 6 23 / 70%)}#attachment-modal:fullscreen #attachment-modal-panel{width:100%;height:100%;max-width:none;border-radius:0}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
/* ---------- PDF viewer (pdf.js → <canvas>, no iframe/embed) ---------- */
let prPdfToken = 0;
const PR_PDF_CSS_WIDTH = 794; // A4 @ 96dpi; pages are normalised to this width
const PR_ZOOM_MIN = 0.35, PR_ZOOM_MAX = 3;
let prPdfAppliedScale = 1;
// Scales the PDF pages and keeps the point under (fx, fy) — default: viewport centre — fixed on screen.
window.applyPrPdfScale = function (scale, fx, fy) {
    const shell = document.getElementById('pdf-zoom-shell'), pages = document.getElementById('pdf-canvas-pages'), vp = document.getElementById('pdf-scroll-area');
    if (!shell || !pages) return;
    const old = prPdfAppliedScale || 1;
    let anchor = null;
    if (vp && shell.offsetParent !== null) {
        const r = vp.getBoundingClientRect(), s = shell.getBoundingClientRect();
        const cx = fx ?? r.left + r.width / 2, cy = fy ?? r.top + r.height / 2;
        anchor = {cx, cy, u: (cx - s.left) / old, v: (cy - s.top) / old};
    }
    pages.style.transformOrigin = 'top left';
    pages.style.transform = `scale(${scale})`;
    shell.style.width = `${PR_PDF_CSS_WIDTH * scale}px`;
    shell.style.height = `${pages.offsetHeight * scale}px`;
    prPdfAppliedScale = scale;
    if (anchor) {
        const s2 = shell.getBoundingClientRect();
        vp.scrollLeft += s2.left + anchor.u * scale - anchor.cx;
        vp.scrollTop += s2.top + anchor.v * scale - anchor.cy;
    }
};
window.setPrPdfZoom = function (scale, fx, fy) {
    scale = Math.max(PR_ZOOM_MIN, Math.min(PR_ZOOM_MAX, Number(scale) || 1));
    const ws = document.getElementById('pr-workspace'), data = window.Alpine?.$data?.(ws);
    if (data) data.scale = scale; else if (ws) ws.dataset.scale = scale;
    window.applyPrPdfScale(scale, fx, fy);
};
window.centerPrPdf = function () {
    const vp = document.getElementById('pdf-scroll-area');
    if (!vp) return;
    vp.scrollLeft = Math.max(0, (vp.scrollWidth - vp.clientWidth) / 2);
    vp.scrollTop = 0;
};
// Pinch (touch) and ctrl+wheel / trackpad pinch zoom, anchored at the gesture centre.
(function () {
    const inPdf = e => e.target?.closest?.('#pdf-scroll-area');
    const cur = () => Number(document.getElementById('pr-workspace')?.dataset.scale || 1);
    const dist = t => Math.hypot(t[0].clientX - t[1].clientX, t[0].clientY - t[1].clientY);
    let pinch = null, raf = 0;
    document.addEventListener('wheel', e => {
        if (!e.ctrlKey || !inPdf(e)) return;
        e.preventDefault();
        window.setPrPdfZoom(cur() * Math.exp(-e.deltaY * 0.01), e.clientX, e.clientY);
    }, {passive: false});
    document.addEventListener('touchstart', e => { if (e.touches.length === 2 && inPdf(e)) pinch = {d: dist(e.touches), s: cur()}; }, {passive: false});
    document.addEventListener('touchmove', e => {
        if (!pinch || e.touches.length !== 2) return;
        e.preventDefault();
        const t = e.touches, mx = (t[0].clientX + t[1].clientX) / 2, my = (t[0].clientY + t[1].clientY) / 2, k = dist(t) / pinch.d;
        const nextScale = pinch.s * k;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(() => window.setPrPdfZoom(nextScale, mx, my));
    }, {passive: false});
    document.addEventListener('touchend', e => { if (e.touches.length < 2) pinch = null; });
    document.addEventListener('touchcancel', () => { pinch = null; });
})();
/* ---------- Signature placement (draw / move / remove a 50% transparent box on a PDF page) ---------- */
const prSign = {active: false, el: null, page: null, drag: null};
const PR_SIGN_MIN_W = 24, PR_SIGN_MIN_H = 12;
function prSignLocal(page, e) {
    const r = page.getBoundingClientRect(), k = r.width / PR_PDF_CSS_WIDTH || 1;
    return {x: (e.clientX - r.left) / k, y: (e.clientY - r.top) / k, w: PR_PDF_CSS_WIDTH, h: r.height / k};
}
function prSignRemoveBox(notify = true) {
    const had = !!prSign.el;
    prSign.el?.remove(); prSign.el = null; prSign.page = null; prSign.drag = null;
    if (had && notify) window.dispatchEvent(new CustomEvent('pr-signature-cleared'));
}
function prSignNotify() {
    if (!prSign.el || !prSign.page) return;
    const pageNumber = Number(prSign.page.dataset.page);
    if (!Number.isInteger(pageNumber) || pageNumber < 1) return;
    const w = PR_PDF_CSS_WIDTH, h = parseFloat(prSign.page.style.height) || 1;
    const x = parseFloat(prSign.el.style.left), y = parseFloat(prSign.el.style.top), bw = parseFloat(prSign.el.style.width), bh = parseFloat(prSign.el.style.height);
    // Fractions of the page (origin top-left) so any renderer can map them to PDF coordinates.
    window.dispatchEvent(new CustomEvent('pr-signature-placed', {detail: {page: pageNumber, x: x / w, y: y / h, width: bw / w, height: bh / h, pageWidth: Number(prSign.page.dataset.ptW) || 0, pageHeight: Number(prSign.page.dataset.ptH) || 0}}));
}
window.getPrSignaturePlacement = function () {
    if (!prSign.el || !prSign.page) return null;
    let detail = null;
    const h = (ev) => { detail = ev.detail; };
    window.addEventListener('pr-signature-placed', h, {once: true});
    prSignNotify();
    window.removeEventListener('pr-signature-placed', h);
    return detail;
};
window.setPrSignMode = function (on) {
    on = !!on;
    if (prSign.active === on) return;
    prSign.active = on;
    document.getElementById('pdf-canvas-pages')?.classList.toggle('pr-signing', on);
    if (!on) prSignRemoveBox(false);
    window.dispatchEvent(new CustomEvent('pr-sign-mode', {detail: {active: on}}));
};
(function () {
    const pageOf = e => { const p = e.target?.closest?.('.pr-pdf-page'); return p && document.getElementById('pdf-canvas-pages')?.contains(p) ? p : null; };
    document.addEventListener('pointerdown', e => {
        if (!prSign.active || (e.pointerType === 'mouse' && e.button !== 0) || !e.isPrimary) return;
        const page = pageOf(e);
        if (!page) return;
        const pt = prSignLocal(page, e);
        if (e.target.closest('.pr-sign-box') && prSign.el) {
            // Grab the existing box to move it.
            e.preventDefault();
            prSign.drag = {mode: 'move', dx: pt.x - parseFloat(prSign.el.style.left), dy: pt.y - parseFloat(prSign.el.style.top), id: e.pointerId};
            prSign.el.classList.add('is-dragging');
            try { prSign.el.setPointerCapture(e.pointerId); } catch (err) {}
            return;
        }
        // Click outside the box (anywhere on a page): remove it, and start drawing a new one from here.
        e.preventDefault();
        prSignRemoveBox(true);
        const box = document.createElement('div');
        box.className = 'pr-sign-box is-dragging';
        box.innerHTML = '<span>Signature</span>';
        Object.assign(box.style, {left: pt.x + 'px', top: pt.y + 'px', width: '0px', height: '0px'});
        page.appendChild(box);
        prSign.el = box; prSign.page = page;
        prSign.drag = {mode: 'draw', x0: pt.x, y0: pt.y, id: e.pointerId, moved: false};
        try { page.setPointerCapture(e.pointerId); } catch (err) {}
    });
    document.addEventListener('pointermove', e => {
        const d = prSign.drag;
        if (!d || !prSign.el || !prSign.page || e.pointerId !== d.id) return;
        e.preventDefault();
        const pt = prSignLocal(prSign.page, e), box = prSign.el;
        if (d.mode === 'draw') {
            const x1 = Math.max(0, Math.min(pt.w, pt.x)), y1 = Math.max(0, Math.min(pt.h, pt.y));
            if (Math.abs(x1 - d.x0) > 2 || Math.abs(y1 - d.y0) > 2) d.moved = true;
            Object.assign(box.style, {left: Math.min(d.x0, x1) + 'px', top: Math.min(d.y0, y1) + 'px', width: Math.abs(x1 - d.x0) + 'px', height: Math.abs(y1 - d.y0) + 'px'});
        } else {
            const bw = parseFloat(box.style.width), bh = parseFloat(box.style.height);
            box.style.left = Math.max(0, Math.min(pt.w - bw, pt.x - d.dx)) + 'px';
            box.style.top = Math.max(0, Math.min(pt.h - bh, pt.y - d.dy)) + 'px';
        }
    }, {passive: false});
    const finish = e => {
        const d = prSign.drag;
        if (!d || e.pointerId !== d.id) return;
        const box = prSign.el;
        prSign.drag = null;
        box?.classList.remove('is-dragging');
        if (d.mode === 'draw' && box && (parseFloat(box.style.width) < PR_SIGN_MIN_W || parseFloat(box.style.height) < PR_SIGN_MIN_H)) { prSignRemoveBox(false); return; } // plain click = just remove
        prSignNotify();
    };
    document.addEventListener('pointerup', finish);
    document.addEventListener('pointercancel', finish);
    document.addEventListener('keydown', e => {
        if (!prSign.active) return;
        if (e.key === 'Escape') window.setPrSignMode(false);
        else if ((e.key === 'Delete' || e.key === 'Backspace') && prSign.el && !/input|textarea|select/i.test(e.target?.tagName || '')) prSignRemoveBox(true);
    });
})();
window.clearPrPdf = function () {
    prPdfToken++;
    window.setPrSignMode?.(false);
    document.getElementById('pdf-canvas-pages')?.replaceChildren();
};
window.renderPrPdf = async function (url) {
    const pages = document.getElementById('pdf-canvas-pages');
    if (!pages) return;
    const token = ++prPdfToken;
    window.setPrSignMode?.(false);
    pages.replaceChildren();
    pages.dataset.pageCount = '0';
    if (!url) return;
    try {
        if (!window.pdfjsLib) throw new Error('PDF viewer library failed to load.');
        if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
            // Cross-origin workers are blocked, so wrap the CDN worker in a same-origin blob.
            const workerUrl = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            pdfjsLib.GlobalWorkerOptions.workerSrc = URL.createObjectURL(new Blob([`importScripts("${workerUrl}");`], {type: 'text/javascript'}));
        }
        const doc = await pdfjsLib.getDocument({url}).promise;
        pages.dataset.pageCount = String(doc.numPages);
        const density = Math.min(window.devicePixelRatio || 1, 2) * 1.5; // crisp when zoomed
        for (let n = 1; n <= doc.numPages; n++) {
            if (token !== prPdfToken) return;
            const page = await doc.getPage(n);
            const base = page.getViewport({scale: 1});
            const cssScale = PR_PDF_CSS_WIDTH / base.width;
            const viewport = page.getViewport({scale: cssScale * density});
            const canvas = document.createElement('canvas');
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            const wrap = document.createElement('div');
            wrap.className = 'pr-pdf-page';
            wrap.dataset.page = String(n);
            wrap.dataset.ptW = String(base.width); wrap.dataset.ptH = String(base.height); // PDF points
            wrap.style.cssText = `position:relative;width:${PR_PDF_CSS_WIDTH}px;height:${Math.round(base.height * cssScale)}px;background:#fff;margin:0 0 12px;box-shadow:0 1px 3px rgba(0,0,0,.25)`;
            canvas.style.cssText = 'display:block;width:100%;height:100%';
            canvas.setAttribute('aria-label', `PDF page ${n} of ${doc.numPages}`);
            wrap.appendChild(canvas);
            pages.appendChild(wrap);
            await page.render({canvasContext: canvas.getContext('2d'), viewport}).promise;
            if (token === prPdfToken) window.applyPrPdfScale(prPdfAppliedScale);
        }
        if (token === prPdfToken) { window.syncPrCanvasSize?.(); window.centerPrPdf(); }
    } catch (error) {
        console.error('PDF render failed:', error);
        if (token !== prPdfToken) return;
        const msg = document.createElement('p');
        msg.className = 'rounded-xl bg-rose-50 p-4 text-xs font-bold text-rose-700';
        msg.textContent = error.message || 'Unable to display this PDF.';
        pages.replaceChildren(msg);
    }
};

/* ---------- PDF compile helpers ---------- */
function prCssToMm(value, fallback) {
    const n = parseFloat(value);
    if (!Number.isFinite(n)) return fallback;
    const v = String(value);
    if (v.endsWith('mm')) return n;
    if (v.endsWith('cm')) return n * 10;
    if (v.endsWith('in')) return n * 25.4;
    if (v.endsWith('px')) return n * 25.4 / 96;
    return fallback;
}
// Mirror Shadow DOM into an isolated document so the server receives the actual form.
async function prRenderFormHtml(host) {
    const shadow = host.shadowRoot;
    const hostCs = getComputedStyle(host);
    const widthPx = Math.round(host.offsetWidth) || 794;
    const iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    Object.assign(iframe.style, {position: 'fixed', left: '-10000px', top: '0', width: widthPx + 'px', height: '1200px', border: '0', pointerEvents: 'none'});
    document.body.appendChild(iframe);
    try {
        const doc = iframe.contentDocument, win = iframe.contentWindow;
        doc.open(); doc.write('<!doctype html><html><head><meta charset="utf-8"></head><body style="margin:0;background:#fff"></body></html>'); doc.close();

        let adopted = '';
        for (const sheet of (shadow.adoptedStyleSheets || [])) adopted += [...sheet.cssRules].map(r => r.cssText).join('\n');
        adopted = adopted.replace(/:host\b/g, '#pr-print-root');
        if (adopted) { const s = doc.createElement('style'); s.textContent = adopted; doc.head.appendChild(s); }

        const container = doc.createElement('div');
        container.id = 'pr-print-root';
        Object.assign(container.style, {
            width: '100%', boxSizing: 'border-box', background: '#fff', padding: '0',
            color: hostCs.color, fontFamily: hostCs.fontFamily, fontSize: hostCs.fontSize, lineHeight: hostCs.lineHeight
        });
        doc.body.appendChild(container);
        shadow.childNodes.forEach(node => container.appendChild(doc.importNode(node, true)));
        const visualPages = [...container.querySelectorAll('.generic-paper-page')];
        if (visualPages.length) {
            let mergedMain = null;
            visualPages.forEach(page => {
                const pageMain = page.querySelector(':scope > .form-document, :scope > .rfq-document, :scope > main');
                if (!pageMain) { page.remove(); return; }
                if (!mergedMain) mergedMain = pageMain;
                else while (pageMain.firstChild) mergedMain.appendChild(pageMain.firstChild);
                page.remove();
            });
            if (mergedMain) {
                Object.assign(mergedMain.style, {width: '100%', height: 'auto', minHeight: '0', overflow: 'visible', margin: '0'});
                container.appendChild(mergedMain);
            }
        }
        container.querySelectorAll('style').forEach(s => { s.textContent = s.textContent.replace(/:host\b/g, '#pr-print-root'); });

        // innerHTML/cloneNode do not reliably carry typed values over, so copy them explicitly.
        const src = [...shadow.querySelectorAll('input,textarea,select')], dst = [...container.querySelectorAll('input,textarea,select')];
        src.forEach((from, i) => {
            const to = dst[i]; if (!to) return;
            if (from.tagName === 'SELECT') to.selectedIndex = from.selectedIndex;
            else if (from.tagName === 'TEXTAREA') { to.value = from.value; to.textContent = from.value; }
            else if (from.type === 'checkbox' || from.type === 'radio') { to.checked = from.checked; to.toggleAttribute('checked', from.checked); }
            else { to.value = from.value; to.setAttribute('value', from.value); }
        });

        // Strip editor-only UI.
        container.querySelectorAll('script,button,.template-row,.add-actions-row,.no-print,[data-pdf-ignore]').forEach(el => el.remove());

        await (doc.fonts?.ready || Promise.resolve());
        await Promise.all([...container.querySelectorAll('img')].map(img => img.complete ? null : new Promise(r => { img.onload = img.onerror = r; })));
        await new Promise(r => win.requestAnimationFrame(() => win.requestAnimationFrame(r)));

        container.querySelectorAll('input,textarea,select').forEach(control => {
            if (control.type === 'hidden') { control.remove(); return; }
            const text = control.tagName === 'SELECT'
                ? (control.selectedOptions[0]?.textContent || '')
                : (control.type === 'checkbox' || control.type === 'radio'
                    ? (control.checked ? '[✓]' : '[ ]')
                    : control.value);
            const value = doc.createElement('span');
            value.style.whiteSpace = 'pre-wrap';
            value.style.overflowWrap = 'anywhere';
            value.textContent = text;
            control.replaceWith(value);
        });
        container.querySelectorAll('[contenteditable]').forEach(el => el.removeAttribute('contenteditable'));
        for (const img of container.querySelectorAll('img')) {
            const source = img.currentSrc || img.src;
            if (!img.hasAttribute('src') && !img.hasAttribute('srcset')) continue;
            if (/^data:image\/(?:png|jpe?g|gif|webp);base64,/i.test(source)) {
                img.src = source;
                img.removeAttribute('srcset');
                continue;
            }
            if (!img.complete || !img.naturalWidth) throw new Error('A form image did not load and cannot be included in the PDF.');
            if (!source.startsWith('data:image/') && new URL(source, window.location.href).origin !== window.location.origin) {
                throw new Error('External form images cannot be included in a PDF. Use an embedded or same-site image.');
            }
            try {
                const canvas = doc.createElement('canvas');
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                canvas.getContext('2d').drawImage(img, 0, 0);
                img.src = canvas.toDataURL('image/png');
            } catch (error) {
                throw new Error('A form image could not be safely embedded in the PDF.', {cause: error});
            }
            img.removeAttribute('srcset');
        }
        const pageWidth = prCssToMm(host.dataset.formPageWidth || '210mm', 210);
        const pageHeight = prCssToMm(host.dataset.formPageHeight || '297mm', 297);
        const margins = {
            top: prCssToMm(host.dataset.formMarginTop || '10mm', 10),
            right: prCssToMm(host.dataset.formMarginRight || '10mm', 10),
            bottom: prCssToMm(host.dataset.formMarginBottom || '10mm', 10),
            left: prCssToMm(host.dataset.formMarginLeft || '10mm', 10)
        };
        const printStyle = doc.createElement('style');
        printStyle.textContent = `@page{size:${pageWidth}mm ${pageHeight}mm;margin:${margins.top}mm ${margins.right}mm ${margins.bottom}mm ${margins.left}mm}html,body{margin:0;padding:0;background:#fff}#pr-print-root{width:100%;box-sizing:border-box}`;
        doc.head.appendChild(printStyle);
        return {
            html: '<!doctype html>' + doc.documentElement.outerHTML,
            pageWidth,
            pageHeight,
            marginTop: margins.top,
            marginRight: margins.right,
            marginBottom: margins.bottom,
            marginLeft: margins.left
        };

    } finally {
        iframe.remove();
    }
}

window.updatePrCompletenessCharts=function(){
 const mandatory=[...document.querySelectorAll('[data-attachment-card][data-is-mandatory="1"]')];
 const phaseCards=[...document.querySelectorAll('#phase-timeline-view section[id^="phase-card-"]')];
 const completedPhaseCount=phaseCards.filter(phase=>{const required=[...phase.querySelectorAll('[data-attachment-card][data-is-mandatory="1"]')];return required.every(card=>card.dataset.workflowStatus==='COMPLETED')}).length;
 const completedPhaseLabel=document.querySelector('[data-completed-phases]');if(completedPhaseLabel)completedPhaseLabel.textContent=String(completedPhaseCount);
 document.querySelectorAll('[data-completeness-chart]').forEach(chart=>{
  const cards=chart.dataset.completenessChart==='overall'?mandatory:mandatory.filter(card=>card.dataset.attachmentPhaseId===chart.dataset.phaseId);
  const required=cards.length,complete=cards.filter(card=>card.dataset.workflowStatus==='COMPLETED').length;
  const percent=required?Math.round(complete*100/required):100;
  chart.setAttribute('aria-label',`${chart.dataset.completenessChart==='overall'?'Overall':'Current phase'} completeness: ${percent} percent`);
  const key=chart.dataset.completenessChart,bar=document.querySelector(`[data-completeness-bar="${key}"]`),remaining=document.querySelector(`[data-completeness-remaining="${key}"]`);
  if(bar)bar.style.width=`${percent}%`;if(remaining)remaining.textContent=String(Math.max(0,required-complete));
  const value=chart.querySelector('[data-completeness-percent]'),from=chart.dataset.hasAnimated==='true'?Number(chart.dataset.currentPercent||0):0,color=chart.dataset.chartColor||'#2563eb',started=performance.now();
  if(chart._pieAnimationFrame)cancelAnimationFrame(chart._pieAnimationFrame);
  const animate=now=>{const progress=Math.min(1,(now-started)/850),eased=1-Math.pow(1-progress,3),shown=Math.round(from+(percent-from)*eased);chart.style.background=`conic-gradient(${color} ${shown}%, #e2e8f0 0)`;if(value)value.textContent=`${shown}%`;if(progress<1)chart._pieAnimationFrame=requestAnimationFrame(animate);else{chart.dataset.currentPercent=String(percent);chart.dataset.hasAnimated='true'}};
  chart._pieAnimationFrame=requestAnimationFrame(animate);
  const count=document.querySelector(`[data-completeness-count="${key}"]`);
  if(count)count.textContent=required?`${complete} of ${required} required attachments complete`:(key==='phase'?'No required attachments in this phase':'No required attachments configured');
 });
};
// Alpine state for attachment selection, autosave, workflow actions, and signing.
function prWorkspace(){return{
 scale:1,isComplete:false,canvasReady:false,isPrimaryForm:false,canEdit:false,prFullySigned:false,viewMode:'form',selectedAttachment:null,pendingReplace:null,replaceDialogOpen:false,activeUploadAttachmentId:null,pdfPreviewUrl:'',pdfFileName:'',pdfPreviews:{},completedAttachments:[],attachmentStatus:{},saveBusy:false,formComplete:false,formDirty:false,formChangedInSession:false,formEditVersion:0,feedbackMessage:'Changes save automatically after you finish editing a field.',feedbackSuccess:false,signMode:false,signing:{loading:false,hasTurn:false,canSign:false,passwordRequired:false,message:'',roleName:''},signPassword:'',signPlacement:null,signBusy:false,signingToken:0,autoSaveTimer:null,fullscreen:false,
 initWorkspace(){const input=document.getElementById('existing-attachment-data'),form=document.getElementById('pr-form');this.canEdit=form?.dataset.canEdit==='1';this.prFullySigned=form?.dataset.prFullySigned==='1';let saved={};try{saved=JSON.parse(input?.textContent||'{}')||{}}catch(e){};Object.entries(saved).forEach(([id,record])=>{const key=String(id);this.attachmentStatus[key]=!!record.form_complete;if(record.ready||record.completed){if(!this.completedAttachments.includes(key))this.completedAttachments.push(key)}});this.$nextTick(()=>{window.updatePrCompletenessCharts?.();const params=new URLSearchParams(window.location.search),target=params.get('open_attachment');if(!target)return;const editPr=params.get('edit_pr')==='1';if(target==='pr'){const pr=saved.pr||{};this.selectAttachment('pr','Purchase Request',editPr,true,editPr?'form':'upload',pr.file_url||null,pr.file_name||null);this.canEdit=editPr;return}const card=document.querySelector(`[data-attachment-card="${CSS.escape(target)}"]`);if(!card)return;const record=saved[target]||{};const hasForm=editPr&&card.dataset.hasForm==='1';this.selectAttachment(target,card.dataset.attachmentName||'Attachment',hasForm,false,hasForm?'form':'upload',record.file_url||null,record.file_name||null);this.canEdit=hasForm&&card.dataset.canAct==='1'})},

 setScale(value){this.scale=Math.max(.35,Math.min(3,Number(value)||1));this.$nextTick(()=>requestAnimationFrame(()=>window.syncPrCanvasSize?.()))},zoomIn(){this.setScale(this.scale+.1)},zoomOut(){this.setScale(this.scale-.1)},resetZoom(){this.setScale(1)},fitWidth(){if(this.viewMode==='upload')window.fitPrPdfWidth?.();else window.fitPrCanvasWidth?.()},
 async toggleFullscreen(){const target=document.getElementById('attachment-modal');if(!target)return;try{if(document.fullscreenElement===target)await document.exitFullscreen();else await target.requestFullscreen()}catch(error){console.error('Unable to change attachment fullscreen mode.',error);this.feedback('Fullscreen mode could not be changed.',false)}},
 selectAttachment(id,name,hasForm,isPrimaryForm,view,savedPdfUrl=null,savedPdfName=null){this.canEdit=document.querySelector(`[data-attachment-card="${String(id)}"]`)?.dataset.canAct==='1';this.formComplete=false;this.formDirty=false;this.formChangedInSession=false;this.formEditVersion=0;this.revokePdfPreview();id=String(id);this.selectedAttachment={id,name,hasForm:!!hasForm,isPrimaryForm:!!isPrimaryForm};this.activeUploadAttachmentId=id;this.viewMode=hasForm&&view==='form'?'form':'upload';this.feedbackMessage=this.viewMode==='form'?'Changes save automatically after you finish editing a field.':'';this.feedbackSuccess=false;this.isPrimaryForm=!!isPrimaryForm;this.pdfPreviewUrl=savedPdfUrl||'';this.pdfFileName=savedPdfName||'';this.canvasReady=false;this.scale=1;this.fullscreen=document.fullscreenElement===document.getElementById('attachment-modal');if(this.viewMode==='form'){this.$nextTick(()=>{const loader=this.isPrimaryForm?window.loadPrAttachment:window.loadGenericAttachment;this.canvasReady=loader?.(id)===true;if(this.canvasReady)this.$nextTick(()=>{this.fitWidth();if(this.isPrimaryForm&&window.prAttachmentCompleteness?.[id]!==undefined)this.attachmentStatus[id]=!!window.prAttachmentCompleteness[id];if(!this.isPrimaryForm)this.formComplete=!!window.getActiveGenericAttachmentSnapshot?.()?.complete})})}else{window.parkPrCanvas?.();this.canvasReady=!!this.pdfPreviewUrl;this.$nextTick(async()=>{this.loadSigningStatus();await window.renderPrPdf?.(this.pdfPreviewUrl);if(this.canvasReady)this.fitWidth()})}},
 startUpload(id,name,hasForm,isPrimaryForm,savedPdfUrl=null,savedPdfName=null){this.canEdit=document.querySelector(`[data-attachment-card="${String(id)}"]`)?.dataset.canAct==='1';if(hasForm)return;this.selectAttachment(id,name,false,false,'upload',savedPdfUrl,savedPdfName);this.$nextTick(()=>this.openUploadPicker())},
 switchToUpload(){if(!this.selectedAttachment||this.selectedAttachment.hasForm)return;this.viewMode='upload';this.canvasReady=!!this.pdfPreviewUrl;window.parkPrCanvas?.();this.$nextTick(async()=>{this.loadSigningStatus();await window.renderPrPdf?.(this.pdfPreviewUrl);if(this.pdfPreviewUrl)this.fitWidth()})},
 switchToForm(){if(!this.selectedAttachment?.hasForm)return;this.formComplete=false;this.viewMode='form';this.isPrimaryForm=this.selectedAttachment.isPrimaryForm;const loader=this.isPrimaryForm?window.loadPrAttachment:window.loadGenericAttachment;this.canvasReady=loader?.(this.selectedAttachment.id)===true;if(this.canvasReady)this.$nextTick(()=>this.fitWidth())},
 async closeAttachment(){const editingForm=!!(this.selectedAttachment?.hasForm&&this.viewMode==='form');if(this.autoSaveTimer){clearTimeout(this.autoSaveTimer);this.autoSaveTimer=null}if(editingForm){while(this.saveBusy)await new Promise(resolve=>setTimeout(resolve,40));if(this.formDirty){const saved=await this.saveFormChanges(true);if(!saved)return}}this.selectedAttachment=null;this.formDirty=false;this.formChangedInSession=false;this.canvasReady=false;this.isPrimaryForm=false;this.fullscreen=false;this.revokePdfPreview();window.parkPrCanvas?.();await this.$nextTick();const url=new URL(window.location.href);url.searchParams.delete('open_attachment');url.searchParams.delete('edit_pr');window.history.replaceState({},'',url);window.location.reload()},
 discardActiveForm(){if(this.autoSaveTimer){clearTimeout(this.autoSaveTimer);this.autoSaveTimer=null}this.formDirty=false;this.formChangedInSession=false;this.selectedAttachment=null;this.canvasReady=false;this.isPrimaryForm=false;this.fullscreen=false;this.revokePdfPreview();window.parkPrCanvas?.()},
 openUploadPicker(){const input=document.getElementById(`attachment-pdf-upload-${this.activeUploadAttachmentId}`);if(input)input.click()},
 async attachmentFileSelected(event,id){const file=event.target.files?.[0];if(!file)return;if(file.type!=='application/pdf'&&!file.name.toLowerCase().endsWith('.pdf')){alert('Please select a PDF file.');event.target.value='';return}id=String(id||this.activeUploadAttachmentId||'');const old=this.pdfPreviews[id]?.url;if(old?.startsWith('blob:'))URL.revokeObjectURL(old);const localUrl=URL.createObjectURL(file);this.pdfPreviews[id]={url:localUrl,name:file.name};this.pdfPreviewUrl=localUrl;this.pdfFileName=file.name;this.viewMode='upload';this.canvasReady=true;this.saveBusy=true;try{const result=await this.persistAttachment(id,{},true,file,false,false);if(result?.file_url){if(localUrl.startsWith('blob:'))URL.revokeObjectURL(localUrl);this.pdfPreviewUrl=result.file_url;this.pdfFileName=result.file_name||file.name;await window.renderPrPdf?.(this.pdfPreviewUrl);this.setAttachmentReady(id,result);this.loadSigningStatus();this.feedback('PDF uploaded and saved.',true)}this.$nextTick(()=>this.fitWidth())}catch(error){this.feedback(error.message||'Unable to save the PDF.',false);alert(error.message||'Unable to save the PDF.')}finally{this.saveBusy=false;event.target.value=''}},
 queueAutoSave(id){if(!this.selectedAttachment||this.viewMode!=='form'||String(this.selectedAttachment.id)!==String(id)||!this.canvasReady)return;this.formDirty=true;this.formChangedInSession=true;this.formEditVersion++;if(this.autoSaveTimer)clearTimeout(this.autoSaveTimer);this.feedback('Changes detected · saving automatically…',false);this.autoSaveTimer=setTimeout(()=>this.saveFormChanges(true),900)},
    async saveFormChanges(silent = false) {
      if (!this.selectedAttachment) return;
      if (!this.selectedAttachment.hasForm) {
          this.feedback(this.pdfFileName ? 'PDF already saved.' : 'Choose a PDF to save this attachment.', true);
          return
      }
      const snapshot = this.selectedAttachment.isPrimaryForm
          ? window.getActivePrAttachmentSnapshot?.()
          : window.getActiveGenericAttachmentSnapshot?.();
      if (!snapshot || String(snapshot.id) !== String(this.selectedAttachment.id)) {
          if (!silent) alert('The selected form is not ready to save.');
          return
      }
      const editVersion = this.formEditVersion;
      this.saveBusy = true;
      try {
          const result = await this.persistAttachment(snapshot.id, snapshot.fields, snapshot.complete, null, false, false);
          this.setAttachmentReady(snapshot.id, result);
          if (this.formEditVersion === editVersion) this.formDirty = false;
          const savedMessage = this.selectedAttachment.isPrimaryForm
              ? `Saved · ${result.procurement_item_count ?? 0} procurement item row${result.procurement_item_count === 1 ? '' : 's'}.`
              : (result.workflow_status === 'COMPLETED'
                  ? 'Attachment completed.'
                  : (result.workflow_status === 'READY'
                      ? 'PDF compiled and ready to route.'
                      : (result.is_complete
                          ? 'Form saved and complete · compile the PDF to make it ready.'
                          : 'Form progress saved.')));
          this.feedback(savedMessage, true);
          return result
      } catch (error) {
          this.feedback(error.message || 'Unable to save form changes.', false);
          if (!silent) alert(error.message || 'Unable to save form changes.');
          return null
      } finally {
          this.saveBusy = false
      }
  },
 hasAtLeastOnePrItem(fields={}){
  const rows={};
  for(const [key,raw] of Object.entries(fields||{})){
   const value=Array.isArray(raw)?(raw.find(item=>String(item??'').trim()!=='')??raw[0]??''):raw;
   let match=key.match(/^items\[(\d+)\]\[sub_items\]\[(\d+)\]\[([^\]]+)\]$/);
   if(match){rows[match[1]]=rows[match[1]]||{};rows[match[1]].sub_items=rows[match[1]].sub_items||{};rows[match[1]].sub_items[match[2]]=rows[match[1]].sub_items[match[2]]||{};rows[match[1]].sub_items[match[2]][match[3]]=value;continue}
   match=key.match(/^items\[(\d+)\]\[([^\]]+)\]$/);if(match){rows[match[1]]=rows[match[1]]||{};rows[match[1]][match[2]]=value}
  }
  const hasData=row=>{if(!row)return false;for(const key of ['unit','description','cost'])if(String(row[key]??'').trim()!=='')return true;const qty=String(row.quantity??'').replace(/,/g,'').trim();return qty!==''&&Number.isFinite(Number(qty))&&Number(qty)!==1};
  return Object.values(rows).some(row=>!row.is_lot?hasData(row):Object.values(row.sub_items||{}).some(hasData));
 },

 async loadSigningStatus(){
  const att=this.selectedAttachment,form=document.getElementById('pr-form');
  const empty={loading:false,hasTurn:false,canSign:false,passwordRequired:false,message:'',roleName:''};
  if(!att||!this.pdfPreviewUrl||!form?.dataset.signingStatusUrl){this.signing=empty;return}
  const mine=++this.signingToken;
  this.signing={...empty,loading:true};
  try{
   const response=await fetch(form.dataset.signingStatusUrl.replace('__ID__',encodeURIComponent(att.id)),{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
   const body=await response.json().catch(()=>({}));
   if(mine!==this.signingToken||String(this.selectedAttachment?.id)!==String(att.id))return; // a newer request/attachment superseded this one
   if(!response.ok)throw new Error(body.message||'Unable to check signing status.');
   this.signing={loading:false,hasTurn:!!body.has_turn,canSign:!!body.can_sign,passwordRequired:!!body.password_required,message:body.message||'',roleName:body.role_name||''};
  }catch(error){
   if(mine===this.signingToken)this.signing={...empty,message:error.message||'Unable to check signing status.'};
  }
 },

 signAttachment(){
  if(!this.selectedAttachment||!this.pdfPreviewUrl||!this.signing.canSign)return;
  const on=!this.signMode;
  window.setPrSignMode(on);
  this.feedback(on?'Drag on a page to draw the signature box. Drag the box to move it; click outside it to remove it.':'Signature placement cancelled.',false);
 },

 async submitDigiSign(){
  const att=this.selectedAttachment,place=this.signPlacement,form=document.getElementById('pr-form');
  if(this.signBusy||!att||!place||!this.signing.canSign||!form?.dataset.signUrl)return;
  const pageNumber=Number(place.page),pageCount=Number(document.getElementById('pdf-canvas-pages')?.dataset.pageCount||0);
  if(!Number.isInteger(pageNumber)||pageNumber<1||pageNumber>pageCount){this.feedback('Select a signature position on a valid PDF page before signing.',false);return}
  if(!place.pageWidth||!place.pageHeight){this.feedback('Unable to read the page size. Reopen the PDF and place the signature again.',false);return}
  if(this.signing.passwordRequired&&!this.signPassword){this.feedback('Enter your P12 password before signing.',false);return}
  const token=form.querySelector('input[name="_token"]')?.value||document.querySelector('meta[name="csrf-token"]')?.content||'';
  const payload={page:pageNumber,left:place.x*place.pageWidth,top:place.y*place.pageHeight,width:place.width*place.pageWidth,height:place.height*place.pageHeight,page_width:place.pageWidth,page_height:place.pageHeight};
  if(this.signing.passwordRequired)payload.password=this.signPassword;
  this.signBusy=true;this.feedback('Signing with DigiSign…',false);
  try{
   const response=await fetch(form.dataset.signUrl.replace('__ID__',encodeURIComponent(att.id)),{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',body:JSON.stringify(payload)});
   const body=await response.json().catch(()=>({}));
   if(!response.ok||body.ok===false)throw new Error(body.message||(body.errors?Object.values(body.errors).flat().join(' '):'DigiSign could not sign this document.'));
   window.location.reload();
  }catch(error){
   this.feedback(error.message||'Unable to sign the document.',false);
   alert(error.message||'Unable to sign the document.');
  }finally{
   this.signBusy=false;this.signPassword='';
  }
 },

 async compileAttachmentPdf(){
  if(this.saveBusy)return;
  const attachment=this.selectedAttachment,host=document.getElementById('form-canvas-root');
  if(!attachment?.hasForm||!host?.shadowRoot){this.feedback('Open a form attachment before compiling.',false);return}
  if(this.autoSaveTimer){clearTimeout(this.autoSaveTimer);this.autoSaveTimer=null}
  const check=attachment.isPrimaryForm?window.checkPrFormCompleteness?.():window.checkGenericFormCompleteness?.();
  if(!check?.complete){
   this.formComplete=false;
   const message=check?.message||'Complete all required fields before compiling the PDF.';
   this.feedback(message,false);
   try{check?.field?.scrollIntoView?.({block:'center',behavior:'smooth'});check?.field?.focus?.()}catch(e){}
   alert(message);return;
  }
  const snapshot=attachment.isPrimaryForm?window.getActivePrAttachmentSnapshot?.():window.getActiveGenericAttachmentSnapshot?.();
  if(!snapshot||String(snapshot.id)!==String(attachment.id)){alert('The selected form is not ready to compile.');return}
  if(!snapshot.complete){this.feedback('Complete all required fields before compiling the PDF.',false);return}
  this.saveBusy=true;this.feedback('Compiling PDF and saving attachment…',false);
  try{
   const render=await prRenderFormHtml(host);
   const result=await this.persistAttachment(attachment.id,snapshot.fields,true,null,true,true,render);
   if(!result?.is_ready||!result?.file_url)throw new Error('PDF was generated, but the server did not confirm that it was saved to this attachment.');
   window.location.reload();
  }catch(error){
   console.error('Compile PDF failed:',error);
   this.feedback(error.message||'Unable to compile the PDF.',false);
   alert(error.message||'Unable to compile the PDF.');
  }finally{
   this.saveBusy=false;
  }
 },
 async persistAttachment(id,fields={},complete=false,file=null,isCompiled=false,silent=false,rendered=null){const form=document.getElementById('pr-form'),token=form?.querySelector('input[name="_token"]')?.value||document.querySelector('meta[name="csrf-token"]')?.content||'';const data=new FormData();data.append('_token',token);data.append('fields',JSON.stringify(fields||{}));data.append('is_complete',complete?'1':'0');data.append('is_compiled',isCompiled?'1':'0');if(file)data.append('attachment_file',file,file.name||'attachment.pdf');if(rendered){data.append('rendered_html',rendered.html);data.append('pdf_page_width',String(rendered.pageWidth));data.append('pdf_page_height',String(rendered.pageHeight));data.append('pdf_margin_top',String(rendered.marginTop));data.append('pdf_margin_right',String(rendered.marginRight));data.append('pdf_margin_bottom',String(rendered.marginBottom));data.append('pdf_margin_left',String(rendered.marginLeft))}const url=(form?.dataset.attachmentSaveUrl||'').replace('__ID__',encodeURIComponent(String(id)));const response=await fetch(url,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:data,credentials:'same-origin'});let body={};try{body=await response.json()}catch(e){}if(!response.ok||body.ok===false){const message=body.message||(body.errors?Object.values(body.errors).flat().join(' '):'The server could not save this attachment.');throw new Error(message)}if(body.is_ready&&body.file_url){this.pdfFileName=body.file_name||this.pdfFileName;this.pdfPreviewUrl=body.file_url}return body},
 openAttachmentAction(event,action){const b=event.currentTarget,item={id:String(b.dataset.attachmentId),name:b.dataset.attachmentName||'Attachment',hasForm:b.dataset.hasForm==='1',isMain:b.dataset.mainPr==='1',url:b.dataset.fileUrl||null,fileName:b.dataset.fileName||null};if(action==='view'){this.selectAttachment(item.id,item.name,item.hasForm,item.isMain,'upload',item.url,item.fileName);return}this.pendingReplace=item;this.replaceDialogOpen=true},
 confirmReplace(){const item=this.pendingReplace;this.replaceDialogOpen=false;this.pendingReplace=null;if(!item)return;if(item.hasForm)this.selectAttachment(item.id,item.name,true,item.isMain,'form',item.url,item.fileName);else this.startUpload(item.id,item.name,false,false,item.url,item.fileName)},
 setAttachmentReady(id,result){id=String(id);const ready=!!result?.is_ready,complete=!!result?.is_complete,state=result?.workflow_status||(complete?'COMPLETED':(ready?'READY':'INCOMPLETE'));if(ready&&!this.completedAttachments.includes(id))this.completedAttachments.push(id);if(!ready)this.completedAttachments=this.completedAttachments.filter(x=>x!==id);this.attachmentStatus[id]=complete;const card=document.querySelector(`[data-attachment-card="${id}"]`);if(card){card.dataset.workflowStatus=state;const iconBox=card.querySelector('[data-attachment-icon]');if(iconBox){iconBox.classList.remove('bg-emerald-50','text-emerald-700','dark:bg-emerald-950','dark:text-emerald-300','bg-slate-100','text-slate-500','dark:bg-slate-800','dark:text-slate-400','bg-blue-50','text-blue-700','dark:bg-blue-950','dark:text-blue-300','bg-rose-50','text-rose-700','dark:bg-rose-950','dark:text-rose-300');const tone=state==='COMPLETED'?'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300':(card.dataset.isMandatory!=='1'?'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400':(card.dataset.hasForm==='1'?'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300':'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300'));iconBox.classList.add(...tone.split(' '))}const status=card.querySelector(`[data-attachment-status-for="${id}"]`);if(status){const tone=state==='COMPLETED'?'text-emerald-700 dark:text-emerald-300':(state==='ROUTING'?'text-blue-700 dark:text-blue-300':'text-amber-700 dark:text-amber-300');const icon=state==='COMPLETED'?'fa-circle-check':(state==='ROUTING'?'fa-share-nodes':'fa-clock');const label=state==='COMPLETED'?'Completed':(state==='ROUTING'?`Routing · ${result?.signed_count||0}/${result?.signatory_count||0} signed`:'Ready to route');status.className='inline-flex items-center gap-1.5 font-extrabold '+tone;status.innerHTML=`<i class="fa-solid ${icon}"></i><span>${label}</span>`}const notReady=card.querySelector(`[data-actions-not-ready="${id}"]`),isReady=card.querySelector(`[data-actions-ready="${id}"]`);notReady?.classList.toggle('hidden',ready);isReady?.classList.toggle('hidden',!ready);card.querySelector('[data-route-attachment]')?.classList.toggle('hidden',state!=='READY'||card.dataset.hasSigned==='1');const phase=card.closest('[data-phase-id]');if(phase){const canRoute=[...phase.querySelectorAll('[data-attachment-card]')].some(item=>item.dataset.workflowStatus==='READY'&&item.dataset.hasSignatories==='1'&&item.dataset.hasSigned!=='1');const routeAll=phase.querySelector('[data-route-all-button]');if(routeAll)routeAll.style.display=canRoute?'':'none'}window.updatePrCompletenessCharts?.();card.querySelectorAll(`[data-attachment-id="${id}"]`).forEach(button=>{if('fileUrl'in button.dataset)button.dataset.fileUrl=result?.file_url||'';if('fileName'in button.dataset)button.dataset.fileName=result?.file_name||''})}},
 feedback(message,success=false){this.feedbackMessage=message||'';this.feedbackSuccess=!!success},
 revokePdfPreview(){for(const preview of Object.values(this.pdfPreviews||{}))if(preview?.url?.startsWith('blob:'))URL.revokeObjectURL(preview.url);this.pdfPreviews={};this.pdfPreviewUrl='';this.pdfFileName='';this.signMode=false;this.signPlacement=null;this.signPassword='';this.signingToken++;this.signing={loading:false,hasTurn:false,canSign:false,passwordRequired:false,message:'',roleName:''};window.clearPrPdf?.()},
 markAttachmentCompleted(id,complete){this.attachmentStatus[String(id)]=!!complete},
 prepareSubmission(event){const action=event.submitter?.dataset?.saveAction||document.getElementById('save-action')?.value||'draft';const status=document.getElementById('attachment-completion-data');if(status)status.value=JSON.stringify(this.attachmentStatus);if(typeof window.preparePrSubmission!=='function'||window.preparePrSubmission(event,action)===false)event.preventDefault()}
}}

</script>

@push('scripts')
<script>
(function() {
    // Preserve each attachment's in-progress canvas while users switch between forms.
    let rowIdx = 0;
    let draggedRow = null;
    let draggedRows = [];
    let activeDropTarget = null;
    let activeDropPlacement = null;
    const attachmentFormCache = new Map();
    let activeCanvasAttachmentId = null;
    let canvasRootEventsBound = false;
    const dragDropRoots = new WeakSet();
    const prFieldInteractionRoots = new WeakSet();
    const canvasCellActivationRoots = new WeakSet();
    let prOutsideClickBound = false;
    let existingAttachmentData = {};
    try { existingAttachmentData = JSON.parse(document.getElementById('existing-attachment-data')?.textContent || '{}') || {}; } catch (error) { console.error('Unable to read saved attachment data.', error); }

    // Resolve controls inside the canvas Shadow DOM.
    function getCanvasRoot() {
        const host = document.getElementById('form-canvas-root');
        return host?.shadowRoot || host || document;
    }
    function getTbody(root = getCanvasRoot()) { return root?.querySelector?.('[data-each="procurement.items"]') || null; }
    function getGrandTotalInput() { return getCanvasRoot().querySelector('[data-print="procurement.abc_amount"]'); }
    function getPrimaryPrRoot() {
        const host = getCanvasHost();
        if (host?.dataset.mainPr === '1' && host.shadowRoot?.querySelector('[data-each="procurement.items"]')) return host.shadowRoot;
        for (const [id, fragment] of attachmentFormCache.entries()) {
            const template = document.getElementById(`attachment-form-template-${id}`);
            if (template?.dataset.mainPr === '1' && fragment?.querySelector?.('[data-each="procurement.items"]')) return fragment;
        }
        return null;
    }

    function getCanvasHost() { return document.getElementById('form-canvas-root'); }

    let cachedPrTemplateSchema = null;
    function getPrTemplateSchema() {
        if (cachedPrTemplateSchema) return cachedPrTemplateSchema;
        try { cachedPrTemplateSchema = JSON.parse(document.getElementById('pr-template-schema')?.textContent || '{}'); }
        catch (error) { console.error('Unable to read the PR form schema.', error); cachedPrTemplateSchema = {}; }
        return cachedPrTemplateSchema;
    }
    function getPrFieldDefinition(path) { return getPrTemplateSchema().fields?.[path] || null; }
    function queryAllIncludingRoot(root, selector) {
        return [
            ...(root.matches?.(selector) ? [root] : []),
            ...root.querySelectorAll(selector),
        ];
    }
    function refreshCanvasCellOverlays(root) {
        if (!root || root === document) return;
        const styleRoot = root.matches?.('tr,td') ? getCanvasRoot() : root;
        if (!styleRoot.querySelector('style[data-canvas-cell-overlay-styles]')) {
            const style = document.createElement('style');
            style.dataset.canvasCellOverlayStyles = '1';
            style.textContent = `
                [data-canvas-editable-cell]{position:relative;isolation:isolate;cursor:pointer;transition:background-color .12s ease}
                [data-canvas-editable-cell]::after{content:"";position:absolute;inset:0;z-index:4;pointer-events:none;opacity:0}
                [data-canvas-editable-cell].canvas-required-empty::after{background:rgba(239,68,68,.5);opacity:1}
                [data-canvas-editable-cell]:hover::after{background:rgba(59,130,246,.5);opacity:1}
                [data-canvas-editable-cell]:focus-within::after{opacity:0}
                [data-canvas-editable-cell]:focus-visible{outline:2px solid #2563eb;outline-offset:1px}
            `;
            styleRoot.prepend(style);
        }

        queryAllIncludingRoot(root, '[data-input]').forEach(element => {
            if (element.closest('.template-row')) return;
            const cell = element.matches('td') ? element : element.closest('td');
            if (!cell || cell !== element) return;
            cell.dataset.canvasEditableCell = '1';
            if (!cell.hasAttribute('tabindex')) cell.tabIndex = 0;
            if (!cell.hasAttribute('data-value')) cell.dataset.value = cell.dataset.boundValue ?? cell.textContent.trim();
            if (cell.dataset.boundValue === undefined) cell.dataset.boundValue = cell.dataset.value;
        });

        queryAllIncludingRoot(root, '[data-required]').forEach(element => {
            if (element.closest('.template-row')) return;
            const cell = element.matches('td') ? element : element.closest('[data-canvas-editable-cell]');
            if (!cell) return;
            const value = prFieldValue(cell);
            const empty = !cell.closest('[hidden]') && !String(value ?? '').trim();
            cell.classList.toggle('canvas-required-empty', empty);
            cell.toggleAttribute('aria-invalid', empty);
            if (empty) cell.setAttribute('data-required-empty', '');
            else cell.removeAttribute('data-required-empty');
        });
    }

    function bindCanvasCellActivation(root) {
        if (!root || root === document || canvasCellActivationRoots.has(root)) return;
        canvasCellActivationRoots.add(root);
        root.addEventListener('click', event => {
            const target = event.composedPath?.()[0] || event.target;
            if (target?.closest?.('input,textarea,select,button,a,[contenteditable="true"]')) return;
            const cell = target?.closest?.('[data-canvas-editable-cell]');
            if (!cell || getCanvasHost()?.dataset.mainPr !== '1') return;
            const canEdit = window.Alpine?.$data?.(document.getElementById('pr-workspace'))?.canEdit;
            if (!canEdit) return;
            const field = cell.matches('[data-input]') ? cell : cell.querySelector('[data-input]');
            if (field) {
                event.preventDefault();
                activatePrFieldEditor(field);
            }
        });
        root.addEventListener('keydown', event => {
            if (getCanvasHost()?.dataset.mainPr !== '1') return;
            const target = event.composedPath?.()[0] || event.target;
            if (target?.closest?.('input,textarea,select,[contenteditable="true"],[data-pr-editor]')) return;
            const cell = target?.closest?.('[data-canvas-editable-cell]');
            if (!cell || (event.key !== 'Enter' && event.key !== ' ')) return;
            event.preventDefault();
            cell.click();
        });
    }

    function bindNumericInput(control) {
        if (!control || control.dataset.numericInputBound === '1') return;
        control.dataset.numericInputBound = '1';
        const clean = value => {
            const filtered = String(value ?? '').replace(/[^\d.]/g, '');
            const dot = filtered.indexOf('.');
            return dot < 0 ? filtered : `${filtered.slice(0, dot + 1)}${filtered.slice(dot + 1).replace(/\./g, '')}`;
        };
        control.addEventListener('keydown', event => {
            if (event.ctrlKey || event.metaKey || event.altKey || event.key.length !== 1) return;
            if (!/[0-9.]/.test(event.key)) event.preventDefault();
            if (event.key === '.' && String(control.value).includes('.')) event.preventDefault();
        });
        control.addEventListener('beforeinput', event => {
            if (!event.data || !['insertText', 'insertFromPaste', 'insertCompositionText'].includes(event.inputType)) return;
            const current = String(control.value ?? '');
            const start = control.selectionStart ?? current.length;
            const end = control.selectionEnd ?? start;
            const inserted = clean(event.data);
            const candidate = current.slice(0, start) + inserted + current.slice(end);
            if (/^\d*\.?\d*$/.test(candidate) && inserted === event.data) return;
            event.preventDefault();
            const next = clean(candidate);
            control.value = next;
            control.dispatchEvent(new Event('input', {bubbles: true}));
        });
        control.addEventListener('input', () => {
            const value = String(control.value ?? '');
            const sanitized = clean(value);
            if (value !== sanitized) control.value = sanitized;
        });
    }

    function configurePrFields(root) {
        const styleRoot = root.matches?.('tr,td') ? getCanvasRoot() : root;
        const rowTemplate = root.querySelector?.('#standalone-template');
        const actionsTemplate = root.querySelector?.('#add-actions-template');
        rowTemplate?.classList.add('template-row');
        actionsTemplate?.classList.add('template-row');
        if (rowTemplate) rowTemplate.hidden = true;
        if (actionsTemplate) actionsTemplate.hidden = true;
        root.querySelectorAll('[data-print]').forEach(element => {
            const printClass = {
                'item.line_no': 'ln-text',
                'item.total_cost': 'line-total',
                'procurement.abc_amount': 'pr-grand-total',
                'procurement.pr_number': 'pr-number',
            }[element.dataset.print];
            if (printClass) element.classList.add(printClass);
            if (element.dataset.print === 'procurement.abc_amount') element.closest('tr')?.classList.add('pr-grand-total-row');
        });
        if (!styleRoot.querySelector('style[data-pr-editor-styles]')) {
            const style = document.createElement('style');
            style.dataset.prEditorStyles = '1';
            style.textContent = `
                [data-pr-editor][hidden]{display:none!important}
                .pr-row.drag-drop-before>td{box-shadow:inset 0 3px #3b82f6!important}
                .pr-row.drag-drop-after>td{box-shadow:inset 0 -3px #3b82f6!important}
                .pr-row.drag-drop-into-lot.drag-drop-before>td{box-shadow:inset 0 3px #16a34a!important;background-color:#dcfce7!important}
                .pr-row.drag-drop-into-lot.drag-drop-after>td{box-shadow:inset 0 -3px #16a34a!important;background-color:#dcfce7!important}
                .pr-row.drag-drop-out-of-lot.drag-drop-before>td{box-shadow:inset 0 3px #ea580c!important;background-color:#ffedd5!important}
                .pr-row.drag-drop-out-of-lot.drag-drop-after>td{box-shadow:inset 0 -3px #ea580c!important;background-color:#ffedd5!important}
            `;
            styleRoot.prepend(style);
        }
        queryAllIncludingRoot(root, '[data-input]').forEach(boundElement => {
            let element = boundElement.matches('td') ? boundElement : boundElement.closest('td');
            if (!element) return;
            if (element !== boundElement) {
                const initialValue = boundElement.dataset.value ?? boundElement.textContent;
                element.dataset.input = boundElement.dataset.input;
                element.dataset.value = initialValue;
                boundElement.removeAttribute('data-input');
                element.replaceChildren(document.createTextNode(initialValue));
            }
            const definition = getPrFieldDefinition(element.dataset.input);
            if (!definition) return;
            const type = definition.inputType || 'text';
            const value = element.dataset.value ?? element.dataset.boundValue ?? element.textContent;
            element.dataset.value = value;
            element.dataset.boundValue = value;
            const isEditableText = element.dataset.input === 'item.description' || element.dataset.input === 'procurement.purpose';
            if (isEditableText) {
                element.classList.add('editable-content');
                element.classList.add(element.dataset.input === 'item.description' ? 'description-editable' : 'purpose-editable');
            } else {
                element.classList.add('pr-schema-field');
                if (element.dataset.input === 'item.quantity') element.classList.add('quantity-input');
                if (element.dataset.input === 'item.cost') element.classList.add('cost-input');
            }
            element.dataset.prFieldContainer = '1';
            element.dataset.inputType = type;
            if (definition.source) element.dataset.source = definition.source;
            if (element.classList.contains('editable-content')) {
                element.style.whiteSpace = 'pre-wrap';
                element.style.overflowWrap = 'anywhere';
            }
            const rowType = element.closest('tr[data-type]')?.dataset.type;
            const requiredForRow = definition.required
                && (rowType !== 'lot_member' || element.dataset.input === 'item.description');
            if (requiredForRow) {
                element.dataset.required = 'true';
                element.dataset.label = definition.label || element.dataset.input;
                element.classList.add('required-field');
            } else {
                delete element.dataset.required;
                element.classList.remove('required-field');
                element.classList.remove('canvas-required-empty');
                element.removeAttribute('aria-invalid');
                element.removeAttribute('data-required-empty');
            }
            if (definition.min !== undefined) element.dataset.min = String(definition.min);
            if (definition.step !== undefined) element.dataset.step = String(definition.step);
            if (!element.querySelector('[data-pr-editor]')) element.textContent = prFieldDisplayText(element, value);
        });
        refreshCanvasCellOverlays(root);
        if (!root.matches?.('tr,td')) bindCanvasCellActivation(root);
    }

    function prFieldControl(container) {
        return container?.querySelector?.('[data-pr-editor]') || null;
    }
    function prFieldValue(container) {
        const editor = container?.querySelector?.('[data-pr-editor]');
        if (editor) return String(editor.value ?? '');
        return String(container?.dataset.value ?? container?.dataset.boundValue ?? '');
    }
    function prFieldDisplayText(container, value) {
        if (container.dataset.displayValue !== undefined) return container.dataset.displayValue;
        if (container.dataset.format) return genericDisplayValue(container, value);
        if (container.dataset.input === 'item.cost' || container.dataset.input === 'item.quantity') {
            return value === '' ? '' : formatDecimal(value);
        }
        return genericDisplayValue(container, value);
    }
    function setPrFieldValue(container, value) {
        const normalized = value == null ? '' : String(value);
        container.dataset.value = normalized;
        container.dataset.boundValue = normalized;
        if (container.dataset.source && container.dataset.options) {
            try {
                const options = JSON.parse(container.dataset.options);
                const selected = options.find(option => String(option.value) === normalized);
                if (selected) container.dataset.displayValue = selected.label;
                else delete container.dataset.displayValue;
            } catch (error) {
                console.error(`Unable to resolve the display value for ${container.dataset.input}.`, error);
            }
        }
        const editor = prFieldControl(container);
        if (editor) {
            editor.value = normalized;
            return;
        }
        container.textContent = prFieldDisplayText(container, normalized);
    }
    function resizePrTextarea(control, container) {
        control.style.height = 'auto';
        const cellHeight = Math.max(0, container.clientHeight);
        const style = getComputedStyle(container);
        const verticalPadding = (parseFloat(style.paddingTop) || 0) + (parseFloat(style.paddingBottom) || 0);
        control.style.height = `${Math.max(control.scrollHeight, cellHeight - verticalPadding)}px`;
    }
    function createPrFieldEditor(container) {
        const definition = getPrFieldDefinition(container.dataset.input) || {};
        const type = definition.inputType || 'text';
        const control = type === 'select' ? document.createElement('select')
            : type === 'textarea' ? document.createElement('textarea')
                : document.createElement('input');
        control.dataset.prEditor = '1';
        if (definition.transportName) {
            const rowIndex = container.closest('tr')?.dataset.rowIdx;
            control.name = definition.transportName.replace('{idx}', rowIndex || '');
        }
        if (control instanceof HTMLInputElement) {
            control.type = ['number', 'date', 'datetime-local', 'email'].includes(type) ? type : 'text';
            if (type === 'number') bindNumericInput(control);
            if (definition.min !== undefined) control.min = String(definition.min);
            if (definition.step !== undefined) control.step = String(definition.step);
            if (definition.max !== undefined) control.max = String(definition.max);
        }
        if (definition.placeholder !== undefined && 'placeholder' in control) control.placeholder = String(definition.placeholder);
        if (definition.required) control.required = true;
        control.className = [...container.classList].filter(name => name !== 'pr-field-container').join(' ');
        Object.assign(control.style, {
            position: 'absolute',
            inset: '0',
            width: '100%',
            height: '100%',
            minWidth: '0',
            minHeight: '1em',
            boxSizing: 'border-box',
            margin: '0',
            padding: '0 4px',
            verticalAlign: 'middle',
            lineHeight: 'inherit',
            font: 'inherit',
            color: 'inherit',
            textAlign: 'inherit',
            zIndex: '5',
            border: '1px solid #6366f1',
            outline: 'none',
            background: '#fff',
        });
        if (control instanceof HTMLTextAreaElement) {
            Object.assign(control.style, {
                position: 'relative',
                inset: 'auto',
                display: 'block',
                height: 'auto',
                minHeight: `${Math.max(20, container.clientHeight - 8)}px`,
                overflow: 'hidden',
                resize: 'none',
                verticalAlign: 'top',
                lineHeight: '1.2',
            });
            control.addEventListener('input', () => resizePrTextarea(control, container));
        }
        if (control instanceof HTMLSelectElement) {
            control.add(new Option(definition.placeholder || 'Select…', ''));
            let options = [];
            try { options = JSON.parse(container.dataset.options || '[]'); }
            catch (error) { console.error(`Unable to read choices for ${container.dataset.input}.`, error); }
            options.forEach(option => control.add(new Option(option.label, option.value)));
        }
        const value = prFieldValue(container);
        control.value = value;
        if (control instanceof HTMLSelectElement && value && ![...control.options].some(option => option.value === value)) {
            control.add(new Option(container.dataset.displayValue || value, value));
        }
        if (container.matches('.cost-input,.qty-input,.quantity-input')) control.addEventListener('input', calculateTotals);
        if (type === 'date' || type === 'datetime-local') control.dataset.showPicker = '1';
        return control;
    }
    function commitPrField(container, control) {
        if (!container || !control) return;
        if (control.dataset.cancelEdit === '1') {
            control.remove();
            container.textContent = prFieldDisplayText(container, container.dataset.value || '');
            refreshCanvasCellOverlays(container.getRootNode());
            return;
        }
        let value = String(control.value ?? '');
        if ((container.matches('.cost-input,.qty-input,.quantity-input')) && value.trim() !== '') value = formatDecimal(value);
        if (control instanceof HTMLSelectElement) container.dataset.displayValue = control.selectedOptions[0]?.textContent || '';
        else delete container.dataset.displayValue;
        control.remove();
        setPrFieldValue(container, value);
        refreshCanvasCellOverlays(container.getRootNode());
        calculateTotals();
        publishCompleteness();
        window.dispatchEvent(new CustomEvent('pr-form-changed', {detail: {id: activeCanvasAttachmentId}}));
    }
    function activatePrFieldEditor(field) {
        const container = field?.closest?.('[data-pr-field-container]');
        if (!container || container.querySelector('[data-pr-editor]')) return;
        const host = getCanvasHost();
        const canEdit = window.Alpine?.$data?.(document.getElementById('pr-workspace'))?.canEdit;
        if (host?.dataset.mainPr !== '1' || !canEdit) return;
        const control = createPrFieldEditor(container);
        container.style.position ||= 'relative';
        container.replaceChildren(control);
        if (control instanceof HTMLTextAreaElement) resizePrTextarea(control, container);
        control.focus({preventScroll:true});
        if (control instanceof HTMLInputElement && ['text', 'search', 'url', 'tel', 'password', 'number', 'email'].includes(control.type)) {
            try { control.select(); } catch (_) { /* Some native input types do not expose text selection. */ }
        } else if (control instanceof HTMLTextAreaElement) {
            control.setSelectionRange(control.value.length, control.value.length);
        }
        if ((control instanceof HTMLSelectElement || control instanceof HTMLInputElement && ['date', 'datetime-local'].includes(control.type))
            && typeof control.showPicker === 'function') {
            try { control.showPicker(); } catch (_) { /* Browser may require an explicit select click. */ }
        }
    }
    function bindPrFieldContainers(root) {
        if (!root || prFieldInteractionRoots.has(root)) return;
        root.addEventListener('click', event => {
            const target = event.composedPath?.()[0] || event.target;
            if (target?.closest?.('[data-pr-editor]')) return;
            activatePrFieldEditor(target);
        });
        root.addEventListener('focusout', event => {
            const control = event.composedPath?.()[0] || event.target;
            if (!control?.matches?.('[data-pr-editor]')) return;
            const container = control.closest('[data-pr-field-container]');
            if (container) commitPrField(container, control);
        }, true);
        root.addEventListener('keydown', event => {
            const control = (event.composedPath?.()[0] || event.target)?.closest?.('[data-pr-editor]');
            if (!control) return;
            if (control instanceof HTMLTextAreaElement && event.key === 'Enter') {
                event.stopPropagation();
                return;
            }
            if (!['Escape', 'Enter'].includes(event.key)) return;
            event.preventDefault();
            event.stopPropagation();
            if (event.key === 'Escape') control.dataset.cancelEdit = '1';
            control.blur();
        }, true);
        if (!prOutsideClickBound) {
            document.addEventListener('pointerdown', event => {
                if (event.composedPath().some(target => target === itemBankDropdown || (target instanceof Node && itemBankDropdown.contains(target)))) return;
                const activeRoot = getCanvasRoot();
                const editor = activeRoot?.activeElement?.matches?.('[data-pr-editor]')
                    ? activeRoot.activeElement
                    : null;
                if (editor && !event.composedPath().includes(editor)) editor.blur();
            }, true);
            prOutsideClickBound = true;
        }
        prFieldInteractionRoots.add(root);
    }
    function prActionButtonMarkup(action, label, extraClass = '') {
        const classes = getPrTemplateSchema().actions?.rowActions || {};
        const actionClass = Object.keys(classes).find(className => classes[className] === action);
        if (!actionClass) return '';
        const color = extraClass.includes('text-red') ? '#dc2626' : '#1e293b';
        return `<button type="button" class="pr-action-button ${actionClass} ${extraClass}" style="display:block;width:100%;padding:6px 10px;border:0;background:#fff;color:${color};text-align:left;font:inherit;cursor:pointer">${label}</button>`;
    }
    function prAddRowButtonMarkup(kind, label) {
        const classes = getPrTemplateSchema().actions?.addRows || {};
        const actionClass = Object.keys(classes).find(className => classes[className] === kind);
        if (!actionClass) return '';
        return `<button type="button" class="btn-icon ${actionClass}" style="padding:4px 8px;border:1px solid #cbd5e1;border-radius:4px;background:#f1f5f9;font:inherit;cursor:pointer">${label}</button>`;
    }

    function decoratePrRow(row, type) {
        row.classList.add('pr-row', `pr-row-${type}`);
        const stockCell = row.cells[0];
        if (stockCell && !stockCell.querySelector('.drag-handle')) {
            stockCell.style.position = 'relative';
            const handle = document.createElement('span');
            handle.className = 'drag-handle';
            handle.draggable = true;
            handle.textContent = '⣿';
            handle.setAttribute('aria-label', 'Drag to reorder item');
            handle.title = 'Drag to reorder item';
            handle.style.cssText = 'position:absolute;left:-18px;top:50%;transform:translateY(-50%);cursor:grab;color:#64748b;font-size:16px;z-index:5;opacity:0;transition:opacity .15s';
            stockCell.appendChild(handle);
            row.addEventListener('mouseenter', () => { handle.style.opacity = '1'; });
            row.addEventListener('mouseleave', () => { handle.style.opacity = '0'; });
        }
        if (type === 'lot_parent') {
            const unitCell = row.querySelector('[data-lot-unit-cell]');
            if (unitCell && !unitCell.querySelector('.lot-group-drag-handle')) {
                unitCell.style.position = 'relative';
                unitCell.style.paddingLeft = '24px';
                const groupHandle = document.createElement('span');
                groupHandle.className = 'lot-group-drag-handle';
                groupHandle.draggable = true;
                groupHandle.textContent = '⣿';
                groupHandle.setAttribute('aria-label', 'Drag to reorder entire lot');
                groupHandle.title = 'Drag to reorder entire lot';
                groupHandle.style.cssText = 'position:absolute;left:4px;top:50%;transform:translateY(-50%);cursor:grab;color:#64748b;font-size:14px;z-index:5;opacity:0;transition:opacity .15s';
                unitCell.appendChild(groupHandle);
                row.addEventListener('mouseenter', () => { groupHandle.style.opacity = '1'; });
                row.addEventListener('mouseleave', () => { groupHandle.style.opacity = '0'; });
            }
        }
        const total = row.querySelector('[data-print="item.total_cost"]');
        const totalCell = total?.closest('td');
        const actionCell = row.cells[0] || totalCell;
        if (!actionCell || actionCell.querySelector('.row-controls')) return;
        actionCell.style.position = 'relative';
        const controls = document.createElement('div');
        controls.className = 'row-controls';
        controls.style.cssText = 'position:absolute;right:2px;top:50%;transform:translateY(-50%);display:flex;gap:4px;z-index:20;opacity:0;transition:opacity .15s';
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'btn-icon menu-trigger';
        trigger.textContent = '⋮';
        trigger.setAttribute('aria-label', 'Item actions');
        trigger.style.cssText = 'padding:2px 6px;border:1px solid #cbd5e1;border-radius:4px;background:#f1f5f9;font:inherit;cursor:pointer';
        const menu = document.createElement('div');
        menu.className = 'context-menu';
        menu.style.cssText = 'position:absolute;right:0;top:100%;z-index:50;display:none;min-width:160px;flex-direction:column;border:1px solid #cbd5e1;border-radius:4px;background:#fff;box-shadow:0 4px 6px -1px rgb(0 0 0 / .1)';
        controls.append(trigger, menu);
        actionCell.appendChild(controls);
        row.addEventListener('mouseenter', () => { controls.style.opacity = '1'; });
        row.addEventListener('mouseleave', () => { controls.style.opacity = '0'; });
        setRowMenu(row, type);
        trigger.addEventListener('click', event => {
            getCanvasRoot().querySelectorAll('.context-menu').forEach(itemMenu => {
                if (itemMenu !== menu) itemMenu.style.display = 'none';
            });
            menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
            event.stopPropagation();
        });
    }

    // Search price references as users type in the primary PR item descriptions.
    const itemBankSearchUrl = @js(route('api.search.item-bank'));
    let itemBankSearchTimer = null;
    let activeItemBankField = null;
    let itemBankSearchVersion = 0;
    const itemBankDropdown = document.createElement('div');
    itemBankDropdown.className = 'fixed z-[260] hidden max-h-80 w-[min(30rem,calc(100vw-2rem))] overflow-auto rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900';
    itemBankDropdown.setAttribute('role', 'listbox');
    itemBankDropdown.setAttribute('aria-label', 'Item bank suggestions');
    document.body.appendChild(itemBankDropdown);

    function closeItemBankDropdown() {
        itemBankDropdown.classList.add('hidden');
        activeItemBankField = null;
    }

    function showItemBankMessage(message, dismissAfter = 0) {
        const status = document.createElement('div');
        status.className = 'flex items-start gap-3 px-3 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400';
        status.setAttribute('role', 'status');
        const text = document.createElement('span');
        text.className = 'min-w-0 flex-1';
        text.textContent = message;
        status.appendChild(text);
        if (dismissAfter > 0) {
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'shrink-0 rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:hover:bg-slate-800 dark:hover:text-slate-200';
            close.setAttribute('aria-label', 'Close item reference message');
            close.title = 'Close';
            close.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
            close.addEventListener('click', closeItemBankDropdown);
            status.appendChild(close);
        }
        itemBankDropdown.replaceChildren(status);
        itemBankDropdown.classList.remove('hidden');
        if (dismissAfter > 0) {
            setTimeout(() => {
                if (status.isConnected && itemBankDropdown.firstElementChild === status) closeItemBankDropdown();
            }, dismissAfter);
        }
    }

    function positionItemBankDropdown(field) {
        const rect = field.getBoundingClientRect();
        const gap = 8;
        const width = itemBankDropdown.offsetWidth || Math.min(480, window.innerWidth - gap * 2);
        const height = itemBankDropdown.offsetHeight || 48;
        const left = Math.max(gap, Math.min(rect.left, window.innerWidth - width - gap));
        const below = rect.bottom + 6;
        const top = below + height <= window.innerHeight - gap
            ? below
            : Math.max(gap, rect.top - height - 6);
        itemBankDropdown.style.left = `${left}px`;
        itemBankDropdown.style.top = `${top}px`;
    }

    function appendItemBankGroup(label, items) {
        if (!items?.length) return;
        const heading = document.createElement('div');
        heading.className = 'sticky top-0 z-10 border-b border-slate-100 bg-slate-50 px-3 py-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400';
        heading.textContent = label;
        itemBankDropdown.appendChild(heading);

        items.forEach(item => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'block w-full border-b border-slate-100 px-3 py-2.5 text-left transition hover:bg-blue-50 dark:border-slate-800 dark:hover:bg-blue-950/40';
            button.setAttribute('role', 'option');
            button.addEventListener('pointerdown', event => {
                event.preventDefault();
                if (!activeItemBankField) return;
                const field = activeItemBankField.closest('.description-editable') || activeItemBankField;
                const row = field.closest('tr');
                if (!row) return;
                const descriptionEditor = field.matches('textarea[data-pr-editor], input[data-field="description"]')
                    ? field
                    : field.querySelector('textarea[data-pr-editor], input[data-field="description"]');
                if (descriptionEditor) {
                    descriptionEditor.value = item.description || '';
                    descriptionEditor.dispatchEvent(new Event('input', { bubbles: true, composed: true }));
                } else if (field.matches('[contenteditable="true"]')) {
                    field.textContent = item.description || '';
                    field.dispatchEvent(new Event('input', { bubbles: true, composed: true }));
                }

                const unit = row.querySelector('[data-field="unit"], [data-input="item.unit"]');
                if (unit && item.unit) {
                    const unitField = unit.matches('[data-pr-field-container]') ? unit : unit.closest('[data-pr-field-container]');
                    if (unitField) setPrFieldValue(unitField, item.unit);
                }
                const cost = row.querySelector('[data-field="cost"], .cost-input');
                if (cost && item.price !== null && item.price !== undefined) {
                    const costField = cost.matches('[data-pr-field-container]') ? cost : cost.closest('[data-pr-field-container]');
                    if (costField) setPrFieldValue(costField, String(item.price));
                }
                row.dataset.itemBankItemId = String(item.id || '');
                closeItemBankDropdown();
                calculateTotals();
                publishCompleteness();
                notifyPrFormChanged();
            });

            const name = document.createElement('span');
            name.className = 'block text-xs font-bold text-slate-800 dark:text-slate-100';
            name.textContent = item.description || 'Catalog item';
            const details = document.createElement('span');
            details.className = 'mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px] text-slate-500 dark:text-slate-400';
            const price = document.createElement('span');
            if (item.price !== null && item.price !== undefined && item.price !== '') {
                price.className = 'font-mono font-extrabold text-emerald-700 dark:text-emerald-300';
                price.textContent = `₱ ${Number(item.price).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}${item.unit ? ` / ${item.unit}` : ''}`;
            } else {
                price.className = 'font-semibold text-slate-400 dark:text-slate-500';
                price.textContent = 'No reference price';
            }
            details.append(price);
            [item.date, item.source].filter(Boolean).forEach(value => {
                const part = document.createElement('span');
                part.textContent = value;
                details.append(part);
            });
            button.append(name, details);
            itemBankDropdown.appendChild(button);
        });
    }

    async function searchItemBank(field) {
        const editor = field.matches('input,textarea') ? field : field.querySelector('textarea[data-pr-editor], input[data-field="description"]');
        const query = String(editor?.value ?? prFieldValue(field)).trim();
        const version = ++itemBankSearchVersion;
        if (query.length < 2) return closeItemBankDropdown();
        activeItemBankField = field;
        showItemBankMessage('Searching item references…');
        positionItemBankDropdown(field);
        const url = new URL(itemBankSearchUrl, window.location.origin);
        url.searchParams.set('q', query);
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('Item suggestions could not be loaded.');
            const results = await response.json();
            if (version !== itemBankSearchVersion || !field.isConnected) return;
            itemBankDropdown.replaceChildren();
            appendItemBankGroup('Bid entries · past 5 months', results.bids);
            appendItemBankGroup('Manual references · past 5 months', results.manual);
            if (!itemBankDropdown.childElementCount) showItemBankMessage('No matching references from the past 5 months. You can continue with a manual description.', 5000);
            itemBankDropdown.classList.remove('hidden');
            positionItemBankDropdown(field);
        } catch (error) {
            if (version === itemBankSearchVersion && field.isConnected) {
                showItemBankMessage('Item suggestions are temporarily unavailable. You can continue with a manual description.');
                positionItemBankDropdown(field);
            }
            console.error('Item Bank search failed.', error);
        }
    }

    function itemBankDescriptionField(event) {
        const path = typeof event.composedPath === 'function' ? event.composedPath() : [];
        const candidates = [event.target, ...path];
        const editor = candidates.find(node => node instanceof HTMLTextAreaElement
            && node.matches('[data-pr-editor]')
            && node.closest('.description-editable'));
        if (editor) return editor.closest('.description-editable');
        return candidates.find(node => node instanceof Element && (
            node.matches('.description-editable, input[data-field="description"]')
            || node.matches('textarea[data-pr-editor]') && node.closest('.description-editable')
            || (node.isContentEditable && node.matches('[data-field="description"]'))
        )) || null;
    }

    function scheduleItemBankSearch(field, event) {
        if (!(field instanceof Element)) return;
        const row = field.closest('tr');
        const host = getCanvasHost();
        const form = document.getElementById('pr-form');
        if (!event.isTrusted || !row || row.classList.contains('template-row')
            || host?.dataset.mainPr !== '1' || form?.dataset.canEditPrForm !== '1') return;
        delete row.dataset.itemBankItemId;
        clearTimeout(itemBankSearchTimer);
        itemBankSearchTimer = setTimeout(() => searchItemBank(field), 220);
    }
    document.addEventListener('focusout', event => {
        const field = event.composedPath?.()[0] || event.target;
        if (field === activeItemBankField) setTimeout(() => { if (!itemBankDropdown.matches(':hover')) closeItemBankDropdown(); }, 120);
    });
    window.addEventListener('resize', () => { if (activeItemBankField?.isConnected) positionItemBankDropdown(activeItemBankField); });
    window.addEventListener('scroll', () => { if (activeItemBankField?.isConnected && !itemBankDropdown.classList.contains('hidden')) positionItemBankDropdown(activeItemBankField); }, true);

    function saveActiveAttachmentCanvas() {
        const host = getCanvasHost(), root = host?.shadowRoot;
        if (!root || !activeCanvasAttachmentId) return;
        const key = String(activeCanvasAttachmentId);
        const state = genericFormStates.get(key);
        if (host.dataset.mainPr === '1' && state) genericUnpaginate(state);
        const fragment = document.createDocumentFragment();
        while (root.firstChild) fragment.appendChild(root.firstChild);
        if (host.dataset.mainPr === '1' && state) state.root = fragment;
        attachmentFormCache.set(key, fragment);
    }
    window.parkPrCanvas = function() {
        saveActiveAttachmentCanvas();
        activeCanvasAttachmentId = null;
        const host = getCanvasHost();
        if (host?.shadowRoot) host.shadowRoot.replaceChildren();
        if (host) { host.dataset.currentAttachmentId = ''; host.dataset.mainPr = '0'; }
        const shell = document.getElementById('canvas-zoom-shell');
        if (shell) { shell.style.width = '0px'; shell.style.height = '0px'; }
        window.syncPrCanvasSize?.();
    };
    window.loadPrAttachment = function(id) {
        const key = String(id), host = getCanvasHost(), template = document.getElementById(`attachment-form-template-${key}`);
        if (!host || !template) return false;
        const root = host.shadowRoot || host.attachShadow({mode:'open'});
        if (activeCanvasAttachmentId && activeCanvasAttachmentId !== key) saveActiveAttachmentCanvas();
        if (activeCanvasAttachmentId !== key) {
            root.replaceChildren();
            const cached = attachmentFormCache.get(key);
            if (cached) { root.appendChild(cached); attachmentFormCache.delete(key); host.dataset.prHydrated = '1'; host.dataset.attachmentDataHydrated = '1'; }
            else { root.innerHTML = template.innerHTML; host.dataset.prHydrated = template.dataset.mainPr === '1' ? '0' : '1'; host.dataset.attachmentDataHydrated = '0'; }
        }
        activeCanvasAttachmentId = key;
        host.dataset.currentAttachmentId = key;
        host.dataset.mainPr = template.dataset.mainPr || '0';
        host.style.width = template.dataset.paperWidth || '210mm';
        if (host.dataset.mainPr === '1') {
            host.style.minHeight = '0';
            host.style.padding = '0';
        }
        if (host.dataset.mainPr === '1') {
            let state = genericFormStates.get(key);
            if (!state) {
                let printable = root.querySelector('main[data-template-code="PR"], main.form-document, main.rfq-document');
                if (!printable) {
                    printable = document.createElement('main');
                    printable.className = 'form-document';
                    [...root.childNodes].forEach(node => {
                        if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'STYLE') return;
                        printable.appendChild(node);
                    });
                    root.appendChild(printable);
                }
                state = {root, context: {}, fields: {}, originalMain: printable, tableGroups: new Map()};
                genericFormStates.set(key, state);
            } else {
                state.root = root;
                state.originalMain ||= root.querySelector('main[data-template-code="PR"], main.form-document, main.rfq-document, main');
            }
            if (host.dataset.prPaginationEventsBound !== '1') {
                const restorePrDocument = event => {
                    if (host.dataset.mainPr !== '1') return;
                    const target = event.composedPath?.()[0] || event.target;
                    // Keep editable cells in their current page until their editor closes.
                    const isEditing = target?.closest?.('input,textarea,select,[contenteditable="true"],.editable-content,[data-pr-field-container],[data-canvas-editable-cell]');
                    if (isEditing && ['click', 'input', 'change'].includes(event.type)) return;
                    const current = genericFormStates.get(String(host.dataset.currentAttachmentId));
                    if (current) genericUnpaginate(current);
                    if (event.type === 'change' || (event.type === 'click' && !isEditing)) {
                        requestAnimationFrame(() => window.syncPrCanvasSize?.());
                    }
                };
                root.addEventListener('click', restorePrDocument, true);
                root.addEventListener('input', restorePrDocument, true);
                root.addEventListener('change', restorePrDocument, true);
                root.addEventListener('focusout', () => requestAnimationFrame(() => {
                    const current = genericFormStates.get(String(host.dataset.currentAttachmentId));
                    const focused = current?.root?.activeElement;
                    if (focused?.matches?.('input,textarea,select,[contenteditable="true"],.editable-content') || focused?.isContentEditable) return;
                    if (current) genericUnpaginate(current);
                    publishCompleteness();
                    window.syncPrCanvasSize?.();
                }), true);
                host.dataset.prPaginationEventsBound = '1';
            }
        }
        host.style.boxShadow = '';
        host.style.outline = '';
        // Let page gutters reveal the attachment workspace background; each paper page stays white.
        host.style.background = host.dataset.mainPr === '1' ? 'transparent' : '';
        host.style.borderRadius = '';
        host.style.width = template.dataset.paperWidth || '210mm';
        host.style.minHeight = host.dataset.mainPr === '1' ? '0' : (template.dataset.paperHeight || '297mm');
        host.style.padding = host.dataset.mainPr === '1' ? '0' : '';
        host.style.paddingTop = host.dataset.mainPr === '1' ? '0' : (template.dataset.marginTop || '10mm');
        host.style.paddingRight = host.dataset.mainPr === '1' ? '0' : (template.dataset.marginRight || '10mm');
        host.style.paddingBottom = host.dataset.mainPr === '1' ? '0' : (template.dataset.marginBottom || '10mm');
        host.style.paddingLeft = host.dataset.mainPr === '1' ? '0' : (template.dataset.marginLeft || '10mm');
        host.dataset.formPageHeight = template.dataset.paperHeight || '297mm';
        host.dataset.formMarginTop = template.dataset.marginTop || '10mm';
        host.dataset.formMarginBottom = template.dataset.marginBottom || '10mm';
        host.dataset.formMarginRight = template.dataset.marginRight || '10mm';
        host.dataset.formMarginLeft = template.dataset.marginLeft || '10mm';
        host.dataset.formPageWidth = template.dataset.paperWidth || '210mm';
        host.style.position = host.dataset.mainPr === '1' ? 'relative' : '';
        if (host.dataset.mainPr === '1') host.dataset.needsHydrate = host.dataset.prHydrated === '1' ? '0' : '1';
        else host.dataset.needsHydrate = '0';
        host.style.transform = `scale(${Number(document.getElementById('pr-workspace')?.dataset.scale || 1)})`;
        // ShadowRoot has no dataset; keep the one-time binding marker on its host element.
        if (host.dataset.templateActionsBound !== '1') {
            root.addEventListener('click', event => {
                const mappings = getPrTemplateSchema().actions || {};
                const action = event.target.closest('.pr-action-button');
                if (action) {
                    const actionClass = [...action.classList].find(className => mappings.rowActions?.[className]);
                    if (actionClass) { event.preventDefault(); window.handleMenuAction?.(mappings.rowActions[actionClass], action); return; }
                }
                const add = event.target.closest('.btn-icon');
                if (add) {
                    const addClass = [...add.classList].find(className => mappings.addRows?.[className]);
                    if (addClass) { event.preventDefault(); window.addBaseRow?.(mappings.addRows[addClass]); }
                }
            });
            root.addEventListener('input', event => {
                scheduleItemBankSearch(itemBankDescriptionField(event), event);
            });
            host.dataset.templateActionsBound = '1';
        }
        window.initPrCanvas?.();
        return true;
    };

    // Non-PR attachment forms use declarative data bindings. They share the paper canvas,
    // multi-page PDF compiler, and save endpoint, but never run the PR-specific editor.
    const genericFormStates = new Map();
    const rfqDocumentToggleRoots = new WeakSet();
    function genericValue(object, path) {
        return String(path || '').split('.').reduce((value, part) => {
            if (value == null) return undefined;
            if (Object.prototype.hasOwnProperty.call(value, part)) return value[part];
            const snake = part.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
            if (Object.prototype.hasOwnProperty.call(value, snake)) return value[snake];
            const camel = part.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
            return value[camel];
        }, object);
    }
    function genericCanonicalCollectionPath(path) {
        return path === 'procurement.root_items' ? 'procurement.items' : path;
    }
    function genericRenderBindings(root, context) {
        root.querySelectorAll('[data-each]').forEach(container => {
            const path = container.dataset.each;
            const rows = genericValue(context, path);
            if (!Array.isArray(rows)) return;
            const rowTemplate = [...container.children].find(child => !child.hasAttribute('data-child-row'));
            const childTemplate = [...container.children].find(child => child.hasAttribute('data-child-row'));
            const template = rowTemplate?.cloneNode(true);
            if (!template) return;
            const alias = container.dataset.as || path.split('.').at(-1).replace(/s$/, '') || 'item';
            const childPath = container.dataset.childEach || '';
            const childAlias = container.dataset.childAs || alias;
            container.replaceChildren();
            rows.forEach((row, index) => {
                const clone = template.cloneNode(true);
                clone.removeAttribute('data-row-template');
                clone.removeAttribute('data-each-item');
                const nested = {...context, [alias]: row, index: index + 1};
                const children = childPath ? genericValue(nested, childPath) : [];
                const childRows = Array.isArray(children) ? children : [];
                clone.querySelectorAll('[data-rowspan-children]').forEach(cell => {
                    cell.rowSpan = Math.max(1, childRows.length + 1);
                    cell.removeAttribute('data-rowspan-children');
                });
                clone.__genericContext = nested;
                clone.__genericRow = row;
                clone.__genericCollectionPath = genericCanonicalCollectionPath(path);
                clone.__genericRecordAlias = alias;
                if (row?.id != null) clone.dataset.genericRecordId = String(row.id);
                const toggleCell = clone.querySelector('[data-rfq-document-toggle]');
                if (toggleCell && row?.id != null) {
                    const selectedIds = genericValue(context, 'procurement.rfq_documents');
                    const isSelected = Array.isArray(selectedIds) && selectedIds.some(id => String(id) === String(row.id));
                    toggleCell.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                    toggleCell.setAttribute('aria-label', `${isSelected ? 'Remove' : 'Add'} ${row.label || row.title || 'document'} ${isSelected ? 'from' : 'to'} required documents`);
                }
                genericRenderBindings(clone, nested);
                container.appendChild(clone);
                if (childTemplate) childRows.forEach((child, childIndex) => {
                    const childClone = childTemplate.cloneNode(true);
                    childClone.removeAttribute('data-child-row');
                    const childContext = {...nested, [childAlias]: child, index: childIndex + 1};
                    childClone.__genericContext = childContext;
                    childClone.__genericRow = child;
                    childClone.__genericCollectionPath = genericCanonicalCollectionPath(path);
                    childClone.__genericRecordAlias = childAlias;
                    if (child?.id != null) childClone.dataset.genericRecordId = String(child.id);
                    genericRenderBindings(childClone, childContext);
                    container.appendChild(childClone);
                });
            });
            container.removeAttribute('data-each');
            container.removeAttribute('data-as');
            container.removeAttribute('data-child-each');
            container.removeAttribute('data-child-as');
        });
        root.querySelectorAll('[data-print]').forEach(element => {
            const path = element.dataset.print;
            const value = genericValue(context, path);
            if (value === undefined || value === null || value === '') return;
            genericSetDisplayValue(element, value);
        });
    }
    function genericCompleteness(root) {
        const fields = [...root.querySelectorAll('[data-required]')];
        const missing = fields.find(field => {
            if (field.closest('[hidden]')) return false;
            const control = field.matches('input,textarea,select') ? field : field.querySelector('input,textarea,select');
            if (control?.type === 'checkbox') return !control.checked;
            const value = control ? control.value : (field.dataset.boundValue ?? field.textContent);
            return !String(value ?? '').trim();
        });
        return {complete: !missing, field: missing, message: missing ? `${missing.dataset.label || missing.dataset.input || 'A required field'} is required.` : ''};
    }
    function genericFieldValue(root, element) {
        const control = element.matches('input,textarea,select') ? element : element.querySelector('input,textarea,select');
        const value = control ? (control.type === 'checkbox' ? control.checked : control.value) : (element.dataset.boundValue ?? '');
        if (element.dataset.valueType === 'boolean') return value === true || value === 1 || value === '1' || value === 'true' ? 'true' : 'false';
        return value;
    }
    function genericDisplayValue(element, value) {
        if (value === null || value === undefined || value === '') return '';
        const format = String(element.dataset.format || '').trim();
        const formatType = format.split(':', 1)[0].toLowerCase();
        if ((formatType === 'currency' || format === 'currency') && Number.isFinite(Number(value)))
            return new Intl.NumberFormat('en-PH', {style: 'currency', currency: 'PHP'}).format(Number(value));
        if ((formatType === 'number' || format === 'number') && Number.isFinite(Number(value)))
            return new Intl.NumberFormat('en-PH').format(Number(value));
        const datePattern = formatType === 'date' ? (format.includes(':') ? format.slice(format.indexOf(':') + 1) : 'dd-Mmm-yyyy')
            : (/^(?:d{1,2}|M{1,4}|y{2,4})[\s\S]*$/.test(format) ? format : (['date', 'datetime', 'datetime-local'].includes(element.dataset.inputType) ? 'dd-Mmm-yyyy' : null));
        if (datePattern) {
            const rawDate = String(value);
            const hasTimezone = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(rawDate);
            const match = rawDate.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[T\s](\d{1,2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?)?/);
            if (match) {
                let year = Number(match[1]), month = Number(match[2]), day = Number(match[3]);
                let hour = Number(match[4] || 0), minute = Number(match[5] || 0), second = Number(match[6] || 0);
                if (hasTimezone) {
                    const date = new Date(rawDate);
                    if (Number.isNaN(date.getTime())) return String(value);
                    year = date.getFullYear(); month = date.getMonth() + 1; day = date.getDate();
                    hour = date.getHours(); minute = date.getMinutes(); second = date.getSeconds();
                }
                const validDate = new Date(year, month - 1, day);
                if (validDate.getFullYear() === year && validDate.getMonth() === month - 1 && validDate.getDate() === day
                    && hour < 24 && minute < 60 && second < 60) {
                    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    const hour12 = hour % 12 || 12;
                    const values = {
                        d: String(day), dd: String(day).padStart(2, '0'), M: String(month), MM: String(month).padStart(2, '0'),
                        Mmm: months[month - 1].slice(0, 3), MMM: months[month - 1].slice(0, 3), MMMM: months[month - 1], yy: String(year).slice(-2), yyyy: String(year),
                        H: String(hour), HH: String(hour).padStart(2, '0'), h: String(hour12), hh: String(hour12).padStart(2, '0'),
                        m: String(minute), mm: String(minute).padStart(2, '0'), s: String(second), ss: String(second).padStart(2, '0'),
                        a: hour < 12 ? 'am' : 'pm', A: hour < 12 ? 'AM' : 'PM',
                    };
                    return datePattern.replace(/yyyy|MMMM|Mmm|MMM|MM|M|dd|d|yy|HH|H|hh|h|mm|m|ss|s|a|A/g, token => {
                        return values[token] ?? token;
                    });
                }
            }
        }
        if (element.dataset.inputType === 'select') {
            let selected = String(value);
            try {
                const options = JSON.parse(element.dataset.options || '[]');
                const option = options.find(item => element.dataset.valueType === 'boolean'
                    ? ((item.value === true || item.value === 1 || item.value === '1' || item.value === 'true') === (value === true || value === 1 || value === '1' || value === 'true'))
                    : String(item.value) === selected);
                if (option) return String(option.label ?? option.name ?? option.value);
            } catch {}
        }
        return String(value);
    }
    function genericSetDisplayValue(element, value) {
        const displayValue = genericDisplayValue(element, value);
        const multiline = element.dataset.format === 'multiline'
            || ['item.description', 'subitem.description'].includes(element.dataset.print);
        if (!multiline) {
            element.textContent = displayValue;
            return;
        }
        const lines = displayValue.split(/\r\n|\r|\n/);
        element.replaceChildren();
        lines.forEach((line, index) => {
            if (index) element.appendChild(document.createElement('br'));
            element.appendChild(document.createTextNode(line));
        });
    }
    function genericApplyConditions(state) {
        const {root, context} = state;
        root.querySelectorAll('[data-target]').forEach(owner => {
            const selector = owner.dataset.target;
            const controller = owner.querySelector('[data-input]');
            let target = null;
            try { target = selector ? root.querySelector(selector) : null; } catch {}
            if (!controller || !target) return;
            const selected = genericFieldValue(root, controller);
            target.dataset.targetController = controller.dataset.input;
            target.querySelectorAll('[data-target-value]').forEach(option => {
                option.hidden = String(selected) !== option.dataset.targetValue;
            });
        });
        root.querySelectorAll('[data-show-if], [data-show-if-not]').forEach(element => {
            const localContext = element.closest('[data-generic-record-id]')?.__genericContext || context;
            const check = (path, expected, negate) => {
                if (!path) return true;
                const actual = genericValue(localContext, path);
                const matches = expected === undefined ? !!actual : String(actual) === expected;
                return negate ? !matches : matches;
            };
            const visible = check(element.dataset.showIf, element.dataset.showIfValue, false)
                && check(element.dataset.showIfNot, element.dataset.showIfNotValue, true);
            element.hidden = !visible;
        });
        root.querySelectorAll('[data-input]').forEach(element => {
            const cell = element.closest('td');
            if (cell && cell.querySelectorAll('[data-input]').length === 1) {
                cell.dataset.genericEditableCell = '1';
                if (!cell.hasAttribute('tabindex')) cell.tabIndex = 0;
            }
        });
        root.querySelectorAll('[data-default], [data-default-from]').forEach(element => {
            if (element.hidden || String(element.dataset.boundValue ?? '').trim() !== '') return;
            const localContext = element.closest('[data-generic-record-id]')?.__genericContext || context;
            const fallback = element.dataset.defaultFrom ? genericValue(localContext, element.dataset.defaultFrom) : element.dataset.default;
            if (fallback === undefined || fallback === null || String(fallback).trim() === '') return;
            element.dataset.boundValue = String(fallback);
            element.textContent = genericDisplayValue(element, fallback);
            const row = element.closest('[data-generic-record-id]');
            const path = element.dataset.input;
            if (row?.__genericRow && path?.startsWith(`${row.__genericRecordAlias}.`)) {
                row.__genericRow[path.slice(row.__genericRecordAlias.length + 1)] = fallback;
            } else if (path?.startsWith('procurement.')) {
                const parts = path.slice('procurement.'.length).split('.');
                let target = context.procurement;
                while (parts.length > 1) target = target[parts.shift()] ||= {};
                target[parts[0]] = fallback;
            }
        });
        root.querySelectorAll('[data-clear-if], [data-clear-if-not]').forEach(element => {
            const localContext = element.closest('[data-generic-record-id]')?.__genericContext || context;
            const shouldClear = (element.dataset.clearIf && !!genericValue(localContext, element.dataset.clearIf))
                || (element.dataset.clearIfNot && !genericValue(localContext, element.dataset.clearIfNot));
            if (!shouldClear || element.dataset.boundValue === '') return;
            element.dataset.boundValue = '';
            element.textContent = '';
            const control = element.querySelector('input,textarea,select');
            if (control) control.value = '';
        });
    }
    function genericRefreshValidation(state) {
        const {root} = state;
        refreshCanvasCellOverlays(root);
        const isMainPr = getCanvasHost()?.dataset.mainPr === '1';
        root.querySelectorAll('[data-required]').forEach(element => {
            const hidden = !!element.closest('[hidden]');
            const control = element.matches('input,textarea,select') ? element : element.querySelector('input,textarea,select');
            const value = genericFieldValue(root, element);
            const empty = !hidden && (control?.type === 'checkbox' ? !control.checked : !String(value ?? '').trim());
            const cell = element.closest('[data-canvas-editable-cell]');
            if (isMainPr) {
                element.classList.remove('required-empty');
                element.removeAttribute('aria-invalid');
                element.removeAttribute('data-required-empty');
                if (cell) {
                    cell.classList.toggle('canvas-required-empty', empty);
                    cell.toggleAttribute('aria-invalid', empty);
                    if (empty) cell.setAttribute('data-required-empty', '');
                    else cell.removeAttribute('data-required-empty');
                }
                return;
            }
            element.classList.toggle('required-empty', empty);
            element.toggleAttribute('aria-invalid', empty);
            if (empty) element.setAttribute('data-required-empty', '');
            else element.removeAttribute('data-required-empty');
            if (cell) {
                cell.classList.toggle('canvas-required-empty', empty);
                cell.toggleAttribute('aria-invalid', empty);
                if (empty) cell.setAttribute('data-required-empty', '');
                else cell.removeAttribute('data-required-empty');
            }
        });
        const workspace = window.Alpine?.$data(document.getElementById('pr-workspace'));
        if (workspace) workspace.formComplete = genericCompleteness(root).complete;
    }
    function genericRecalculate(state) {
        const items = state.context?.procurement?.items;
        if (!Array.isArray(items)) return;
        const total = items.filter(item => !item.is_lot).reduce((sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.cost) || 0), 0);
        state.context.procurement.abc_amount = Math.round((total + Number.EPSILON) * 100) / 100;
        state.root.querySelectorAll('[data-print="procurement.abc_amount"]').forEach(element => {
            element.textContent = genericDisplayValue(element, state.context.procurement.abc_amount);
        });
    }
    function genericSnapshot(id) {
        const key = String(id), state = genericFormStates.get(key), host = getCanvasHost();
        if (!state || host?.dataset.currentAttachmentId !== key || host.dataset.mainPr === '1') return null;
        const fields = {...state.fields};
        state.root.querySelectorAll('[data-input]').forEach(element => {
            const row = element.closest('[data-generic-record-id]');
            const path = row && element.dataset.input.startsWith(`${row.__genericRecordAlias}.`)
                ? `${row.__genericCollectionPath}.${row.dataset.genericRecordId}.${element.dataset.input.slice(row.__genericRecordAlias.length + 1)}`
                : element.dataset.input;
            if (element.closest('[hidden]')) {
                delete fields[path];
                return;
            }
            const value = genericFieldValue(state.root, element);
            fields[path] = value;
        });
        const check = genericCompleteness(state.root);
        return {id: key, fields, complete: check.complete};
    }
    function genericCssPixels(value) {
        const match = String(value || '').trim().match(/^([\d.]+)(mm|cm|in|px)?$/i);
        if (!match) return 0;
        const units = {mm: 96 / 25.4, cm: 96 / 2.54, in: 96, px: 1};
        return Number(match[1]) * (units[String(match[2] || 'px').toLowerCase()] || 1);
    }
    function genericUnpaginate(state) {
        const root = state.root, pages = [...root.querySelectorAll('.generic-paper-page')];
        if (!pages.length || !state.originalMain) return;
        const restored = [], restoredTables = new Set();
        pages.forEach(page => {
            const main = page.querySelector(':scope > .form-document, :scope > .rfq-document, :scope > main');
            if (!main) return;
            [...main.children].forEach(child => {
                const group = child.dataset.genericTableGroup;
                if (!group) { restored.push(child); return; }
                const original = state.tableGroups.get(group);
                if (!original || restoredTables.has(group)) return;
                const destination = original.tBodies[0];
                child.querySelectorAll('[data-pagination-rowspan-continuation]').forEach(cell => cell.remove());
                [...child.tBodies].forEach(body => [...body.rows].forEach(row => destination.appendChild(row)));
                original.removeAttribute('data-nobreak-forwarded');
                restoredTables.add(group);
                restored.push(original);
            });
        });
        state.originalMain.replaceChildren(...restored);
        state.originalMain.querySelectorAll('[data-nobreak-forwarded]').forEach(element => element.removeAttribute('data-nobreak-forwarded'));
        state.originalMain.removeAttribute('data-nobreak-forwarded');
        root.insertBefore(state.originalMain, pages[0]);
        pages.forEach(page => page.remove());
    }
    // The PR source table has header, item, and summary rows in separate tbody elements.
    // The shared paginator splits one tbody at a time, so normalize those rows into the
    // designated item tbody before pagination. Unpagination restores all rows in order.
    function normalizePrTableForPagination(root) {
        const itemBody = root?.querySelector?.('tbody[data-each="procurement.items"]');
        const table = itemBody?.closest('table');
        if (!itemBody || !table || table.dataset.prPaginationNormalized === '1') return;
        const bodies = [...table.tBodies];
        const itemIndex = bodies.indexOf(itemBody);
        if (itemIndex < 0) return;
        const before = bodies.slice(0, itemIndex).flatMap(body => [...body.rows]);
        const after = bodies.slice(itemIndex + 1).flatMap(body => [...body.rows]);
        before.reverse().forEach(row => itemBody.insertBefore(row, itemBody.firstChild));
        after.forEach(row => itemBody.appendChild(row));
        bodies.filter(body => body !== itemBody).forEach(body => body.remove());
        table.dataset.prPaginationNormalized = '1';
    }
    function isPrEditorFocused(root) {
        const focused = root?.activeElement;
        return !!(focused?.matches?.('input,textarea,select,[contenteditable="true"],.editable-content') || focused?.isContentEditable);
    }
    function temporarilyUnpaginatePr(force = false) {
        const host = getCanvasHost();
        if (host?.dataset.mainPr !== '1') return () => {};
        const key = String(host.dataset.currentAttachmentId || '');
        const state = genericFormStates.get(key);
        const template = document.getElementById(`attachment-form-template-${key}`);
        if (!state || !template) return () => {};
        if (!force && isPrEditorFocused(state.root)) return () => {};
        genericUnpaginate(state);
        return () => {
            if (state.originalMain?.parentNode !== state.root) return;
            if (isPrEditorFocused(state.root)) return;
            normalizePrTableForPagination(state.root);
            genericPaginate(state, host, template);
            requestAnimationFrame(() => window.syncPrCanvasSize?.());
        };
    }
    function genericPaginate(state, host, template) {
        genericUnpaginate(state);
        const root = state.root, originalMain = state.originalMain;
        if (!originalMain) return;
        const previousTransform = host.style.transform;
        host.style.transform = 'none';
        originalMain.querySelectorAll('[data-nobreak-forwarded]').forEach(element => element.removeAttribute('data-nobreak-forwarded'));
        originalMain.removeAttribute('data-nobreak-forwarded');
        state.tableGroups = new Map();
        const pageWidth = template.dataset.paperWidth || '210mm';
        const pageHeight = template.dataset.paperHeight || '297mm';
        const margins = {
            top: template.dataset.marginTop || '10mm', right: template.dataset.marginRight || '10mm',
            bottom: template.dataset.marginBottom || '10mm', left: template.dataset.marginLeft || '10mm',
        };
        const availableHeight = Math.max(1, genericCssPixels(pageHeight) - genericCssPixels(margins.top) - genericCssPixels(margins.bottom));
        const sourceChildren = [...originalMain.children];
        sourceChildren.forEach(block => [...(block.matches('img') ? [block] : []), ...block.querySelectorAll('img')].forEach(image => {
            image.style.maxWidth = '100%';
            image.style.maxHeight = `${availableHeight}px`;
            image.style.objectFit = image.style.objectFit || 'contain';
        }));
        originalMain.replaceChildren();
        let page, pageMain;
        const newPage = () => {
            page = document.createElement('div');
            page.className = 'generic-paper-page';
            Object.assign(page.style, {
                position: 'relative', boxSizing: 'border-box', width: pageWidth, height: pageHeight,
                padding: `${margins.top} ${margins.right} ${margins.bottom} ${margins.left}`,
                margin: '0 auto 18px', background: '#fff',
                outline: '1px solid rgba(100, 116, 139, .32)',
            });
            pageMain = originalMain.cloneNode(false);
            pageMain.removeAttribute('id');
            Object.assign(pageMain.style, {display: 'flow-root', boxSizing: 'border-box', width: '100%', height: `${availableHeight}px`, minHeight: `${availableHeight}px`, margin: '0', overflow: 'visible'});
            page.appendChild(pageMain);
            const pageLabel = document.createElement('span');
            pageLabel.className = 'generic-page-label';
            pageLabel.setAttribute('data-pdf-ignore', '');
            pageLabel.textContent = `Page ${root.querySelectorAll('.generic-paper-page').length + 1}`;
            Object.assign(pageLabel.style, {position: 'absolute', right: '5px', bottom: '3px', color: '#64748b', font: '600 9px/1 system-ui,sans-serif', pointerEvents: 'none'});
            page.appendChild(pageLabel);
            root.insertBefore(page, originalMain);
        };
        const pageHasContent = () => [...pageMain.children].some(child => !child.dataset.genericTableSegment || child.tBodies[0]?.rows.length);
        const hasNoBreakAncestor = element => {
            if (originalMain.matches('[data-page-break="nobreak"],.nobreak,[data-pdf-keep]')) return true;
            for (let node = element; node; node = node.parentElement) {
                if (node.dataset?.pageBreak === 'nobreak' || node.classList?.contains('nobreak') || node.hasAttribute?.('data-pdf-keep')) return true;
                if (node === originalMain) break;
            }
            return false;
        };
        const blockOverflows = element => {
            const marginBottom = parseFloat(getComputedStyle(element).marginBottom) || 0;
            const pageBottom = pageMain.getBoundingClientRect().bottom;
            return element.getBoundingClientRect().bottom + marginBottom > pageBottom + 1;
        };
        const hasForcedPageBreakBefore = element => {
            const hasMarker = node => node?.matches?.('[data-page-break-before="always"]')
                || node?.querySelector?.('[data-page-break-before="always"]');
            if (hasMarker(element)) return true;
            const style = getComputedStyle(element);
            return ['page', 'always', 'left', 'right', 'recto', 'verso'].includes(style.breakBefore)
                || ['always', 'left', 'right'].includes(style.pageBreakBefore);
        };
        const makeTableSegment = (original, group, includeCaption) => {
            const segment = original.cloneNode(false);
            segment.removeAttribute('id');
            segment.dataset.genericTableSegment = '1';
            segment.dataset.genericTableGroup = group;
            if (includeCaption && original.caption) segment.appendChild(original.caption.cloneNode(true));
            [...original.children].filter(child => child.tagName === 'COLGROUP' || child.tagName === 'THEAD').forEach(child => segment.appendChild(child.cloneNode(true)));
            const sourceBody = original.tBodies[0], body = document.createElement('tbody');
            [...sourceBody.attributes].forEach(attribute => body.setAttribute(attribute.name, attribute.value));
            segment.appendChild(body);
            return {segment, body};
        };
        const makeParagraphPrefix = (paragraph, wordCount) => {
            const words = paragraph.textContent.match(/\S+\s*/g) || [];
            const endAt = words.slice(0, wordCount).join('').length;
            if (!endAt) return null;
            const walker = document.createTreeWalker(paragraph, NodeFilter.SHOW_TEXT);
            let textNode = walker.nextNode(), consumed = 0, endNode = null, endOffset = 0;
            while (textNode) {
                if (consumed + textNode.length >= endAt) {
                    endNode = textNode;
                    endOffset = endAt - consumed;
                    break;
                }
                consumed += textNode.length;
                textNode = walker.nextNode();
            }
            if (!endNode) return null;
            const range = document.createRange();
            range.setStart(paragraph, 0);
            range.setEnd(endNode, endOffset);
            const prefix = paragraph.cloneNode(false);
            prefix.removeAttribute('id');
            prefix.removeAttribute('data-input');
            prefix.dataset.paginationSplit = '1';
            prefix.appendChild(range.extractContents());
            return prefix;
        };
        const appendAtomic = element => {
            const hadContent = pageHasContent();
            pageMain.appendChild(element);
            if (blockOverflows(element)) {
                if (hasNoBreakAncestor(element) && element.dataset.nobreakForwarded !== '1' && hadContent) {
                    element.remove();
                    element.dataset.nobreakForwarded = '1';
                    newPage();
                    pageMain.appendChild(element);
                    if (!blockOverflows(element)) return;
                }
                if (hasNoBreakAncestor(element) && element.children.length > 1) {
                    const children = [...element.childNodes];
                    let shell = element.cloneNode(false);
                    shell.dataset.nobreakForwarded = '1';
                    element.remove();
                    pageMain.appendChild(shell);
                    children.forEach(child => {
                        shell.appendChild(child);
                        if (!blockOverflows(shell) || shell.childNodes.length === 1) return;
                        child.remove();
                        newPage();
                        shell = element.cloneNode(false);
                        shell.dataset.nobreakForwarded = '1';
                        pageMain.appendChild(shell);
                        shell.appendChild(child);
                    });
                    return;
                }
                const splittableTextBlock = /^(P|DIV|BLOCKQUOTE|H[1-6])$/.test(element.tagName)
                    && !hasNoBreakAncestor(element)
                    && !element.matches('[data-input],[contenteditable="true"]')
                    && !element.querySelector('[data-page-break="nobreak"],.nobreak,[data-pdf-keep]')
                    && !element.querySelector('[data-input],input,textarea,select,[contenteditable="true"],img,svg,canvas,video,table,ul,ol')
                    && element.textContent.trim().split(/\s+/).length > 1;
                if (splittableTextBlock) {
                    element.remove();
                    while (element.textContent.trim()) {
                        const words = element.textContent.match(/\S+\s*/g) || [];
                        let low = 1, high = words.length, best = 0;
                        while (low <= high) {
                            const middle = Math.floor((low + high) / 2);
                            const probeSource = element.cloneNode(true);
                            const probe = makeParagraphPrefix(probeSource, middle);
                            if (!probe) { high = middle - 1; continue; }
                            pageMain.appendChild(probe);
                            const fits = !blockOverflows(probe);
                            probe.remove();
                            if (fits) { best = middle; low = middle + 1; } else high = middle - 1;
                        }
                        if (!best && pageHasContent()) { newPage(); continue; }
                        const count = best || 1;
                        const part = makeParagraphPrefix(element, count);
                        if (!part) { pageMain.appendChild(element); break; }
                        pageMain.appendChild(part);
                        if (element.textContent.trim()) newPage();
                    }
                    return;
                }
                if (pageMain.children.length > 1) { element.remove(); newPage(); pageMain.appendChild(element); }
            }
        };

        newPage();
        sourceChildren.forEach(element => {
            // The same explicit break markers and CSS break-before rules are honored
            // by live pagination and PDF compilation. Margins remain inside the
            // configured page content box when the following block moves.
            if (hasForcedPageBreakBefore(element) && pageHasContent()) newPage();
            if (element.matches('table') && element.tBodies.length === 1 && element.tBodies[0].rows.length) {
                if (hasNoBreakAncestor(element)) {
                    const probe = element.cloneNode(true);
                    pageMain.appendChild(probe);
                    let needsForward = blockOverflows(probe);
                    probe.remove();
                    if (needsForward && pageHasContent()) {
                        newPage();
                        element.dataset.nobreakForwarded = '1';
                        const freshPageProbe = element.cloneNode(true);
                        pageMain.appendChild(freshPageProbe);
                        needsForward = blockOverflows(freshPageProbe);
                        freshPageProbe.remove();
                    }
                    if (!needsForward) { appendAtomic(element); return; }
                    // The group was forwarded once and still exceeds a page, so let the
                    // regular row paginator split it instead of leaving it clipped.
                    element.dataset.nobreakForwarded = '1';
                }
                const group = `table-${state.tableGroups.size + 1}`;
                state.tableGroups.set(group, element);
                const rows = [...element.tBodies[0].rows];
                const rowSpanContinuations = [];
                rows.forEach((row, rowIndex) => {
                    let columnIndex = 0;
                    [...row.cells].forEach(cell => {
                        if (cell.rowSpan > 1) {
                            rowSpanContinuations.push({
                                cell,
                                columnIndex,
                                startRow: rowIndex,
                                endRow: Math.min(rows.length, rowIndex + cell.rowSpan),
                            });
                        }
                        columnIndex += cell.colSpan || 1;
                    });
                });
                const addRowSpanContinuations = (row, rowIndex) => {
                    rowSpanContinuations
                        .filter(span => span.startRow < rowIndex && span.endRow > rowIndex)
                        .forEach(span => {
                            const continuation = span.cell.cloneNode(true);
                            continuation.dataset.paginationRowspanContinuation = '1';
                            continuation.rowSpan = span.endRow - rowIndex;
                            let column = 0;
                            const before = [...row.cells].find(cell => {
                                const startsAt = column;
                                column += cell.colSpan || 1;
                                return startsAt >= span.columnIndex;
                            });
                            row.insertBefore(continuation, before || null);
                        });
                };
                let segmentState = makeTableSegment(element, group, true);
                pageMain.appendChild(segmentState.segment);
                rows.forEach((row, rowIndex) => {
                    segmentState.body.appendChild(row);
                    const rowBottom = Math.max(
                        segmentState.segment.getBoundingClientRect().bottom,
                        row.getBoundingClientRect().bottom,
                        ...[...row.cells].map(cell => cell.getBoundingClientRect().bottom),
                    );
                    if (rowBottom <= pageMain.getBoundingClientRect().bottom + 1) return;
                    row.remove();
                    const hasRows = segmentState.body.rows.length > 0;
                    if (!hasRows) segmentState.segment.remove();
                    if (hasRows || pageHasContent()) newPage();
                    segmentState = makeTableSegment(element, group, false);
                    pageMain.appendChild(segmentState.segment);
                    addRowSpanContinuations(row, rowIndex);
                    segmentState.body.appendChild(row);
                });
                if (element.tFoot) segmentState.segment.appendChild(element.tFoot.cloneNode(true));
                return;
            }
            if (element.matches('ul,ol') && element.children.length) {
                const items = [...element.children].filter(child => child.tagName === 'LI');
                if (items.length) {
                    if (hasNoBreakAncestor(element) && element.dataset.nobreakForwarded !== '1' && pageHasContent()) {
                        const probe = element.cloneNode(true);
                        pageMain.appendChild(probe);
                        const needsForward = blockOverflows(probe);
                        probe.remove();
                        if (needsForward && pageHasContent()) newPage();
                        element.dataset.nobreakForwarded = '1';
                    }
                    if (hasNoBreakAncestor(element)) { appendAtomic(element); return; }
                    let list = element.cloneNode(false);
                    let nextItemIndex = Number(element.getAttribute('start') || 1);
                    pageMain.appendChild(list);
                    items.forEach(item => {
                        list.appendChild(item);
                        if (!blockOverflows(list)) { nextItemIndex++; return; }
                        item.remove();
                        if (!list.children.length) list.remove();
                        if (pageHasContent()) newPage();
                        list = element.cloneNode(false);
                        if (element.tagName === 'OL') list.setAttribute('start', String(nextItemIndex));
                        pageMain.appendChild(list);
                        list.appendChild(item);
                        nextItemIndex++;
                    });
                    return;
                }
            }
            appendAtomic(element);
        });
        originalMain.remove();
        host.style.transform = previousTransform;
    }
    window.repaginateGenericAttachment = function(id) {
        const key = String(id), host = getCanvasHost(), template = document.getElementById(`attachment-form-template-${key}`), state = genericFormStates.get(key);
        if (!host || !template || !state || host.dataset.currentAttachmentId !== key || host.dataset.mainPr === '1') return;
        genericPaginate(state, host, template);
        requestAnimationFrame(() => window.syncPrCanvasSize?.());
    };
    window.getActiveGenericAttachmentSnapshot = () => genericSnapshot(activeCanvasAttachmentId);
    window.checkGenericFormCompleteness = () => {
        const state = genericFormStates.get(String(activeCanvasAttachmentId));
        return state ? genericCompleteness(state.root) : {complete: false, message: 'The form is not ready.'};
    };
    window.loadGenericAttachment = function(id) {
        const key = String(id), host = getCanvasHost(), template = document.getElementById(`attachment-form-template-${key}`);
        if (!host || !template || template.dataset.mainPr === '1') return false;
        const root = host.shadowRoot || host.attachShadow({mode: 'open'});
        if (activeCanvasAttachmentId && activeCanvasAttachmentId !== key) saveActiveAttachmentCanvas();
        if (activeCanvasAttachmentId !== key) {
            root.replaceChildren();
            const cached = attachmentFormCache.get(key);
            if (cached) { root.appendChild(cached); attachmentFormCache.delete(key); }
            else {
                root.innerHTML = template.innerHTML;
                let context = {};
                try { context = JSON.parse(template.dataset.formContext || '{}'); } catch (error) { console.error('Invalid form binding context.', error); }
                const saved = existingAttachmentData[key]?.form_data || context.saved || {};
                const state = {root, context, fields: {...saved}};
                genericRenderBindings(root, context);
                root.querySelectorAll('[data-input]').forEach(element => {
                    const path = element.dataset.input;
                    const row = element.closest('[data-generic-record-id]');
                    const fieldPath = row && path.startsWith(`${row.__genericRecordAlias}.`)
                        ? `${row.__genericCollectionPath}.${row.dataset.genericRecordId}.${path.slice(row.__genericRecordAlias.length + 1)}` : path;
                    const value = Object.prototype.hasOwnProperty.call(state.fields, fieldPath) ? state.fields[fieldPath] : genericValue(row?.__genericContext || context, path);
                    element.dataset.boundValue = value == null ? '' : (element.dataset.valueType === 'boolean' ? (value === true || value === 1 || value === '1' || value === 'true' ? 'true' : 'false') : String(value));
                    if (element.matches('select') && element.dataset.valueType === 'boolean') element.value = element.dataset.boundValue;
                    else if (element.matches('input[type="checkbox"]')) element.checked = value === true || value === 1 || value === '1' || value === 'true';
                    else element.textContent = genericDisplayValue(element, value);
                    if (element.dataset.inputType) element.dataset.genericEditable = '1';
                    if (element.dataset.required) element.setAttribute('aria-label', element.dataset.label || path);
                });
                const editorStyle = document.createElement('style');
                editorStyle.textContent = `
                    [data-generic-editable="1"]{display:inline-block;min-width:1.5em;min-height:1.2em;vertical-align:middle;cursor:pointer;transition:background-color .12s ease,outline-color .12s ease;outline:1px solid transparent;outline-offset:1px}
                    [hidden]{display:none!important}
                    [data-generic-editable="1"]:empty{min-width:4em;padding:1px 3px}
                    [data-generic-editable="1"]:hover{background:rgba(219,234,254,.55);outline-color:#93c5fd}
                    [data-generic-editable="1"].required-empty{background:rgba(254,226,226,.68);outline-color:#f87171}
                    [data-generic-editable="1"]:focus-within{background:#fff;outline:2px solid #6366f1}
                    [data-generic-editable="1"] input{width:100%;font:inherit;color:inherit;text-align:inherit;border:0;background:transparent;outline:0}
                    [data-rfq-document-toggle]{position:relative;cursor:pointer}
                    [data-rfq-document-toggle]:hover:not([data-rfq-saved])::after{content:"Toggle";position:absolute;inset:0;display:grid;place-items:center;background:rgba(219,234,254,.94);color:#134991;font:700 9px Arial,sans-serif}
                    [data-rfq-document-toggle]:focus-visible{outline:2px solid #2563eb;outline-offset:1px}
                    [data-rfq-document-toggle][aria-busy="true"]::after{content:"Saving…";position:absolute;inset:0;display:grid;place-items:center;background:rgba(219,234,254,.94);color:#134991;font:700 8px Arial,sans-serif}
                `;
                root.prepend(editorStyle);
                genericApplyConditions(state);
                genericRecalculate(state);
                genericRefreshValidation(state);
                const updateGenericInput = event => {
                    const control = event.target.closest?.('input,textarea,select');
                    const element = control?.closest('[data-input]');
                    if (!element) return;
                    const rawValue = control.type === 'checkbox' ? control.checked : control.value;
                    const value = element.dataset.valueType === 'boolean'
                        ? (rawValue === true || rawValue === '1' || rawValue === 'true')
                        : rawValue;
                    element.dataset.boundValue = element.dataset.valueType === 'boolean' ? (value ? 'true' : 'false') : String(value);
                    const row = element.closest('[data-generic-record-id]');
                    const path = element.dataset.input;
                    const relative = row && path.startsWith(`${row.__genericRecordAlias}.`) ? path.slice(row.__genericRecordAlias.length + 1) : null;
                    if (row?.__genericRow && relative) {
                        row.__genericRow[relative] = value;
                        const relatedItem = state.context.procurement?.items?.find(item => String(item.id) === row.dataset.genericRecordId);
                        if (relatedItem) relatedItem[relative] = value;
                    }
                    else if (path.startsWith('procurement.')) {
                        const parts = path.slice('procurement.'.length).split('.');
                        let target = state.context.procurement;
                        while (parts.length > 1) target = target[parts.shift()] ||= {};
                        target[parts[0]] = control.type === 'checkbox' ? control.checked : value;
                    }
                    genericApplyConditions(state);
                    genericRecalculate(state);
                    genericRefreshValidation(state);
                    window.Alpine?.$data(document.getElementById('pr-workspace'))?.queueAutoSave(key);
                };
                root.addEventListener('input', updateGenericInput);
                root.addEventListener('change', updateGenericInput);
                if (!rfqDocumentToggleRoots.has(root)) {
                    root.addEventListener('click', async event => {
                        const cell = event.target.closest?.('[data-rfq-document-toggle]');
                        if (!cell || !root.contains(cell)) return;
                        event.preventDefault();

                        const workspace = window.Alpine?.$data(document.getElementById('pr-workspace'));
                        if (!workspace?.canEdit) return;

                        const row = cell.closest('[data-generic-record-id]');
                        const documentId = row?.dataset.genericRecordId;
                        const attachmentId = String(activeCanvasAttachmentId || '');
                        const state = genericFormStates.get(attachmentId);
                        if (!documentId || !attachmentId || !state) return;

                        const token = document.querySelector('#pr-form input[name="_token"]')?.value
                            || document.querySelector('meta[name="csrf-token"]')?.content
                            || '';
                        const selectedIds = Array.isArray(state.context.procurement?.rfq_documents)
                            ? state.context.procurement.rfq_documents.map(String)
                            : [];
                        const selected = !selectedIds.includes(String(documentId));
                        const data = new FormData();
                        data.append('_token', token);
                        data.append('selected', selected ? '1' : '0');
                        const url = (document.getElementById('pr-form')?.dataset.rfqDocumentToggleUrl || '')
                            .replace('__ID__', encodeURIComponent(attachmentId))
                            .replace('__DOCUMENT__', encodeURIComponent(String(documentId)));
                        cell.setAttribute('aria-busy', 'true');

                        try {
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                                body: data,
                                credentials: 'same-origin',
                            });
                            let result = {};
                            try { result = await response.json(); } catch {}
                            if (!response.ok || result.ok !== true) {
                                const message = result.message || (result.errors ? Object.values(result.errors).flat().join(' ') : 'Unable to save the required document selection.');
                                throw new Error(message);
                            }

                            state.context.procurement.rfq_documents = selected
                                ? [...selectedIds, String(documentId)]
                                : selectedIds.filter(id => id !== String(documentId));
                            if (row.__genericRow) row.__genericRow.checked_mark = selected ? '✓' : '';
                            const mark = cell.querySelector('[data-print="document.checked_mark"]');
                            if (mark) mark.textContent = selected ? '✓' : '';
                            cell.setAttribute('aria-pressed', selected ? 'true' : 'false');
                            cell.dataset.rfqSaved = '1';
                            cell.addEventListener('pointerleave', () => delete cell.dataset.rfqSaved, {once: true});
                            workspace.setAttachmentReady(attachmentId, result);
                            workspace.feedback('Required document selection saved.', true);
                        } catch (error) {
                            console.error('Unable to update the RFQ required document selection:', error);
                            workspace.feedback(error.message || 'Unable to save the required document selection.', false);
                            alert(error.message || 'Unable to save the required document selection.');
                        } finally {
                            cell.removeAttribute('aria-busy');
                        }
                    });
                    root.addEventListener('keydown', event => {
                        const target = event.composedPath?.()[0] || event.target;
                        if (target?.closest?.('input,textarea,select,[contenteditable="true"],[data-pr-editor]')) return;
                        const cell = target?.closest?.('[data-rfq-document-toggle]');
                        if (cell && (event.key === 'Enter' || event.key === ' ')) {
                            event.preventDefault();
                            cell.click();
                        }
                    });
                    rfqDocumentToggleRoots.add(root);
                }
                root.addEventListener('click', event => {
                    const element = event.target.closest?.('[data-input]')
                        || event.target.closest?.('[data-canvas-editable-cell]')?.querySelector('[data-input]');
                    if (!element || !window.Alpine?.$data(document.getElementById('pr-workspace'))?.canEdit || element.matches('input,textarea,select') || element.querySelector('input,textarea,select')) return;
                    const type = element.dataset.inputType || 'text';
                    const control = type === 'select' ? document.createElement('select') : (type === 'textarea' ? document.createElement('textarea') : document.createElement('input'));
                    if (control instanceof HTMLInputElement) control.type = ['date', 'datetime-local', 'email', 'number', 'checkbox', 'radio'].includes(type) ? type : 'text';
                    if (element.getAttribute('name')) control.name = element.getAttribute('name');
                    if (control instanceof HTMLInputElement && type === 'number') {
                        if (element.dataset.step) control.step = element.dataset.step;
                        if (element.dataset.min) control.min = element.dataset.min;
                        if (element.dataset.max) control.max = element.dataset.max;
                    }
                    if (element.dataset.required !== undefined) control.required = true;
                    if (element.dataset.minLength) control.minLength = Number(element.dataset.minLength);
                    if (element.dataset.maxLength) control.maxLength = Number(element.dataset.maxLength);
                    if (element.dataset.pattern) control.pattern = element.dataset.pattern;
                    if ('placeholder' in control) control.placeholder = element.dataset.unit || element.dataset.placeholder || '';
                    control.style.boxSizing = 'border-box';
                    control.style.width = '100%';
                    control.style.minWidth = '0';
                    control.style.margin = '0';
                    control.style.verticalAlign = 'middle';
                    control.style.lineHeight = 'inherit';
                    control.style.padding = '0 4px';
                    if (type === 'number') bindNumericInput(control);
                    if (control instanceof HTMLSelectElement) {
                        const template = document.getElementById(`attachment-form-template-${key}`);
                        let context = {};
                        try { context = JSON.parse(template?.dataset.formContext || '{}'); } catch {}
                        const options = context.sources?.[element.dataset.source] || [];
                        let declaredOptions = [];
                        try { declaredOptions = JSON.parse(element.dataset.options || '[]'); } catch {}
                        if (declaredOptions.length) declaredOptions.forEach(option => control.add(new Option(option.label || option.name || option.value, option.value)));
                        else {
                            control.add(new Option('Select…', ''));
                            options.forEach(option => {
                                const source = element.dataset.source;
                                const value = source === 'offices' ? option.id : (source === 'fund_clusters' ? option.name : (option.id ?? option.code ?? option.value));
                                const label = source === 'offices' ? option.name : (option.name || option.label || option.code);
                                control.add(new Option(label, value));
                            });
                        }
                    }
                    const savedValue = element.dataset.boundValue || '';
                    const originalEditorStyle = {
                        display: element.style.display,
                        boxSizing: element.style.boxSizing,
                        minHeight: element.style.minHeight,
                        gridTemplateColumns: element.style.gridTemplateColumns,
                        alignItems: element.style.alignItems,
                        verticalAlign: element.style.verticalAlign,
                    };
                    const restoreEditorStyle = () => Object.entries(originalEditorStyle).forEach(([property, value]) => { element.style[property] = value; });
                    if (control instanceof HTMLInputElement && control.type === 'checkbox') control.checked = savedValue === 'true' || savedValue === '1';
                    else if (control instanceof HTMLInputElement && control.type === 'radio') control.checked = String(control.value) === String(savedValue);
                    else if (control instanceof HTMLInputElement && control.type === 'datetime-local') {
                        control.value = String(savedValue).replace(' ', 'T').slice(0, 16);
                    } else control.value = savedValue;
                    control.className = 'field';
                    control.setAttribute('aria-label', element.dataset.label || element.dataset.input);
                    element.replaceChildren(control);
                    element.style.display = 'inline-grid';
                    element.style.gridTemplateColumns = 'minmax(0,1fr)';
                    element.style.alignItems = 'stretch';
                    element.style.boxSizing = 'border-box';
                    element.style.minHeight = '1em';
                    element.style.verticalAlign = 'middle';
                    control.focus();
                    if (control instanceof HTMLInputElement && ['date', 'datetime-local'].includes(control.type)) control.showPicker?.();
                    else if (control instanceof HTMLInputElement) control.select();
                    let cancelled = false;
                    const commit = () => {
                        if (cancelled) {
                            element.dataset.boundValue = savedValue;
                            element.dataset.value = savedValue;
                            element.textContent = genericDisplayValue(element, savedValue);
                            if (element.dataset.valueType === 'boolean' && control instanceof HTMLInputElement && control.type === 'checkbox') {
                                element.textContent = savedValue === 'true' ? (element.dataset.trueLabel || 'Yes') : (element.dataset.falseLabel || 'No');
                            }
                            restoreEditorStyle();
                            genericRefreshValidation(genericFormStates.get(key));
                            requestAnimationFrame(() => window.repaginateGenericAttachment?.(key));
                            return;
                        }
                        const rawValue = control instanceof HTMLInputElement && control.type === 'checkbox' ? control.checked : control.value;
                        const value = element.dataset.valueType === 'boolean'
                            ? (rawValue === true || rawValue === '1' || rawValue === 'true' ? 'true' : 'false')
                            : String(rawValue ?? '');
                        element.dataset.boundValue = value;
                        element.dataset.value = value;
                        element.textContent = genericDisplayValue(element, value);
                        if (element.dataset.valueType === 'boolean' && control instanceof HTMLInputElement && control.type === 'checkbox') {
                            element.textContent = value === 'true' ? (element.dataset.trueLabel || 'Yes') : (element.dataset.falseLabel || 'No');
                        }
                        restoreEditorStyle();
                        genericRefreshValidation(genericFormStates.get(key));
                        window.Alpine?.$data(document.getElementById('pr-workspace'))?.queueAutoSave(key);
                        requestAnimationFrame(() => window.repaginateGenericAttachment?.(key));
                    };
                    control.addEventListener('blur', commit, {once: true});
                    control.addEventListener('keydown', inputEvent => {
                        if (inputEvent.key === 'Enter' && control.tagName === 'INPUT') control.blur();
                        if (inputEvent.key === 'Escape') {
                            inputEvent.preventDefault();
                            cancelled = true;
                            if (control instanceof HTMLInputElement && control.type === 'checkbox') control.checked = savedValue === 'true' || savedValue === '1';
                            else if (control instanceof HTMLInputElement && control.type === 'datetime-local') {
                                control.value = String(savedValue).replace(' ', 'T').slice(0, 16);
                            } else control.value = savedValue;
                            control.blur();
                        }
                    });
                });
                root.addEventListener('keydown', event => {
                    const target = event.composedPath?.()[0] || event.target;
                    if (target?.closest?.('input,textarea,select,[contenteditable="true"],[data-pr-editor]')) return;
                    const cell = target?.closest?.('[data-canvas-editable-cell]');
                    if (cell && (event.key === 'Enter' || event.key === ' ')) {
                        event.preventDefault();
                        cell.click();
                    }
                });
            }
        }
        activeCanvasAttachmentId = key;
        const context = (() => { try { return JSON.parse(template.dataset.formContext || '{}'); } catch { return {}; } })();
        const state = genericFormStates.get(key) || {root, context, fields: {...(existingAttachmentData[key]?.form_data || {})}, tableGroups: new Map()};
        state.root = root;
        state.context = context;
        state.originalMain ||= root.querySelector('main.form-document, main.rfq-document, main');
        genericFormStates.set(key, state);
        host.dataset.currentAttachmentId = key;
        host.dataset.mainPr = '0';
        host.dataset.prHydrated = '1';
        host.dataset.attachmentDataHydrated = '1';
        host.dataset.needsHydrate = '0';
        host.style.width = template.dataset.paperWidth || '210mm';
        host.style.minHeight = '0';
        host.style.padding = '0';
        host.dataset.formMarginTop = template.dataset.marginTop || '10mm';
        host.dataset.formMarginRight = template.dataset.marginRight || '10mm';
        host.dataset.formMarginBottom = template.dataset.marginBottom || '10mm';
        host.dataset.formMarginLeft = template.dataset.marginLeft || '10mm';
        host.dataset.formPageWidth = template.dataset.paperWidth || '210mm';
        host.dataset.formPageHeight = template.dataset.paperHeight || '297mm';
        host.style.boxShadow = 'none';
        host.style.outline = 'none';
        host.style.background = 'transparent';
        host.style.borderRadius = '0';
        if (!root.querySelector('.generic-paper-page')) genericPaginate(state, host, template);
        host.style.transform = `scale(${Number(document.getElementById('pr-workspace')?.dataset.scale || 1)})`;
        window.bindPrCanvasZoom?.();
        window.syncPrCanvasSize?.();
        return true;
    };

    // Expose functions globally to window so inline handlers always resolve
    function notifyPrFormChanged() {
        if (!activeCanvasAttachmentId) return;
        window.dispatchEvent(new CustomEvent('pr-form-changed', {detail: {id: activeCanvasAttachmentId}}));
    }

    window.addBaseRow = function(type) {
        const tbody = getTbody();
        if (!tbody) return;

        const actionsRow = tbody.querySelector('.add-actions-row:not(.template-row)');

        if (type === 'standalone') {
            const row = getTemplate('standalone');
            actionsRow ? tbody.insertBefore(row, actionsRow) : tbody.appendChild(row);
        } else if (type === 'lot') {
            const lotId = `lot_${Date.now()}`;
            const parent = getTemplate('lot_parent', lotId);
            const member = getTemplate('lot_member', lotId);
            if (actionsRow) {
                tbody.insertBefore(parent, actionsRow);
                tbody.insertBefore(member, actionsRow);
            } else {
                tbody.append(parent, member);
            }
            updateLotRowspan(lotId);
        }
        refreshView();
        notifyPrFormChanged();
    };

    window.handleMenuAction = function(action, btn) {
        const row = btn.closest('tr');
        if (!row) return;

        const type = row.dataset.type;
        const lotId = row.dataset.lotId;
        
        if (action === 'add_above') {
            row.before(getTemplate('standalone'));
        } else if (action === 'add_below') {
            row.after(getTemplate('standalone'));
        } else if (action === 'add_member_above') {
            row.before(getTemplate('lot_member', lotId));
            updateLotRowspan(lotId);
        } else if (action === 'add_member_below') {
            row.after(getTemplate('lot_member', lotId));
            updateLotRowspan(lotId);
        } else if (action === 'add_lot_above') {
            const newLotId = `lot_${Date.now()}`;
            row.before(getTemplate('lot_parent', newLotId));
            row.before(getTemplate('lot_member', newLotId));
            updateLotRowspan(newLotId);
        } else if (action === 'add_lot_below') {
            const newLotId = `lot_${Date.now()}`;
            const parent = getTemplate('lot_parent', newLotId);
            const member = getTemplate('lot_member', newLotId);
            row.after(parent, member);
            updateLotRowspan(newLotId);
        } else if (action === 'delete') {
            const liveItemRows = [...getTbody().querySelectorAll('tr.item-row:not(.template-row):not(.add-actions-row)')];
            if (liveItemRows.length <= 1) {
                window.alert('At least one item row must remain in the Purchase Request.');
                return;
            }
            row.remove();
            if (type === 'lot_member') cleanUpEmptyLots(lotId);
        } else if (action === 'delete_lot') {
            const root = getCanvasRoot();
            const liveItemRows = [...root.querySelectorAll('[data-each="procurement.items"] tr.item-row:not(.template-row):not(.add-actions-row)')];
            const lotMembers = [...root.querySelectorAll(`tr.item-row[data-lot-id="${lotId}"]`)];
            if (liveItemRows.length <= lotMembers.length) {
                window.alert('At least one item row must remain in the Purchase Request.');
                return;
            }
            root.querySelectorAll(`tr[data-lot-id="${lotId}"]`).forEach(r => r.remove());
        }
        
        const menu = btn.closest('.context-menu');
        if (menu) menu.style.display = 'none';
        refreshView();
        notifyPrFormChanged();
    };

    function getTemplate(type, lotId = null) {
        const root = getCanvasRoot();
        const source = root.getElementById('standalone-template');
        if (!source) throw new Error('The PR item row template is missing.');
        const template = source.cloneNode(true);
        template.hidden = false;
        template.classList.remove('template-row');
        template.removeAttribute('id');
        template.classList.toggle('item-row', type !== 'lot_parent');
        rowIdx++;
        template.dataset.rowIdx = rowIdx;
        template.dataset.type = type;
        template.querySelectorAll('[name]').forEach(el => {
            el.name = el.name.replace('{idx}', rowIdx);
        });

        if (type === 'lot_parent') {
            template.dataset.type = 'lot_parent';
            template.dataset.lotId = lotId || `lot_${Date.now()}`;
            const unitCell = template.querySelector('[data-input="item.unit"]')?.closest('td');
            if (unitCell) {
                unitCell.removeAttribute('data-input');
                unitCell.removeAttribute('data-required');
                unitCell.removeAttribute('data-pr-field-container');
                unitCell.dataset.value = 'Lot';
                unitCell.dataset.boundValue = 'Lot';
                unitCell.replaceChildren(document.createTextNode('Lot'));
                unitCell.dataset.lotUnitCell = '1';
            }
        } else if (type === 'lot_member') {
            template.dataset.type = 'lot_member';
            template.dataset.lotId = lotId;
            template.querySelector('[data-input="item.unit"]')?.closest('td')?.remove();
        }

        configurePrFields(template);
        decoratePrRow(template, type);
        template.querySelectorAll('.qty-input, .quantity-input').forEach(input => { input.addEventListener('input', calculateTotals); bindQuantityInput(input); });
        template.querySelectorAll('.cost-input').forEach(bindMoneyInput);
        template.querySelectorAll('.line-total').forEach(output => { output.textContent = formatMoney(parseMoney(output.textContent)); });

        bindEditableFields(template);
        bindDragAndDrop(template);
        return template;
    }

    function updateLotRowspan(lotId) {
        const root = getCanvasRoot();
        const parent = root.querySelector(`tr[data-type="lot_parent"][data-lot-id="${lotId}"]`);
        if (!parent) return;
        const members = [...root.querySelectorAll(`tr[data-type="lot_member"][data-lot-id="${lotId}"]`)];
        const rowSpan = members.length + 1;
        const unitCell = parent.querySelector('[data-lot-unit-cell]');
        if (unitCell) unitCell.rowSpan = rowSpan;
        [
            {selector: '.cost-input', value: 'cost'},
            {selector: '.line-total', value: 'lineTotal'},
        ].forEach(({selector}) => {
            const cell = parent.querySelector(selector)?.closest('td');
            if (cell) cell.rowSpan = rowSpan;
        });
        members.forEach(member => {
            const unit = member.querySelector('[data-input="item.unit"]');
            if (unit) member.dataset.unit = prFieldValue(unit);
            [
                {selector: '.cost-input', value: 'cost', read: field => prFieldValue(field)},
                {selector: '.line-total', value: 'lineTotal', read: field => field.textContent},
            ].forEach(({selector, value, read}) => {
                const field = member.querySelector(selector);
                if (!field) return;
                member.dataset[value] = read(field);
                field.closest('td')?.remove();
            });
            unit?.closest('td')?.remove();
        });
    }

    function restoreLotMemberCells(row) {
        const root = getCanvasRoot();
        const source = root.getElementById('standalone-template');
        if (!source) throw new Error('The PR item row template is missing.');
        const restoreCell = (sourceSelector, fieldSelector, cellIndex) => {
            if (row.querySelector(fieldSelector)) return;
            const sourceField = source.querySelector(sourceSelector);
            const sourceCell = sourceField?.closest('td');
            if (!sourceCell) throw new Error(`The PR item row template is missing ${sourceSelector}.`);
            const cell = sourceCell.cloneNode(true);
            row.insertBefore(cell, row.cells[cellIndex] || null);
        }
        restoreCell('[data-input="item.unit"]', '[data-input="item.unit"]', 1);
        restoreCell('[data-input="item.cost"]', '.cost-input', 4);
        restoreCell('[data-print="item.total_cost"]', '.line-total', 5);
        configurePrFields(row);
        const unit = row.querySelector('[data-input="item.unit"]');
        const cost = row.querySelector('.cost-input');
        const lineTotal = row.querySelector('.line-total');
        if (unit) setPrFieldValue(unit, row.dataset.unit ?? '');
        if (cost) setPrFieldValue(cost, row.dataset.cost ?? '');
        if (lineTotal) lineTotal.textContent = row.dataset.lineTotal || '0.00';
        delete row.dataset.unit;
        delete row.dataset.cost;
        delete row.dataset.lineTotal;
    }

    function cleanUpEmptyLots(lotId) {
        const root = getCanvasRoot();
        const members = root.querySelectorAll(`tr[data-type="lot_member"][data-lot-id="${lotId}"]`);
        if (members.length === 0) {
            const parent = root.querySelector(`tr[data-type="lot_parent"][data-lot-id="${lotId}"]`);
            if (parent) parent.remove();
        } else {
            updateLotRowspan(lotId);
        }
    }

    function updateLineNumbers() {
        const tbody = getTbody();
        if (!tbody) return;
        let ln = 1;
        tbody.querySelectorAll('tr:not(.template-row):not(.add-actions-row)').forEach(row => {
            const lnEl = row.querySelector('.ln-text');
            if (lnEl) lnEl.textContent = ln++;
        });
    }

    function parseMoney(value) { const number = Number(String(value ?? '').replace(/,/g, '').trim()); return Number.isFinite(number) ? number : 0; }
    function formatMoney(value) { return parseMoney(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function formatDecimal(value) {
        const number = Number(String(value ?? '').replace(/,/g, '').trim());
        return Number.isFinite(number) ? number.toFixed(2) : '';
    }
    function bindQuantityInput(field) {
        const input = prFieldControl(field);
        if (!input?.matches?.('input') || input.dataset.quantityBound === '1') return;
        input.dataset.quantityBound = '1';
        input.addEventListener('input', calculateTotals);
    }
    function bindMoneyInput(field) {
        const input = prFieldControl(field);
        if (!input?.matches?.('input') || input.dataset.moneyBound === '1') return;
        input.dataset.moneyBound = '1';
        input.addEventListener('input', calculateTotals);
    }

    function calculateTotals() {
        const tbody = getTbody();
        if (!tbody) return;
        
        let grandTotal = 0;
        tbody.querySelectorAll('tr:not(.template-row):not(.add-actions-row)').forEach(row => {
            const totalInput = row.querySelector('.line-total');
            const qtyInput = row.querySelector('.qty-input, .quantity-input');
            const costInput = row.querySelector('.cost-input');
            
            const qty = parseFloat(prFieldValue(qtyInput) || '0') || 0;
            const cost = parseMoney(costInput ? prFieldValue(costInput) : row.dataset.cost);
            const total = qty * cost;
            
            if (totalInput) totalInput.textContent = formatMoney(total);
            row.dataset.lineTotal = formatMoney(total);
            grandTotal += total;
        });

        const grandTotalInput = getGrandTotalInput();
        if (grandTotalInput) grandTotalInput.textContent = formatMoney(grandTotal);
    }

    function updateAddActionsState() {
        const tbody = getTbody();
        if (!tbody) return;
        const root = getCanvasRoot();
        const itemRows = [...tbody.querySelectorAll('tr.item-row:not(.template-row):not(.add-actions-row)')];
        const empty = itemRows.length === 0;
        const grandTotalRow = root.querySelector('.pr-grand-total-row');
        const actionContainer = grandTotalRow?.closest('tbody') || tbody;
        let emptyRow = root.querySelector('.add-actions-row.empty-state-row');
        let overlayRow = root.querySelector('.add-actions-row.overlay-state-row');

        if (empty) {
            if (overlayRow) overlayRow.remove();
            if (!emptyRow) {
                emptyRow = cloneAddActionsRow('empty-state-row');
                if (emptyRow) grandTotalRow ? actionContainer.insertBefore(emptyRow, grandTotalRow) : tbody.appendChild(emptyRow);
            }
            if (emptyRow && grandTotalRow && emptyRow.nextElementSibling !== grandTotalRow) actionContainer.insertBefore(emptyRow, grandTotalRow);
        } else {
            if (emptyRow) emptyRow.remove();
            if (!overlayRow) {
                overlayRow = cloneAddActionsRow('overlay-state-row');
                if (overlayRow) grandTotalRow ? actionContainer.insertBefore(overlayRow, grandTotalRow) : tbody.appendChild(overlayRow);
            }
            if (overlayRow && grandTotalRow && overlayRow.nextElementSibling !== grandTotalRow) actionContainer.insertBefore(overlayRow, grandTotalRow);
        }
    }

    function cloneAddActionsRow(stateClass) {
        const tbody = getTbody();
        const template = tbody?.querySelector('#add-actions-template');
        if (!template) return null;
        const row = template.cloneNode(true);
        row.removeAttribute('id');
        row.hidden = false;
        row.classList.remove('template-row');
        row.classList.add('add-actions-row', stateClass);
        const cell = row.querySelector('td');
        const container = row.querySelector('div') || document.createElement('div');
        container.classList.add('add-actions');
        container.innerHTML = `${prAddRowButtonMarkup('standalone', '+ Add Item')}${prAddRowButtonMarkup('lot', '+ Add Lot Group')}`;
        container.style.cssText = stateClass === 'empty-state-row'
            ? 'display:flex;justify-content:center;gap:8px;line-height:normal'
            : 'position:absolute;left:50%;bottom:-30px;transform:translateX(-50%);display:none;gap:8px;padding:5px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;box-shadow:0 4px 6px -1px rgb(0 0 0 / .1);z-index:10;line-height:normal';
        if (!container.parentNode) cell?.appendChild(container);
        if (cell) {
            cell.colSpan = 13;
            cell.style.position = 'relative';
            if (stateClass === 'empty-state-row') {
                cell.style.padding = '12px 0';
                cell.style.textAlign = 'center';
                cell.style.border = '1px solid #111';
            } else {
                cell.style.height = '0';
                cell.style.padding = '0';
                cell.style.border = '0';
                cell.style.lineHeight = '0';
            }
        }
        if (tbody && tbody.dataset.addActionsHoverBound !== '1') {
            tbody.addEventListener('mouseenter', () => {
                const actions = tbody.querySelector('.add-actions-row.overlay-state-row .add-actions');
                if (actions) actions.style.display = 'flex';
            });
            tbody.addEventListener('mouseleave', () => {
                const actions = tbody.querySelector('.add-actions-row.overlay-state-row .add-actions');
                if (actions) actions.style.display = 'none';
            });
            tbody.dataset.addActionsHoverBound = '1';
        }
        return row;
    }

    function refreshView() {
        updateAddActionsState();
        updateLineNumbers();
        refreshAllLotSpans();
        calculateTotals();
        publishCompleteness();
        requestAnimationFrame(() => window.syncPrCanvasSize?.());
    }

    function setRowMenu(row, type) {
        const menu = row.querySelector('.context-menu');
        if (!menu) return;
        if (type === 'lot_member') {
            menu.innerHTML = `${prActionButtonMarkup('add_member_above', 'Add Member Above')}${prActionButtonMarkup('add_member_below', 'Add Member Below')}${prActionButtonMarkup('delete', 'Delete Member', 'text-red')}`;
        } else if (type === 'lot_parent') {
            menu.innerHTML = `${prActionButtonMarkup('add_member_below', 'Add Lot Member')}${prActionButtonMarkup('add_above', 'Add Item Above')}${prActionButtonMarkup('delete_lot', 'Delete Entire Lot', 'text-red')}`;
        } else {
            menu.innerHTML = `${prActionButtonMarkup('add_above', 'Add Item Above')}${prActionButtonMarkup('add_below', 'Add Item Below')}${prActionButtonMarkup('add_lot_above', 'Add New Lot Above')}${prActionButtonMarkup('add_lot_below', 'Add New Lot Below')}${prActionButtonMarkup('delete', 'Delete Item', 'text-red')}`;
        }
    }

    function moveRowControls(row, type) {
        const controls = row.querySelector('.row-controls');
        if (!controls) return;
        const actionCell = row.cells[0] || row.querySelector('[data-print="item.total_cost"]')?.closest('td');
        if (!actionCell) return;
        actionCell.style.position = 'relative';
        controls.style.cssText = 'position:absolute;right:2px;top:50%;transform:translateY(-50%);display:flex;gap:4px;z-index:20;opacity:0;transition:opacity .15s';
        actionCell.appendChild(controls);
    }

    function setRowType(row, type, lotId = null) {
        if (type === 'lot_member') {
            const unitCell = row.querySelector('[data-input="item.unit"]')?.closest('td') || row.children[1];
            if (unitCell) {
                const field = unitCell.querySelector('[data-input="item.unit"]');
                if (field) row.dataset.unit = prFieldValue(field);
                unitCell.remove();
            }
            const cost = row.querySelector('.cost-input');
            if (cost) row.dataset.cost = prFieldValue(cost);
            const lineTotal = row.querySelector('.line-total');
            if (lineTotal) row.dataset.lineTotal = lineTotal.textContent;
            row.classList.add('item-row');
            row.dataset.type = 'lot_member';
            row.dataset.lotId = lotId;
            configurePrFields(row);
            moveRowControls(row, type);
        } else if (type === 'standalone') {
            restoreLotMemberCells(row);
            row.classList.add('item-row');
            row.dataset.type = 'standalone';
            delete row.dataset.lotId;
            configurePrFields(row);
            moveRowControls(row, type);
        }
        setRowMenu(row, row.dataset.type);
    }

    function insertRows(reference, nodes, after) {
        if (!reference || !nodes.length) return;
        if (after) reference.after(...nodes);
        else reference.before(...nodes);
    }

    function refreshAllLotSpans() {
        const root = getCanvasRoot();
        root.querySelectorAll('tr[data-type="lot_parent"][data-lot-id]').forEach(parent => updateLotRowspan(parent.dataset.lotId));
    }

    function bindDragAndDrop(row) {
        const handles = row.querySelectorAll('.drag-handle, .lot-group-drag-handle');
        if (!handles.length) return;

        handles.forEach(handle => {
            if (handle.dataset.dragBound === '1') return;
            handle.dataset.dragBound = '1';
            handle.addEventListener('dragstart', e => {
                draggedRow = row;
                const root = getCanvasRoot();
                activeDropTarget = null;
                activeDropPlacement = null;
                if (row.dataset.type === 'lot_parent' && row.dataset.lotId) {
                    draggedRows = [...root.querySelectorAll(`tr[data-lot-id="${row.dataset.lotId}"]`)];
                } else {
                    draggedRows = [row];
                }
                draggedRows.forEach(item => item.classList.add('dragging'));
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', row.dataset.rowIdx || 'row');
            });
        });

        row.addEventListener('dragend', () => {
            draggedRows.forEach(item => item.classList.remove('dragging'));
            clearDragDropIndicators(getCanvasRoot());
            draggedRow = null;
            draggedRows = [];
            activeDropTarget = null;
            activeDropPlacement = null;
            refreshView();
        });

        const root = getCanvasRoot();
        if (dragDropRoots.has(root)) return;
        root.addEventListener('dragover', event => {
            if (!draggedRow) return;
            const drop = resolvePrDropTarget(event, root);
            clearDragDropIndicators(root);
            activeDropTarget = drop?.row || null;
            activeDropPlacement = drop?.placement || null;
            if (drop) {
                drop.row.classList.add(`drag-drop-${drop.placement}`);
                drop.row.dataset.dropPlacement = drop.placement;
                const dropKind = getPrDropKind(draggedRow, drop.row);
                if (dropKind) {
                    drop.row.classList.add(`drag-drop-${dropKind}`);
                    drop.row.dataset.dropKind = dropKind;
                }
                event.preventDefault();
                if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
            }
        });
        root.addEventListener('drop', event => {
            if (!draggedRow) return;
            const drop = resolvePrDropTarget(event, root);
            const target = activeDropTarget || drop?.row;
            const placement = activeDropPlacement || drop?.placement;
            if (!target || !placement || draggedRows.includes(target)) return;
            event.preventDefault();
            movePrRowToTarget(target, placement, root);
        });
        root.addEventListener('dragleave', event => {
            if (event.relatedTarget instanceof Node && root.contains?.(event.relatedTarget)) return;
            clearDragDropIndicators(root);
            activeDropTarget = null;
            activeDropPlacement = null;
        });
        dragDropRoots.add(root);
    }

    function resolvePrDropTarget(event, root) {
        const tbody = getTbody(root);
        if (!tbody || !draggedRow) return null;
        const path = event.composedPath?.() || [event.target];
        const targetRow = path.find(node => node instanceof Element && node.matches('tr.item-row:not(.template-row):not(.add-actions-row)'));
        if (targetRow && !draggedRows.includes(targetRow)) {
            const rect = targetRow.getBoundingClientRect();
            return {row: targetRow, placement: event.clientY < rect.top + rect.height / 2 ? 'before' : 'after'};
        }

        const itemRows = [...tbody.querySelectorAll('tr.item-row:not(.template-row):not(.add-actions-row)')];
        const lastRow = itemRows[itemRows.length - 1];
        if (!lastRow || draggedRows.includes(lastRow)) return null;
        const isActionOverlay = path.some(node => node instanceof Element && node.closest?.('.add-actions-row'));
        const lastRect = lastRow.getBoundingClientRect();
        const isAtLastBottom = event.clientY >= lastRect.bottom - 4 && event.clientY <= lastRect.bottom + 40;
        if (isActionOverlay || isAtLastBottom) return {row: lastRow, placement: 'after'};
        return null;
    }

    function getPrDropKind(source, target) {
        if (!source || !target) return null;
        const sourceType = source.dataset.type;
        const targetType = target.dataset.type;
        const sourceLotId = source.dataset.lotId || null;
        const targetLotId = target.dataset.lotId || null;
        const targetIsLot = targetType === 'lot_parent' || targetType === 'lot_member';
        if (targetIsLot && sourceType !== 'lot_parent' && sourceLotId !== targetLotId) return 'into-lot';
        if (sourceType === 'lot_member' && !targetIsLot) return 'out-of-lot';
        if (sourceType === 'lot_parent' && !targetIsLot) return 'out-of-lot';
        return null;
    }

    function movePrRowToTarget(row, placement, root) {
        if (!draggedRow || !row || draggedRows.includes(row)) return;
        const sourceRow = draggedRow;
        const sourceType = sourceRow.dataset.type;
        const oldLotId = sourceRow.dataset.lotId || null;
        const targetType = row.dataset.type;
        const targetLotId = row.dataset.lotId || null;
        const isTargetLot = targetType === 'lot_parent' || targetType === 'lot_member';
        const after = placement === 'after';
        clearDragDropIndicators(root);
        activeDropTarget = null;
        activeDropPlacement = null;

        if (sourceType === 'lot_parent') {
            if (oldLotId && oldLotId === targetLotId) return;
            let reference = row;
            if (isTargetLot) {
                const targetParent = root.querySelector(`tr[data-type="lot_parent"][data-lot-id="${targetLotId}"]`);
                const targetMembers = [...root.querySelectorAll(`tr[data-type="lot_member"][data-lot-id="${targetLotId}"]`)];
                reference = after ? (targetMembers[targetMembers.length - 1] || targetParent) : targetParent;
            }
            insertRows(reference, draggedRows, after);
        } else if (isTargetLot) {
            if (sourceType === 'standalone') setRowType(sourceRow, 'lot_member', targetLotId);
            else if (sourceType === 'lot_member' && oldLotId !== targetLotId) sourceRow.dataset.lotId = targetLotId;

            if (targetType === 'lot_parent') {
                const targetMembers = [...root.querySelectorAll(`tr[data-type="lot_member"][data-lot-id="${targetLotId}"]`)]
                    .filter(member => member !== sourceRow);
                const reference = after ? (targetMembers[targetMembers.length - 1] || row) : row;
                insertRows(reference, [sourceRow], true);
            } else {
                insertRows(row, [sourceRow], after);
            }
        } else {
            if (sourceType === 'lot_member') {
                setRowType(sourceRow, 'standalone');
                const targetParent = row.parentNode;
                if (targetParent) targetParent.insertBefore(sourceRow, after ? row.nextSibling : row);
            } else {
                insertRows(row, [sourceRow], after);
            }
        }

        if (oldLotId && oldLotId !== (sourceRow.dataset.lotId || null)) cleanUpEmptyLots(oldLotId);
        refreshAllLotSpans();
        refreshView();
    }

    function clearDragDropIndicators(root) {
        root?.querySelectorAll('.drag-over,.drag-drop-before,.drag-drop-after,.drag-drop-into-lot,.drag-drop-out-of-lot').forEach(row => {
            row.classList.remove('drag-over', 'drag-drop-before', 'drag-drop-after', 'drag-drop-into-lot', 'drag-drop-out-of-lot');
            delete row.dataset.dropPlacement;
            delete row.dataset.dropKind;
        });
    }

    // Serialize shadow DOM controls into the regular procurement save request.
    function addPayloadInput(container, name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value == null ? '' : String(value);
        container.appendChild(input);
    }

    // Serialize the PR rows even when another attachment is currently open or the canvas is collapsed.
    function serializePrItems(payload, prRoot) {
        const tbody = getTbody(prRoot);
        if (!tbody) return 0;
        const allRows = [...tbody.querySelectorAll('tr:not(.template-row):not(.add-actions-row)')]
            .filter(row => ['standalone', 'lot_parent', 'lot_member'].includes(row.dataset.type));
        const hasText = value => String(value ?? '').trim() !== '';
        const hasItemData = row => {
            const unit = rowValue(row, '[data-input="item.unit"]');
            const description = rowValue(row, '.description-editable');
            const quantity = String(rowValue(row, '.qty-input, .quantity-input') ?? '').replace(/,/g, '').trim();
            const cost = rowValue(row, '.cost-input');
            return hasText(unit) || hasText(description) || hasText(cost) || (hasText(quantity) && Number(quantity) !== 1);
        };
        let itemIndex = 0;
        for (const row of allRows) {
            if (row.dataset.type === 'lot_member' && allRows.some(parent => parent.dataset.type === 'lot_parent' && parent.dataset.lotId === row.dataset.lotId)) continue;
            if (row.dataset.type === 'lot_parent') {
                const lotId = row.dataset.lotId;
                const members = allRows.filter(member => member.dataset.type === 'lot_member' && member.dataset.lotId === lotId && hasItemData(member));
                const headerHasData = hasText(rowValue(row, '.cost-input'))
                    || (hasText(rowValue(row, '.qty-input, .quantity-input'))
                        && Number(String(rowValue(row, '.qty-input, .quantity-input')).replace(/,/g, '')) !== 1);
                if (!members.length && !headerHasData) continue;
                const prefix = `items[${itemIndex}]`;
                if (row.dataset.procurementItemId) addPayloadInput(payload, `${prefix}[id]`, row.dataset.procurementItemId);
                addPayloadInput(payload, `${prefix}[is_lot]`, '1');
                addPayloadInput(payload, `${prefix}[line_no]`, itemIndex + 1);
                addPayloadInput(payload, `${prefix}[description]`, rowValue(row, '.description-editable'));
                addPayloadInput(payload, `${prefix}[quantity]`, rowValue(row, '.qty-input, .quantity-input'));
                addPayloadInput(payload, `${prefix}[cost]`, rowValue(row, '.cost-input'));
                members.forEach((member, subIndex) => {
                    const sub = `${prefix}[sub_items][${subIndex}]`;
                    if (member.dataset.procurementItemId) addPayloadInput(payload, `${sub}[id]`, member.dataset.procurementItemId);
                    if (member.dataset.itemBankItemId) addPayloadInput(payload, `${sub}[item_bank_item_id]`, member.dataset.itemBankItemId);
                    addPayloadInput(payload, `${sub}[unit]`, rowValue(member, '[data-input="item.unit"]'));
                    addPayloadInput(payload, `${sub}[description]`, rowValue(member, '.description-editable'));
                    addPayloadInput(payload, `${sub}[quantity]`, rowValue(member, '.qty-input, .quantity-input'));
                    addPayloadInput(payload, `${sub}[cost]`, rowValue(member, '.cost-input'));
                });
                itemIndex++;
                continue;
            }
            if (!hasItemData(row)) continue;
            const prefix = `items[${itemIndex}]`;
            if (row.dataset.procurementItemId) addPayloadInput(payload, `${prefix}[id]`, row.dataset.procurementItemId);
            if (row.dataset.itemBankItemId) addPayloadInput(payload, `${prefix}[item_bank_item_id]`, row.dataset.itemBankItemId);
            addPayloadInput(payload, `${prefix}[line_no]`, itemIndex + 1);
            addPayloadInput(payload, `${prefix}[unit]`, rowValue(row, '[data-input="item.unit"]'));
            addPayloadInput(payload, `${prefix}[description]`, rowValue(row, '.description-editable'));
            addPayloadInput(payload, `${prefix}[quantity]`, rowValue(row, '.qty-input, .quantity-input'));
            addPayloadInput(payload, `${prefix}[cost]`, rowValue(row, '.cost-input'));
            itemIndex++;
        }
        return itemIndex;
    }

    function editableText(element) {
        const editor = element?.querySelector?.('textarea[data-pr-editor]');
        if (editor) return String(editor.value ?? '').replace(/\r\n?/g, '\n');
        if (element?.dataset.value !== undefined) return element.dataset.value;
        return String(element?.innerText ?? element?.textContent ?? '')
            .replace(/\u00a0/g, ' ')
            .replace(/\r\n?/g, '\n');
    }

    function syncEditableContent(element) {
        if (!element) return '';
        const value = editableText(element);
        if (!element.querySelector('textarea[data-pr-editor]')) element.dataset.value = value;
        element.dataset.boundValue = value;
        element.classList.toggle('has-content', value.trim().length > 0);
        const mirror = element.closest('td')?.querySelector('.editable-value');
        if (mirror) mirror.value = value;
        return value;
    }

    function serializePrScalarFields(root, fields) {
        root.querySelectorAll('[data-input^="procurement."]').forEach(field => {
            if (field.closest('[data-each="procurement.items"]') || field.closest('.template-row')) return;
            const path = field.dataset.input.slice('procurement.'.length);
            if (!path || path.includes('.')) return;
            let value;
            if (field.matches('[data-pr-field-container]')) value = prFieldControl(field)?.value ?? field.dataset.value ?? '';
            else if (field.matches('.editable-content,[contenteditable="true"]')) value = syncEditableContent(field);
            else if (field.matches('input[type="checkbox"]')) value = field.checked ? '1' : '0';
            else if (field.matches('input[type="radio"]')) {
                if (!field.checked) return;
                value = field.value;
            } else if (field.matches('input,select,textarea')) value = field.value;
            else value = field.dataset.boundValue ?? field.textContent;
            fields[path] = value == null ? '' : value;
        });
        const purposeField = root.querySelector('.purpose-editable[data-input="procurement.purpose"]');
        if (purposeField) {
            const purposeEditor = prFieldControl(purposeField);
            fields.purpose = purposeEditor?.value ?? purposeField.dataset.value ?? purposeField.dataset.boundValue ?? purposeField.textContent ?? '';
        }
    }

    function bindEditableFields(root = getCanvasRoot()) {
        if (!root) return;
        root.querySelectorAll('.editable-content').forEach(element => {
            if (element.closest('.template-row')) return;
            element.dataset.editableBound = '1';
            syncEditableContent(element);
        });
    }

    function rowValue(row, selector) {
        const field = row.querySelector(selector);
        if (!field) {
            if (selector === '[data-input="item.unit"]') return row.dataset.unit || '';
            if (selector === '.cost-input') return row.dataset.cost || '';
            if (selector === '.line-total') return row.dataset.lineTotal || '';
            return '';
        }
        if (field.matches('.editable-content,.description-editable,[contenteditable="true"]')) return syncEditableContent(field);
        const control = prFieldControl(field);
        const value = control?.matches?.('input,select,textarea')
            ? control.value
            : (field.dataset.boundValue ?? field.dataset.value ?? field.textContent);
        return field.matches('.cost-input') ? String(value ?? '').replace(/,/g, '') : String(value ?? '');
    }

    // Ignore untouched placeholder rows; a preset unit and default quantity do not count as an item.
    function hasMeaningfulItemData(row) {
        const description = rowValue(row, '.description-editable');
        const quantity = String(rowValue(row, '.qty-input, .quantity-input') ?? '').replace(/,/g, '').trim();
        const cost = rowValue(row, '.cost-input');
        return String(description ?? '').trim() !== '' || String(cost ?? '').trim() !== '' || (quantity !== '' && Number.isFinite(Number(quantity)) && Number(quantity) !== 1);
    }
    window.hasAtLeastOnePrItem = function() {
        const root = getCanvasRoot(), tbody = getTbody(root);
        if (!tbody) return false;
        return [...tbody.querySelectorAll('tr.item-row:not(.template-row):not(.add-actions-row)')]
            .some(hasMeaningfulItemData);
    };

    // Share validation rules across completeness indicators and PDF compilation.
    function requiredFieldInvalid(field) {
        const value = String(field.value ?? '');
        if (field.required && !value.trim()) return true;
        return field.type === 'number' && value.trim() !== '' && field.min !== '' && Number(value) < Number(field.min);
    }

    function routeValidation(root = getCanvasRoot(), tbody = getTbody()) {
        if (!root || !tbody) return { complete:false, message:'The purchase request template has not finished loading.' };
        const focusTarget = field => field?.closest?.('[data-canvas-editable-cell]') || field;
        const form = document.getElementById('pr-form');
        if (form?.dataset.hasPhases !== '1') return { complete:false, message:'This procurement mode has no configured workflow phases. Configure the workflow before starting routing.' };
        const purposeEditor = root.querySelector('.purpose-editable');
        if (!purposeEditor || !syncEditableContent(purposeEditor).trim()) return { complete:false, field:purposeEditor, message:'Enter the purchase request purpose.' };
        const invalidHeader = [...root.querySelectorAll('[data-required]')]
            .find(field => !field.closest('[data-each="procurement.items"]') && !field.closest('.template-row') && !prFieldValue(field).trim());
        if (invalidHeader) return { complete:false, field:invalidHeader, message:'Complete all required purchase request header fields.' };
        const rows = [...tbody.querySelectorAll('tr:not(.template-row):not(.add-actions-row)')]
            .filter(row => ['standalone','lot_parent','lot_member'].includes(row.dataset.type));
        const itemRows = rows.filter(row => row.classList.contains('item-row'));
        if (!itemRows.length) return { complete:false, message:'Add at least one item row to the Purchase Request.' };
        calculateTotals();
        if (!(parseMoney(getGrandTotalInput()?.textContent || 0) > 0)) return { complete:false, field:getGrandTotalInput(), message:'The calculated total amount must be greater than zero. Add an item with a quantity and unit cost.' };
        const lotParents = rows.filter(row => row.dataset.type === 'lot_parent');
        for (const parent of lotParents) {
            const members = rows.filter(member => member.dataset.type === 'lot_member' && member.dataset.lotId === parent.dataset.lotId);
            if (!members.length) return { complete:false, field:parent, message:'Every lot must have at least one item entered.' };
        }
        for (const row of rows) {
            // Lot members describe the contents of a package; the lot parent
            // carries the required quantity and unit cost.
            if (row.dataset.type === 'lot_member') {
                const description = row.querySelector('.description-editable');
                if (!description || !syncEditableContent(description).trim()) return { complete:false, field:focusTarget(description), message:'Enter a description for every lot member.' };
                continue;
            }
            const unit = row.querySelector('[data-input="item.unit"]');
            if (row.dataset.type === 'standalone' && !prFieldValue(unit).trim()) {
                return { complete:false, field:focusTarget(unit || row), message:'Enter a unit of measurement for every standalone item.' };
            }
            const description = row.querySelector('.description-editable');
            if (!description || !syncEditableContent(description).trim()) return { complete:false, field:focusTarget(description), message:'Enter a description for every item and lot.' };
            const qty = row.querySelector('.qty-input, .quantity-input');
            const quantity = prFieldValue(qty);
            if (!quantity.trim() || !Number.isFinite(Number(quantity)) || Number(quantity) < 1) return { complete:false, field:focusTarget(qty), message:'Quantity must be at least 1.00.' };
            const cost = row.querySelector('.cost-input');
            const costValue = cost ? prFieldValue(cost) : row.dataset.cost || '';
            if (!costValue.trim() || !Number.isFinite(parseMoney(costValue)) || parseMoney(costValue) < 1) return { complete:false, field:focusTarget(cost), message:'Unit cost must be at least 1.00.' };
        }
        const total = parseMoney(getGrandTotalInput()?.textContent || 0);
        const modeMin = Number(document.getElementById('pr-form')?.dataset.abcMin || 0);
        const maxRaw = document.getElementById('pr-form')?.dataset.abcMax;
        const modeMax = maxRaw === '' || maxRaw == null ? null : Number(maxRaw);
        if (modeMin > 0 && total < modeMin) return { complete:false, message:`Calculated ABC (₱ ${total.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}) is below the minimum of ₱ ${modeMin.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}.` };
        if (modeMax != null && Number.isFinite(modeMax) && total > modeMax) return { complete:false, message:`Calculated ABC (₱ ${total.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}) exceeds the maximum of ₱ ${modeMax.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}.` };
        return { complete:true };
    }

    // Single source of truth for "is this form 100% complete?" (all required fields + at least one item on the PR form).
    function evaluateFormCompleteness(root = getCanvasRoot(), host = getCanvasHost()) {
        if (!root || root === document || !host) return { complete:false, message:'The form has not finished loading.' };
        const tbody = getTbody(root);
        if (tbody && host.dataset.mainPr === '1') {
            const result = routeValidation(root, tbody);
            return { complete:!!result.complete, message:result.message || '', field:result.field || null, main:true };
        }
        const live = field => !field.closest('.template-row');
        const fail = (field, message = 'Complete all required fields before compiling.') => ({ complete:false, message, field });
        const required = [...root.querySelectorAll('input[required],select[required],textarea[required]')].filter(live);
        for (const field of required) {
            if (field.type === 'radio') {
                const group = [...root.querySelectorAll('input[type="radio"]')].filter(r => r.name === field.name);
                if (!group.some(r => r.checked)) return fail(field);
            } else if (field.type === 'checkbox') {
                if (!field.checked) return fail(field);
            } else if (requiredFieldInvalid(field)) return fail(field);
        }
        const editables = [...root.querySelectorAll('[contenteditable="true"][data-required="true"]')].filter(live);
        for (const editor of editables) if (!syncEditableContent(editor).trim()) return fail(editor);
        const hasEntered = [...root.querySelectorAll('input:not([type="hidden"]),textarea,select,[contenteditable="true"]')].some(field => String(field.value ?? field.textContent ?? '').trim() !== '');
        if (!(required.length > 0 || editables.length > 0 || hasEntered)) return fail(null, 'This form is empty.');
        return { complete:true, message:'' };
    }

    function publishCompleteness() {
        const restorePagination = temporarilyUnpaginatePr();
        const root = getCanvasRoot(), host = getCanvasHost();
        if (!root || root === document || !host?.dataset.currentAttachmentId) { restorePagination(); return null; }
        refreshCanvasCellOverlays(root);
        const result = evaluateFormCompleteness(root, host);
        if (result.main) window.dispatchEvent(new CustomEvent('pr-completeness-change', {detail:{complete:result.complete,message:result.message}}));
        window.prAttachmentCompleteness = window.prAttachmentCompleteness || {};
        window.prAttachmentCompleteness[String(host.dataset.currentAttachmentId)] = result.complete;
        window.dispatchEvent(new CustomEvent('pr-attachment-completion', {detail:{id:host.dataset.currentAttachmentId,complete:result.complete}}));
        restorePagination();
        return result;
    }

    // Fresh, on-demand check used right before compiling (never trusts cached state).
    window.checkPrFormCompleteness = function() {
        const result = publishCompleteness();
        if (!result) return { complete:false, message:'The form has not finished loading.' };
        return result;
    };

    // Bind form controls; procurement fields and item rows hydrate from their owning records.
    function hydrateAttachmentData(root) {
        root.querySelectorAll('.editable-content').forEach(syncEditableContent);
        calculateTotals();
    }

    function hydratePrTemplateSources(root, template) {
        let context = {};
        try { context = JSON.parse(template?.dataset.formContext || '{}'); } catch (error) { console.error('Invalid PR form binding context.', error); }
        configurePrFields(root);
        root.querySelectorAll('[data-pr-field-container]').forEach(container => {
            const definition = getPrFieldDefinition(container.dataset.input);
            const sourceName = definition?.source;
            if (!sourceName) return;
            const source = context.sources?.[sourceName] || [];
            const options = source.map(option => {
                const value = sourceName === 'offices' ? option.id : (sourceName === 'fund_clusters' ? option.name : (option.name ?? option.code ?? option.id));
                const label = sourceName === 'offices' ? option.name : (option.name ?? option.label ?? value);
                return {value: String(value ?? ''), label: String(label ?? value ?? '')};
            });
            // Always render the procurement's saved office label, even if it is no
            // longer in the active-office picklist (or the picklist is unavailable).
            if (sourceName === 'offices') {
                const savedOffice = context.procurement?.end_user_office;
                if (savedOffice?.id && savedOffice?.name && !options.some(option => option.value === String(savedOffice.id))) {
                    options.push({value: String(savedOffice.id), label: String(savedOffice.name)});
                }
            }
            container.dataset.options = JSON.stringify(options);
            const currentValue = container.dataset.value || container.dataset.boundValue;
            const selected = options.find(option => option.value === String(currentValue ?? ''));
            if (selected) container.dataset.displayValue = selected.label;
            if (currentValue != null) setPrFieldValue(container, currentValue);
        });
        root.querySelectorAll('[data-print]').forEach(element => {
            const value = genericValue(context, element.dataset.print);
            if (value !== undefined && value !== null) element.textContent = genericDisplayValue(element, value);
        });
        return context;
    }

    // Reapply procurement scalars to the final paginated DOM. The pagination pass
    // rebuilds table fragments, so hydrate these controls after that pass as well.
    function hydratePrScalarFields(root, template) {
        const context = hydratePrTemplateSources(root, template);
        let existing = {};
        try { existing = JSON.parse(document.getElementById('existing-pr-data')?.textContent || '{}'); } catch {}
        const fields = existing.fields || {};
        root.querySelectorAll('[data-input^="procurement."]').forEach(field => {
            if (field.closest('[data-each="procurement.items"]') || field.closest('.template-row') || field.matches('textarea.editable-value')) return;
            const fieldName = field.getAttribute('name') || field.dataset.input.slice('procurement.'.length);
            let value = Object.prototype.hasOwnProperty.call(fields, fieldName)
                ? fields[fieldName]
                : genericValue(context, field.dataset.input);
            if (value == null) return;
            if (field.matches('[data-pr-field-container]')) setPrFieldValue(field, value);
            else if (field.matches('.editable-content,[contenteditable="true"]')) setPrFieldValue(field, value);
            else if (field.matches('input[type="checkbox"]')) field.checked = value === true || value === 1 || value === '1' || value === 'true';
            else if (field.matches('input[type="radio"]')) field.checked = String(field.value) === String(value);
            else if (field.matches('input,select,textarea')) field.value = String(value);
            field.dataset.boundValue = String(value);
        });
        const purpose = root.querySelector('.purpose-editable');
        if (purpose && fields.purpose != null) setPrFieldValue(purpose, fields.purpose);
    }

    function hydrateExistingPrData(root) {
        const host = document.getElementById('form-canvas-root');
        if (!host || host.dataset.prHydrated === '1') return;
        host.dataset.prHydrated = '1';
        const template = document.getElementById(`attachment-form-template-${host.dataset.currentAttachmentId}`);
        const context = hydratePrTemplateSources(root, template);
        let data = {items:[],fields:{}};
        try { data = JSON.parse(document.getElementById('existing-pr-data')?.textContent || '{}'); } catch (error) { console.error('Unable to read existing PR data.', error); }
        const fields = data.fields || {};
        root.querySelectorAll('[data-input^="procurement."]').forEach(field => {
            if (field.closest('[data-each="procurement.items"]') || field.matches('textarea.editable-value')) return;
            const transportName = field.dataset.input.slice('procurement.'.length);
            let value = Object.prototype.hasOwnProperty.call(fields, transportName)
                ? fields[transportName]
                : genericValue(context, field.dataset.input);
            if (value == null) return;
            if (field.matches('[data-pr-field-container]')) setPrFieldValue(field, value);
            else if (field.matches('.editable-content,[contenteditable="true"]')) setPrFieldValue(field, value);
            else if (field.type === 'checkbox' || field.type === 'radio') field.checked = String(field.value) === String(value);
            else field.value = String(value);
            field.dataset.boundValue = String(value);
        });
        const purpose = root.querySelector('.purpose-editable');
        if (purpose && fields.purpose != null) setPrFieldValue(purpose, fields.purpose);
        const tbody = getTbody();
        if (!tbody) return;
        const insertRow = row => { const actions = tbody.querySelector('.add-actions-row:not(.template-row)'); actions ? tbody.insertBefore(row, actions) : tbody.appendChild(row); };
        const setRowValue = (row, selector, value) => {
            const matchedField = row.querySelector(selector);
            const field = matchedField?.closest?.('[data-pr-field-container]') || matchedField;
            if (!field || value == null) return;
            if (field.matches('.editable-content,[contenteditable="true"]')) {
                setPrFieldValue(field, value);
                syncEditableContent(field);
                return;
            }
            const control = prFieldControl(field);
            const formatted = field.matches('.cost-input,.qty-input,.quantity-input') ? formatDecimal(value) : String(value);
            if (field.matches('[data-pr-field-container]')) setPrFieldValue(field, formatted);
            else if (control?.matches('input,select,textarea')) control.value = formatted;
            else field.textContent = formatted;
            if (field.matches('.cost-input')) bindMoneyInput(control || field);
            if (field.matches('.qty-input,.quantity-input')) bindQuantityInput(control || field);
        };
        (Array.isArray(data.items) ? data.items : []).forEach(item => {
            if (item.is_lot) {
                const lotId = `lot_saved_${++rowIdx}`;
                const parent = getTemplate('lot_parent', lotId);
                if (item.id) parent.dataset.procurementItemId = String(item.id);
                setRowValue(parent, '.description-editable', item.description ?? '');
                setRowValue(parent, '.qty-input, .quantity-input', item.quantity ?? '');
                setRowValue(parent, '.cost-input', item.cost ?? '');
                insertRow(parent);
                const members = Array.isArray(item.sub_items) && item.sub_items.length ? item.sub_items : [{}];
                members.forEach(sub => {
                    const member = getTemplate('lot_member', lotId);
                    if (sub.id) member.dataset.procurementItemId = String(sub.id);
                    setRowValue(member, '[data-input="item.unit"]', sub.unit ?? '');
                    setRowValue(member, '.description-editable', sub.description ?? '');
                    setRowValue(member, '.qty-input, .quantity-input', sub.quantity ?? '');
                    setRowValue(member, '.cost-input', sub.cost ?? '');
                    if (sub.item_bank_item_id) member.dataset.itemBankItemId = String(sub.item_bank_item_id);
                    insertRow(member);
                });
                updateLotRowspan(lotId);
            } else {
                const row = getTemplate('standalone');
                if (item.id) row.dataset.procurementItemId = String(item.id);
                setRowValue(row, '[data-input="item.unit"]', item.unit ?? '');
                setRowValue(row, '.description-editable', item.description ?? '');
                setRowValue(row, '.qty-input, .quantity-input', item.quantity ?? '');
                setRowValue(row, '.cost-input', item.cost ?? '');
                if (item.item_bank_item_id) row.dataset.itemBankItemId = String(item.item_bank_item_id);
                insertRow(row);
            }
        });
    }

    let canvasResizeObserver = null;
    window.syncPrCanvasSize = function() {
        const host = document.getElementById('form-canvas-root');
        const shell = document.getElementById('canvas-zoom-shell');
        const workspace = document.getElementById('pr-workspace');
        if (!host || !shell) return;
        const scale = Math.max(0.35, Math.min(3, Number(workspace?.dataset.scale || 1)));
        if (host.dataset.mainPr === '1') {
            const key = String(host.dataset.currentAttachmentId || '');
            const state = genericFormStates.get(key);
            const template = document.getElementById(`attachment-form-template-${key}`);
            if (state && template && state.originalMain) {
                host.style.width = template.dataset.paperWidth || '210mm';
                host.style.minHeight = '0';
                host.style.padding = '0';
                const focused = host.shadowRoot?.activeElement;
                const editing = focused?.matches?.('input,textarea,select,[contenteditable="true"],.editable-content') || focused?.isContentEditable;
                if (!editing && (!host.shadowRoot?.querySelector('.generic-paper-page')
                    || state.originalMain.parentNode === host.shadowRoot)) {
                    genericPaginate(state, host, template);
                }
            }
        }
        host.style.transform = `scale(${scale})`;
        host.style.transformOrigin = 'top left';
        const width = host.offsetWidth;
        const height = host.offsetHeight;
        if (width) shell.style.width = `${width * scale}px`;
        if (height) shell.style.height = `${height * scale}px`;
        if (document.getElementById('pdf-canvas-pages')?.childElementCount) window.applyPrPdfScale(scale);
    };
    window.setPrCanvasZoom = function(value, fx, fy) {
        const viewport = document.getElementById('canvas-scroll-area');
        const shell = document.getElementById('canvas-zoom-shell');
        const workspace = document.getElementById('pr-workspace');
        const oldScale = Math.max(.35, Math.min(3, Number(workspace?.dataset.scale || 1)));
        const scale = Math.max(.35, Math.min(3, Number(value) || 1));
        if (!viewport || !shell || !workspace) return;
        const rect = shell.getBoundingClientRect();
        const x = fx ?? rect.left + rect.width / 2;
        const y = fy ?? rect.top + rect.height / 2;
        const anchor = {x:(x - rect.left) / oldScale, y:(y - rect.top) / oldScale};
        const data = window.Alpine?.$data?.(workspace);
        if (data) data.scale = scale; else workspace.dataset.scale = String(scale);
        window.syncPrCanvasSize();
        const nextRect = shell.getBoundingClientRect();
        viewport.scrollLeft += nextRect.left + anchor.x * scale - x;
        viewport.scrollTop += nextRect.top + anchor.y * scale - y;
    };
    window.bindPrCanvasZoom = function() {
        const scrollArea = document.getElementById('canvas-scroll-area');
        if (!scrollArea || scrollArea.dataset.zoomBound === '1') return;
        scrollArea.dataset.zoomBound = '1';
        let pinchDistance = 0, pinchScale = 1, zoomFrame = 0;
        const distance = touches => Math.hypot(touches[0].clientX - touches[1].clientX, touches[0].clientY - touches[1].clientY);
        scrollArea.addEventListener('touchstart', event => {
            if (event.touches.length === 2) {
                pinchDistance = distance(event.touches);
                pinchScale = Number(document.getElementById('pr-workspace')?.dataset.scale || 1);
            }
        }, {passive: true});
        scrollArea.addEventListener('touchmove', event => {
            if (event.touches.length !== 2 || !pinchDistance) return;
            if (event.cancelable) event.preventDefault();
            const touches = event.touches;
            const next = pinchScale * distance(touches) / pinchDistance;
            const x = (touches[0].clientX + touches[1].clientX) / 2;
            const y = (touches[0].clientY + touches[1].clientY) / 2;
            cancelAnimationFrame(zoomFrame);
            zoomFrame = requestAnimationFrame(() => window.setPrCanvasZoom(next, x, y));
        }, {passive: false});
        scrollArea.addEventListener('touchend', event => { if (event.touches.length < 2) pinchDistance = 0; }, {passive: true});
        scrollArea.addEventListener('touchcancel', () => { pinchDistance = 0; cancelAnimationFrame(zoomFrame); }, {passive: true});
        scrollArea.addEventListener('wheel', event => {
            if (!event.ctrlKey && !event.metaKey) return;
            event.preventDefault();
            const current = Number(document.getElementById('pr-workspace')?.dataset.scale || 1);
            window.setPrCanvasZoom(current * Math.exp(-event.deltaY * .01), event.clientX, event.clientY);
        }, {passive: false});
    };
    window.fitPrCanvasWidth = function() {
        const host = getCanvasHost(), viewport = document.getElementById('canvas-scroll-area');
        if (!host || !viewport || !host.offsetWidth) return;
        const padding = 48;
        const fit = Math.max(.35, Math.min(1.5, (viewport.clientWidth - padding) / host.offsetWidth));
        window.dispatchEvent(new CustomEvent('pr-set-scale', {detail:{scale:fit}}));
        requestAnimationFrame(() => window.syncPrCanvasSize?.());
    };
    window.fitPrPdfWidth = function() {
        const shell = document.getElementById('pdf-zoom-shell'), viewport = document.getElementById('pdf-scroll-area');
        if (!shell || !viewport) return;
        window.setPrPdfZoom(Math.max(.35, Math.min(1.5, (viewport.clientWidth - 48) / 794)));
        window.centerPrPdf();
    };

    // Initialize amount formatting, input events, zoom behavior, and scrolling.
    window.initPrCanvas = function() {
        const root = getCanvasRoot();
        const host = document.getElementById('form-canvas-root');
        const scrollArea = document.getElementById('canvas-scroll-area');
        if (!root || root === document || !host || !activeCanvasAttachmentId) return;
        const hydrateInitialPrData = host.dataset.needsHydrate === '1';
        if (hydrateInitialPrData) { hydrateExistingPrData(root); host.dataset.needsHydrate = '0'; host.dataset.prHydrated = '1'; }
        if (host.dataset.mainPr === '1') {
            const tbody = getTbody(root);
            const itemRows = tbody ? [...tbody.querySelectorAll('tr.item-row:not(.template-row):not(.add-actions-row)')] : [];
            if (tbody && itemRows.length === 0) {
                const row = getTemplate('standalone');
                const actions = tbody.querySelector('.add-actions-row:not(.template-row)');
                actions ? tbody.insertBefore(row, actions) : tbody.appendChild(row);
            }
        }
        if (host.dataset.attachmentDataHydrated !== '1') { hydrateAttachmentData(root); host.dataset.attachmentDataHydrated = '1'; }
        if (host.dataset.mainPr === '1') {
            const key = String(host.dataset.currentAttachmentId || '');
            const state = genericFormStates.get(key);
            const template = document.getElementById(`attachment-form-template-${key}`);
            if (hydrateInitialPrData && template) hydratePrScalarFields(root, template);
            if (state?.originalMain && template && !root.querySelector('.generic-paper-page')) {
                normalizePrTableForPagination(root);
                genericPaginate(state, host, template);
            }
        }
        // Bind cell-owned editors after pagination, which rebuilds table fragments.
        bindEditableFields(root);
        bindPrFieldContainers(root);
        if (!canvasRootEventsBound) {
            root.addEventListener('input', () => { calculateTotals(); publishCompleteness(); window.dispatchEvent(new CustomEvent('pr-form-changed',{detail:{id:activeCanvasAttachmentId}})); requestAnimationFrame(() => window.syncPrCanvasSize?.()); }, true);
            root.addEventListener('change', () => { calculateTotals(); publishCompleteness(); window.dispatchEvent(new CustomEvent('pr-form-changed',{detail:{id:activeCanvasAttachmentId}})); }, true);
            canvasRootEventsBound = true;
        }
        root.querySelectorAll('.qty-input, .quantity-input').forEach(input => {
            bindQuantityInput(input);
            if (input.dataset.totalBound !== '1') { input.dataset.totalBound='1'; input.addEventListener('input', calculateTotals); }
        });
        root.querySelectorAll('.qty-input, .quantity-input').forEach(field => {
            const value = prFieldValue(field);
            if (value.trim() !== '') setPrFieldValue(field, formatDecimal(value));
        });
        root.querySelectorAll('.cost-input').forEach(bindMoneyInput);
        root.querySelectorAll('.cost-input').forEach(field => {
            const value = prFieldValue(field);
            if (value.trim() !== '') setPrFieldValue(field, formatDecimal(value));
        });
        root.querySelectorAll('.line-total').forEach(output => { output.textContent = formatMoney(output.textContent); });
        const grandTotal = getGrandTotalInput();
        if (grandTotal) grandTotal.textContent = formatMoney(grandTotal.textContent);
        if (scrollArea) window.bindPrCanvasZoom();
        if (typeof ResizeObserver !== 'undefined') {
            canvasResizeObserver?.disconnect();
            canvasResizeObserver = new ResizeObserver(() => requestAnimationFrame(() => window.syncPrCanvasSize?.()));
            canvasResizeObserver.observe(host);
        }
        refreshView();
        requestAnimationFrame(() => { window.syncPrCanvasSize?.(); publishCompleteness(); });
    };

    // Capture current field values for autosave and PDF compilation.
    window.getActivePrAttachmentSnapshot = function() {
        const restorePagination = temporarilyUnpaginatePr();
        const root = getCanvasRoot(), id = String(activeCanvasAttachmentId || ''), host = getCanvasHost();
        if (!id || !root || root === document || !host?.dataset.currentAttachmentId) { restorePagination(); return null; }
        const fields = {};
        root.querySelectorAll('[name]').forEach(field => {
            if (field.disabled || field.closest('.template-row') || field.closest('[data-each="procurement.items"] tr.template-row')) return;
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) return;
            const value = field.matches('.editable-content,[contenteditable="true"]') ? syncEditableContent(field) : (field.matches('.cost-input') ? String(field.value ?? '').replace(/,/g, '') : field.value);
            if (Object.prototype.hasOwnProperty.call(fields, field.name)) fields[field.name] = Array.isArray(fields[field.name]) ? [...fields[field.name], value] : [fields[field.name], value];
            else fields[field.name] = value;
        });
        serializePrScalarFields(root, fields);
        // Use the same nested item serialization as the regular procurement save,
        // including stable IDs and each lot member's unit of measurement.
        Object.keys(fields).filter(key => /^items\[\d+\]/.test(key)).forEach(key => delete fields[key]);
        const itemPayload = document.createElement('div');
        serializePrItems(itemPayload, root);
        itemPayload.querySelectorAll('input[name]').forEach(input => { fields[input.name] = input.value; });
        const officeField = root.querySelector('[data-input="procurement.end_user_office_id"]');
        if (officeField) fields.end_user_office_id = String(prFieldControl(officeField)?.value ?? officeField.dataset.value ?? '');
        const snapshot = {id, fields, complete: !!(window.prAttachmentCompleteness || {})[id]};
        restorePagination();
        return snapshot;
    };

    // Shadow DOM controls are serialized into ordinary hidden inputs before submission.
    window.preparePrSubmission = function(event, action = 'draft') {
        const form = document.getElementById('pr-form');
        const host = getCanvasHost();
        const isDraft = action === 'draft';
        const restorePagination = temporarilyUnpaginatePr(true);
        const root = getCanvasRoot();
        const tbody = getTbody(root);
        try {
        if (!form) { event?.preventDefault(); return false; }
        const payloadGet = () => { let node = form.querySelector('#pr-item-payload'); if (!node) { node = document.createElement('div'); node.id = 'pr-item-payload'; node.hidden = true; form.appendChild(node); } return node; };
        const payload = payloadGet();
        payload.replaceChildren();
        const formData = {};
        const addFormSnapshot = (id, formRoot) => {
            if (!formRoot) return;
            const fields = {};
            formRoot.querySelectorAll('[name]').forEach(field => {
                if (field.disabled || field.closest('[data-each="procurement.items"]') && ['INPUT','SELECT','TEXTAREA'].includes(field.tagName) && field.closest('tr.template-row')) return;
                if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) return;
                const value = field.matches('.editable-content,[contenteditable="true"]') ? syncEditableContent(field) : (field.matches('.cost-input') ? String(field.value ?? '').replace(/,/g, '') : field.value);
                if (Object.prototype.hasOwnProperty.call(fields, field.name)) fields[field.name] = Array.isArray(fields[field.name]) ? [...fields[field.name], value] : [fields[field.name], value];
                else fields[field.name] = value;
            });
            serializePrScalarFields(formRoot, fields);
            if (formRoot.querySelector('[data-each="procurement.items"]')) {
                const itemPayload = document.createElement('div');
                serializePrItems(itemPayload, formRoot);
                itemPayload.querySelectorAll('input[name]').forEach(input => { fields[input.name] = input.value; });
            }
            formData[id] = fields;
        };
        if (activeCanvasAttachmentId && root && root !== document) addFormSnapshot(activeCanvasAttachmentId, root);
        attachmentFormCache.forEach((fragment, id) => addFormSnapshot(id, fragment));
        const attachmentDataInput = document.getElementById('attachment-fields');
        if (attachmentDataInput) attachmentDataInput.value = JSON.stringify(formData);
        const mainPrRoot = getPrimaryPrRoot();
        const itemCount = mainPrRoot ? serializePrItems(payload, mainPrRoot) : 0;
        if ((!root || root === document || !host?.dataset.currentAttachmentId || !tbody) && isDraft) return true;
        if (!root || root === document || !host?.dataset.currentAttachmentId || !tbody) {
            event?.preventDefault();
            window.alert(isDraft ? 'Unable to capture the selected attachment form.' : 'Open the main Purchase Request form attachment and complete its required fields before routing.');
            return false;
        }
        if (!isDraft && host.dataset.mainPr !== '1') {
            event?.preventDefault();
            window.alert('Open and complete the main Purchase Request form before starting routing.');
            return false;
        }
        const saveAction = form.querySelector('#save-action');
        if (saveAction) saveAction.value = isDraft ? 'draft' : 'route';
        if (!isDraft) {
            const result = routeValidation(root, tbody);
            if (!result.complete) {
                event?.preventDefault();
                result.field?.focus?.();
                window.alert(result.message || 'Complete all required fields before starting routing.');
                publishCompleteness();
                return false;
            }
        }

        if (!tbody) {
            root.querySelectorAll('[name]').forEach(field => {
                if (field.disabled || field.closest('.template-row')) return;
                if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) return;
                const value = field.matches('.editable-content,[contenteditable="true"]') ? syncEditableContent(field) : (field.matches('.cost-input') ? String(field.value ?? '').replace(/,/g, '') : field.value);
                addPayloadInput(payload, `attachment_forms[${host.dataset.currentAttachmentId}][${field.name}]`, value ?? '');
            });
            return true;
        }
        bindEditableFields(root);
        root.querySelectorAll('.editable-content').forEach(syncEditableContent);

        // Preserve existing non-item field names from the template.
        root.querySelectorAll('[name]').forEach(field => {
            if (field.closest('[data-each="procurement.items"]') || field.disabled) return;
            if ((field.type === 'checkbox' || field.type === 'radio') && !field.checked) return;
            if (field.tagName === 'SELECT' && field.multiple) [...field.selectedOptions].forEach(option => addPayloadInput(payload, field.name, option.value));
            else addPayloadInput(payload, field.name, field.value);
        });

        // Draft intentionally bypasses requirose-field checks and may contain no item rows.
        if (!isDraft && itemCount === 0) {
            event?.preventDefault();
            window.alert('Add at least one item or lot before starting routing.');
            return false;
        }
        return true;
        } finally {
            restorePagination();
        }
    };

    const initializeEditableFields = () => bindEditableFields(getCanvasRoot());
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeEditableFields, { once: true });
    else initializeEditableFields();

    // Global listener to dismiss context menus on outside click
    document.addEventListener('click', () => {
        getCanvasRoot().querySelectorAll('.context-menu').forEach(menu => { menu.style.display = 'none'; });
    });
    window.addEventListener('pr-attachment-signed', event => {
        if (event.detail?.attachment_id === 'pr' && event.detail?.all_signed) window.setTimeout(() => window.location.reload(), 700);
    });
})();
</script>
@endpush

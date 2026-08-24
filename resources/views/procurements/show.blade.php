@extends('layouts.app')

@section('title', $procurement->tracking_number)

@section('page-header')

@php
    /*
    |--------------------------------------------------------------------------
    | DOCUMENT PROGRESS
    |--------------------------------------------------------------------------
    */

    /*
     * Normalize names so these are treated similarly:
     *
     * Purchase Request
     * Purchase_Request
     * Purchase-Request.pdf
     * Purchase Request - Signed.pdf
     */
    $normalizeDocumentName = function ($value) {
        $value = pathinfo((string) $value, PATHINFO_FILENAME);

        $value = preg_replace('/[_-]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return strtolower(trim($value));
    };


    /*
    |--------------------------------------------------------------------------
    | Check if an uploaded document satisfies a requirement
    |--------------------------------------------------------------------------
    */

    $documentMatchesRequirement = function ($requiredDocument, $uploadedDocument) use ($normalizeDocumentName) {

        $requiredName = $normalizeDocumentName(
            $requiredDocument->document_type_name
        );

        $uploadedName = $normalizeDocumentName(
            $uploadedDocument->file_name
        );

        if (!$requiredName || !$uploadedName) {
            return false;
        }

        /*
         * Exact match
         */
        if ($uploadedName === $requiredName) {
            return true;
        }

        /*
         * Allow filenames with additional descriptive text.
         *
         * Example:
         *
         * Required:
         * Purchase Request
         *
         * Uploaded:
         * Purchase Request Signed
         * Purchase Request Final
         * Purchase Request 2026
         */
        return str_contains(
            $uploadedName,
            $requiredName
        );
    };


    /*
    |--------------------------------------------------------------------------
    | Current Phase Progress
    |--------------------------------------------------------------------------
    */

    $currentPhase = $procurement->currentPhase;

    if ($currentPhase) {

        /*
         * Mandatory documents required by the current phase.
         */
        $phaseRequiredDocs = $currentPhase->documents
            ->filter(fn ($document) => $document->is_mandatory)
            ->values();

        $phaseRequiredCount = $phaseRequiredDocs->count();


        /*
         * Only documents uploaded for the current phase.
         */
        $phaseUploadedDocs = $procurement->documents
            ->where('phase_id', $currentPhase->id)
            ->values();


        /*
         * Count each required document only once.
         */
        $phaseUploadedCount = $phaseRequiredDocs
            ->filter(function ($requiredDocument) use (
                $phaseUploadedDocs,
                $documentMatchesRequirement
            ) {
                return $phaseUploadedDocs->contains(
                    fn ($uploadedDocument) =>
                        $documentMatchesRequirement(
                            $requiredDocument,
                            $uploadedDocument
                        )
                );
            })
            ->count();


        /*
         * If there are no mandatory documents for this phase,
         * consider the phase complete.
         */
        $phaseProgress = $phaseRequiredCount > 0
            ? min(
                100,
                (int) round(
                    ($phaseUploadedCount / $phaseRequiredCount) * 100
                )
            )
            : 100;

    } else {

        $phaseRequiredCount = 0;
        $phaseUploadedCount = 0;
        $phaseProgress = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | General / Overall Progress
    |--------------------------------------------------------------------------
    */

    $allPhases = $procurement->mode?->phases ?? collect();

    $allRequiredCount = 0;
    $allUploadedCount = 0;


    foreach ($allPhases as $phase) {

        /*
         * Mandatory requirements for this phase.
         */
        $mandatoryDocs = $phase->documents
            ->filter(fn ($document) => $document->is_mandatory)
            ->values();

        $allRequiredCount += $mandatoryDocs->count();


        /*
         * Only uploaded documents belonging to this phase.
         */
        $phaseUploadedDocs = $procurement->documents
            ->where('phase_id', $phase->id)
            ->values();


        /*
         * Check every requirement independently.
         */
        foreach ($mandatoryDocs as $requiredDocument) {

            $hasUploaded = $phaseUploadedDocs->contains(
                fn ($uploadedDocument) =>
                    $documentMatchesRequirement(
                        $requiredDocument,
                        $uploadedDocument
                    )
            );

            if ($hasUploaded) {
                $allUploadedCount++;
            }
        }
    }


    /*
     * If there are no mandatory documents at all,
     * consider the procurement complete from a document
     * perspective.
     */
    $generalProgress = $allRequiredCount > 0
        ? min(
            100,
            (int) round(
                ($allUploadedCount / $allRequiredCount) * 100
            )
        )
        : 100;


    /*
    |--------------------------------------------------------------------------
    | Dynamic Indicator Colors
    |--------------------------------------------------------------------------
    */

    $getProgressColor = function ($percentage) {

        if ($percentage < 25) {
            return [
                'stroke' => '#ef4444',
                'text'   => 'text-rose-600 dark:text-rose-400',
            ];
        }

        if ($percentage < 50) {
            return [
                'stroke' => '#f97316',
                'text'   => 'text-orange-600 dark:text-orange-400',
            ];
        }

        if ($percentage < 75) {
            return [
                'stroke' => '#eab308',
                'text'   => 'text-amber-600 dark:text-amber-400',
            ];
        }

        return [
            'stroke' => '#10b981',
            'text'   => 'text-emerald-600 dark:text-emerald-400',
        ];
    };


    $generalColor = $getProgressColor($generalProgress);
    $phaseColor   = $getProgressColor($phaseProgress);

    /*
    |--------------------------------------------------------------------------
    | Navigation Data
    |--------------------------------------------------------------------------
    */

    $priorPhasesJson = $currentPhase
        ? $procurement->mode->phases
            ->where(
                'phase_sequence',
                '<',
                $currentPhase->phase_sequence
            )
            ->sortBy('phase_sequence')
            ->values()
            ->toJson()
        : '[]';

    $nextPhase = $procurement->getNextPhase();
@endphp

<div class="w-full mb-6">

    {{-- ================================================================
         BACK
         ================================================================ --}}
    <a href="{{ route('procurements.index') }}"
       class="group inline-flex items-center gap-2 mb-4
              text-xs font-semibold
              text-slate-500 dark:text-slate-400
              hover:text-indigo-600 dark:hover:text-indigo-400
              transition-colors">
        <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Directory
    </a>

    <div class="grid grid-cols-2 w-full">
        {{-- IDENTITY (Takes Flex Priority) --}}
        <div class="flex-1">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="font-mono text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    {{ $procurement->tracking_number }}
                </span>
                <span class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg border border-indigo-100 dark:border-indigo-900/60 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 text-[9px] font-black uppercase tracking-widest shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ $procurement->status }}
                </span>
            </div>

            <h1 class="mt-2 text-base sm:text-lg lg:text-xl font-bold leading-snug text-slate-800 dark:text-slate-100 break-words">
                {{ $procurement->title }}
            </h1>
        </div>
        <div x-data="dynamicActionBar()"
            x-ref="headerContainer"
            class="flex flex-col lg:flex-row items-end gap-4 lg:gap-6 w-full">
            {{-- ============================================================
                DYNAMIC RESPONSIVE PROCUREMENT ACTIONS BAR
                ============================================================ --}}
            <div x-ref="wrapper"
                class="flex justify-end gap-1.5 w-full min-w-0 whitespace-nowrap shrink-0">

                {{-- 1. PRINT --}}
                <a href="{{ route('procurements.print-routing-slip', $procurement->id) }}"
                target="_blank"
                title="Print procurement routing slip"
                data-action-priority="1"
                x-show="!isHidden(1)"
                class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg bg-slate-700 dark:bg-slate-700 hover:bg-slate-800 dark:hover:bg-slate-600 text-white text-xs font-semibold shadow-xs hover:shadow-md cursor-pointer transition-all duration-150 active:scale-95 shrink-0">
                    <i class="fa-solid fa-print text-[11px]"></i>
                    <span>Print Slip</span>
                </a>

                {{-- 2. EDIT --}}
                @can('update', $procurement)
                    <a href="{{ route('procurements.edit', $procurement->id) }}"
                    title="Edit procurement request"
                    data-action-priority="2"
                    x-show="!isHidden(2)"
                    class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg bg-blue-600 dark:bg-blue-600 hover:bg-blue-700 dark:hover:bg-blue-500 text-white text-xs font-semibold shadow-xs hover:shadow-md cursor-pointer transition-all duration-150 active:scale-95 shrink-0">
                        <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                        <span>Edit</span>
                    </a>
                @endcan

                {{-- 3. CANCEL --}}
                @can('cancel', $procurement)
                    <button type="button"
                            title="Cancel this procurement request"
                            data-action-priority="3"
                            x-show="!isHidden(3)"
                            onclick="openCancelModal('{{ route('procurements.cancel', $procurement->id) }}', '{{ $procurement->tracking_number }}')"
                            class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg bg-amber-600 dark:bg-amber-600 hover:bg-amber-700 dark:hover:bg-amber-500 text-white text-xs font-semibold shadow-xs hover:shadow-md cursor-pointer transition-all duration-150 active:scale-95 shrink-0">
                        <i class="fa-solid fa-ban text-[11px]"></i>
                        <span>Cancel</span>
                    </button>
                @endcan

                {{-- 4. RETURN --}}
                @can('return', $procurement)
                    <button type="button"
                            title="Return procurement to a previous phase"
                            data-action-priority="4"
                            x-show="!isHidden(4)"
                            onclick="openReturnModal('{{ route('procurements.return', $procurement->id) }}', '{{ $procurement->tracking_number }}', {{ $priorPhasesJson }})"
                            class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg bg-rose-500 dark:bg-rose-500 hover:bg-rose-600 dark:hover:bg-rose-400 text-white text-xs font-semibold shadow-xs hover:shadow-md cursor-pointer transition-all duration-150 active:scale-95 shrink-0">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Return</span>
                    </button>
                @endcan

                {{-- 5. REJECT --}}
                @can('reject', $procurement)
                    <button type="button"
                            title="Reject and terminate this procurement"
                            data-action-priority="5"
                            x-show="!isHidden(5)"
                            onclick="openRejectModal('{{ route('procurements.reject', $procurement->id) }}', '{{ $procurement->tracking_number }}')"
                            class="inline-flex items-center justify-center gap-2 h-9 px-3.5 rounded-lg bg-rose-700 dark:bg-rose-700 hover:bg-rose-800 dark:hover:bg-rose-600 text-white text-xs font-semibold shadow-xs hover:shadow-md cursor-pointer transition-all duration-150 active:scale-95 shrink-0">
                        <i class="fa-solid fa-circle-xmark text-[11px]"></i>
                        <span>Reject</span>
                    </button>
                @endcan

                @if($phaseProgress == 100)

                    {{-- 6. ROUTE --}}
                    @can('route', $procurement)
                        <button type="button"
                                title="Route procurement to the next phase step"
                                data-action-priority="6"
                                x-show="!isHidden(6)"
                                onclick="openRouteModal('{{ route('procurements.route', $procurement->id) }}', '{{ $procurement->tracking_number }}', '{{ $procurement->getNextPhase()?->phase_name }}')"
                                class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg bg-indigo-600 dark:bg-indigo-600 hover:bg-indigo-700 dark:hover:bg-indigo-500 text-white text-xs font-bold shadow-xs hover:shadow-md cursor-pointer transition-all duration-150 active:scale-95 shrink-0">
                            <i class="fa-solid fa-paper-plane text-[11px]"></i>
                            <span>Route to Next Phase</span>
                        </button>
                    @endcan

                    {{-- 7. COMPLETE --}}
                    @can('complete', $procurement)
                        <button type="button"
                                title="Approve and mark procurement as complete"
                                data-action-priority="7"
                                x-show="!isHidden(7)"
                                onclick="openApproveModal('{{ route('procurements.complete', $procurement->id) }}', '{{ $procurement->tracking_number }}')"
                                class="inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg bg-emerald-600 dark:bg-emerald-600 hover:bg-emerald-700 dark:hover:bg-emerald-500 text-white text-xs font-bold shadow-xs hover:shadow-md cursor-pointer transition-all duration-150 active:scale-95 shrink-0">
                            <i class="fa-solid fa-circle-check text-[11px]"></i>
                            <span>Complete Procurement</span>
                        </button>
                    @endcan

                @endif

                {{-- 3-DOTS OVERFLOW MENU --}}
                <div class="relative shrink-0"
                    x-show="hiddenPriorities.length > 0"
                    x-cloak
                    @click.outside="open = false">

                    <button type="button"
                            @click="open = !open"
                            :aria-expanded="open"
                            aria-label="More actions"
                            title="More actions"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-lg bg-slate-700 dark:bg-slate-800 text-white hover:bg-slate-800 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500/40 cursor-pointer active:scale-95 transition-all duration-150 shadow-xs">
                        <i class="fa-solid fa-ellipsis-vertical text-sm"></i>
                    </button>

                    <div x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl shadow-slate-900/10 dark:shadow-black/30 ring-1 ring-black/5 dark:ring-white/5">

                        <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-700">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                More Actions
                            </p>
                        </div>

                        <div class="p-1.5 space-y-0.5">

                            <a href="{{ route('procurements.print-routing-slip', $procurement->id) }}"
                            target="_blank"
                            x-show="isHidden(1)"
                            class="flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white cursor-pointer transition-colors duration-150">
                                <i class="fa-solid fa-print w-4 text-center text-slate-400 dark:text-slate-500"></i>
                                <span class="text-xs font-semibold">Print Routing Slip</span>
                            </a>

                            @can('update', $procurement)
                                <a href="{{ route('procurements.edit', $procurement->id) }}"
                                x-show="isHidden(2)"
                                class="flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-lg text-blue-700 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-950/40 cursor-pointer transition-colors duration-150">
                                    <i class="fa-solid fa-pen-to-square w-4 text-center"></i>
                                    <span class="text-xs font-semibold">Edit Procurement</span>
                                </a>
                            @endcan

                            @can('cancel', $procurement)
                                <button type="button"
                                        x-show="isHidden(3)"
                                        @click="open = false"
                                        onclick="openCancelModal('{{ route('procurements.cancel', $procurement->id) }}', '{{ $procurement->tracking_number }}')"
                                        class="flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-lg text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/40 cursor-pointer transition-colors duration-150">
                                    <i class="fa-solid fa-ban w-4 text-center"></i>
                                    <span class="text-xs font-semibold">Cancel Procurement</span>
                                </button>
                            @endcan

                            @can('return', $procurement)
                                <button type="button"
                                        x-show="isHidden(4)"
                                        @click="open = false"
                                        onclick="openReturnModal('{{ route('procurements.return', $procurement->id) }}', '{{ $procurement->tracking_number }}', {{ $priorPhasesJson }})"
                                        class="flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-lg text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer transition-colors duration-150">
                                    <i class="fa-solid fa-rotate-left w-4 text-center"></i>
                                    <span class="text-xs font-semibold">Return to Previous Phase</span>
                                </button>
                            @endcan

                            @can('reject', $procurement)
                                <button type="button"
                                        x-show="isHidden(5)"
                                        @click="open = false"
                                        onclick="openRejectModal('{{ route('procurements.reject', $procurement->id) }}', '{{ $procurement->tracking_number }}')"
                                        class="flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer transition-colors duration-150">
                                    <i class="fa-solid fa-circle-xmark w-4 text-center"></i>
                                    <span class="text-xs font-semibold">Reject Procurement</span>
                                </button>
                            @endcan

                            @if($phaseProgress == 100)

                                @can('route', $procurement)
                                    <button type="button"
                                            x-show="isHidden(6)"
                                            @click="open = false"
                                            onclick="openRouteModal('{{ route('procurements.route', $procurement->id) }}', '{{ $procurement->tracking_number }}', '{{ $procurement->getNextPhase()?->phase_name }}')"
                                            class="flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-lg text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 cursor-pointer transition-colors duration-150">
                                        <i class="fa-solid fa-paper-plane w-4 text-center"></i>
                                        <span class="text-xs font-bold">Route to Next Phase</span>
                                    </button>
                                @endcan

                                @can('complete', $procurement)
                                    <button type="button"
                                            x-show="isHidden(7)"
                                            @click="open = false"
                                            onclick="openApproveModal('{{ route('procurements.complete', $procurement->id) }}', '{{ $procurement->tracking_number }}')"
                                            class="flex items-center gap-3 w-full text-left px-3 py-2.5 rounded-lg text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer transition-colors duration-150">
                                        <i class="fa-solid fa-circle-check w-4 text-center"></i>
                                        <span class="text-xs font-bold">Complete Procurement</span>
                                    </button>
                                @endcan

                            @endif

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Metadata --}}
    <div class="flex
                items-center
                justify-center
                gap-x-4
                gap-y-2
                mt-4">
        {{-- Office --}}
        <div class="inline-flex
                    items-center
                    gap-1
                    min-w-0
                    text-xs
                    font-medium
                    text-slate-500
                    dark:text-slate-400">
            <span class="flex shrink-0
                            items-center
                            justify-center
                            w-7 h-7
                            rounded-lg
                            bg-slate-100
                            dark:bg-slate-800
                            text-slate-400">

                <i class="fa-solid fa-building text-[10px]"></i>
            </span>
            <span class="truncate max-w-[220px]">
                {{ $procurement->endUserOffice->name }}
            </span>
        </div>

        {{-- Mode --}}
        <div class="inline-flex
                    items-center
                    gap-1
                    min-w-0
                    text-xs
                    font-medium
                    text-slate-500
                    dark:text-slate-400">

            <span class="flex shrink-0
                            items-center
                            justify-center
                            w-7 h-7
                            rounded-lg
                            bg-slate-100
                            dark:bg-slate-800
                            text-slate-400">

                <i class="fa-solid fa-gavel text-[10px]"></i>

            </span>

            <span class="truncate max-w-[220px]">
                {{ $procurement->mode->name }}
            </span>

        </div>

        {{-- ABC --}}
        <div class="inline-flex
                    items-center
                    gap-1
                    text-xs
                    font-semibold
                    text-slate-700
                    dark:text-slate-200">

            <span class="flex shrink-0
                            items-center
                            justify-center
                            w-7 h-7
                            rounded-lg
                            bg-emerald-50
                            dark:bg-emerald-950/40
                            text-emerald-600
                            dark:text-emerald-400">

                <i class="fa-solid fa-peso-sign text-[10px]"></i>

            </span>

            <span class="font-mono">
                ₱{{ number_format($procurement->abc_amount, 2) }}
            </span>

        </div>

    </div>

    {{-- =========================================================
        PROGRESS ROW
        ========================================================= --}}
    <div class="mt-6 pt-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            {{-- Overall Progress Card --}}
            <div class="flex items-center gap-4 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                <div class="relative w-14 h-14 shrink-0">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3" class="stroke-slate-200 dark:stroke-slate-700" />
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="{{ $generalColor['stroke'] }}" stroke-width="3" stroke-linecap="round" stroke-dasharray="{{ $generalProgress }}, 100" class="transition-all duration-700 ease-out" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-[10px] font-black {{ $generalColor['text'] }}">
                            {{ $generalProgress }}%
                        </span>
                    </div>
                </div>

                <div class="min-w-0">
                    <div class="text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">
                        Overall Completion
                    </div>
                    <div class="mt-1 text-sm font-bold text-slate-800 dark:text-slate-200">
                        {{ $allUploadedCount }}
                        <span class="font-normal text-slate-400">
                            / {{ $allRequiredCount }}
                        </span>
                    </div>
                    <div class="text-[10px] text-slate-400 dark:text-slate-500">
                        Required documents completed
                    </div>
                </div>
            </div>

            {{-- Current Phase Progress Card --}}
            <div class="flex items-center gap-4 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50">
                <div class="relative w-14 h-14 shrink-0">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke-width="3" class="stroke-slate-200 dark:stroke-slate-700" />
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="{{ $phaseColor['stroke'] }}" stroke-width="3" stroke-linecap="round" stroke-dasharray="{{ $phaseProgress }}, 100" class="transition-all duration-700 ease-out" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-[10px] font-black {{ $phaseColor['text'] }}">
                            {{ $phaseProgress }}%
                        </span>
                    </div>
                </div>

                <div class="min-w-0">
                    <div class="text-[9px] font-black uppercase tracking-[0.15em] text-slate-400">
                        Current Phase
                    </div>
                    <div class="mt-1 text-sm font-bold truncate text-slate-800 dark:text-slate-200" title="{{ $currentPhase?->phase_name }}">
                        {{ $currentPhase?->phase_name ?? 'No active phase' }}
                    </div>
                    <div class="text-[10px] text-slate-400 dark:text-slate-500">
                        {{ $phaseUploadedCount }} / {{ $phaseRequiredCount }} required documents
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">

    {{-- ============================================================
        LEFT COLUMN
        ============================================================ --}}
    <div class="lg:col-span-2 min-w-0 lg:max-h-[calc(100vh-220px)] lg:overflow-y-auto lg:pr-2 space-y-6">

        {{-- Line Items --}}
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">

            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                Line Items Breakdown
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">

                    <thead>
                        <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">

                            <th class="py-2 px-2">
                                Description
                            </th>

                            <th class="py-2 px-2 text-center">
                                Qty
                            </th>

                            <th class="py-2 px-2 text-center">
                                Unit
                            </th>

                            <th class="py-2 px-2 text-right">
                                Unit Price
                            </th>

                            <th class="py-2 px-2 text-right">
                                Total
                            </th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">

                        @foreach($procurement->items as $item)

                            <tr>

                                <td class="py-2.5 px-2">
                                    {{ $item->description }}
                                </td>

                                <td class="py-2.5 px-2 text-center">
                                    {{ $item->quantity }}
                                </td>

                                <td class="py-2.5 px-2 text-center">
                                    {{ $item->unit_of_measure }}
                                </td>

                                <td class="py-2.5 px-2 text-right font-mono">
                                    ₱ {{ number_format($item->unit_cost, 2) }}
                                </td>

                                <td class="py-2.5 px-2 text-right font-mono font-bold">
                                    ₱ {{ number_format($item->total_cost, 2) }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>
            </div>
        </div>


        {{-- Phase Documents --}}
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-6">

            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                        Phase Attachments & Checklist
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Current Phase:
                        <strong class="text-indigo-600 dark:text-indigo-400">
                            {{ $procurement->currentPhase->phase_name ?? 'Phase Not Set' }}
                        </strong>
                    </p>
                </div>

                <span class="text-xs font-mono font-bold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    {{ $procurement->documents->where('phase_id', $procurement->current_phase_id)->count() }} Files Attached
                </span>
            </div>

            {{-- Requirements --}}
            @if($procurement->currentPhase)
                <div class="p-4 bg-indigo-50/60 dark:bg-slate-800/60 rounded-xl border border-indigo-100 dark:border-slate-700/80 space-y-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">
                        Required Documents for Next Transition:
                    </span>

                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach($procurement->currentPhase->documents as $req)
                            @php
                                $isUploaded = $procurement->documents
                                    ->where('phase_id', $procurement->current_phase_id)
                                    ->contains(
                                        fn($doc) => str_contains(
                                            strtolower($doc->file_name),
                                            strtolower($req->document_type_name)
                                        )
                                    );
                            @endphp

                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border
                                {{ $isUploaded
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300'
                                    : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300'
                                }}">
                                <i class="fa-solid {{ $isUploaded ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-amber-500' }}"></i>
                                {{ $req->document_type_name }}

                                @if($req->is_mandatory)
                                    <span class="text-[9px] uppercase px-1 rounded bg-rose-100 text-rose-700 font-bold">
                                        Required
                                    </span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Upload --}}
            @if($procurement->currentPhase)
                <form action="{{ route('procurements.documents.store', $procurement->id) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="space-y-3 bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
                    @csrf

                    <input type="hidden" name="phase_id" value="{{ $procurement->currentPhase->id }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Document Label / Type <span class="text-rose-500">*</span>
                            </label>

                            <select name="document_type_name"
                                    required
                                    class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-1.5 text-xs text-slate-800 dark:text-slate-100 outline-none focus:border-indigo-500">
                                <option value="">Select Requirement Type</option>
                                @foreach($procurement->currentPhase->documents as $req)
                                    <option value="{{ $req->document_type_name }}">{{ $req->document_type_name }}</option>
                                @endforeach
                                <option value="Supporting Document">Other Supporting Document</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1">
                                Select File <span class="text-rose-500">*</span>
                            </label>

                            <input type="file"
                                name="file"
                                required
                                class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 cursor-pointer">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            Upload Attachment
                        </button>
                    </div>
                </form>
            @endif

            {{-- Attached Files --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                    <thead>
                        <tr class="text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200 dark:border-slate-800">
                            <th class="py-2.5 px-3">File Name</th>
                            <th class="py-2.5 px-3">Phase Context</th>
                            <th class="py-2.5 px-3">Size</th>
                            <th class="py-2.5 px-3">Uploaded By</th>
                            <th class="py-2.5 px-3 text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($procurement->documents as $doc)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition">
                                <td class="py-3 px-3 font-medium text-slate-800 dark:text-slate-200">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-file-pdf text-rose-500 text-base"></i>
                                        <span class="truncate max-w-xs" title="{{ $doc->file_name }}">
                                            @php
                                                $nameParts = explode(' - ', $doc->file_name, 2);
                                            @endphp
                                            <div>{{ $nameParts[0] }}</div>
                                            @if(count($nameParts) > 1)
                                                <div class="text-[10px] italic font-normal text-slate-500 dark:text-slate-400">
                                                    {{ implode(' ', array_slice($nameParts, 1)) }}
                                                </div>
                                            @endif
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3 px-3" title="{{ $doc->phase->phase_name ?? '' }}">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold text-slate-600 dark:text-slate-300">
                                        {{ $doc->phase->phase_sequence ?? 'N/A' }}
                                    </span>
                                </td>

                                <td class="py-3 px-3 font-mono text-[11px]">
                                    {{ number_format($doc->file_size / 1024, 1) }} KB
                                </td>

                                <td class="py-3 px-3">
                                    {{ $doc->uploader->name ?? 'User' }}
                                </td>

                                <td class="py-3 px-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Download --}}
                                        <a href="{{ route('procurements.documents.download', [$procurement->id, $doc->id]) }}"
                                        class="p-1.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 hover:bg-indigo-100 rounded-lg text-xs font-semibold transition"
                                        title="Download Document">
                                            <i class="fa-solid fa-download"></i>
                                        </a>

                                        {{-- Delete (Visible ONLY if current authenticated user uploaded the document) --}}
                                        @if(auth()->id() === ($doc->uploaded_by ?? $doc->user_id ?? $doc->uploader?->id))
                                            <form action="{{ route('procurements.documents.destroy', [$procurement->id, $doc->id]) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this attachment?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                        class="p-1.5 bg-rose-50 dark:bg-rose-950/60 text-rose-600 hover:bg-rose-100 rounded-lg text-xs font-semibold transition"
                                                        title="Delete Attachment">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-slate-400 text-xs">
                                    <i class="fa-solid fa-folder-open text-2xl mb-1 block opacity-50"></i>
                                    No files uploaded for this procurement project yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>


    {{-- ============================================================
        RIGHT COLUMN
        ============================================================ --}}
    <div class="min-w-0 lg:max-h-[calc(100vh-220px)] lg:overflow-y-auto lg:pr-2 space-y-6">

        {{-- Workflow Progress --}}
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">

            <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider border-b border-slate-100 dark:border-slate-800 pb-2">
                Workflow Progress
            </h2>

            <div class="relative pl-6 border-l-2 border-slate-200 dark:border-slate-800 space-y-6">

                @foreach($procurement->mode->phases as $phase)

                    @php

                        $isCurrent =
                            $procurement->current_phase_id === $phase->id;

                        $isPassed =
                            $procurement->currentPhase &&
                            $phase->phase_sequence <
                            $procurement->currentPhase->phase_sequence;

                    @endphp

                    <div class="relative">

                        <span
                            class="absolute -left-[31px] top-0 w-4 h-4 rounded-full border-2
                            {{ $isCurrent
                                ? 'bg-indigo-600 border-indigo-200 animate-pulse'
                                : ($isPassed
                                    ? 'bg-emerald-500 border-white'
                                    : 'bg-slate-300 border-white dark:bg-slate-700')
                            }}"
                        ></span>

                        <p
                            class="text-xs font-bold
                            {{ $isCurrent
                                ? 'text-indigo-600 dark:text-indigo-400'
                                : 'text-slate-700 dark:text-slate-300'
                            }}"
                        >
                            {{ $phase->phase_name }}
                        </p>

                        <span class="text-[10px] text-slate-400">
                            SLA Ceiling: {{ $phase->allowable_days }} Days
                        </span>

                    </div>

                @endforeach

            </div>

        </div>


        {{-- History --}}
        <div class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">

            <div class="border-b border-slate-100 dark:border-slate-800 pb-2 mb-4">

                <div class="flex items-center justify-between gap-3">

                    <div>

                        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                            Immutable History Log
                        </h2>

                        <p class="text-[10px] text-slate-400 mt-0.5">
                            Permanent procurement activity record
                        </p>

                    </div>

                    <span class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-[10px] font-semibold text-slate-500 dark:text-slate-400">

                        <i class="fa-solid fa-clock-rotate-left"></i>

                        {{ $procurement->history->count() }}

                    </span>

                </div>

            </div>


            <ul class="space-y-3 text-xs">

                @forelse($procurement->history as $log)

                    <li class="p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">

                        <div class="flex justify-between items-center text-[10px] text-slate-400 gap-3">

                            <span class="font-bold text-slate-700 dark:text-slate-300 truncate">
                                {{ $log->user->name ?? 'System' }}
                            </span>

                            <span class="shrink-0">
                                {{ $log->created_at->format('M d, g:i A') }}
                            </span>

                        </div>

                        <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 mt-1">
                            {{ $log->action }}
                        </p>

                        @if($log->remarks)

                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 italic">
                                "{{ $log->remarks }}"
                            </p>

                        @endif

                    </li>

                @empty

                    <li class="py-8 text-center text-slate-400">

                        <i class="fa-solid fa-clock-rotate-left text-2xl mb-2 block opacity-40"></i>

                        No history records available.

                    </li>

                @endforelse

            </ul>

        </div>

    </div>

</div>

@endsection

@push('scripts')

{{-- Dynamic Resize Observer JavaScript --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dynamicActionBar', () => ({
        open: false,
        hiddenPriorities: [],
        buttonWidths: {},

        init() {
            this.$nextTick(() => {
                this.cacheWidths();
                this.calculateOverflow();
            });

            // Observe the overall header container so action buttons yield space to Identity
            const resizeObserver = new ResizeObserver(() => {
                this.calculateOverflow();
            });
            resizeObserver.observe(this.$refs.headerContainer);
        },

        cacheWidths() {
            const items = this.$refs.wrapper.querySelectorAll('[data-action-priority]');
            items.forEach(el => {
                const priority = parseInt(el.getAttribute('data-action-priority'));
                if (el.offsetWidth > 0) {
                    this.buttonWidths[priority] = el.offsetWidth;
                }
            });
        },

        isHidden(priority) {
            return this.hiddenPriorities.includes(priority);
        },

        calculateOverflow() {
            const wrapper = this.$refs.wrapper;
            if (!wrapper) return;

            const availableWidth = wrapper.clientWidth;
            const ellipsisBtnWidth = 40;
            const gap = 6;

            const domItems = Array.from(wrapper.querySelectorAll('[data-action-priority]'));
            if (domItems.length === 0) return;

            domItems.forEach(el => {
                const p = parseInt(el.getAttribute('data-action-priority'));
                if (!this.buttonWidths[p] && el.offsetWidth > 0) {
                    this.buttonWidths[p] = el.offsetWidth;
                }
            });

            const activeItems = domItems.map(el => {
                const p = parseInt(el.getAttribute('data-action-priority'));
                return { priority: p, width: this.buttonWidths[p] || 100 };
            });

            const totalWidthNeeded = activeItems.reduce((acc, i) => acc + i.width, 0) + ((activeItems.length - 1) * gap);

            if (totalWidthNeeded <= availableWidth) {
                this.hiddenPriorities = [];
                return;
            }

            const spaceForButtons = availableWidth - ellipsisBtnWidth;
            const sortedByImportance = [...activeItems].sort((a, b) => b.priority - a.priority);

            const newlyHidden = [];
            let usedSpace = 0;

            sortedByImportance.forEach(item => {
                const cost = item.width + (usedSpace > 0 ? gap : 0);
                if (usedSpace + cost <= spaceForButtons) {
                    usedSpace += cost;
                } else {
                    newlyHidden.push(item.priority);
                }
            });

            this.hiddenPriorities = newlyHidden;
        }
    }));
});
</script>
@endpush
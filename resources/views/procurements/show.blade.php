@extends('layouts.app')
@section('title', 'Purchase Request')
@push('styles')
<style>
    .progress-doughnut{position:relative;isolation:isolate}
    .progress-doughnut::before{content:"";position:absolute;inset:7px;border-radius:9999px;background:#fff;box-shadow:inset 0 1px 3px rgba(15,23,42,.12);z-index:0}
    .dark .progress-doughnut::before{background:#0f172a;box-shadow:inset 0 1px 3px rgba(0,0,0,.45)}
    .progress-doughnut [data-completeness-percent]{position:relative;z-index:1;font-size:11px;font-weight:900;letter-spacing:-.04em}
</style>
@endpush
@section('page-header')
<div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
    <div class="flex flex-col min-w-0">
        @include('partials.page-back-link', ['href' => route('procurements.index'), 'label' => 'Back to Directory'])
        <div class="flex flex-wrap items-center gap-1">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#134991] dark:bg-blue-950 dark:text-blue-300">
                <i class="fa-solid fa-file-invoice"></i>
            </span>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                {{ $procurement->pr_number }}
            </h1>
            <span class="inline-flex max-w-full items-center gap-1.5 rounded-full border px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-wide {{ $headerStatusTone }}"><i class="fa-solid {{ $status === 'ROUTING' ? 'fa-share-nodes' : (filled($headerStatusText) ? 'fa-circle-info' : 'fa-circle-minus') }}"></i><span class="truncate">{{ $headerStatusText }}</span></span>
            @if($canAuthUserSignPr)<span class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-amber-100 px-3 py-1 text-[9px] font-extrabold uppercase tracking-wide text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200"><i class="fa-solid fa-signature"></i>Your turn to sign the PR as {{ $pendingPrRole?->name ?? 'Signatory' }}</span>@endif
        </div>
        {{-- <p class="mt-0 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
            {{ $procurement->purpose ?: 'No purpose entered yet.' }}
        </p> --}}
        <div class="mt-0 flex flex-wrap gap-x-4 gap-y-1 text-[10px] font-semibold text-slate-400">
            <span><i class="fa-solid fa-layer-group mr-1"></i>{{ $procurement->mode?->name ?? $modeCode }}</span>
            <span><i class="fa-regular fa-calendar mr-1"></i>{{ $procurement->pr_date ? \Illuminate\Support\Carbon::parse($procurement->pr_date)->format('M d, Y') : 'Date not set' }}</span>
            <span><i class="fa-solid fa-building mr-1"></i>{{ $procurement->endUserOffice?->name ?? 'Office not set' }}</span>
        </div>
        @if($isFirstPhase && $hasPrSignatories)<div class="mt-3 flex flex-wrap items-center gap-1.5"><span class="mr-1 text-[9px] font-extrabold uppercase tracking-wide text-slate-400">PR signing sequence</span>@foreach($procurementPrSignatories as $signatoryStep)@php
            $stepSigned = (bool)$signatoryStep->is_signed;
            $stepIsCurrent = $prPendingSequence !== null && (int)$signatoryStep->sequence === (int)$prPendingSequence;
            $stepTone = $stepSigned ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300' : ($stepIsCurrent ? 'border-amber-300 bg-amber-100 text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200' : 'border-slate-200 bg-slate-50 text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400');
        @endphp<span class="inline-flex items-center gap-1 rounded-full border px-2 py-1 text-[9px] font-bold {{ $stepTone }}"><span class="grid h-4 w-4 place-items-center rounded-full bg-white/70 font-mono text-[8px] dark:bg-slate-950/40">{{ $signatoryStep->sequence }}</span>{{ $signatoryStep->configuredRole?->name ?? 'Signatory' }}<i class="fa-solid {{ $stepSigned ? 'fa-check' : ($stepIsCurrent ? 'fa-pen' : 'fa-clock') }}"></i></span>@endforeach</div>@endif
        <div class="mt-3 grid max-w-4xl grid-cols-2 gap-x-5 gap-y-2 border-t border-slate-200 pt-3 text-[10px] dark:border-slate-800 sm:grid-cols-3 lg:grid-cols-5">
            <span><b class="block text-slate-400">End user</b><span class="font-bold text-slate-700 dark:text-slate-200">{{ $procurement->endUserOffice?->name ?? '—' }}</span></span>
            <span><b class="block text-slate-400">Fund cluster</b><span class="font-bold text-slate-700 dark:text-slate-200">{{ $procurement->fund_cluster ?: '—' }}</span></span>
            <span><b class="block text-slate-400">Responsibility code</b><span class="font-bold text-slate-700 dark:text-slate-200">{{ $procurement->responsibility_code ?: '—' }}</span></span>
            <span><b class="block text-slate-400">ABC</b><span class="font-mono font-extrabold text-slate-800 dark:text-white">₱{{ number_format((float)$procurement->abc_amount, 2) }}</span></span>
            <span><b class="block text-slate-400">Created by</b><span class="font-bold text-slate-700 dark:text-slate-200">{{ $procurement->creator?->name ?? '—' }}</span></span>
        </div>
    </div>
    <div class="flex w-full flex-col gap-3 xl:w-[27rem] xl:shrink-0">
        <div class="flex flex-col gap-2 justify-end sm:flex-row sm:flex-wrap">
            @if($canAuthUserSignPr)<button type="button" @click="$dispatch('open-pr-pdf')" class="inline-flex min-h-14 flex-1 items-center justify-center gap-3 rounded-xl bg-amber-700 px-5 py-3 text-sm font-extrabold text-white shadow-md shadow-amber-900/15 transition hover:bg-amber-800 hover:shadow-lg"><i class="fa-solid fa-signature text-base"></i>Sign PR as {{ $pendingPrRole?->name ?? 'Signatory' }}</button>@endif
            @if($canEditPrForm && $prForm && in_array($status, ['DRAFT', 'RETURNED'], true))<button type="button" @click="$dispatch('open-pr-form')" class="inline-flex min-h-14 flex-1 items-center justify-center gap-3 rounded-xl bg-[#134991] px-5 py-3 text-sm font-extrabold text-white shadow-md shadow-blue-900/15 transition hover:bg-blue-800 hover:shadow-lg"><i class="fa-solid fa-pen-to-square text-base"></i>Edit PR Form</button>@endif
            @if($canRoutePr)<button type="button" @click="$dispatch('open-confirmation', {action: '{{ route('procurements.attachments.route', [$procurement, 'pr']) }}', title: '{{ $status === 'RETURNED' ? 'Reroute the Purchase Request?' : 'Route the Purchase Request for signing?' }}', message: 'The compiled PR will be sent to its configured signatories.', eyebrow: 'PR signing', button: '{{ $status === 'RETURNED' ? 'Reroute PR' : 'Route PR' }}', method: 'POST', tone: 'blue', icon: 'fa-share-nodes'})" class="inline-flex min-h-14 flex-1 items-center justify-center gap-3 rounded-xl bg-blue-600 px-5 py-3 text-sm font-extrabold text-white shadow-md shadow-blue-900/15 transition hover:bg-blue-700 hover:shadow-lg"><i class="fa-solid fa-share-nodes text-base"></i>{{ $status === 'RETURNED' ? 'Reroute PR' : 'Route PR' }}</button>@endif
            @if($canProceed)<button type="button" @click="$dispatch('open-confirmation', {action: '{{ route('procurements.proceed-next-phase', $procurement) }}', title: '{{ $isFinalPhase ? 'Mark the purchase request as completed?' : 'Proceed to the next phase?' }}', message: '{{ $isFinalPhase ? 'The final phase is complete and this purchase request will be marked Completed.' : 'The current phase is complete and the request will advance to the next phase.' }}', eyebrow: 'Workflow', button: '{{ $isFinalPhase ? 'Mark PR as Completed' : 'Proceed to Next Phase' }}', method: 'POST', tone: '{{ $isFinalPhase ? 'emerald' : 'blue' }}', icon: '{{ $isFinalPhase ? 'fa-circle-check' : 'fa-forward-step' }}'})" class="inline-flex min-h-14 flex-1 items-center justify-center gap-3 rounded-xl px-5 py-3 text-sm font-extrabold text-white shadow-md transition hover:shadow-lg {{ $isFinalPhase ? 'bg-emerald-600 shadow-emerald-900/15 hover:bg-emerald-700' : 'bg-[#134991] shadow-blue-900/15 hover:bg-blue-800' }}"><i class="fa-solid {{ $isFinalPhase ? 'fa-circle-check' : 'fa-forward-step' }} text-base"></i>{{ $isFinalPhase ? 'Mark PR as Completed' : 'Proceed to Next Phase' }}</button>@endif
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200/80 pt-3 dark:border-slate-800">
            @if($prHasCompiledPdf)<button type="button" @click="$dispatch('open-pr-pdf')" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[10px] font-bold text-emerald-800 transition hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300"><i class="fa-solid fa-eye"></i>View PR</button>@endif
            @if($canReturn)<button type="button" data-open-modal="return-modal" class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[10px] font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300"><i class="fa-solid fa-rotate-left"></i>Return to Phase</button>@endif
            @if($canCancel)<button type="button" data-open-modal="cancel-modal" class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[10px] font-bold text-rose-700 transition hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300"><i class="fa-solid fa-ban"></i>Cancel</button>@endif
            @if($canArchive)<button type="button" @click="$dispatch('open-action-confirmation', {action: '{{ route('procurements.archive-action', $procurement) }}', title: 'Archive this purchase request?', message: 'The request will be archived and can no longer be edited or routed.', button: 'Archive request', method: 'POST', tone: 'archive'})" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-[10px] font-bold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"><i class="fa-solid fa-box-archive"></i>Archive</button>@endif
            @if($canDelete)<button type="button" @click="$dispatch('open-action-confirmation', {action: '{{ route('procurements.destroy', $procurement) }}', title: 'Permanently delete this archived PR?', message: 'This permanently removes the purchase request and stored attachment records. This action cannot be undone.', button: 'Delete permanently', method: 'DELETE', tone: 'delete'})" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-3 py-2 text-[10px] font-bold text-white transition hover:bg-rose-700"><i class="fa-solid fa-trash-can"></i>Delete</button>@endif
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="flex min-w-0 flex-col gap-4">
    @if(!$prForm)
        <div role="alert" class="flex flex-col gap-3 rounded-2xl border border-rose-300 bg-rose-50 p-4 text-rose-950 shadow-sm dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-100 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-200"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div>
                    <p class="text-sm font-extrabold">Purchase Request form is not configured</p>
                    <p class="mt-1 text-xs leading-5 text-rose-800 dark:text-rose-200">The PR form is unavailable, so it cannot be viewed or edited. Configure a published PR template in system settings.</p>
                </div>
            </div>
            @if($canConfigurePrForm)
                <a href="{{ route('admin.settings.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 self-start rounded-xl bg-rose-700 px-4 py-2.5 text-xs font-extrabold text-white transition hover:bg-rose-800 sm:self-center"><i class="fa-solid fa-sliders"></i>Configure PR form</a>
            @else
                <p class="shrink-0 self-start rounded-xl border border-rose-200 bg-white/70 px-3 py-2 text-[11px] font-bold text-rose-800 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200 sm:self-center">Please contact a system administrator.</p>
            @endif
        </div>
    @endif
    <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start">
        <section aria-label="Procurement phase workspace" id="procurement-phase-scroll" class="min-w-0 flex-1 pr-1">
            @include('procurements._phase-attachments')
        </section>
        <aside aria-label="Purchase request details" class="flex w-full min-w-0 flex-col gap-3 pr-1 sm:w-[18rem] sm:shrink-0">
            @if($status === 'ROUTING')<div class="mb-4 flex flex-col gap-2 rounded-2xl border border-blue-200 bg-blue-50/70 p-4 dark:border-blue-900 dark:bg-blue-950/30 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-start gap-3"><span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-white text-[#134991] shadow-sm dark:bg-slate-900 dark:text-blue-300"><i class="fa-solid fa-list-check"></i></span><div><p class="text-sm font-extrabold text-blue-950 dark:text-blue-200">Complete the current phase attachments</p><p class="mt-1 text-xs leading-5 text-blue-800/80 dark:text-blue-300/80">Changes save automatically. Complete bound forms and compile them to PDF, or upload the required PDF.</p></div></div><span class="rounded-full bg-white px-3 py-1.5 text-[10px] font-extrabold text-blue-800 shadow-sm dark:bg-slate-900 dark:text-blue-300">{{ $phases->count() }} PHASES</span></div>@endif
            <section aria-label="Procurement progress" class="mb-4 grid shrink-0 grid-cols-1 gap-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3"><div><h2 class="text-[10px] font-extrabold uppercase tracking-wide text-slate-500">Current phase</h2><p class="mt-1 text-xs font-extrabold text-slate-900 dark:text-white">{{ $phaseForStats?->phase_name ?? 'No active phase' }}</p></div><div data-completeness-chart="phase" data-phase-id="{{ $phaseForStats?->id }}" data-chart-color="#2563eb" data-completion-target="{{ $phasePercent }}" role="img" aria-label="Current phase completeness: {{ $phasePercent }} percent" class="progress-doughnut grid h-16 w-16 shrink-0 place-items-center rounded-full bg-slate-200 text-blue-800 shadow-inner dark:bg-slate-700 dark:text-blue-300" style="--completion: 0%; background: conic-gradient(#2563eb 0%, #e2e8f0 0)"><span data-completeness-percent>0%</span></div></div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div data-completeness-bar="phase" class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $phasePercent }}%"></div></div>
                    <p data-completeness-count="phase" class="mt-2 text-[9px] font-semibold text-slate-600 dark:text-slate-300">{{ $phaseRequired ? "$phaseReady of $phaseRequired required attachments complete" : 'No required attachments in this phase' }}</p>
                    @if($phaseRequired)<p class="mt-1 text-[9px] text-slate-400"><span data-completeness-remaining="phase">{{ max(0, $phaseRequired - $phaseReady) }}</span> remaining · {{ $phaseRequired }} required total</p>@endif
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3"><div><h2 class="text-[10px] font-extrabold uppercase tracking-wide text-slate-500">Overall progress</h2><p class="mt-1 text-xs font-extrabold text-slate-900 dark:text-white"><span data-completed-phases>{{ $completedPhases }}</span> of {{ $phases->count() }} phases complete</p></div><div data-completeness-chart="overall" data-chart-color="#0f766e" data-completion-target="{{ $overallPercent }}" role="img" aria-label="Overall completeness: {{ $overallPercent }} percent" class="progress-doughnut grid h-16 w-16 shrink-0 place-items-center rounded-full bg-slate-200 text-teal-800 shadow-inner dark:bg-slate-700 dark:text-teal-300" style="--completion: 0%; background: conic-gradient(#0f766e 0%, #e2e8f0 0)"><span data-completeness-percent>0%</span></div></div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"><div data-completeness-bar="overall" class="h-full rounded-full bg-teal-600 transition-all" style="width: {{ $overallPercent }}%"></div></div>
                    <p data-completeness-count="overall" class="mt-2 text-[9px] font-semibold text-slate-600 dark:text-slate-300">{{ $overallRequired ? "$overallReady of $overallRequired required attachments complete" : 'No required attachments configured' }}</p>
                    @if($overallRequired)<p class="mt-1 text-[9px] text-slate-400"><span data-completeness-remaining="overall">{{ max(0, $overallRequired - $overallReady) }}</span> remaining across all phases</p>@endif
                </article>

            </section>
            <section class="shrink-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800"><div class="flex items-center gap-2"><i class="fa-solid fa-list-ul text-blue-700"></i><h2 class="text-xs font-extrabold text-slate-900 dark:text-white">PR Items</h2></div>@if($canEditPrForm && $prForm && in_array($status, ['DRAFT', 'RETURNED'], true))<button type="button" aria-label="Edit Purchase Request form" title="Edit Purchase Request form" @click="$dispatch('open-pr-form')" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-300"><i class="fa-solid fa-pen-to-square"></i></button>@endif</header>
                <div class="space-y-3 p-3">
                    @forelse($existingItems as $index => $item)
                        <article class="rounded-xl border border-slate-100 p-3 dark:border-slate-800"><div class="flex items-start justify-between gap-2"><div><p class="text-[10px] font-extrabold text-slate-800 dark:text-slate-200">{{ $item['description'] ?: ('Item '.($index + 1)) }}</p><p class="mt-1 text-[9px] text-slate-500">{{ $item['is_lot'] ? 'Lot' : ($item['unit'] ?? '') }} · Qty {{ $item['quantity'] ?? '—' }}</p></div>@if(empty($item['is_lot']))<span class="whitespace-nowrap text-[10px] font-bold text-slate-700 dark:text-slate-200">₱{{ number_format((float)($item['cost'] ?? 0), 2) }}</span>@endif</div>
                        @if(!empty($item['sub_items']))<div class="mt-2 space-y-1 border-l-2 border-slate-100 pl-3 dark:border-slate-800">@foreach($item['sub_items'] as $sub)<p class="text-[9px] text-slate-600 dark:text-slate-300">{{ $sub['description'] }} <span class="text-slate-400">· {{ $sub['quantity'] }} × ₱{{ number_format((float)$sub['cost'], 2) }}</span></p>@endforeach</div>@endif</article>
                    @empty<p class="py-6 text-center text-[10px] text-slate-400">No PR items have been added.</p>@endforelse
                </div>
            </section>
                            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3"><h2 class="flex items-center gap-2 text-[10px] font-extrabold uppercase tracking-wide text-slate-500"><i class="fa-solid fa-clock-rotate-left text-blue-700 dark:text-blue-300"></i>Latest update</h2><button type="button" @click="$dispatch('open-procurement-history')" class="text-[9px] font-extrabold text-blue-700 hover:underline dark:text-blue-300">Show all</button></div>
                    @if($latestHistory)<p class="mt-2 text-[10px] font-extrabold text-slate-800 dark:text-slate-200">{{ str_replace('_', ' ', strtolower($latestHistory->action ?? $latestHistory->action_taken ?? 'Updated')) }}</p><p class="mt-1 line-clamp-2 text-[9px] leading-4 text-slate-500 dark:text-slate-400">{{ $latestHistory->remarks ?: 'No remarks recorded.' }}</p><p class="mt-2 text-[9px] text-slate-400">{{ $latestHistory->user?->name ?? 'System' }} · {{ $latestHistory->created_at?->format('M d, Y · h:i A') }}</p>@else<p class="mt-3 text-[9px] text-slate-400">No history has been recorded yet.</p>@endif
                </article>
            @if($status === 'RETURNED' || $status === 'REJECTED')<div class="mb-4 rounded-2xl border {{ $status === 'RETURNED' ? 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30' : 'border-rose-200 bg-rose-50 dark:border-rose-900 dark:bg-rose-950/30' }} p-4"><p class="text-xs font-extrabold">{{ $status === 'RETURNED' ? 'This purchase request was returned for revision.' : 'This purchase request was rejected.' }}</p><p class="mt-1 text-[10px] leading-5 text-slate-600 dark:text-slate-300">{{ $decision?->remarks ?: 'See procurement history for the decision details.' }}</p></div>@endif
        </aside>
    </div>
</div>

<div x-data="{open:false}" @open-procurement-history.window="open=true" @keydown.escape.window="open=false" x-cloak x-show="open" class="fixed inset-0 z-[140] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="procurement-history-title" @click.self="open=false">
    <section class="flex max-h-[85dvh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
        <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800"><div><p class="text-[9px] font-extrabold uppercase tracking-[.16em] text-blue-600 dark:text-blue-300">Purchase request activity</p><h2 id="procurement-history-title" class="mt-1 text-sm font-extrabold text-slate-900 dark:text-white">Complete history</h2></div><button type="button" @click="open=false" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close history"><i class="fa-solid fa-xmark"></i></button></header>
        <div class="custom-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto p-5">@forelse(($procurement->history ?? collect())->sortByDesc('created_at') as $event)<article class="relative border-l-2 border-slate-100 pl-4 dark:border-slate-800"><span class="absolute -left-[5px] top-1 h-2 w-2 rounded-full bg-blue-500 ring-4 ring-white dark:ring-slate-900"></span><p class="text-[10px] font-extrabold uppercase tracking-wide text-slate-700 dark:text-slate-200">{{ str_replace('_', ' ', strtolower($event->action ?? $event->action_taken ?? 'Updated')) }}</p><p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $event->remarks ?: 'No remarks recorded.' }}</p><p class="mt-1 text-[9px] text-slate-400">{{ $event->user?->name ?? 'System' }} · {{ $event->created_at?->format('M d, Y · h:i A') }}</p></article>@empty<p class="text-xs text-slate-400">No history has been recorded yet.</p>@endforelse</div>
    </section>
</div>

@push('scripts')<script>
window.focusProcurementPhase=function(event,link){
 event.preventDefault();
 const target=document.querySelector(link.getAttribute('href'));
 if(!target)return;
 const focusTarget=()=>{target.closest('details')?.setAttribute('open','');target.scrollIntoView({behavior:'smooth',block:'start'});target.classList.add('ring-2','ring-blue-400');setTimeout(()=>target.classList.remove('ring-2','ring-blue-400'),1400)};
 const workspaceRoot=document.getElementById('pr-workspace');
 const workspace=workspaceRoot&&window.Alpine?.$data(workspaceRoot);
 const editingUnsavedForm=workspace?.selectedAttachment?.hasForm&&workspace.viewMode==='form'&&workspace.formDirty;
 if(!editingUnsavedForm){focusTarget();return}
 window.dispatchEvent(new CustomEvent('open-confirmation',{detail:{
  title:'Discard unsaved form changes?',
  message:'Your latest changes to this form have not been saved. Discard them and open the selected phase?',
  eyebrow:'Unsaved form changes',tone:'amber',icon:'fa-triangle-exclamation',
  confirmButton:'Discard changes',cancelButton:'Keep editing',
  onConfirm:()=>{workspace.discardActiveForm();requestAnimationFrame(focusTarget)}
 }}));
};
</script>@endpush
{{-- Action confirmation dialogs --}}
@if($canCancel)<div id="cancel-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true"><div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300"><i class="fa-solid fa-ban"></i></span><h2 class="mt-4 text-base font-extrabold text-slate-900 dark:text-white">Cancel purchase request?</h2><p class="mt-1 text-xs leading-5 text-slate-500">This marks the PR as cancelled and records the reason in its history.</p><form action="{{ route('procurements.cancel', $procurement) }}" method="POST" class="mt-4">@csrf<label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Reason for cancelling <span class="text-rose-500">*</span></label><textarea name="reason" required rows="3" maxlength="5000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-rose-500 focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="Explain why this PR is being cancelled."></textarea><div class="mt-5 flex justify-end gap-2"><button type="button" data-close-modal="cancel-modal" class="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">Keep PR</button><button class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-extrabold text-white hover:bg-rose-700">Cancel PR</button></div></form></div></div>@endif


@if($canReturn)<div id="return-modal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true"><div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"><i class="fa-solid fa-rotate-left"></i></span><h2 class="mt-4 text-base font-extrabold text-slate-900 dark:text-white">Return PR to a previous phase</h2><p class="mt-1 text-xs leading-5 text-slate-500">Choose a previous phase to return this purchase request to.</p><form action="{{ route('procurements.return-phase', $procurement) }}" method="POST" class="mt-4 space-y-4">@csrf<div><label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Return to phase <span class="text-rose-500">*</span></label><select name="phase_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="">Select previous phase</option>@foreach($priorPhases as $prior)<option value="{{ $prior->id }}">Phase {{ $prior->phase_sequence }} — {{ $prior->phase_name }}</option>@endforeach</select></div><div><label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Reason for return <span class="text-slate-400">(optional)</span></label><textarea name="reason" rows="3" maxlength="5000" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="Optional notes for the return."></textarea></div><div class="flex justify-end gap-2"><button type="button" data-close-modal="return-modal" class="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">Keep current phase</button><button type="submit" class="rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-extrabold text-white hover:bg-amber-700">Return PR</button></div></form></div></div>@endif
@push('scripts')<script>(()=>{const dialogs=['cancel-modal','return-modal'].map(id=>document.getElementById(id)).filter(Boolean);document.querySelectorAll('[data-open-modal]').forEach(button=>button.addEventListener('click',()=>{const modal=document.getElementById(button.dataset.openModal);modal?.classList.remove('hidden');modal?.classList.add('flex')}));document.querySelectorAll('[data-close-modal]').forEach(button=>button.addEventListener('click',()=>{const modal=document.getElementById(button.dataset.closeModal);modal?.classList.add('hidden');modal?.classList.remove('flex');window.location.reload()}));dialogs.forEach(modal=>modal.addEventListener('click',event=>{if(event.target===modal){modal.classList.add('hidden');modal.classList.remove('flex')}}));document.addEventListener('keydown',event=>{if(event.key==='Escape')dialogs.forEach(modal=>{modal.classList.add('hidden');modal.classList.remove('flex')})})})();</script>@endpush
@endsection

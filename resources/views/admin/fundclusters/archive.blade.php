@extends('layouts.app')
@section('title', 'Archived Fund Clusters')
@section('page-header')
<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
  <div><h1 class="text-xl font-bold text-slate-900 dark:text-white">Archived Fund Clusters</h1><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Search and restore inactive fund clusters.</p></div>
  @include('partials.page-back-link', ['href' => route('admin.fundclusters.index'), 'label' => 'Back to active'])
</div>
@endsection
@section('content')
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
<form method="GET" action="{{ route('admin.fundclusters.archive') }}" data-directory-filters class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-950/30 sm:p-4">
 <label class="relative min-w-[12rem] flex-1"><span class="sr-only">Search archived fund clusters</span><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="search" value="{{ request('search') }}" type="search" data-live-search placeholder="Search archive…" class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-800 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-white"></label>
 
 <a href="{{ route('admin.fundclusters.archive') }}" data-clear-filters @if(!request()->hasAny(['search','location_type'])) hidden @endif class="rounded-lg px-2.5 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-800">Clear</a>
</form>
<div class="divide-y divide-slate-100 dark:divide-slate-800">
 @forelse($fundClusters as $fundCluster)
 <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
  <div class="min-w-0"><p class="font-mono text-[11px] font-bold text-blue-700 dark:text-blue-300">{{ $fundCluster->code }}</p><p class="mt-1 truncate text-sm font-bold text-slate-900 dark:text-white">{{ $fundCluster->name }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Fund cluster</p></div>
@can('fundclusters.edit')
  <form method="POST" action="{{ route('admin.fundclusters.toggle', $fundCluster->id) }}" class="shrink-0" data-confirm-trigger data-confirm-title="Restore this Fund cluster?" data-confirm-message="This item will become active and available again." data-confirm-button="Restore" data-confirm-tone="archive" data-confirm-method="PATCH">@csrf @method('PATCH')<button type="button" data-confirm-open class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 px-3 py-2 text-xs font-bold text-blue-700 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-300 dark:hover:bg-blue-950"><i class="fa-solid fa-rotate-left"></i> Restore</button></form>
@endcan
 </div>
 @empty<div class="p-10 text-center"><i class="fa-solid fa-box-open text-2xl text-slate-300"></i><p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">No archived records found</p><p class="mt-1 text-xs text-slate-500">Try a different search or return to the active directory.</p></div>@endforelse
</div>
<div class="border-t border-slate-100 p-4 dark:border-slate-800">{{ $fundClusters->links() }}</div>
</div>

@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-directory-filters]').forEach(form => {
 const input=form.querySelector('[data-live-search]'); let timer;
 input?.addEventListener('input',()=>{clearTimeout(timer);const value=input.value.trim();if(value.length>1) timer=setTimeout(()=>form.requestSubmit(),350);else if(value.length===0) form.requestSubmit();});
 form.querySelectorAll('[data-auto-submit]').forEach(el=>el.addEventListener('change',()=>form.requestSubmit()));
});

}));
</script>
@include('partials.directory-clear-sync')
@endpush
